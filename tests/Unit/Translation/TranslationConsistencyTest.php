<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Tests\Unit\Translation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Yaml\Yaml;

/**
 * Editorial's counterpart to aurora-core's test of the same name.
 *
 * Core's version once globbed a fixed directory depth and silently skipped 8 of
 * its 14 translation directories, which is how a messages.en.yaml came to hold
 * French text unnoticed. This one discovers files by walking the package, so a
 * translation directory added anywhere is covered the moment it exists.
 */
final class TranslationConsistencyTest extends TestCase
{
    /** @return list<array{string, array<string, mixed>, array<string, mixed>}> */
    public static function translationPairsProvider(): array
    {
        $root = dirname(__DIR__, 3);
        $pairs = [];

        $walker = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($walker as $file) {
            if ('messages.fr.yaml' !== $file->getFilename()) {
                continue;
            }

            $dir = \dirname($file->getPathname());
            if (str_contains($dir, '/vendor/') || str_contains($dir, '/tools/')) {
                continue;
            }

            $enFile = $dir.'/messages.en.yaml';
            if (!file_exists($enFile)) {
                continue;
            }

            // Keyed by path, not by a derived module name: two directories that
            // reduce to the same label overwrite each other in this array and
            // only one of them ever gets asserted on.
            $label = str_replace($root.'/', '', $dir);

            $pairs[$label] = [
                $label,
                Yaml::parseFile($file->getPathname()) ?? [],
                Yaml::parseFile($enFile) ?? [],
            ];
        }

        ksort($pairs);

        return array_values($pairs);
    }

    /**
     * @param array<string, mixed> $fr
     * @param array<string, mixed> $en
     */
    #[DataProvider('translationPairsProvider')]
    public function testFrEnKeyParity(string $label, array $fr, array $en): void
    {
        $flatFr = self::flatten($fr);
        $flatEn = self::flatten($en);

        self::assertSame(
            [],
            array_keys(array_diff_key($flatFr, $flatEn)),
            sprintf('[%s] Keys present in FR but missing in EN', $label),
        );

        self::assertSame(
            [],
            array_keys(array_diff_key($flatEn, $flatFr)),
            sprintf('[%s] Keys present in EN but missing in FR', $label),
        );
    }

    /**
     * @param array<string, mixed> $fr
     * @param array<string, mixed> $en
     */
    #[DataProvider('translationPairsProvider')]
    public function testNoEmptyValues(string $label, array $fr, array $en): void
    {
        foreach (['FR' => $fr, 'EN' => $en] as $locale => $tree) {
            $empty = array_filter(
                self::flatten($tree),
                static fn (mixed $value): bool => '' === $value || null === $value,
            );

            self::assertSame(
                [],
                array_keys($empty),
                sprintf('[%s] Empty or null %s values', $label, $locale),
            );
        }
    }

    /**
     * Placeholders must match between locales, or the translated string drops a
     * value at runtime instead of failing loudly.
     *
     * @param array<string, mixed> $fr
     * @param array<string, mixed> $en
     */
    #[DataProvider('translationPairsProvider')]
    public function testPlaceholderConsistency(string $label, array $fr, array $en): void
    {
        $flatFr = self::flatten($fr);
        $flatEn = self::flatten($en);

        foreach (array_keys(array_intersect_key($flatFr, $flatEn)) as $key) {
            $frPlaceholders = self::placeholders((string) $flatFr[$key]);
            $enPlaceholders = self::placeholders((string) $flatEn[$key]);

            self::assertSame(
                $enPlaceholders,
                $frPlaceholders,
                sprintf('[%s] Placeholder mismatch for "%s"', $label, $key),
            );
        }
    }

    /**
     * @param array<string, mixed> $tree
     *
     * @return array<string, mixed>
     */
    private static function flatten(array $tree, string $prefix = ''): array
    {
        $flat = [];

        foreach ($tree as $key => $value) {
            if (is_array($value)) {
                $flat += self::flatten($value, $prefix.$key.'.');

                continue;
            }
            $flat[$prefix.$key] = $value;
        }

        return $flat;
    }

    /** @return list<string> */
    private static function placeholders(string $value): array
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $value, $matches);
        $found = array_unique($matches[1]);
        sort($found);

        return array_values($found);
    }
}
