<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\DataFixtures;

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
 * Sample editorial content for local work.
 *
 * The built-in post types, the built-in taxonomies and the landing page moved
 * to EditorialBootstrapProvider: they are what the module needs to function,
 * and fixtures never run in production. What is left here illustrates the
 * product — sample tag terms — and expects `aurora:install` to have run.
 *
 * Historical note, kept because the name still says "Bootstrap": this class
 * used to declare itself "bootstrap data the Editorial module needs to
 * function" and "Dev/test only" three lines apart. Both were true, which is
 * exactly why a production install came up unusable.
 * (tag/category). This is bootstrap data the Editorial module needs to
 * function — previously seeded by the core AppFixtures, extracted here so the
 * core stays decoupled from Editorial. Exposes the "article" type via
 * {@see articleTypeRef} for the editorial demo and integration tests.
 *
 * Dev/test only (registered via AbstractAuroraModuleBundle when@dev gating).
 */
class EditorialBootstrapFixtures extends Fixture implements FixtureGroupInterface
{
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

        $manager->flush();

        $this->addReference(self::articleTypeRef(), $articleType);
    }
}
