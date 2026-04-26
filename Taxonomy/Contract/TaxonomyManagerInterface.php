<?php

declare(strict_types=1);

namespace App\Module\Editorial\Taxonomy\Contract;

use App\Module\Editorial\Taxonomy\DTO\TaxonomyInput;
use App\Module\Editorial\Taxonomy\DTO\TaxonomyTermInput;
use App\Module\Editorial\Taxonomy\Entity\Taxonomy;
use App\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;

interface TaxonomyManagerInterface
{
    public function create(TaxonomyInput $input): Taxonomy;

    public function update(Taxonomy $taxonomy, TaxonomyInput $input): void;

    public function delete(Taxonomy $taxonomy): void;

    public function createTerm(Taxonomy $taxonomy, TaxonomyTermInput $input): TaxonomyTerm;

    public function updateTerm(TaxonomyTerm $term, TaxonomyTermInput $input): void;

    public function deleteTerm(TaxonomyTerm $term): void;

    /**
     * @param list<array{id: int, parentId: ?int, position: int}> $entries
     */
    public function reorderTerms(Taxonomy $taxonomy, array $entries): void;
}
