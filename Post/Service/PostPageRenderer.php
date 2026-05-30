<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Service;

use Aurora\Core\Frontend\Service\Context;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Configuration\Theme\Service\ThemeContext;
use Aurora\Module\Configuration\Theme\Service\ThemeResolver;
use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Entity\PostTranslationInterface;
use Aurora\Module\Editorial\Seo\Service\AlternatesBuilder;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Ged\Document\Service\DocumentUrlGenerator;
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
        private DocumentUrlGenerator $documentUrlGenerator,
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
        if ($featuredMedia instanceof DocumentInterface) {
            $focalPositionCss = $this->documentUrlGenerator->focalPositionCss($featuredMedia);
            $featuredMediaData = [
                'publicUrl' => $this->documentUrlGenerator->publicUrl($featuredMedia),
                'variantLargeUrl' => $this->documentUrlGenerator->variantUrl($featuredMedia, 'large'),
                'url' => $this->documentUrlGenerator->variantUrl($featuredMedia, 'large') ?? $this->documentUrlGenerator->publicUrl($featuredMedia),
                'alt' => $featuredMedia->getAlt(),
                'focalPositionCss' => $focalPositionCss,
                'focalPosition' => $focalPositionCss,
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
        if ($ogImageMedia instanceof DocumentInterface) {
            $ogImageData = [
                'publicUrl' => $this->documentUrlGenerator->publicUrl($ogImageMedia),
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
