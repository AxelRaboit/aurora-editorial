<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Editorial\Post\Contract\PostManagerInterface;
use Aurora\Module\Editorial\Post\DTO\PostInput;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostRevision;
use Aurora\Module\Editorial\Post\Entity\PostTranslation;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostRevisionRepository;
use Aurora\Module\Editorial\Post\Security\PostVoter;
use Aurora\Module\Editorial\Post\Serializer\PostRevisionSerializer;
use Aurora\Module\Editorial\Post\Serializer\PostSerializer;
use Aurora\Module\Editorial\Post\Service\PostPageRenderer;
use Aurora\Module\Editorial\Post\View\PostsViewBuilder;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/posts', name: 'backend_posts')]
#[IsGranted('editorial.posts.view')]
class PostsController extends AbstractController
{
    use JsonRequestTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly PostManagerInterface $postManager,
        private readonly PostSerializer $postSerializer,
        private readonly PostRevisionRepository $revisionRepository,
        private readonly PostRevisionSerializer $revisionSerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly EntityManagerInterface $entityManager,
        private readonly PostPageRenderer $postPageRenderer,
        private readonly PostsViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination, Request $request): Response
    {
        $payload = $this->buildListPayload($pagination, $request);

        if ('XMLHttpRequest' === $request->headers->get('X-Requested-With')) {
            return $this->json($payload);
        }

        return $this->render('@Editorial/backend/posts/index.html.twig', $this->viewBuilder->indexView($payload, $pagination, $request->query->getBoolean('trashed')));
    }

    #[Route('/search', name: '_search', methods: [HttpMethodEnum::Get->value])]
    public function search(Request $request): JsonResponse
    {
        $idsParam = (string) $request->query->get('ids', '');
        if ('' !== $idsParam) {
            $ids = array_values(array_filter(array_map(intval(...), explode(',', $idsParam)), static fn (int $id): bool => $id > 0));
            $results = $this->postRepository->findByIds($ids);
        } else {
            $query = mb_trim((string) $request->query->get('q', ''));
            $excludeId = $request->query->getInt('excludeId') ?: null;
            $postTypeId = $request->query->getInt('postTypeId') ?: null;
            $results = $this->postRepository->searchForReference($query, $excludeId, $postTypeId);
        }

        return $this->jsonSuccess([
            'results' => array_map($this->postSerializer->serializeReference(...), $results),
        ]);
    }

    #[Route('/{id}/preview/{locale}', name: '_preview', methods: [HttpMethodEnum::Get->value])]
    public function preview(Post $post, string $locale, Request $request): Response
    {
        if (!$post->getTranslation($locale) instanceof PostTranslation) {
            throw $this->createNotFoundException();
        }

        $request->setLocale($locale);

        return $this->postPageRenderer->render($post, $locale);
    }

    #[Route('/{id}', name: '_show', methods: [HttpMethodEnum::Get->value])]
    public function show(Post $post): JsonResponse
    {
        return $this->jsonSuccess(['post' => $this->postSerializer->serializeFull($post)]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = $this->demoteIfNotPublishable(PostInput::fromArray($this->decodeJson($request)));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $post = $this->postManager->create($input);

        return $this->jsonSuccess(['post' => $this->postSerializer->serialize($post)]);
    }

    #[Route('/{id}/edit', name: '_edit', methods: [HttpMethodEnum::Post->value])]
    public function edit(Post $post, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(PostVoter::EDIT, $post);

        $input = $this->demoteIfNotPublishable(PostInput::fromArray($this->decodeJson($request)), $post);

        if (!$input->force && null !== $input->version) {
            try {
                $this->entityManager->lock($post, LockMode::OPTIMISTIC, $input->version);
            } catch (OptimisticLockException) {
                return $this->json(['success' => false, 'conflict' => true], Response::HTTP_CONFLICT);
            }
        }

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->jsonInvalidInput($errors);
        }

        $this->postManager->update($post, $input);

        return $this->jsonSuccess(['post' => $this->postSerializer->serialize($post)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Post $post): JsonResponse
    {
        $this->denyAccessUnlessGranted(PostVoter::DELETE, $post);

        $this->postManager->delete($post);

        return $this->jsonSuccess();
    }

    #[Route('/{id}/restore', name: '_restore', methods: [HttpMethodEnum::Post->value])]
    public function restore(Post $post): JsonResponse
    {
        $this->postManager->restore($post);

        return $this->jsonSuccess(['post' => $this->postSerializer->serialize($post)]);
    }

    #[Route('/{id}/force-delete', name: '_force_delete', methods: [HttpMethodEnum::Post->value])]
    public function forceDelete(Post $post): JsonResponse
    {
        $this->postManager->forceDelete($post);

        return $this->jsonSuccess();
    }

    #[Route('/empty-trash', name: '_empty_trash', methods: [HttpMethodEnum::Post->value])]
    public function emptyTrash(): JsonResponse
    {
        $posts = $this->postRepository->findAllTrashed();
        foreach ($posts as $post) {
            $this->postManager->forceDelete($post);
        }

        return $this->jsonSuccess(['count' => count($posts)]);
    }

    #[Route('/{id}/revisions', name: '_revisions', methods: [HttpMethodEnum::Get->value])]
    public function listRevisions(Post $post): JsonResponse
    {
        return $this->jsonSuccess([
            'revisions' => array_map($this->revisionSerializer->serialize(...), $this->revisionRepository->findByPost($post)),
        ]);
    }

    #[Route('/{id}/revisions/{revisionId}', name: '_revision_show', methods: [HttpMethodEnum::Get->value])]
    public function showRevision(Post $post, int $revisionId): JsonResponse
    {
        $revision = $this->revisionRepository->find($revisionId);
        if (!$revision instanceof PostRevision || $revision->getPost() !== $post) {
            return $this->json(['success' => false], Response::HTTP_NOT_FOUND);
        }

        return $this->jsonSuccess(['revision' => $this->revisionSerializer->serializeFull($revision)]);
    }

    #[Route('/{id}/revisions/{revisionId}/restore', name: '_revision_restore', methods: [HttpMethodEnum::Post->value])]
    public function restoreRevision(Post $post, int $revisionId): JsonResponse
    {
        $revision = $this->revisionRepository->find($revisionId);
        if (!$revision instanceof PostRevision || $revision->getPost() !== $post) {
            return $this->json(['success' => false], Response::HTTP_NOT_FOUND);
        }

        $this->postManager->restoreRevision($post, $revision);

        return $this->jsonSuccess(['post' => $this->postSerializer->serialize($post)]);
    }

    /** @return array{success: bool, items: list<array<string, mixed>>, total: int, page: int, totalPages: int} */
    private function buildListPayload(PaginationRequest $pagination, Request $request): array
    {
        $postTypeId = $request->query->getInt('postTypeId') ?: null;
        $trashed = $request->query->getBoolean('trashed');
        $authorId = $this->scopedAuthorId();

        $result = $this->postRepository->findPaginated($pagination->page, 10, $pagination->search, $postTypeId, trashed: $trashed, authorId: $authorId);

        return [
            'success' => true,
            'items' => array_map($this->postSerializer->serialize(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ];
    }

    /**
     * Authors can only see their own posts in the list. Editor+ see everything.
     * Returns null when no scoping is needed.
     */
    private function scopedAuthorId(): ?int
    {
        // Dev and Admin see all posts; User with manage privilege sees only own
        if ($this->isGranted(UserRoleEnum::Dev->value) || $this->isGranted(UserRoleEnum::Admin->value)) {
            return null;
        }

        $currentUser = $this->getUser();

        return $currentUser instanceof User ? $currentUser->getId() : null;
    }

    /**
     * If the caller cannot publish (creating, or editing a specific post they can't publish),
     * downgrade Published → PendingReview so the change still goes through but waits for moderation.
     */
    private function demoteIfNotPublishable(PostInput $input, ?Post $post = null): PostInput
    {
        if (PostStatusEnum::Published->value !== $input->status) {
            return $input;
        }

        $allowed = $post instanceof Post
            ? $this->isGranted(PostVoter::PUBLISH, $post)
            : ($this->isGranted(UserRoleEnum::Admin->value) || $this->isGranted(UserRoleEnum::Dev->value));

        return $allowed ? $input : $input->withStatus(PostStatusEnum::PendingReview->value);
    }
}
