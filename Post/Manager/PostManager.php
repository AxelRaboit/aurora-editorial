<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Editorial\Post\Contract\PostManagerInterface;
use Aurora\Module\Editorial\Post\DTO\PostInput;
use Aurora\Module\Editorial\Post\DTO\PostTranslationInput;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostRevision;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Repository\PostRevisionRepository;
use Aurora\Module\Editorial\Post\Repository\PostSlugHistoryRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Post\Service\PostTextExtractor;
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
final readonly class PostManager implements PostManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PostTypeRepository $postTypeRepository,
        private TaxonomyTermRepository $termRepository,
        private MediaRepository $mediaRepository,
        private PostRevisionRepository $revisionRepository,
        private PostSlugHistoryRepository $slugHistoryRepository,
        private SettingRepository $settingRepository,
        private SluggerInterface $slugger,
        private Security $security,
        private PostTextExtractor $textExtractor,
        private TranslatorInterface $translator,
        private AuditLogger $auditLogger,
        private SequenceGenerator $sequenceGenerator,
    ) {}

    public function create(PostInput $input): Post
    {
        $post = new Post();
        $this->applyInput($post, $input);

        $currentUser = $this->security->getUser();
        if ($currentUser instanceof User) {
            $post->setAuthor($currentUser);
        }

        $prefix = $this->settingRepository->get(ApplicationParameterEnum::EditorialPostPrefix->value, SequencePrefixEnum::Post->value) ?? SequencePrefixEnum::Post->value;
        $post->setReference($this->sequenceGenerator->next($prefix));
        $this->entityManager->persist($post);
        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'post.created', 'Post', $post->getId(), [
            'title' => $post->translate('fr')->getTitle() ?? $post->translate('en')->getTitle(),
        ]);

        return $post;
    }

    public function update(Post $post, PostInput $input): void
    {
        $this->applyInput($post, $input);
        // Force the Post entity to be marked as dirty so Doctrine's @Version increments
        // even when only related entities (translations, tags) changed — @Version only
        // bumps when the owning entity itself is scheduled for UPDATE.
        $post->updateTimestamps();
        $this->entityManager->flush();

        $this->snapshotRevision($post);

        $this->auditLogger->log('editorial', 'post.updated', 'Post', $post->getId(), [
            'title' => $post->translate('fr')->getTitle() ?? $post->translate('en')->getTitle(),
            'status' => $post->getStatus()->value,
        ]);
    }

    public function delete(Post $post): void
    {
        if ($post->isTrashed()) {
            return;
        }

        $post->setDeletedAt(new DateTimeImmutable());
        $post->updateTimestamps();

        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'post.deleted', 'Post', $post->getId());
    }

    public function restore(Post $post): void
    {
        $post->setDeletedAt(null);
        $post->updateTimestamps();

        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'post.restored', 'Post', $post->getId());
    }

    public function forceDelete(Post $post): void
    {
        $id = $post->getId();
        $this->entityManager->remove($post);
        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'post.force_deleted', 'Post', $id);
    }

    public function restoreRevision(Post $post, PostRevision $revision): void
    {
        $snapshot = $revision->getSnapshot();

        $post->setStatus(PostStatusEnum::from($snapshot['status'] ?? PostStatusEnum::Draft->value));

        $post->setPublishedAt($this->hydrateDate($snapshot['publishedAt'] ?? null));
        $post->setScheduledAt($this->hydrateDate($snapshot['scheduledAt'] ?? null));

        $featuredMediaId = $snapshot['featuredMediaId'] ?? null;
        $post->setFeaturedMedia(null !== $featuredMediaId ? $this->mediaRepository->find($featuredMediaId) : null);

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

            $ogImageId = $translationData['ogImageMediaId'] ?? null;
            $translation->setOgImage(null !== $ogImageId ? $this->mediaRepository->find($ogImageId) : null);
            $translation->setCanonicalUrl($translationData['canonicalUrl'] ?? null);
            $translation->setNoindex((bool) ($translationData['noindex'] ?? false));
            $translation->setFocusKeyword($translationData['focusKeyword'] ?? null);
            $jsonLd = $translationData['jsonLd'] ?? null;
            $translation->setJsonLd(is_array($jsonLd) ? $jsonLd : null);

            $translation->setSearchContent($this->textExtractor->extract($translation));
        }

        $post->updateTimestamps();
        $this->entityManager->flush();

        $this->snapshotRevision($post);

        $this->auditLogger->log('editorial', 'post.revision_restored', 'Post', $post->getId(), [
            'revisionId' => $revision->getId(),
        ]);
    }

    private function applyInput(Post $post, PostInput $input): void
    {
        $postType = $this->postTypeRepository->find($input->postTypeId);
        if (null === $postType) {
            throw new InvalidArgumentException($this->translator->trans('admin.posts.errors.post_type_not_found', ['{id}' => $input->postTypeId]));
        }

        $post->setPostType($postType);

        $status = PostStatusEnum::from($input->status);
        $post->setStatus($status);

        if (PostStatusEnum::Scheduled === $status && null !== $input->scheduledAt) {
            $post->setScheduledAt(new DateTimeImmutable($input->scheduledAt));
        } else {
            $post->setScheduledAt(null);
        }

        if (PostStatusEnum::Published === $status && !$post->getPublishedAt() instanceof DateTimeImmutable) {
            $post->setPublishedAt(new DateTimeImmutable());
        }

        $featuredMedia = null !== $input->featuredMediaId
            ? $this->mediaRepository->find($input->featuredMediaId)
            : null;
        $post->setFeaturedMedia($featuredMedia);

        $post->setCommentsEnabled($input->commentsEnabled);
        $this->syncTerms($post, $input->termIds);
        $this->syncRelatedPosts($post, $input->relatedPostIds);

        foreach ($input->translations as $locale => $translationInput) {
            $this->applyTranslation($post, $locale, $translationInput);
        }
    }

    /** @param array<int> $termIds */
    private function syncTerms(Post $post, array $termIds): void
    {
        foreach ($post->getTerms() as $existingTerm) {
            if (!in_array($existingTerm->getId(), $termIds, true)) {
                $post->removeTerm($existingTerm);
            }
        }

        $currentTermIds = $post->getTerms()->map(fn ($term): ?int => $term->getId())->toArray();

        foreach ($termIds as $termId) {
            if (!in_array($termId, $currentTermIds, true)) {
                $term = $this->termRepository->find($termId);
                if (null !== $term) {
                    $post->addTerm($term);
                }
            }
        }
    }

    /** @param array<int> $relatedPostIds */
    private function syncRelatedPosts(Post $post, array $relatedPostIds): void
    {
        $relatedPostIds = array_values(array_filter($relatedPostIds, fn (int $id): bool => $id !== $post->getId()));

        foreach ($post->getRelatedPosts() as $existing) {
            if (!in_array($existing->getId(), $relatedPostIds, true)) {
                $post->removeRelatedPost($existing);
            }
        }

        $currentIds = $post->getRelatedPosts()->map(fn ($related): ?int => $related->getId())->toArray();

        $repository = $this->entityManager->getRepository(Post::class);
        foreach ($relatedPostIds as $id) {
            if (!in_array($id, $currentIds, true)) {
                $related = $repository->find($id);
                if (null !== $related) {
                    $post->addRelatedPost($related);
                }
            }
        }
    }

    private function applyTranslation(Post $post, string $locale, PostTranslationInput $input): void
    {
        $translation = $post->translate($locale);

        $translation->setTitle($input->title);
        $translation->setBlocks($input->blocks);
        $translation->setMetaTitle($input->metaTitle);
        $translation->setMetaDescription($input->metaDescription);
        $translation->setCustomFields($input->customFields);

        $translation->setOgImage(
            null !== $input->ogImageMediaId ? $this->mediaRepository->find($input->ogImageMediaId) : null,
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

    private function snapshotRevision(Post $post): void
    {
        $revision = new PostRevision();
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
    private function buildSnapshot(Post $post): array
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
}
