<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\EventSubscriber;

use Aurora\Module\Editorial\EditorialContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * 404s every Editorial admin route (`backend_editorial_*`, `backend_editorial_comments_*`,
 * `backend_editorial_forms_*`, `backend_editorial_post_types_*`, `backend_editorial_posts_*`, `backend_editorial_taxonomies_*`,
 * `backend_editorial_sitemap_*`, `backend_editorial_menus_*`) when EditorialEnabled is off.
 */
final readonly class EditorialRouteGateSubscriber implements EventSubscriberInterface
{
    private const array ADMIN_PREFIXES = [
        'backend_editorial_',
        'backend_editorial_comments',
        'backend_editorial_forms',
        'backend_editorial_post_types',
        'backend_editorial_posts',
        'backend_editorial_taxonomies',
        'backend_editorial_sitemap',
        'backend_editorial_menus',
    ];

    public function __construct(private EditorialContext $editorialContext) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 0]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = (string) $event->getRequest()->attributes->get('_route', '');
        if ('' === $route) {
            return;
        }

        foreach (self::ADMIN_PREFIXES as $prefix) {
            if (str_starts_with($route, $prefix)) {
                if (!$this->editorialContext->isBackendEnabled()) {
                    throw new NotFoundHttpException();
                }

                return;
            }
        }
    }
}
