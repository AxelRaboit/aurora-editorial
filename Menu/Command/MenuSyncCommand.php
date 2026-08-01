<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Command;

use Aurora\Module\Editorial\Menu\Dto\MenuInput;
use Aurora\Module\Editorial\Menu\Dto\MenuItemInput;
use Aurora\Module\Editorial\Menu\Entity\Menu;
use Aurora\Module\Editorial\Menu\Enum\MenuItemTargetTypeEnum;
use Aurora\Module\Editorial\Menu\Enum\MenuItemVisibilityEnum;
use Aurora\Module\Editorial\Menu\Manager\MenuManagerInterface;
use Aurora\Module\Editorial\Menu\Repository\MenuRepository;
use Aurora\Module\Editorial\Menu\Service\MenuLocationRegistry;
use Aurora\Module\Editorial\PostType\Repository\PostTypeRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'aurora:menus:sync',
    description: 'Crée les menus manquants pour les locations enregistrées (primary, footer, …).',
    aliases: ['aurora:menus'],
)]
class MenuSyncCommand extends Command
{
    public function __construct(
        private readonly MenuLocationRegistry $registry,
        private readonly MenuRepository $menuRepository,
        private readonly MenuManagerInterface $menuManager,
        private readonly PostTypeRepository $postTypeRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les changements sans les appliquer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        if ($dryRun) {
            $symfonyStyle->note('Mode dry-run — aucun changement ne sera enregistré.');
        }

        $created = 0;
        $existing = 0;
        $backfilled = 0;

        foreach ($this->registry->all() as $location => $meta) {
            $menu = $this->menuRepository->findByLocation($location);

            if ($menu instanceof Menu) {
                // Existing menu: top up the *protected* defaults it's missing,
                // rather than skipping the location outright. A menu created by
                // something other than this command — the demo fixtures create
                // the `account` menu and add no item to it — used to stay empty
                // forever, because "menu exists" was read as "location is done".
                //
                // Unprotected defaults are deliberately not backfilled: they are
                // a starting point offered once, so re-adding one the user chose
                // to delete would read as the deletion having failed.
                $missing = $this->missingDefaultItems(
                    $menu,
                    array_values(array_filter(
                        $meta['defaultItems'],
                        static fn (array $itemConfig): bool => true === ($itemConfig['protected'] ?? false),
                    )),
                );

                if ([] === $missing) {
                    $symfonyStyle->writeln(sprintf('  <comment>=</comment> %s (déjà présent)', $location));
                    ++$existing;

                    continue;
                }

                $symfonyStyle->writeln(sprintf(
                    '  <info>~</info> %s (déjà présent) — %d entrée(s) par défaut ajoutée(s)',
                    $location,
                    count($missing),
                ));
                ++$backfilled;

                if (!$dryRun) {
                    $this->createDefaultItems($menu, $missing);
                }

                continue;
            }

            $symfonyStyle->writeln(sprintf('  <info>+</info> %s — %s', $location, $meta['name']));
            ++$created;

            if (!$dryRun) {
                $menu = $this->menuManager->create(new MenuInput(
                    name: $meta['name'],
                    location: $location,
                    description: $meta['description'],
                ));
                $this->createDefaultItems($menu, $meta['defaultItems']);
            }
        }

        $symfonyStyle->success(sprintf(
            '%d créé(s), %d complété(s), %d déjà présent(s).',
            $created,
            $backfilled,
            $existing,
        ));

        return Command::SUCCESS;
    }

    /**
     * Declared default items the menu doesn't already carry.
     *
     * Matched on targetType, which is what identifies a default item. Only
     * protected defaults are ever passed here, so an entry the user deleted on
     * purpose stays deleted — the unprotected ones are seeded once, when the
     * menu is created, and never reinstated.
     *
     * @param list<array{targetType: MenuItemTargetTypeEnum, visibility?: MenuItemVisibilityEnum}> $defaultItems
     *
     * @return list<array{targetType: MenuItemTargetTypeEnum, visibility?: MenuItemVisibilityEnum}>
     */
    private function missingDefaultItems(Menu $menu, array $defaultItems): array
    {
        $present = [];
        foreach ($menu->getItems() as $item) {
            $present[$item->getTargetType()->value] = true;
        }

        return array_values(array_filter(
            $defaultItems,
            static fn (array $itemConfig): bool => !isset($present[$itemConfig['targetType']->value]),
        ));
    }

    /**
     * @param list<array{targetType: MenuItemTargetTypeEnum, visibility?: MenuItemVisibilityEnum, targetSlug?: string}> $itemConfigs
     */
    private function createDefaultItems(Menu $menu, array $itemConfigs): void
    {
        foreach ($itemConfigs as $itemConfig) {
            $targetId = $this->resolveTargetId($itemConfig);

            // A default that names a target it cannot find is skipped rather
            // than created pointing at nothing: an archive entry with a null
            // targetId resolves to no URL and would be dropped from the site
            // anyway, while still occupying a row the user has to clean up.
            if (isset($itemConfig['targetSlug']) && null === $targetId) {
                continue;
            }

            $this->menuManager->createItem($menu, new MenuItemInput(
                targetType: $itemConfig['targetType'],
                targetId: $targetId,
                customUrl: null,
                parentId: null,
                openInNewTab: false,
                cssClass: null,
                visibility: $itemConfig['visibility'] ?? MenuItemVisibilityEnum::Always,
                translations: [],
            ));
        }
    }

    /**
     * Resolves a default item's `targetSlug` to a concrete id.
     *
     * The registry is static configuration and cannot know database ids, so a
     * default pointing at a post type archive names the slug and has it looked
     * up here.
     *
     * @param array{targetType: MenuItemTargetTypeEnum, visibility?: MenuItemVisibilityEnum, targetSlug?: string} $itemConfig
     */
    private function resolveTargetId(array $itemConfig): ?int
    {
        $slug = $itemConfig['targetSlug'] ?? null;

        if (null === $slug || MenuItemTargetTypeEnum::PostTypeArchive !== $itemConfig['targetType']) {
            return null;
        }

        return $this->postTypeRepository->findOneBy(['slug' => $slug])?->getId();
    }
}
