<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Menu\Command;

use Aurora\Module\Editorial\Menu\Dto\MenuInput;
use Aurora\Module\Editorial\Menu\Dto\MenuItemInput;
use Aurora\Module\Editorial\Menu\Entity\Menu;
use Aurora\Module\Editorial\Menu\Enum\MenuItemVisibilityEnum;
use Aurora\Module\Editorial\Menu\Manager\MenuManagerInterface;
use Aurora\Module\Editorial\Menu\Repository\MenuRepository;
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

        foreach ($this->registry->all() as $location => $meta) {
            if ($this->menuRepository->findByLocation($location) instanceof Menu) {
                $symfonyStyle->writeln(sprintf('  <comment>=</comment> %s (déjà présent)', $location));
                ++$existing;

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
                foreach ($meta['defaultItems'] as $itemConfig) {
                    $this->menuManager->createItem($menu, new MenuItemInput(
                        targetType: $itemConfig['targetType'],
                        targetId: null,
                        customUrl: null,
                        parentId: null,
                        openInNewTab: false,
                        cssClass: null,
                        visibility: $itemConfig['visibility'] ?? MenuItemVisibilityEnum::Always,
                        translations: [],
                    ));
                }
            }
        }

        $symfonyStyle->success(sprintf('%d créé(s), %d déjà présent(s).', $created, $existing));

        return Command::SUCCESS;
    }
}
