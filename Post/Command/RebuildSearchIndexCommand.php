<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Command;

use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Repository\PostTranslationRepository;
use Aurora\Module\Editorial\Post\Service\PostTextExtractor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:posts:rebuild-search-index',
    description: 'Rebuild the search_content column for every post translation.',
)]
final class RebuildSearchIndexCommand extends Command
{
    private const int BATCH_SIZE = 50;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PostTranslationRepository $translationRepository,
        private readonly PostTextExtractor $textExtractor,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $total = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(PostTranslation::class, 't')
            ->getQuery()
            ->getSingleScalarResult();

        if (0 === $total) {
            $io->success('Nothing to index.');

            return Command::SUCCESS;
        }

        $io->text(sprintf('Indexing %d post translation(s)…', $total));
        $io->progressStart($total);

        $processed = 0;
        foreach ($this->translationRepository->findAll() as $translation) {
            $translation->setSearchContent($this->textExtractor->extract($translation));
            ++$processed;

            if (0 === $processed % self::BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();
        $io->success(sprintf('Re-indexed %d translation(s).', $processed));

        return Command::SUCCESS;
    }
}
