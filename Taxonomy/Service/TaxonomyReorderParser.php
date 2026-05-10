<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Taxonomy\Service;

/**
 * Parses and sanitises the raw reorder payload sent by the frontend
 * into a normalised list of entries ready for TaxonomyManager::reorderTerms().
 */
final class TaxonomyReorderParser
{
    /**
     * @param mixed $rawEntries Raw value of `data['entries']` from the request (may not be an array).
     *
     * @return list<array{id: int, parentId: int|null, position: int}>
     */
    public static function parseReorderEntries(mixed $rawEntries): array
    {
        $entries = [];
        foreach ((array) $rawEntries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $id = (int) ($entry['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $entries[] = [
                'id' => $id,
                'parentId' => isset($entry['parentId']) && (int) $entry['parentId'] > 0 ? (int) $entry['parentId'] : null,
                'position' => (int) ($entry['position'] ?? 0),
            ];
        }

        return $entries;
    }
}
