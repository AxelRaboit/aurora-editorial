<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\DataFixtures;

use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Service\PostTextExtractor;
use Aurora\Module\Editorial\PostType\Entity\PostType;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;

use function assert;

/**
 * Built-in editorial post types (page/article) + built-in taxonomies
 * (tag/category). This is bootstrap data the Editorial module needs to
 * function — previously seeded by the core AppFixtures, extracted here so the
 * core stays decoupled from Editorial. Exposes the "article" type via
 * {@see articleTypeRef} for the editorial demo and integration tests.
 *
 * Dev/test only (registered via AbstractAuroraModuleBundle when@dev gating).
 */
class EditorialBootstrapFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly PostTextExtractor $textExtractor,
    ) {}

    public static function articleTypeRef(): string
    {
        return 'editorial_bootstrap_article_type';
    }

    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function load(ObjectManager $manager): void
    {
        assert($manager instanceof EntityManagerInterface);

        // The built-in types and taxonomies are no longer created here: they
        // are structural, so EditorialBootstrapProvider owns them and
        // `aurora:install` creates them in every environment. This fixture
        // only adds the sample content that sits on top, and therefore expects
        // that command to have run first — every Makefile target that loads
        // fixtures does so.
        $pageType = $manager->getRepository(PostType::class)->findOneBy(['slug' => 'page']);
        $articleType = $manager->getRepository(PostType::class)->findOneBy(['slug' => 'article']);

        if (!$pageType instanceof PostType || !$articleType instanceof PostType) {
            throw new RuntimeException('Les types de contenu intégrés sont absents. Lance `php bin/console aurora:install` avant de charger les fixtures.');
        }

        $tagTaxonomy = $manager->getRepository(Taxonomy::class)->findOneBy(['slug' => 'tag']);

        if ($tagTaxonomy instanceof Taxonomy) {
            foreach (['Nouveauté' => 'nouveaute', 'Tutoriel' => 'tutoriel'] as $name => $termSlug) {
                $term = new TaxonomyTerm()->setTaxonomy($tagTaxonomy);
                foreach (['fr', 'en'] as $locale) {
                    $term->translate($locale)->setName($name)->setSlug($termSlug);
                }

                $manager->persist($term);
            }
        }

        // Default home page (a sample Page) — was part of the core AppFixtures.
        $homePage = new Post()->setPostType($pageType)->setStatus(PostStatusEnum::Published);
        $homePageFrench = new PostTranslation()
            ->setPost($homePage)
            ->setLocale(LocaleEnum::French->value)
            ->setTitle('Accueil')
            ->setSlug('accueil')
            ->setBlocks([
                ['type' => 'heading', 'data' => ['text' => 'Bienvenue sur Aurora', 'level' => 1]],
                ['type' => 'paragraph', 'data' => ['text' => 'Votre CMS moderne propulsé par Symfony et Vue 3.']],
            ]);
        $homePageEnglish = new PostTranslation()
            ->setPost($homePage)
            ->setLocale(LocaleEnum::English->value)
            ->setTitle('Home')
            ->setSlug('home')
            ->setBlocks([
                ['type' => 'heading', 'data' => ['text' => 'Welcome to Aurora', 'level' => 1]],
                ['type' => 'paragraph', 'data' => ['text' => 'Your modern CMS powered by Symfony and Vue 3.']],
            ]);
        $homePageFrench->setSearchContent($this->textExtractor->extract($homePageFrench));
        $homePageEnglish->setSearchContent($this->textExtractor->extract($homePageEnglish));

        $manager->persist($homePage);
        $manager->persist($homePageFrench);
        $manager->persist($homePageEnglish);

        $manager->flush();

        $this->addReference(self::articleTypeRef(), $articleType);
    }
}
