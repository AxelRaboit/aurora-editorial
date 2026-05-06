<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\EventSubscriber;

use Aurora\Module\Editorial\Service\EditorialContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * 404s every Editorial admin route (`backend_editorial_*`, `backend_comments_*`,
 * `backend_forms_*`, `backend_post_types_*`, `backend_posts_*`, `backend_taxonomies_*`,
 * `backend_sitemap_*`, `backend_menus_*`) when EditorialAdminEnabled is off.
 */
final readonly class EditorialRouteGateSubscriber implements EventSubscriberInterface
{
    private const array ADMIN_PREFIXES = [
        'backend_editorial_',
        'backend_comments',
        'backend_forms',
        'backend_post_types',
        'backend_posts',
        'backend_taxonomies',
        'backend_sitemap',
        'backend_menus',
    ];

    public function __construct(private EditorialContext $editorialContext) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 16]];
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
                if (!$this->editorialContext->isAdminEnabled()) {
                    throw new NotFoundHttpException();
                }

                return;
            }
        }
    }
}
