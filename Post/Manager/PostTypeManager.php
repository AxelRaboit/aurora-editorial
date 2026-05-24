<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Editorial\Post\Dto\PostTypeFieldInputInterface;
use Aurora\Module\Editorial\Post\Dto\PostTypeInputInterface;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Entity\PostTypeField;
use Aurora\Module\Editorial\Post\Entity\PostTypeFieldInterface;
use Aurora\Module\Editorial\Post\Entity\PostTypeInterface;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(PostTypeManagerInterface::class)]
class PostTypeManager implements PostTypeManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly PostTypeRepository $postTypeRepository,
        protected readonly TaxonomyRepository $taxonomyRepository,
        protected readonly TranslatorInterface $translator,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(PostTypeInputInterface $input): PostTypeInterface
    {
        if (null !== $this->postTypeRepository->findOneBy(['slug' => $input->getSlug()])) {
            throw new InvalidArgumentException($this->translator->trans('backend.post_types.errors.slug_taken', ['{slug}' => $input->getSlug()]));
        }

        $postType = $this->createPostType();
        $this->applyInput($postType, $input);
        $postType->setIsBuiltIn(false);

        $this->entityManager->persist($postType);
        $this->entityManager->flush();

        $this->auditCreated($postType);

        return $postType;
    }

    public function update(PostTypeInterface $postType, PostTypeInputInterface $input): void
    {
        if (!$postType->isBuiltIn() && $input->getSlug() !== $postType->getSlug() && null !== $this->postTypeRepository->findOneBy(['slug' => $input->getSlug()])) {
            throw new InvalidArgumentException($this->translator->trans('backend.post_types.errors.slug_taken', ['{slug}' => $input->getSlug()]));
        }

        $this->applyInput($postType, $input);

        $this->entityManager->flush();

        $this->auditUpdated($postType);
    }

    public function delete(PostTypeInterface $postType): void
    {
        if ($postType->isBuiltIn()) {
            throw new RuntimeException($this->translator->trans('backend.post_types.errors.builtin_protected'));
        }

        if ($postType->getPosts()->count() > 0) {
            throw new RuntimeException($this->translator->trans('backend.post_types.errors.has_posts'));
        }

        $this->auditDeleted($postType);

        $this->entityManager->remove($postType);
        $this->entityManager->flush();
    }

    public function createField(PostTypeInterface $postType, PostTypeFieldInputInterface $input): PostTypeFieldInterface
    {
        $this->assertFieldNameIsUnique($postType, $input->getName());

        $field = $this->createPostTypeField();
        $this->applyFieldInput($field, $input);
        $field->setPosition($this->nextPosition($postType));
        $field->setPostType($postType);

        $postType->addField($field);

        $this->entityManager->persist($field);
        $this->entityManager->flush();

        return $field;
    }

    public function updateField(PostTypeFieldInterface $field, PostTypeFieldInputInterface $input): void
    {
        if ($input->getName() !== $field->getName()) {
            $this->assertFieldNameIsUnique($field->getPostType(), $input->getName(), $field);
        }

        $this->applyFieldInput($field, $input);

        $this->entityManager->flush();
    }

    public function deleteField(PostTypeFieldInterface $field): void
    {
        $this->entityManager->remove($field);
        $this->entityManager->flush();
    }

    public function reorderFields(PostTypeInterface $postType, array $orderedFieldIds): void
    {
        $fieldsById = [];
        foreach ($postType->getFields() as $field) {
            $fieldsById[$field->getId()] = $field;
        }

        $position = 0;
        foreach ($orderedFieldIds as $fieldId) {
            $field = $fieldsById[(int) $fieldId] ?? null;
            if (null === $field) {
                continue;
            }

            $field->setPosition($position++);
        }

        $this->entityManager->flush();
    }

    // ── Hooks: instanciation ──────────────────────────────────────────────────

    protected function createPostType(): PostTypeInterface
    {
        return new PostType();
    }

    protected function createPostTypeField(): PostTypeFieldInterface
    {
        return new PostTypeField();
    }

    // ── Hooks: hydratation ────────────────────────────────────────────────────

    protected function applyInput(PostTypeInterface $postType, PostTypeInputInterface $input): void
    {
        $postType->setSlug($input->getSlug());
        $postType->setLabel($input->getLabel());
        $postType->setIcon($input->getIcon());
        $postType->setHasArchive($input->hasArchive());
        $postType->setSupports($input->getSupports());
        $this->syncTaxonomies($postType, $input->getTaxonomyIds());
    }

    protected function applyFieldInput(PostTypeFieldInterface $field, PostTypeFieldInputInterface $input): void
    {
        $field->setName($input->getName());
        $field->setLabel($input->getLabel());
        $field->setType($input->getType());
        $field->setRequired($input->isRequired());
        $field->setTranslatable($input->isTranslatable());
        $field->setOptions($input->getOptions());
    }

    // ── Hooks: audit ──────────────────────────────────────────────────────────

    protected function auditCreated(PostTypeInterface $postType): void
    {
        $this->auditLogger->log('editorial', 'post_type.created', 'PostType', $postType->getId(), $this->auditPayload($postType));
    }

    protected function auditUpdated(PostTypeInterface $postType): void
    {
        $this->auditLogger->log('editorial', 'post_type.updated', 'PostType', $postType->getId(), $this->auditPayload($postType));
    }

    protected function auditDeleted(PostTypeInterface $postType): void
    {
        $this->auditLogger->log('editorial', 'post_type.deleted', 'PostType', $postType->getId(), $this->auditPayload($postType));
    }

    protected function auditPayload(PostTypeInterface $postType): array
    {
        return ['slug' => $postType->getSlug()];
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    /** @param list<int> $taxonomyIds */
    private function syncTaxonomies(PostTypeInterface $postType, array $taxonomyIds): void
    {
        foreach ($postType->getTaxonomies() as $existing) {
            if (!in_array($existing->getId(), $taxonomyIds, true)) {
                $postType->removeTaxonomy($existing);
            }
        }

        if ([] !== $taxonomyIds) {
            foreach ($this->taxonomyRepository->findBy(['id' => $taxonomyIds]) as $taxonomy) {
                if (!$postType->getTaxonomies()->contains($taxonomy)) {
                    $postType->addTaxonomy($taxonomy);
                }
            }
        }
    }

    private function assertFieldNameIsUnique(PostTypeInterface $postType, string $name, ?PostTypeFieldInterface $ignore = null): void
    {
        foreach ($postType->getFields() as $field) {
            if ($field === $ignore) {
                continue;
            }

            if ($field->getName() === $name) {
                throw new InvalidArgumentException($this->translator->trans('backend.post_types.errors.field_name_taken', ['{name}' => $name]));
            }
        }
    }

    private function nextPosition(PostTypeInterface $postType): int
    {
        $max = -1;
        foreach ($postType->getFields() as $field) {
            if ($field->getPosition() > $max) {
                $max = $field->getPosition();
            }
        }

        return $max + 1;
    }
}
