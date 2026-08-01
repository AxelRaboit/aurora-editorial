<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Bootstrap;

use Aurora\Core\Bootstrap\BootstrapProviderInterface;
use Aurora\Module\Configuration\Setting\Entity\Setting;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Service\PostTextExtractor;
use Aurora\Module\Editorial\PostType\Entity\PostType;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Doctrine\ORM\EntityManagerInterface;

/**
 * The post types and taxonomies Editorial cannot work without.
 *
 * These lived in the Editorial bootstrap fixture, whose own docblock called them
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

    private const array HOME_PAGE = [
        'fr' => ['title' => 'Accueil', 'slug' => 'accueil', 'heading' => 'Bienvenue sur Aurora', 'paragraph' => 'Votre CMS moderne propulsé par Symfony et Vue 3.'],
        'en' => ['title' => 'Home', 'slug' => 'home', 'heading' => 'Welcome to Aurora', 'paragraph' => 'Your modern CMS powered by Symfony and Vue 3.'],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private PostTextExtractor $textExtractor,
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

        yield from $this->seedHomePage();

        $this->entityManager->flush();
    }

    /**
     * A landing page, and the setting that points the site at it.
     *
     * Without this a fresh install answers `/` with the article listing —
     * Aurora's fallback when no homepage is designated — so a brand new site
     * greets its first visitor with an empty "Articles" screen and a search
     * box. WordPress ships a sample page for the same reason.
     *
     * Deliberately plain: it exists so the site has somewhere to land, and is
     * meant to be rewritten. It carries no demo styling that an administrator
     * would have to undo.
     *
     * @return iterable<string>
     */
    private function seedHomePage(): iterable
    {
        $pageType = $this->entityManager->getRepository(PostType::class)->findOneBy(['slug' => 'page']);
        $settingRepository = $this->entityManager->getRepository(Setting::class);
        $setting = $settingRepository->findOneBy(['key' => ApplicationParameterEnum::HomepagePostId->value]);

        // An administrator who has chosen a homepage keeps it, and one who
        // deliberately cleared the setting to get the article listing back is
        // not overruled on the next deploy. Only an untouched install is seeded.
        if (!$pageType instanceof PostType || (null !== $setting && '' !== (string) $setting->getValue())) {
            return;
        }

        if (null !== $this->entityManager->getRepository(Post::class)->findOneBy(['postType' => $pageType])) {
            return;
        }

        $page = new Post()->setPostType($pageType)->setStatus(PostStatusEnum::Published);
        $this->entityManager->persist($page);

        foreach (self::HOME_PAGE as $locale => $content) {
            $translation = new PostTranslation()
                ->setPost($page)
                ->setLocale($locale)
                ->setTitle($content['title'])
                ->setSlug($content['slug'])
                ->setBlocks([
                    ['type' => 'heading', 'data' => ['text' => $content['heading'], 'level' => 1]],
                    ['type' => 'paragraph', 'data' => ['text' => $content['paragraph']]],
                ]);
            $translation->setSearchContent($this->textExtractor->extract($translation));
            $this->entityManager->persist($translation);
        }

        $this->entityManager->flush();

        if (!$setting instanceof Setting) {
            $setting = new Setting()
                ->setKey(ApplicationParameterEnum::HomepagePostId->value)
                ->setType(ApplicationParameterEnum::HomepagePostId->getType())
                ->setGroup(ApplicationParameterEnum::HomepagePostId->getGroup());
            $this->entityManager->persist($setting);
        }

        $setting->setValue((string) $page->getId());

        yield "page d'accueil";
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
