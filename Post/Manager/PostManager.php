<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Media\Library\Repository\MediaRepository;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Enum\UserRoleEnum;
use Aurora\Module\Editorial\Post\Dto\PostInputInterface;
use Aurora\Module\Editorial\Post\Dto\PostTranslationInput;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Entity\PostRevision;
use Aurora\Module\Editorial\Post\Entity\PostRevisionInterface;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostRevisionRepository;
use Aurora\Module\Editorial\Post\Repository\PostSlugHistoryRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Post\Security\PostVoter;
use Aurora\Module\Editorial\Post\Service\PostTextExtractor;
use Aurora\Module\Editorial\Setting\EditorialSettingEnum;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use const DATE_ATOM;

#[AsAlias(PostManagerInterface::class)]
class PostManager implements PostManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly PostTypeRepository $postTypeRepository,
        protected readonly TaxonomyTermRepository $termRepository,
        protected readonly MediaRepository $mediaRepository,
        protected readonly PostRevisionRepository $revisionRepository,
        protected readonly PostSlugHistoryRepository $slugHistoryRepository,
        protected readonly SettingRepository $settingRepository,
        protected readonly SluggerInterface $slugger,
        protected readonly Security $security,
        protected readonly PostTextExtractor $textExtractor,
        protected readonly TranslatorInterface $translator,
        protected readonly AuditLogger $auditLogger,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly PostRepository $postRepository,
    ) {}

    public function create(PostInputInterface $input): PostInterface
    {
        $post = $this->createPost();
        $this->applyInput($post, $input);

        $currentUser = $this->security->getUser();
        if ($currentUser instanceof User) {
            $post->setAuthor($currentUser);
        }

        $prefix = $this->settingRepository->getOrDefault(EditorialSettingEnum::PostPrefix);
        $post->setReference($this->sequenceGenerator->next($prefix));
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        $this->auditCreated($post);

        return $post;
    }

    public function update(PostInterface $post, PostInputInterface $input): void
    {
        $this->applyInput($post, $input);
        // Force the Post entity to be marked as dirty so Doctrine's @Version increments
        // even when only related entities (translations, tags) changed — @Version only
        // bumps when the owning entity itself is scheduled for UPDATE.
        $this->entityManager->getUnitOfWork()->scheduleForUpdate($post);
        $this->entityManager->flush();

        $this->snapshotRevision($post);

        $this->auditUpdated($post);
    }

    public function delete(PostInterface $post): void
    {
        if ($post->isTrashed()) {
            return;
        }

        $post->setDeletedAt(new DateTimeImmutable());

        $this->entityManager->flush();

        $this->auditDeleted($post);
    }

    public function restore(PostInterface $post): void
    {
        $post->setDeletedAt(null);

        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'post.restored', 'Post', $post->getId(), $this->auditPayload($post));
    }

    public function forceDelete(PostInterface $post): void
    {
        $this->auditLogger->log('editorial', 'post.force_deleted', 'Post', $post->getId(), $this->auditPayload($post));
        $this->entityManager->remove($post);
        $this->entityManager->flush();
    }

    public function restoreRevision(PostInterface $post, PostRevisionInterface $revision): void
    {
        $snapshot = $revision->getSnapshot();

        $post->setStatus(PostStatusEnum::from($snapshot['status'] ?? PostStatusEnum::Draft->value));

        $post->setPublishedAt($this->hydrateDate($snapshot['publishedAt'] ?? null));
        $post->setScheduledAt($this->hydrateDate($snapshot['scheduledAt'] ?? null));

        $featuredMediaId = $snapshot['featuredMediaId'] ?? null;
        $ogImageIds = array_filter(array_map(
            static fn (array $t): ?int => isset($t['ogImageMediaId']) ? (int) $t['ogImageMediaId'] : null,
            (array) ($snapshot['translations'] ?? []),
        ));
        $mediaMap = $this->buildMediaMap(array_values(array_filter([
            $featuredMediaId,
            ...$ogImageIds,
        ])));

        $post->setFeaturedMedia(null !== $featuredMediaId ? ($mediaMap[$featuredMediaId] ?? null) : null);

        $this->syncTerms($post, array_values(array_filter(
            array_map(intval(...), $snapshot['termIds'] ?? []),
            static fn (int $termId): bool => $termId > 0,
        )));

        $this->syncRelatedPosts($post, array_values(array_filter(
            array_map(intval(...), $snapshot['relatedPostIds'] ?? []),
            static fn (int $id): bool => $id > 0,
        )));

        foreach ((array) ($snapshot['translations'] ?? []) as $locale => $translationData) {
            if (!is_array($translationData)) {
                continue;
            }

            $translation = $post->translate((string) $locale);
            $translation->setTitle($translationData['title'] ?? null);
            $translation->setSlug($translationData['slug'] ?? null);
            $translation->setBlocks($translationData['blocks'] ?? []);
            $translation->setMetaTitle($translationData['metaTitle'] ?? null);
            $translation->setMetaDescription($translationData['metaDescription'] ?? null);
            $translation->setCustomFields($translationData['customFields'] ?? []);

            $ogImageId = isset($translationData['ogImageMediaId']) ? (int) $translationData['ogImageMediaId'] : null;
            $translation->setOgImage(null !== $ogImageId ? ($mediaMap[$ogImageId] ?? null) : null);
            $translation->setCanonicalUrl($translationData['canonicalUrl'] ?? null);
            $translation->setNoindex((bool) ($translationData['noindex'] ?? false));
            $translation->setFocusKeyword($translationData['focusKeyword'] ?? null);
            $jsonLd = $translationData['jsonLd'] ?? null;
            $translation->setJsonLd(is_array($jsonLd) ? $jsonLd : null);

            $translation->setSearchContent($this->textExtractor->extract($translation));
        }

        $this->entityManager->flush();

        $this->snapshotRevision($post);

        $this->auditLogger->log('editorial', 'post.revision_restored', 'Post', $post->getId(), [
            ...$this->auditPayload($post),
            'revisionId' => $revision->getId(),
        ]);
    }

    public function emptyTrash(): int
    {
        $posts = $this->postRepository->findAllTrashed();
        if ([] === $posts) {
            return 0;
        }

        foreach ($posts as $post) {
            $this->auditLogger->log('editorial', 'post.force_deleted', 'Post', $post->getId(), $this->auditPayload($post));
            $this->entityManager->remove($post);
        }

        $this->entityManager->flush();

        return count($posts);
    }

    public function demoteIfNotPublishable(PostInputInterface $input, ?PostInterface $post = null): PostInputInterface
    {
        if (PostStatusEnum::Published->value !== $input->getStatus()) {
            return $input;
        }

        $allowed = $post instanceof PostInterface
            ? $this->security->isGranted(PostVoter::PUBLISH, $post)
            : ($this->security->isGranted(UserRoleEnum::Admin->value) || $this->security->isGranted(UserRoleEnum::Dev->value));

        return $allowed ? $input : $input->withStatus(PostStatusEnum::PendingReview->value);
    }

    protected function applyInput(PostInterface $post, PostInputInterface $input): void
    {
        $postType = $this->postTypeRepository->find($input->getPostTypeId());
        if (null === $postType) {
            throw new InvalidArgumentException($this->translator->trans('backend.posts.errors.post_type_not_found', ['{id}' => $input->getPostTypeId()]));
        }

        $post->setPostType($postType);

        $status = PostStatusEnum::from($input->getStatus());
        $post->setStatus($status);

        if (PostStatusEnum::Scheduled === $status && null !== $input->getScheduledAt()) {
            $post->setScheduledAt(new DateTimeImmutable($input->getScheduledAt()));
        } else {
            $post->setScheduledAt(null);
        }

        if (PostStatusEnum::Published === $status && !$post->getPublishedAt() instanceof DateTimeImmutable) {
            $post->setPublishedAt(new DateTimeImmutable());
        }

        $featuredMedia = null !== $input->getFeaturedMediaId()
            ? $this->mediaRepository->find($input->getFeaturedMediaId())
            : null;
        $post->setFeaturedMedia($featuredMedia);

        $post->setCommentsEnabled($input->isCommentsEnabled());
        $this->syncTerms($post, $input->getTermIds());
        $this->syncRelatedPosts($post, $input->getRelatedPostIds());

        $ogImageIds = array_values(array_filter(
            array_map(static fn (PostTranslationInput $t): ?int => $t->ogImageMediaId, $input->getTranslations()),
        ));
        $ogImageMap = $this->buildMediaMap($ogImageIds);

        foreach ($input->getTranslations() as $locale => $translationInput) {
            $this->applyTranslation($post, $locale, $translationInput, $ogImageMap);
        }
    }

    /** @param array<int> $termIds */
    private function syncTerms(PostInterface $post, array $termIds): void
    {
        foreach ($post->getTerms() as $existingTerm) {
            if (!in_array($existingTerm->getId(), $termIds, true)) {
                $post->removeTerm($existingTerm);
            }
        }

        $currentTermIds = $post->getTerms()->map(fn ($term): ?int => $term->getId())->toArray();
        $missingTermIds = array_values(array_filter($termIds, static fn (int $id): bool => !in_array($id, $currentTermIds, true)));

        if ([] !== $missingTermIds) {
            foreach ($this->termRepository->findBy(['id' => $missingTermIds]) as $term) {
                $post->addTerm($term);
            }
        }
    }

    /** @param array<int> $relatedPostIds */
    private function syncRelatedPosts(PostInterface $post, array $relatedPostIds): void
    {
        $relatedPostIds = array_values(array_filter($relatedPostIds, fn (int $id): bool => $id !== $post->getId()));

        foreach ($post->getRelatedPosts() as $existing) {
            if (!in_array($existing->getId(), $relatedPostIds, true)) {
                $post->removeRelatedPost($existing);
            }
        }

        $currentIds = $post->getRelatedPosts()->map(fn ($related): ?int => $related->getId())->toArray();
        $missingIds = array_values(array_filter($relatedPostIds, static fn (int $id): bool => !in_array($id, $currentIds, true)));

        if ([] !== $missingIds) {
            foreach ($this->postRepository->findBy(['id' => $missingIds]) as $related) {
                $post->addRelatedPost($related);
            }
        }
    }

    /**
     * Hydrate a single translation row from the DTO.
     *
     * Note on block image lifecycle: Notes\Block diff-cleans uploaded image
     * files on each save (per-user `var/uploads/notes-block/{userId}/`).
     * Posts intentionally do NOT — image blocks reference media-library
     * entries (`MediaController::upload` → `var/uploads/media/`) which are
     * shared, addressable by id, and garbage-collected by the Media
     * module's own usage tracking, not by individual Post saves. So a
     * dropped image block here leaves the underlying Media row in place
     * (intended: it may be referenced by another post or reused later).
     *
     * @param array<int, object> $ogImageMap
     */
    private function applyTranslation(PostInterface $post, string $locale, PostTranslationInput $input, array $ogImageMap = []): void
    {
        $translation = $post->translate($locale);

        $translation->setTitle($input->title);
        $translation->setBlocks($input->blocks);
        $translation->setMetaTitle($input->metaTitle);
        $translation->setMetaDescription($input->metaDescription);
        $translation->setCustomFields($input->customFields);

        $translation->setOgImage(
            null !== $input->ogImageMediaId ? ($ogImageMap[$input->ogImageMediaId] ?? null) : null,
        );
        $translation->setCanonicalUrl($input->canonicalUrl);
        $translation->setNoindex($input->noindex);
        $translation->setFocusKeyword($input->focusKeyword);
        $translation->setJsonLd($input->jsonLd);

        $previousSlug = $translation->getSlug();
        $newSlug = $input->slug ?: ($input->title ? $this->slugger->slug($input->title)->lower()->toString() : null);

        if ($newSlug !== $previousSlug) {
            if (null !== $newSlug) {
                // If the new slug appears in history, remove that entry to avoid a self-redirect.
                $this->slugHistoryRepository->removeByLocaleAndSlug($locale, $newSlug);
            }

            if (null !== $previousSlug && '' !== $previousSlug) {
                $this->slugHistoryRepository->recordIfNew($post, $locale, $previousSlug);
            }

            $translation->setSlug($newSlug);
        }

        $translation->setSearchContent($this->textExtractor->extract($translation));
    }

    private function snapshotRevision(PostInterface $post): void
    {
        $revision = $this->createPostRevision();
        $revision->setPost($post);
        $revision->setPostVersion($post->getVersion());
        $revision->setStatus($post->getStatus());
        $revision->setSnapshot($this->buildSnapshot($post));

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $revision->setAuthor($user);
        }

        $this->entityManager->persist($revision);
        $this->entityManager->flush();

        $limit = (int) $this->settingRepository->get(
            ApplicationParameterEnum::PostRevisionsLimit->value,
            ApplicationParameterEnum::PostRevisionsLimit->getDefaultValue(),
        );

        if ($limit > 0) {
            $this->revisionRepository->pruneOlderThanLimit($post, $limit);
        }
    }

    /** @return array<string, mixed> */
    private function buildSnapshot(PostInterface $post): array
    {
        $translations = [];
        foreach ($post->getTranslations() as $locale => $translation) {
            $translations[(string) $locale] = [
                'title' => $translation->getTitle(),
                'slug' => $translation->getSlug(),
                'blocks' => $translation->getBlocks(),
                'metaTitle' => $translation->getMetaTitle(),
                'metaDescription' => $translation->getMetaDescription(),
                'customFields' => $translation->getCustomFields(),
                'ogImageMediaId' => $translation->getOgImage()?->getId(),
                'canonicalUrl' => $translation->getCanonicalUrl(),
                'noindex' => $translation->isNoindex(),
                'focusKeyword' => $translation->getFocusKeyword(),
                'jsonLd' => $translation->getJsonLd(),
            ];
        }

        return [
            'status' => $post->getStatus()->value,
            'postTypeId' => $post->getPostType()->getId(),
            'featuredMediaId' => $post->getFeaturedMedia()?->getId(),
            'termIds' => $post->getTerms()->map(fn ($term): ?int => $term->getId())->toArray(),
            'relatedPostIds' => $post->getRelatedPosts()->map(fn ($related): ?int => $related->getId())->toArray(),
            'publishedAt' => $post->getPublishedAt()?->format(DATE_ATOM),
            'scheduledAt' => $post->getScheduledAt()?->format(DATE_ATOM),
            'translations' => $translations,
        ];
    }

    /** @param array<int> $ids @return array<int, object> */
    private function buildMediaMap(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $medias = $this->mediaRepository->findBy(['id' => $ids]);

        return array_combine(array_map(static fn ($m): int => $m->getId(), $medias), $medias);
    }

    private function hydrateDate(?string $value): ?DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Instantiates the concrete Post entity. Override in a subclass to return
     * `App\Entity\Post` (or any class implementing `PostInterface`) —
     * `resolve_target_entities` only affects Doctrine relations, not direct
     * `new`. Used by `create()`.
     */
    protected function createPost(): Post
    {
        return new Post();
    }

    protected function createPostRevision(): PostRevision
    {
        return new PostRevision();
    }

    protected function auditCreated(PostInterface $post): void
    {
        $this->auditLogger->log('editorial', 'post.created', 'Post', $post->getId(), $this->auditPayload($post));
    }

    protected function auditUpdated(PostInterface $post): void
    {
        $this->auditLogger->log('editorial', 'post.updated', 'Post', $post->getId(), $this->auditPayload($post));
    }

    protected function auditDeleted(PostInterface $post): void
    {
        $this->auditLogger->log('editorial', 'post.deleted', 'Post', $post->getId(), $this->auditPayload($post));
    }

    protected function auditPayload(PostInterface $post): array
    {
        $title = null;
        foreach ($post->getTranslations() as $translation) {
            $title = $translation->getTitle();
            if (null !== $title) {
                break;
            }
        }

        return [
            'title' => $title,
            'status' => $post->getStatus()->value,
        ];
    }
}
