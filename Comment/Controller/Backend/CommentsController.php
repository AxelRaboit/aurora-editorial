<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\Validation\Dto\PaginationRequest;
use Aurora\Module\Editorial\Comment\Entity\CommentInterface;
use Aurora\Module\Editorial\Comment\Manager\CommentManagerInterface;
use Aurora\Module\Editorial\Comment\Repository\CommentRepository;
use Aurora\Module\Editorial\Comment\Serializer\CommentSerializerInterface;
use Aurora\Module\Editorial\Comment\View\CommentsViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/backend/comments', name: 'backend_comments')]
#[IsGranted('editorial.comments.view')]
final class CommentsController extends AbstractController
{
    use JsonResponseTrait;

    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly CommentManagerInterface $commentManager,
        private readonly CommentSerializerInterface $commentSerializer,
        private readonly SettingRepository $settingRepository,
        private readonly CommentsViewBuilder $viewBuilder,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        return $this->render('@Editorial/backend/comments/index.html.twig', $this->viewBuilder->indexView());
    }

    #[Route('/toggle-moderation', name: '_toggle_moderation', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.comments.moderate')]
    public function toggleModeration(): JsonResponse
    {
        $current = $this->settingRepository->getBoolean(ApplicationParameterEnum::CommentModerationEnabled->value, true);
        $this->settingRepository->set(ApplicationParameterEnum::CommentModerationEnabled->value, $current ? '0' : '1');

        return $this->jsonSuccess(['moderationEnabled' => !$current]);
    }

    #[Route('/list', name: '_list', methods: [HttpMethodEnum::Get->value])]
    public function list(PaginationRequest $pagination, Request $request): JsonResponse
    {
        $status = mb_trim((string) $request->query->get('status', ''));

        $result = $this->commentRepository->findPaginatedForAdmin($pagination->page, 20, $status ?: null);

        $items = array_map(
            $this->commentSerializer->serialize(...),
            $result['items'],
        );

        return $this->jsonSuccess([
            'items' => $items,
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    #[Route('/{id}/approve', name: '_approve', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.comments.moderate')]
    public function approve(CommentInterface $comment): JsonResponse
    {
        $this->commentManager->approve($comment);

        return $this->jsonSuccess(['comment' => $this->commentSerializer->serialize($comment)]);
    }

    #[Route('/{id}/spam', name: '_spam', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.comments.moderate')]
    public function spam(CommentInterface $comment): JsonResponse
    {
        $this->commentManager->spam($comment);

        return $this->jsonSuccess(['comment' => $this->commentSerializer->serialize($comment)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    #[IsGranted('editorial.comments.delete')]
    public function delete(CommentInterface $comment): JsonResponse
    {
        $this->commentManager->delete($comment);

        return $this->jsonSuccess();
    }
}
