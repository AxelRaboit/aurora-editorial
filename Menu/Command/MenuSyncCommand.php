<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Command;

use Aurora\Module\Editorial\Menu\Dto\MenuInput;
use Aurora\Module\Editorial\Menu\Entity\Menu;
use Aurora\Module\Editorial\Menu\Manager\MenuManagerInterface;
use Aurora\Module\Editorial\Menu\Repository\MenuRepository;
use Aurora\Module\Editorial\Menu\Service\MenuDefaultsSeeder;
use Aurora\Module\Editorial\Menu\Service\MenuLocationRegistry;
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
        private readonly MenuDefaultsSeeder $seeder,
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
                $missing = $this->seeder->missingFor($menu, protectedOnly: true);

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
                    $this->seeder->seed($menu, $missing);
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
                $this->seeder->seed($menu, $this->seeder->missingFor($menu));
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
}
