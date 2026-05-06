<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Controller\Front;

use Aurora\Core\Frontend\Controller\FrontLocaleTrait;
use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Core\Frontend\Service\HttpCacheService;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Theme\Service\ThemeResolver;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostSlugHistory;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostSlugHistoryRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Post\Service\PostPageRenderer;
use Aurora\Module\Editorial\Post\View\PageViewBuilder;
use Aurora\Module\Editorial\Taxonomy\Entity\Taxonomy;
use Aurora\Module\Editorial\Taxonomy\Entity\TaxonomyTerm;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    use FrontLocaleTrait;

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly PostTypeRepository $postTypeRepository,
        private readonly PostSlugHistoryRepository $slugHistoryRepository,
        private readonly TaxonomyRepository $taxonomyRepository,
        private readonly FrontContext $frontContext,
        private readonly ThemeResolver $themeResolver,
        private readonly HttpCacheService $httpCache,
        private readonly PostPageRenderer $postPageRenderer,
        private readonly PageViewBuilder $viewBuilder,
    ) {}

    #[Route('/', name: 'frontend_root', priority: 10)]
    public function root(): RedirectResponse
    {
        return $this->redirectToRoute('frontend_home', ['locale' => $this->frontContext->defaultLocale()]);
    }

    #[Route('/{locale}', name: 'frontend_home', requirements: ['locale' => '[a-z]{2}'], priority: 9)]
    public function home(string $locale, Request $request): Response
    {
        $this->assertActiveLocale($this->frontContext, $locale);
        $request->setLocale($locale);

        $homepageId = $this->frontContext->homepagePostId();
        if (null !== $homepageId) {
            $post = $this->postRepository->find($homepageId);
            $homepageTranslation = $post?->getTranslation($locale);
            if (null !== $post && $post->isPublished() && !$post->isTrashed() && $homepageTranslation instanceof PostTranslation) {
                return $this->postPageRenderer->render($post, $locale);
            }
        }

        $postType = $this->postTypeRepository->findOneBy(['slug' => 'article']);
        $result = null !== $postType
            ? $this->postRepository->findPublishedByPostType($postType->getId(), (int) $request->query->get('page', 1), $this->postsPerPage(), $locale)
            : ['items' => [], 'total' => 0, 'page' => 1, 'totalPages' => 1];

        $response = $this->render($this->themeResolver->resolve('home'), $this->viewBuilder->homeView($locale, $result, $postType));

        return $this->withI18nHeaders($response, $locale);
    }

    #[Route('/{locale}/{postTypeSlug}/{slug}', name: 'frontend_post', requirements: ['locale' => '[a-z]{2}'], priority: 5)]
    public function post(string $locale, string $postTypeSlug, string $slug, Request $request): Response
    {
        $this->assertActiveLocale($this->frontContext, $locale);
        $request->setLocale($locale);

        $post = $this->postRepository->findPublishedBySlug($slug, $locale);

        if (!$post instanceof Post) {
            $redirect = $this->tryRedirectFromHistory($locale, $slug);
            if ($redirect instanceof RedirectResponse) {
                return $redirect;
            }

            throw $this->createNotFoundException();
        }

        if ($post->getPostType()->getSlug() !== $postTypeSlug) {
            return $this->redirectToRoute('frontend_post', [
                'locale' => $locale,
                'postTypeSlug' => $post->getPostType()->getSlug(),
                'slug' => $slug,
            ], Response::HTTP_MOVED_PERMANENTLY);
        }

        $lastModified = $post->getUpdatedAt();
        if (($notModified = $this->httpCache->checkNotModified($request, $lastModified)) instanceof Response) {
            return $notModified;
        }

        $response = $this->postPageRenderer->render($post, $locale);
        $this->httpCache->setPublicCache($response, $lastModified);

        return $response;
    }

    #[Route('/{locale}/{postTypeSlug}', name: 'frontend_archive', requirements: ['locale' => '[a-z]{2}'], priority: 3)]
    public function archive(string $locale, string $postTypeSlug, Request $request): Response
    {
        $this->assertActiveLocale($this->frontContext, $locale);
        $request->setLocale($locale);

        $postType = $this->postTypeRepository->findOneBy(['slug' => $postTypeSlug]);
        if (null === $postType || !$postType->hasArchive()) {
            throw $this->createNotFoundException();
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $result = $this->postRepository->findPublishedByPostType($postType->getId(), $page, $this->postsPerPage(), $locale);

        $response = $this->render($this->themeResolver->resolve('archive'), $this->viewBuilder->archiveView($locale, $postType, $result));

        $this->httpCache->setSharedCache($response);

        return $this->withI18nHeaders($response, $locale);
    }

    #[Route('/{locale}/{taxonomySlug}/{termSlug}', name: 'frontend_term', requirements: ['locale' => '[a-z]{2}'], priority: 4)]
    public function term(string $locale, string $taxonomySlug, string $termSlug, Request $request): Response
    {
        $this->assertActiveLocale($this->frontContext, $locale);
        $request->setLocale($locale);

        $taxonomy = $this->taxonomyRepository->findOneBySlug($taxonomySlug);
        if (!$taxonomy instanceof Taxonomy) {
            throw $this->createNotFoundException();
        }

        $term = null;
        foreach ($taxonomy->getTerms() as $candidate) {
            if ($candidate->getTranslation($locale)?->getSlug() === $termSlug) {
                $term = $candidate;
                break;
            }
        }

        if (!$term instanceof TaxonomyTerm) {
            throw $this->createNotFoundException();
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $result = $this->postRepository->findPublishedByTerm($term->getId(), $page, $this->postsPerPage(), $locale);

        $response = $this->render($this->themeResolver->resolve('term'), $this->viewBuilder->termView($locale, $taxonomy, $term, $result));

        $this->httpCache->setSharedCache($response);

        return $this->withI18nHeaders($response, $locale);
    }

    private function tryRedirectFromHistory(string $locale, string $slug): ?RedirectResponse
    {
        $historyEntry = $this->slugHistoryRepository->findOneByLocaleAndSlug($locale, $slug);
        if (!$historyEntry instanceof PostSlugHistory) {
            return null;
        }

        $currentSlug = $historyEntry->getPost()->getTranslation($locale)?->getSlug();
        if (null === $currentSlug || '' === $currentSlug) {
            return null;
        }

        return $this->redirectToRoute('frontend_post', [
            'locale' => $locale,
            'postTypeSlug' => $historyEntry->getPost()->getPostType()->getSlug(),
            'slug' => $currentSlug,
        ], Response::HTTP_MOVED_PERMANENTLY);
    }

    private function postsPerPage(): int
    {
        return (int) ($this->frontContext->setting(ApplicationParameterEnum::PostsPerPage->value, '10') ?? 10);
    }
}
