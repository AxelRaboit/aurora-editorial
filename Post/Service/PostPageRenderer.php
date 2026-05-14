<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Service;

use Aurora\Core\Frontend\Service\Context;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\Theme\Service\ThemeContext;
use Aurora\Core\Theme\Service\ThemeResolver;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Entity\PostTranslationInterface;
use Aurora\Module\Editorial\Seo\Service\AlternatesBuilder;
use DateTimeInterface;
use LogicException;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Renders the public post page. Shared by PageController (full post view)
 * and CommentController (comment-form re-render on validation errors).
 */
final readonly class PostPageRenderer
{
    public function __construct(
        private Environment $twig,
        private ThemeResolver $themeResolver,
        private Context $context,
        private ThemeContext $themeContext,
        private BlocksRenderer $blocksRenderer,
        private AlternatesBuilder $alternatesBuilder,
        private SettingRepository $settingRepository,
    ) {}

    /**
     * @param array<string, string> $commentErrors
     */
    public function render(PostInterface $post, string $locale, array $commentErrors = []): Response
    {
        $translation = $post->getTranslation($locale);
        if (!$translation instanceof PostTranslationInterface) {
            throw new LogicException(sprintf('Post #%d has no translation for locale "%s".', $post->getId(), $locale));
        }

        $commentsEnabled = $this->settingRepository->getBoolean('comments_enabled') && $post->isCommentsEnabled();
        $featuredMedia = $post->getFeaturedMedia();

        $featuredMediaData = null;
        if (null !== $featuredMedia) {
            $featuredMediaData = [
                'publicUrl' => $featuredMedia->getPublicUrl(),
                'variantLargeUrl' => $featuredMedia->getVariantUrl('large'),
                'url' => $featuredMedia->getVariantUrl('large') ?? $featuredMedia->getPublicUrl(),
                'alt' => $featuredMedia->getAlt(),
                'focalPositionCss' => $featuredMedia->getFocalPositionCss(),
                'focalPosition' => $featuredMedia->getFocalPositionCss(),
            ];
        }

        $postData = [
            'id' => $post->getId(),
            'publishedAt' => $post->getPublishedAt()?->format(DateTimeInterface::ATOM),
            'postType' => [
                'slug' => $post->getPostType()->getSlug(),
            ],
            'postTypeSlug' => $post->getPostType()->getSlug(),
        ];

        $ogImageMedia = $translation->getOgImage() ?? $featuredMedia;
        $ogImageData = null;
        if (null !== $ogImageMedia) {
            $ogImageData = [
                'publicUrl' => $ogImageMedia->getPublicUrl(),
            ];
        }

        $translationData = [
            'title' => $translation->getTitle(),
            'slug' => $translation->getSlug(),
            'metaTitle' => $translation->getMetaTitle(),
            'metaDescription' => $translation->getMetaDescription(),
            'canonicalUrl' => $translation->getCanonicalUrl(),
            'noindex' => $translation->isNoindex(),
            'ogImage' => $ogImageData,
            'jsonLd' => $translation->getJsonLd(),
        ];

        $body = $this->twig->render($this->themeResolver->resolve('editorial/post/index'), [
            'locale' => $locale,
            'context' => $this->context,
            'showFrontMenus' => true,
            'themeContext' => $this->themeContext,
            'postData' => $postData,
            'translationData' => $translationData,
            'featuredMediaData' => $featuredMediaData,
            'content' => $this->blocksRenderer->render($translation->getBlocks(), $locale),
            'alternates' => $this->alternatesBuilder->forPost($post),
            'commentsEnabled' => $commentsEnabled,
            'commentErrors' => $commentErrors,
        ]);

        $response = new Response($body);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
