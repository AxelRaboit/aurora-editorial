<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Bootstrap;

use Aurora\Core\Bootstrap\BootstrapProviderInterface;
use Aurora\Module\Editorial\PostType\Entity\PostType;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Doctrine\ORM\EntityManagerInterface;

/**
 * The post types and taxonomies Editorial cannot work without.
 *
 * These lived in EditorialBootstrapFixtures, whose own docblock called them
 * "bootstrap data the Editorial module needs to function" and, two lines later,
 * "Dev/test only". Both statements were true, which is the problem: a
 * production install came up with no post type, so no content could be created
 * at all — the backend offered nothing to write into.
 *
 * Only the structure is here. The sample terms and the demo home page that
 * shared that fixture stay fixtures: they illustrate the product rather than
 * hold it up.
 */
final readonly class EditorialBootstrapProvider implements BootstrapProviderInterface
{
    /**
     * Kept verbatim from the fixture so existing installs recognise their own
     * rows rather than gaining a second, subtly different "Articles".
     */
    private const array POST_TYPES = [
        'page' => ['label' => 'Pages', 'icon' => 'file', 'hasArchive' => false],
        'article' => ['label' => 'Articles', 'icon' => 'file-text', 'hasArchive' => true],
    ];

    private const array TAXONOMIES = [
        'tag' => ['hierarchical' => false, 'labels' => ['fr' => 'Étiquette', 'en' => 'Tag']],
        'category' => ['hierarchical' => true, 'labels' => ['fr' => 'Catégorie', 'en' => 'Category']],
    ];

    private const array SUPPORTS = ['blocks', 'thumbnail', 'excerpt'];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function getPriority(): int
    {
        // After the core: taxonomies carry translations, and reading in a
        // locale the install has not declared yet would be surprising.
        return 50;
    }

    public function bootstrap(): iterable
    {
        $postTypes = [];
        foreach (self::POST_TYPES as $slug => $config) {
            [$postType, $created] = $this->postType($slug, $config);
            $postTypes[] = $postType;

            if ($created) {
                yield sprintf('type de contenu %s', $slug);
            }
        }

        foreach (self::TAXONOMIES as $slug => $config) {
            if (null !== $this->entityManager->getRepository(Taxonomy::class)->findOneBy(['slug' => $slug])) {
                continue;
            }

            $taxonomy = new Taxonomy()
                ->setSlug($slug)
                ->setHierarchical($config['hierarchical'])
                ->setIsBuiltIn(true);

            foreach ($config['labels'] as $locale => $label) {
                $taxonomy->translate($locale)->setLabel($label);
            }

            // Attached to both built-in types, as the fixture did. Only reached
            // when the taxonomy is new, so a deliberate detachment is not undone.
            foreach ($postTypes as $postType) {
                $postType->addTaxonomy($taxonomy);
            }

            $this->entityManager->persist($taxonomy);

            yield sprintf('taxonomie %s', $slug);
        }

        $this->entityManager->flush();
    }

    /**
     * @param array{label: string, icon: string, hasArchive: bool} $config
     *
     * @return array{0: PostType, 1: bool} the type, and whether it was created
     */
    private function postType(string $slug, array $config): array
    {
        $existing = $this->entityManager->getRepository(PostType::class)->findOneBy(['slug' => $slug]);

        if ($existing instanceof PostType) {
            return [$existing, false];
        }

        $postType = new PostType()
            ->setSlug($slug)
            ->setLabel($config['label'])
            ->setIcon($config['icon'])
            ->setHasArchive($config['hasArchive'])
            ->setIsBuiltIn(true)
            ->setSupports(self::SUPPORTS);

        $this->entityManager->persist($postType);

        return [$postType, true];
    }
}
