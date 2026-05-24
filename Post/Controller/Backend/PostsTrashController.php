<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Http\JsonResponseTrait;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Manager\PostManagerInterface;
use Aurora\Module\Editorial\Post\Serializer\PostSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Post trash sub-domain — restore / force-delete / empty-trash. Split
 * from `PostsController`. Route names preserved (`backend_posts_restore`,
 * `_force_delete`, `_empty_trash`).
 */
#[Route('/backend/posts', name: 'backend_posts')]
#[IsGranted('editorial.posts.view')]
final class PostsTrashController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly PostManagerInterface $postManager,
        private readonly PostSerializerInterface $postSerializer,
    ) {}

    #[Route('/{id}/restore', name: '_restore', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.posts.delete')]
    public function restore(Post $post): JsonResponse
    {
        $this->postManager->restore($post);

        return $this->jsonSuccess(['post' => $this->postSerializer->serialize($post)]);
    }

    #[Route('/{id}/force-delete', name: '_force_delete', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.posts.delete')]
    public function forceDelete(Post $post): JsonResponse
    {
        $this->postManager->forceDelete($post);

        return $this->jsonSuccess();
    }

    #[Route('/empty-trash', name: '_empty_trash', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.posts.delete')]
    public function emptyTrash(): JsonResponse
    {
        $count = $this->postManager->emptyTrash();

        return $this->jsonSuccess(['count' => $count]);
    }
}
