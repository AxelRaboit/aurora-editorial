<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Module\Editorial\Post\Contract\PostTypeManagerInterface;
use Aurora\Module\Editorial\Post\DTO\PostTypeFieldInput;
use Aurora\Module\Editorial\Post\DTO\PostTypeInput;
use Aurora\Module\Editorial\Post\Entity\PostType;
use Aurora\Module\Editorial\Post\Entity\PostTypeField;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(PostTypeManagerInterface::class)]
final readonly class PostTypeManager implements PostTypeManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PostTypeRepository $postTypeRepository,
        private TaxonomyRepository $taxonomyRepository,
        private TranslatorInterface $translator,
        private AuditLogger $auditLogger,
    ) {}

    public function create(PostTypeInput $input): PostType
    {
        if (null !== $this->postTypeRepository->findOneBy(['slug' => $input->slug])) {
            throw new InvalidArgumentException($this->translator->trans('admin.postTypes.errors.slug_taken', ['{slug}' => $input->slug]));
        }

        $postType = new PostType()
            ->setSlug($input->slug)
            ->setLabel($input->label)
            ->setIcon($input->icon)
            ->setHasArchive($input->hasArchive)
            ->setIsBuiltIn(false)
            ->setSupports($input->supports);

        $this->syncTaxonomies($postType, $input->taxonomyIds);

        $this->entityManager->persist($postType);
        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'post_type.created', 'PostType', $postType->getId(), ['slug' => $postType->getSlug()]);

        return $postType;
    }

    public function update(PostType $postType, PostTypeInput $input): void
    {
        if (!$postType->isBuiltIn() && $input->slug !== $postType->getSlug()) {
            if (null !== $this->postTypeRepository->findOneBy(['slug' => $input->slug])) {
                throw new InvalidArgumentException($this->translator->trans('admin.postTypes.errors.slug_taken', ['{slug}' => $input->slug]));
            }

            $postType->setSlug($input->slug);
        }

        $postType->setLabel($input->label);
        $postType->setIcon($input->icon);
        $postType->setHasArchive($input->hasArchive);
        $postType->setSupports($input->supports);

        $this->syncTaxonomies($postType, $input->taxonomyIds);

        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'post_type.updated', 'PostType', $postType->getId(), ['slug' => $postType->getSlug()]);
    }

    public function delete(PostType $postType): void
    {
        if ($postType->isBuiltIn()) {
            throw new RuntimeException($this->translator->trans('admin.postTypes.errors.builtin_protected'));
        }

        if ($postType->getPosts()->count() > 0) {
            throw new RuntimeException($this->translator->trans('admin.postTypes.errors.has_posts'));
        }

        $id = $postType->getId();
        $slug = $postType->getSlug();
        $this->entityManager->remove($postType);
        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'post_type.deleted', 'PostType', $id, ['slug' => $slug]);
    }

    public function createField(PostType $postType, PostTypeFieldInput $input): PostTypeField
    {
        $this->assertFieldNameIsUnique($postType, $input->name);

        $field = new PostTypeField()
            ->setName($input->name)
            ->setLabel($input->label)
            ->setType($input->type)
            ->setRequired($input->required)
            ->setTranslatable($input->translatable)
            ->setOptions($input->options)
            ->setPosition($this->nextPosition($postType));

        $field->setPostType($postType);

        $postType->addField($field);

        $this->entityManager->persist($field);
        $this->entityManager->flush();

        return $field;
    }

    public function updateField(PostTypeField $field, PostTypeFieldInput $input): void
    {
        if ($input->name !== $field->getName()) {
            $this->assertFieldNameIsUnique($field->getPostType(), $input->name, $field);
            $field->setName($input->name);
        }

        $field->setLabel($input->label);
        $field->setType($input->type);
        $field->setRequired($input->required);
        $field->setTranslatable($input->translatable);
        $field->setOptions($input->options);

        $this->entityManager->flush();
    }

    public function deleteField(PostTypeField $field): void
    {
        $this->entityManager->remove($field);
        $this->entityManager->flush();
    }

    public function reorderFields(PostType $postType, array $orderedFieldIds): void
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

    /** @param list<int> $taxonomyIds */
    private function syncTaxonomies(PostType $postType, array $taxonomyIds): void
    {
        foreach ($postType->getTaxonomies() as $existing) {
            if (!in_array($existing->getId(), $taxonomyIds, true)) {
                $postType->removeTaxonomy($existing);
            }
        }

        foreach ($taxonomyIds as $taxonomyId) {
            $taxonomy = $this->taxonomyRepository->find($taxonomyId);
            if (null !== $taxonomy && !$postType->getTaxonomies()->contains($taxonomy)) {
                $postType->addTaxonomy($taxonomy);
            }
        }
    }

    private function assertFieldNameIsUnique(PostType $postType, string $name, ?PostTypeField $ignore = null): void
    {
        foreach ($postType->getFields() as $field) {
            if ($field === $ignore) {
                continue;
            }

            if ($field->getName() === $name) {
                throw new InvalidArgumentException($this->translator->trans('admin.postTypes.errors.field_name_taken', ['{name}' => $name]));
            }
        }
    }

    private function nextPosition(PostType $postType): int
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
