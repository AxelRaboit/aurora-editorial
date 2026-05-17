<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Service;

use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostTypeInterface;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds picker items (id/label/hint triplets) consumed by the menu admin UI
 * to populate autocomplete dropdowns when picking a Post / Term / PostType /
 * Taxonomy as a menu item target.
 */
final readonly class MenuPickerService
{
    public function __construct(
        private PostRepository $postRepository,
        private TaxonomyTermRepository $termRepository,
        private PostTypeRepository $postTypeRepository,
        private TaxonomyRepository $taxonomyRepository,
        private TranslatorInterface $translator,
    ) {}

    /**
     * @return list<array{id: int, label: string, hint: string}>
     */
    public function posts(string $query, ?int $postTypeId): array
    {
        return array_map(
            function (Post $post): array {
                $translation = $post->getTranslations()->first();
                $title = false !== $translation ? $translation->getTitle() : null;

                return [
                    'id' => (int) $post->getId(),
                    'label' => null !== $title && '' !== $title
                        ? $title
                        : $this->translator->trans('backend.menus.preview.untitled'),
                    'hint' => $post->getPostType()->getLabel(),
                ];
            },
            $this->postRepository->searchForReference($query, null, $postTypeId),
        );
    }

    /**
     * @return list<array{id: int, label: string, hint: string}>
     */
    public function terms(string $query, ?int $taxonomyId): array
    {
        return array_map(
            function (TaxonomyTerm $term): array {
                $translation = $term->getTranslations()->first();
                $name = false !== $translation ? $translation->getName() : null;

                $taxonomyTranslation = $term->getTaxonomy()->getTranslations()->first();
                $hint = false !== $taxonomyTranslation
                    ? $taxonomyTranslation->getLabel()
                    : $term->getTaxonomy()->getSlug();

                return [
                    'id' => (int) $term->getId(),
                    'label' => null !== $name && '' !== $name
                        ? $name
                        : $this->translator->trans('backend.menus.preview.unnamed'),
                    'hint' => $hint,
                ];
            },
            $this->termRepository->searchByName($query, 20, $taxonomyId),
        );
    }

    /**
     * @return list<array{id: int, label: string, hint: string}>
     */
    public function postTypes(bool $withArchive): array
    {
        $criteria = $withArchive ? ['hasArchive' => true] : [];

        return array_map(
            static fn (PostTypeInterface $postType): array => [
                'id' => (int) $postType->getId(),
                'label' => $postType->getLabel(),
                'hint' => $postType->getSlug(),
            ],
            $this->postTypeRepository->findBy($criteria),
        );
    }

    /**
     * @return list<array{id: int, label: string, hint: string}>
     */
    public function taxonomies(): array
    {
        return array_map(
            static function (TaxonomyInterface $taxonomy): array {
                $translation = $taxonomy->getTranslations()->first();
                $label = false !== $translation ? $translation->getLabel() : null;

                return [
                    'id' => (int) $taxonomy->getId(),
                    'label' => $label ?? $taxonomy->getSlug(),
                    'hint' => $taxonomy->getSlug(),
                ];
            },
            $this->taxonomyRepository->findAll(),
        );
    }
}
