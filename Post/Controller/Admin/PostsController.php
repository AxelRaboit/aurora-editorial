<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonRequestTrait;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Core\Validation\Service\PayloadValidator;
use Aurora\Module\Editorial\Post\Contract\PostManagerInterface;
use Aurora\Module\Editorial\Post\DTO\PostInput;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Entity\PostRevision;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostRevisionRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Post\Security\PostVoter;
use Aurora\Module\Editorial\Post\Serializer\PostRevisionSerializer;
use Aurora\Module\Editorial\Post\Serializer\PostSerializer;
use Aurora\Module\Editorial\Post\Serializer\PostTypeSerializer;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Serializer\TaxonomySerializer;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/posts', name: 'admin_posts')]
#[IsGranted('editorial.posts.view')]
class PostsController extends AbstractController
{
    use JsonRequestTrait;

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly PostTypeRepository $postTypeRepository,
        private readonly TaxonomyRepository $taxonomyRepository,
        private readonly PostManagerInterface $postManager,
        private readonly PostSerializer $postSerializer,
        private readonly PostTypeSerializer $postTypeSerializer,
        private readonly TaxonomySerializer $taxonomySerializer,
        private readonly PostRevisionRepository $revisionRepository,
        private readonly PostRevisionSerializer $revisionSerializer,
        private readonly PayloadValidator $payloadValidator,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(PaginationRequest $pagination, Request $request): Response
    {
        $payload = $this->buildListPayload($pagination, $request);

        if ('XMLHttpRequest' === $request->headers->get('X-Requested-With')) {
            return $this->json($payload);
        }

        return $this->render('@Editorial/admin/posts/index.html.twig', [
            'posts' => $payload,
            'search' => $pagination->search ?? '',
            'postTypes' => array_map($this->postTypeSerializer->serialize(...), $this->postTypeRepository->findAll()),
            'taxonomies' => array_map($this->taxonomySerializer->serializeFull(...), $this->taxonomyRepository->findBy([], ['slug' => 'ASC'])),
            'trashed' => $request->query->getBoolean('trashed'),
            'locales' => $this->getParameter('kernel.enabled_locales'),
        ]);
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

        return $this->json([
            'success' => true,
            'results' => array_map($this->postSerializer->serializeReference(...), $results),
        ]);
    }

    #[Route('/{id}', name: '_show', methods: [HttpMethodEnum::Get->value])]
    public function show(Post $post): JsonResponse
    {
        return $this->json(['success' => true, 'post' => $this->postSerializer->serializeFull($post)]);
    }

    #[Route('', name: '_create', methods: [HttpMethodEnum::Post->value])]
    public function create(Request $request): JsonResponse
    {
        $input = $this->demoteIfNotPublishable(PostInput::fromArray($this->decodeJson($request)));

        $errors = $this->payloadValidator->errors($input);
        if ([] !== $errors) {
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        $post = $this->postManager->create($input);

        return $this->json(['success' => true, 'post' => $this->postSerializer->serialize($post)]);
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
            return $this->json(['success' => false, 'errors' => $errors]);
        }

        $this->postManager->update($post, $input);

        return $this->json(['success' => true, 'post' => $this->postSerializer->serialize($post)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Post $post): JsonResponse
    {
        $this->denyAccessUnlessGranted(PostVoter::DELETE, $post);

        $this->postManager->delete($post);

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/restore', name: '_restore', methods: [HttpMethodEnum::Post->value])]
    public function restore(Post $post): JsonResponse
    {
        $this->postManager->restore($post);

        return $this->json(['success' => true, 'post' => $this->postSerializer->serialize($post)]);
    }

    #[Route('/{id}/force-delete', name: '_force_delete', methods: [HttpMethodEnum::Post->value])]
    public function forceDelete(Post $post): JsonResponse
    {
        $this->postManager->forceDelete($post);

        return $this->json(['success' => true]);
    }

    #[Route('/empty-trash', name: '_empty_trash', methods: [HttpMethodEnum::Post->value])]
    public function emptyTrash(): JsonResponse
    {
        $posts = $this->postRepository->findAllTrashed();
        foreach ($posts as $post) {
            $this->postManager->forceDelete($post);
        }

        return $this->json(['success' => true, 'count' => count($posts)]);
    }

    #[Route('/{id}/revisions', name: '_revisions', methods: [HttpMethodEnum::Get->value])]
    public function listRevisions(Post $post): JsonResponse
    {
        return $this->json([
            'success' => true,
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

        return $this->json(['success' => true, 'revision' => $this->revisionSerializer->serializeFull($revision)]);
    }

    #[Route('/{id}/revisions/{revisionId}/restore', name: '_revision_restore', methods: [HttpMethodEnum::Post->value])]
    public function restoreRevision(Post $post, int $revisionId): JsonResponse
    {
        $revision = $this->revisionRepository->find($revisionId);
        if (!$revision instanceof PostRevision || $revision->getPost() !== $post) {
            return $this->json(['success' => false], Response::HTTP_NOT_FOUND);
        }

        $this->postManager->restoreRevision($post, $revision);

        return $this->json(['success' => true, 'post' => $this->postSerializer->serialize($post)]);
    }

    /** @return array{ok: bool, items: list<array<string, mixed>>, total: int, page: int, totalPages: int} */
    private function buildListPayload(PaginationRequest $pagination, Request $request): array
    {
        $postTypeId = $request->query->getInt('postTypeId') ?: null;
        $trashed = $request->query->getBoolean('trashed');
        $authorId = $this->scopedAuthorId();

        $result = $this->postRepository->findPaginated($pagination->page, 10, $pagination->search, $postTypeId, trashed: $trashed, authorId: $authorId);

        return [
            'ok' => true,
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
        if ($this->isGranted(UserRoleEnum::Editor->value)) {
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
            : $this->isGranted(UserRoleEnum::Author->value);

        return $allowed ? $input : $input->withStatus(PostStatusEnum::PendingReview->value);
    }
}
