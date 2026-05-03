<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Taxonomy\Contract\TaxonomyManagerInterface;
use Aurora\Module\Editorial\Taxonomy\DTO\TaxonomyInput;
use Aurora\Module\Editorial\Taxonomy\DTO\TaxonomyTermInput;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(TaxonomyManagerInterface::class)]
final readonly class TaxonomyManager implements TaxonomyManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TaxonomyRepository $taxonomyRepository,
        private TaxonomyTermRepository $termRepository,
        private PostTypeRepository $postTypeRepository,
        private SluggerInterface $slugger,
        private TranslatorInterface $translator,
        private AuditLogger $auditLogger,
        private SequenceGenerator $sequenceGenerator,
        private SettingRepository $settingRepository,
    ) {}

    public function create(TaxonomyInput $input): Taxonomy
    {
        if ($this->taxonomyRepository->findOneBySlug($input->slug) instanceof Taxonomy) {
            throw new InvalidArgumentException($this->translator->trans('admin.taxonomies.errors.slug_taken', ['{slug}' => $input->slug]));
        }

        $taxonomy = new Taxonomy()
            ->setSlug($input->slug)
            ->setHierarchical($input->hierarchical)
            ->setIsBuiltIn(false);

        $this->applyTranslations($taxonomy, $input->translations);
        $this->syncPostTypes($taxonomy, $input->postTypeIds);

        $this->entityManager->persist($taxonomy);
        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'taxonomy.created', 'Taxonomy', $taxonomy->getId(), ['slug' => $taxonomy->getSlug()]);

        return $taxonomy;
    }

    public function update(Taxonomy $taxonomy, TaxonomyInput $input): void
    {
        if (!$taxonomy->isBuiltIn()) {
            if ($input->slug !== $taxonomy->getSlug()) {
                if ($this->taxonomyRepository->findOneBySlug($input->slug) instanceof Taxonomy) {
                    throw new InvalidArgumentException($this->translator->trans('admin.taxonomies.errors.slug_taken', ['{slug}' => $input->slug]));
                }

                $taxonomy->setSlug($input->slug);
            }

            $taxonomy->setHierarchical($input->hierarchical);
        }

        $this->applyTranslations($taxonomy, $input->translations);
        $this->syncPostTypes($taxonomy, $input->postTypeIds);

        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'taxonomy.updated', 'Taxonomy', $taxonomy->getId(), ['slug' => $taxonomy->getSlug()]);
    }

    public function delete(Taxonomy $taxonomy): void
    {
        if ($taxonomy->isBuiltIn()) {
            throw new RuntimeException($this->translator->trans('admin.taxonomies.errors.builtin_protected'));
        }

        $id = $taxonomy->getId();
        $slug = $taxonomy->getSlug();
        $this->entityManager->remove($taxonomy);
        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'taxonomy.deleted', 'Taxonomy', $id, ['slug' => $slug]);
    }

    public function createTerm(Taxonomy $taxonomy, TaxonomyTermInput $input): TaxonomyTerm
    {
        $term = new TaxonomyTerm()->setTaxonomy($taxonomy);

        $parent = $this->resolveParent($taxonomy, $input->parentId);
        $term->setParent($parent);

        $term->setPosition($this->nextPositionFor($taxonomy, $parent));

        $this->applyTermTranslations($term, $input->translations);

        $this->entityManager->persist($term);
        $this->entityManager->flush();

        $termPrefix = $this->settingRepository->get(ApplicationParameterEnum::EditorialTaxonomyTermPrefix->value, SequencePrefixEnum::TaxonomyTerm->value) ?? SequencePrefixEnum::TaxonomyTerm->value;
        $term->setReference($this->sequenceGenerator->next($termPrefix));
        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'taxonomy.term.created', 'TaxonomyTerm', $term->getId(), ['taxonomySlug' => $taxonomy->getSlug()]);

        return $term;
    }

    public function updateTerm(TaxonomyTerm $term, TaxonomyTermInput $input): void
    {
        $parent = $this->resolveParent($term->getTaxonomy(), $input->parentId);

        if ($parent !== $term->getParent()) {
            if ($parent instanceof TaxonomyTerm && ($parent === $term || $parent->isDescendantOf($term))) {
                throw new InvalidArgumentException($this->translator->trans('admin.taxonomies.errors.term_self_nested'));
            }

            $term->setParent($parent);
        }

        $this->applyTermTranslations($term, $input->translations);

        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'taxonomy.term.updated', 'TaxonomyTerm', $term->getId());
    }

    public function deleteTerm(TaxonomyTerm $term): void
    {
        // Promote direct children to this term's parent so the subtree is preserved.
        foreach ($term->getChildren() as $child) {
            $child->setParent($term->getParent());
        }

        $id = $term->getId();
        $this->entityManager->remove($term);
        $this->entityManager->flush();

        $this->auditLogger->log('editorial', 'taxonomy.term.deleted', 'TaxonomyTerm', $id);
    }

    public function reorderTerms(Taxonomy $taxonomy, array $entries): void
    {
        $termsById = [];
        foreach ($this->termRepository->findByTaxonomyOrdered($taxonomy) as $term) {
            $termsById[$term->getId()] = $term;
        }

        // Build the incoming parent map and detect cycles on the intended tree
        // before mutating entities (avoids false negatives after parent detachment).
        $parentMap = [];
        foreach ($entries as $entry) {
            $id = (int) $entry['id'];
            if (!isset($termsById[$id])) {
                continue;
            }

            $parentMap[$id] = isset($entry['parentId']) && $entry['parentId'] > 0 ? $entry['parentId'] : null;
        }

        foreach ($parentMap as $id => $initialParentId) {
            $visited = [$id => true];
            $current = $initialParentId;
            while (null !== $current) {
                if (isset($visited[$current])) {
                    throw new InvalidArgumentException($this->translator->trans('admin.taxonomies.errors.reorder_cycle', ['{id}' => $id]));
                }

                $visited[$current] = true;
                $current = $parentMap[$current] ?? null;
            }
        }

        // Detach then reassign, now that we know the target tree is acyclic.
        foreach (array_keys($parentMap) as $id) {
            $termsById[$id]->setParent(null);
        }

        foreach ($entries as $entry) {
            $id = (int) $entry['id'];
            $term = $termsById[$id] ?? null;
            if (null === $term) {
                continue;
            }

            $parentId = $parentMap[$id] ?? null;
            $term->setParent(null !== $parentId ? ($termsById[$parentId] ?? null) : null);
            $term->setPosition((int) $entry['position']);
        }

        $this->entityManager->flush();
    }

    /** @param array<string, array{label?: string, description?: ?string}> $translations */
    private function applyTranslations(Taxonomy $taxonomy, array $translations): void
    {
        foreach ($translations as $locale => $payload) {
            $translation = $taxonomy->translate((string) $locale);
            $translation->setLabel((string) ($payload['label'] ?? ''));
            $translation->setDescription($payload['description'] ?? null);
        }
    }

    /** @param array<string, array{name?: ?string, slug?: ?string, description?: ?string}> $translations */
    private function applyTermTranslations(TaxonomyTerm $term, array $translations): void
    {
        foreach ($translations as $locale => $payload) {
            $translation = $term->translate((string) $locale);
            $name = (string) ($payload['name'] ?? '');
            $translation->setName($name);
            $slug = $payload['slug'] ?? null;
            if (null === $slug || '' === $slug) {
                $slug = '' !== $name ? $this->slugger->slug($name)->lower()->toString() : '';
            }

            $translation->setSlug($slug);
            $translation->setDescription($payload['description'] ?? null);
        }
    }

    /** @param array<int> $postTypeIds */
    private function syncPostTypes(Taxonomy $taxonomy, array $postTypeIds): void
    {
        foreach ($taxonomy->getPostTypes() as $existing) {
            if (!in_array($existing->getId(), $postTypeIds, true)) {
                $existing->removeTaxonomy($taxonomy);
            }
        }

        foreach ($postTypeIds as $postTypeId) {
            $postType = $this->postTypeRepository->find($postTypeId);
            if (null !== $postType && !$postType->getTaxonomies()->contains($taxonomy)) {
                $postType->addTaxonomy($taxonomy);
            }
        }
    }

    private function resolveParent(Taxonomy $taxonomy, ?int $parentId): ?TaxonomyTerm
    {
        if (null === $parentId) {
            return null;
        }

        if (!$taxonomy->isHierarchical()) {
            return null;
        }

        $parent = $this->termRepository->find($parentId);
        if (null === $parent || $parent->getTaxonomy() !== $taxonomy) {
            throw new InvalidArgumentException($this->translator->trans('admin.taxonomies.errors.parent_wrong_taxonomy', ['{parentId}' => $parentId, '{taxonomy}' => $taxonomy->getSlug()]));
        }

        return $parent;
    }

    private function nextPositionFor(Taxonomy $taxonomy, ?TaxonomyTerm $parent): int
    {
        $max = 0;
        foreach ($this->termRepository->findBy(['taxonomy' => $taxonomy, 'parent' => $parent]) as $sibling) {
            if ($sibling->getPosition() > $max) {
                $max = $sibling->getPosition();
            }
        }

        return $max + 1;
    }
}
