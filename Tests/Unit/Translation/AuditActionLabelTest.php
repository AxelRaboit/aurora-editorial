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
 * The audit screen builds `backend.audit.actions.<module>.<action>` at runtime,
 * so nothing connects a label to the call site it describes. All 19 Editorial
 * actions shipped without any label at all and rendered as their raw key —
 * "backend.audit.actions.editorial.post.deleted" where the user expected
 * "Contenu mis à la corbeille" — until 2026-08-01, because no check existed on
 * either side of the split.
 *
 * Adding or renaming an action now fails here.
 */
final class AuditActionLabelTest extends TestCase
{
    /**
     * Actions this package emits whose label lives in aurora-core.
     *
     * MenuManager logs under the 'core' module rather than 'editorial': Menu
     * predates the extraction, and the value is kept so audit rows already in
     * the database keep resolving. Their labels are therefore Core's to own,
     * and Core's copy of this test lists them as orphans it tolerates.
     *
     * @var list<string>
     */
    private const array EMITTED_ELSEWHERE = [
        'core.menu.created',
        'core.menu.updated',
        'core.menu.deleted',
        'core.menu.item.created',
        'core.menu.item.updated',
        'core.menu.item.deleted',
    ];

    /** @return list<array{string}> */
    public static function localeProvider(): array
    {
        return [['fr'], ['en']];
    }

    #[DataProvider('localeProvider')]
    public function testEveryEmittedActionHasALabel(string $locale): void
    {
        $missing = array_diff(self::emittedActions(), self::definedLabels($locale), self::EMITTED_ELSEWHERE);

        self::assertSame(
            [],
            array_values($missing),
            sprintf(
                "Audit actions emitted by this package with no %s label — they render as the raw key:\n  %s",
                mb_strtoupper($locale),
                implode("\n  ", $missing),
            ),
        );
    }

    #[DataProvider('localeProvider')]
    public function testEveryLabelHasAnEmitter(string $locale): void
    {
        $orphans = array_diff(self::definedLabels($locale), self::emittedActions());

        self::assertSame(
            [],
            array_values($orphans),
            sprintf(
                "Audit labels in %s describing actions nothing emits — dead weight, or a rename whose label was left behind:\n  %s",
                mb_strtoupper($locale),
                implode("\n  ", $orphans),
            ),
        );
    }

    /**
     * Every `$this->auditLogger->log('<module>', '<action>', …)` call site,
     * flattened to `<module>.<action>`.
     *
     * Only literal arguments match, so the parsed count is asserted against the
     * raw number of call sites: a call assembled from a variable would
     * otherwise leave a silent blind spot.
     *
     * @return list<string>
     */
    private static function emittedActions(): array
    {
        $callSites = 0;
        $parsed = 0;
        $actions = [];

        foreach (self::filesNamed(static fn (string $name): bool => str_ends_with($name, '.php')) as $path) {
            $code = (string) file_get_contents($path);
            $callSites += mb_substr_count($code, 'auditLogger->log(');
            $parsed += preg_match_all("/auditLogger->log\(\s*'([a-z_]+)',\s*'([a-z_.]+)'/", $code, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $actions[$match[1].'.'.$match[2]] = true;
            }
        }

        self::assertSame(
            $callSites,
            $parsed,
            'Some auditLogger->log() call sites pass a non-literal module or action, so this test cannot see them. '
            .'Make the arguments literal, or this drift check has a blind spot.',
        );

        $actions = array_keys($actions);
        sort($actions);

        return $actions;
    }

    /**
     * Every leaf under `backend.audit.actions` in this package's translations.
     *
     * @return list<string>
     */
    private static function definedLabels(string $locale): array
    {
        $labels = [];

        $flatten = static function (mixed $node, string $prefix) use (&$flatten, &$labels): void {
            foreach ((array) $node as $key => $value) {
                if (is_array($value)) {
                    $flatten($value, $prefix.$key.'.');

                    continue;
                }
                $labels[$prefix.$key] = true;
            }
        };

        $name = sprintf('messages.%s.yaml', $locale);
        foreach (self::filesNamed(static fn (string $file): bool => $name === $file) as $path) {
            $parsed = Yaml::parseFile($path) ?? [];
            $flatten($parsed['backend']['audit']['actions'] ?? [], '');
        }

        $labels = array_keys($labels);
        sort($labels);

        return $labels;
    }

    /**
     * @param callable(string): bool $matches
     *
     * @return list<string>
     */
    private static function filesNamed(callable $matches): array
    {
        $root = dirname(__DIR__, 3);
        $paths = [];

        $walker = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($walker as $file) {
            $path = $file->getPathname();
            // Tests/ is excluded, not merely for tidiness: this very file
            // contains the literal "auditLogger->log(" three times — in a
            // docblock, in the substring count, and in the failure message —
            // and scanning itself made the blind-spot assertion count three
            // call sites it could never parse. Core's copy never hit this
            // because it scans src/ while its tests live outside it.
            //
            // Capitalised to match the namespace: this package maps PSR-4 onto
            // its own root, so a lowercase tests/ resolves to ...\tests\... and
            // Symfony's DebugClassLoader aborts the consuming app over the case
            // mismatch — which is exactly how it was first noticed.
            if (str_contains($path, '/vendor/') || str_contains($path, '/tools/')
                || str_contains($path, '/Tests/') || str_contains($path, '/node_modules/')) {
                continue;
            }

            if ($matches($file->getFilename())) {
                $paths[] = $path;
            }
        }

        sort($paths);

        return $paths;
    }
}
