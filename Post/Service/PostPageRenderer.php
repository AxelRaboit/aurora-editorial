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

        $body = $this->twig->render($this->themeResolver->resolve('post'), [
            'locale' => $locale,
            'context' => $this->context,
            'showFrontMenus' => true,
            'themeContext' => $this->themeContext,
            'post' => $post,
            'translation' => $translation,
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
