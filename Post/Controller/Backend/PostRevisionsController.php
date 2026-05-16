<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostRevision;
use Aurora\Module\Editorial\Post\Manager\PostManagerInterface;
use Aurora\Module\Editorial\Post\Repository\PostRevisionRepository;
use Aurora\Module\Editorial\Post\Serializer\PostRevisionSerializer;
use Aurora\Module\Editorial\Post\Serializer\PostSerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Post revisions sub-domain — list revisions, view a revision, restore a
 * revision into the live post. Split from `PostsController`. Route names
 * preserved (`backend_posts_revisions`, `_revision_show`, `_revision_restore`).
 */
#[Route('/backend/posts', name: 'backend_posts')]
#[IsGranted('editorial.posts.view')]
final class PostRevisionsController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly PostRevisionRepository $revisionRepository,
        private readonly PostRevisionSerializer $revisionSerializer,
        private readonly PostManagerInterface $postManager,
        private readonly PostSerializerInterface $postSerializer,
    ) {}

    #[Route('/{id}/revisions', name: '_revisions', requirements: ['id' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
    public function list(Post $post): JsonResponse
    {
        return $this->jsonSuccess([
            'revisions' => array_map($this->revisionSerializer->serialize(...), $this->revisionRepository->findByPost($post)),
        ]);
    }

    #[Route('/{id}/revisions/{revisionId}', name: '_revision_show', requirements: ['id' => '\d+|__id__', 'revisionId' => '\d+|__id__'], methods: [HttpMethodEnum::Get->value])]
    public function show(Post $post, int $revisionId): JsonResponse
    {
        $revision = $this->revisionRepository->find($revisionId);
        if (!$revision instanceof PostRevision || $revision->getPost() !== $post) {
            return $this->jsonNotFound();
        }

        return $this->jsonSuccess(['revision' => $this->revisionSerializer->serializeFull($revision)]);
    }

    #[Route('/{id}/revisions/{revisionId}/restore', name: '_revision_restore', requirements: ['id' => '\d+|__id__', 'revisionId' => '\d+|__id__'], methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.posts.edit')]
    public function restore(Post $post, int $revisionId): JsonResponse
    {
        $revision = $this->revisionRepository->find($revisionId);
        if (!$revision instanceof PostRevision || $revision->getPost() !== $post) {
            return $this->jsonNotFound();
        }

        $this->postManager->restoreRevision($post, $revision);

        return $this->jsonSuccess(['post' => $this->postSerializer->serialize($post)]);
    }
}
