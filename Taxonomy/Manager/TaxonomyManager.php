<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Taxonomy\Dto\TaxonomyInputInterface;
use Aurora\Module\Editorial\Taxonomy\Dto\TaxonomyTermInputInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyInterface;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTermInterface;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(TaxonomyManagerInterface::class)]
class TaxonomyManager implements TaxonomyManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly TaxonomyRepository $taxonomyRepository,
        protected readonly TaxonomyTermRepository $termRepository,
        protected readonly PostTypeRepository $postTypeRepository,
        protected readonly SluggerInterface $slugger,
        protected readonly TranslatorInterface $translator,
        protected readonly AuditLogger $auditLogger,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly SettingRepository $settingRepository,
    ) {}

    public function create(TaxonomyInputInterface $input): TaxonomyInterface
    {
        if ($this->taxonomyRepository->findOneBySlug($input->getSlug()) instanceof Taxonomy) {
            throw new InvalidArgumentException($this->translator->trans('backend.taxonomies.errors.slug_taken', ['{slug}' => $input->getSlug()]));
        }

        $taxonomy = $this->createTaxonomy();
        $taxonomy->setSlug($input->getSlug());
        $taxonomy->setHierarchical($input->isHierarchical());
        $taxonomy->setIsBuiltIn(false);

        $this->applyTaxonomyTranslations($taxonomy, $input->getTranslations());
        $this->syncPostTypes($taxonomy, $input->getPostTypeIds());

        $this->entityManager->persist($taxonomy);
        $this->entityManager->flush();

        $this->auditTaxonomyCreated($taxonomy);

        return $taxonomy;
    }

    public function update(TaxonomyInterface $taxonomy, TaxonomyInputInterface $input): void
    {
        if (!$taxonomy->isBuiltIn()) {
            if ($input->getSlug() !== $taxonomy->getSlug()) {
                if ($this->taxonomyRepository->findOneBySlug($input->getSlug()) instanceof Taxonomy) {
                    throw new InvalidArgumentException($this->translator->trans('backend.taxonomies.errors.slug_taken', ['{slug}' => $input->getSlug()]));
                }

                $taxonomy->setSlug($input->getSlug());
            }

            $taxonomy->setHierarchical($input->isHierarchical());
        }

        $this->applyTaxonomyTranslations($taxonomy, $input->getTranslations());
        $this->syncPostTypes($taxonomy, $input->getPostTypeIds());

        $this->entityManager->flush();

        $this->auditTaxonomyUpdated($taxonomy);
    }

    public function delete(TaxonomyInterface $taxonomy): void
    {
        if ($taxonomy->isBuiltIn()) {
            throw new RuntimeException($this->translator->trans('backend.taxonomies.errors.builtin_protected'));
        }

        $this->auditTaxonomyDeleted($taxonomy);

        $this->entityManager->remove($taxonomy);
        $this->entityManager->flush();
    }

    public function createTerm(TaxonomyInterface $taxonomy, TaxonomyTermInputInterface $input): TaxonomyTermInterface
    {
        $term = $this->createTaxonomyTerm();
        $term->setTaxonomy($taxonomy);

        $parent = $this->resolveParent($taxonomy, $input->getParentId());
        $term->setParent($parent);

        $term->setPosition($this->nextPositionFor($taxonomy, $parent));

        $this->applyTermTranslations($term, $input->getTranslations());

        $this->entityManager->persist($term);
        $this->entityManager->flush();

        $termPrefix = $this->settingRepository->get(ApplicationParameterEnum::EditorialTaxonomyTermPrefix->value, SequencePrefixEnum::TaxonomyTerm->value) ?? SequencePrefixEnum::TaxonomyTerm->value;
        $term->setReference($this->sequenceGenerator->next($termPrefix));
        $this->entityManager->flush();

        $this->auditTermCreated($term);

        return $term;
    }

    public function updateTerm(TaxonomyTermInterface $term, TaxonomyTermInputInterface $input): void
    {
        $parent = $this->resolveParent($term->getTaxonomy(), $input->getParentId());

        if ($parent !== $term->getParent()) {
            if ($parent instanceof TaxonomyTermInterface && ($parent === $term || $parent->isDescendantOf($term))) {
                throw new InvalidArgumentException($this->translator->trans('backend.taxonomies.errors.term_self_nested'));
            }

            $term->setParent($parent);
        }

        $this->applyTermTranslations($term, $input->getTranslations());

        $this->entityManager->flush();

        $this->auditTermUpdated($term);
    }

    public function deleteTerm(TaxonomyTermInterface $term): void
    {
        // Promote direct children to this term's parent so the subtree is preserved.
        foreach ($term->getChildren() as $child) {
            $child->setParent($term->getParent());
        }

        $this->auditTermDeleted($term);

        $this->entityManager->remove($term);
        $this->entityManager->flush();
    }

    public function reorderTerms(TaxonomyInterface $taxonomy, array $entries): void
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
                    throw new InvalidArgumentException($this->translator->trans('backend.taxonomies.errors.reorder_cycle', ['{id}' => $id]));
                }

                $visited[$current] = true;
                $current = $parentMap[$current] ?? null;
            }
        }

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

    // ── Hooks: instanciation ──────────────────────────────────────────────────

    protected function createTaxonomy(): TaxonomyInterface
    {
        return new Taxonomy();
    }

    protected function createTaxonomyTerm(): TaxonomyTermInterface
    {
        return new TaxonomyTerm();
    }

    // ── Hooks: audit ──────────────────────────────────────────────────────────

    protected function auditTaxonomyCreated(TaxonomyInterface $taxonomy): void
    {
        $this->auditLogger->log('editorial', 'taxonomy.created', 'Taxonomy', $taxonomy->getId(), $this->auditTaxonomyPayload($taxonomy));
    }

    protected function auditTaxonomyUpdated(TaxonomyInterface $taxonomy): void
    {
        $this->auditLogger->log('editorial', 'taxonomy.updated', 'Taxonomy', $taxonomy->getId(), $this->auditTaxonomyPayload($taxonomy));
    }

    protected function auditTaxonomyDeleted(TaxonomyInterface $taxonomy): void
    {
        $this->auditLogger->log('editorial', 'taxonomy.deleted', 'Taxonomy', $taxonomy->getId(), $this->auditTaxonomyPayload($taxonomy));
    }

    protected function auditTermCreated(TaxonomyTermInterface $term): void
    {
        $this->auditLogger->log('editorial', 'taxonomy.term.created', 'TaxonomyTerm', $term->getId(), $this->auditTermPayload($term));
    }

    protected function auditTermUpdated(TaxonomyTermInterface $term): void
    {
        $this->auditLogger->log('editorial', 'taxonomy.term.updated', 'TaxonomyTerm', $term->getId(), $this->auditTermPayload($term));
    }

    protected function auditTermDeleted(TaxonomyTermInterface $term): void
    {
        $this->auditLogger->log('editorial', 'taxonomy.term.deleted', 'TaxonomyTerm', $term->getId(), $this->auditTermPayload($term));
    }

    protected function auditTaxonomyPayload(TaxonomyInterface $taxonomy): array
    {
        return ['slug' => $taxonomy->getSlug()];
    }

    protected function auditTermPayload(TaxonomyTermInterface $term): array
    {
        return ['taxonomySlug' => $term->getTaxonomy()->getSlug()];
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    /** @param array<string, array{label?: string, description?: ?string}> $translations */
    private function applyTaxonomyTranslations(TaxonomyInterface $taxonomy, array $translations): void
    {
        foreach ($translations as $locale => $payload) {
            $translation = $taxonomy->translate((string) $locale);
            $translation->setLabel((string) ($payload['label'] ?? ''));
            $translation->setDescription($payload['description'] ?? null);
        }
    }

    /** @param array<string, array{name?: ?string, slug?: ?string, description?: ?string}> $translations */
    private function applyTermTranslations(TaxonomyTermInterface $term, array $translations): void
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
    private function syncPostTypes(TaxonomyInterface $taxonomy, array $postTypeIds): void
    {
        foreach ($taxonomy->getPostTypes() as $existing) {
            if (!in_array($existing->getId(), $postTypeIds, true)) {
                $existing->removeTaxonomy($taxonomy);
            }
        }

        if ([] !== $postTypeIds) {
            foreach ($this->postTypeRepository->findBy(['id' => $postTypeIds]) as $postType) {
                if (!$postType->getTaxonomies()->contains($taxonomy)) {
                    $postType->addTaxonomy($taxonomy);
                }
            }
        }
    }

    private function resolveParent(TaxonomyInterface $taxonomy, ?int $parentId): ?TaxonomyTermInterface
    {
        if (null === $parentId) {
            return null;
        }

        if (!$taxonomy->isHierarchical()) {
            return null;
        }

        $parent = $this->termRepository->find($parentId);
        if (null === $parent || $parent->getTaxonomy() !== $taxonomy) {
            throw new InvalidArgumentException($this->translator->trans('backend.taxonomies.errors.parent_wrong_taxonomy', ['{parentId}' => $parentId, '{taxonomy}' => $taxonomy->getSlug()]));
        }

        return $parent;
    }

    private function nextPositionFor(TaxonomyInterface $taxonomy, ?TaxonomyTermInterface $parent): int
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
