<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Controller\Admin;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\Validation\DTO\PaginationRequest;
use Aurora\Module\Editorial\Comment\Contract\CommentManagerInterface;
use Aurora\Module\Editorial\Comment\Entity\Comment;
use Aurora\Module\Editorial\Comment\Repository\CommentRepository;
use Aurora\Module\Editorial\Comment\Serializer\CommentSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/comments', name: 'admin_comments')]
#[IsGranted('editorial.comments.manage')]
final class CommentsController extends AbstractController
{
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly CommentManagerInterface $commentManager,
        private readonly CommentSerializer $commentSerializer,
        private readonly SettingRepository $settingRepository,
    ) {}

    #[Route('', name: '', methods: [HttpMethodEnum::Get->value])]
    public function index(): Response
    {
        $stats = $this->commentRepository->countByStatus();
        $moderationEnabled = $this->settingRepository->getBoolean(ApplicationParameterEnum::CommentModerationEnabled->value, true);

        return $this->render('@Editorial/admin/comments/index.html.twig', [
            'stats' => $stats,
            'moderationEnabled' => $moderationEnabled,
        ]);
    }

    #[Route('/toggle-moderation', name: '_toggle_moderation', methods: [HttpMethodEnum::Post->value])]
    public function toggleModeration(): JsonResponse
    {
        $current = $this->settingRepository->getBoolean(ApplicationParameterEnum::CommentModerationEnabled->value, true);
        $this->settingRepository->set(ApplicationParameterEnum::CommentModerationEnabled->value, $current ? '0' : '1');

        return $this->json(['ok' => true, 'moderationEnabled' => !$current]);
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

        return $this->json([
            'ok' => true,
            'items' => $items,
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    #[Route('/{id}/approve', name: '_approve', methods: [HttpMethodEnum::Post->value])]
    public function approve(Comment $comment): JsonResponse
    {
        $this->commentManager->approve($comment);

        return $this->json(['ok' => true, 'comment' => $this->commentSerializer->serialize($comment)]);
    }

    #[Route('/{id}/spam', name: '_spam', methods: [HttpMethodEnum::Post->value])]
    public function spam(Comment $comment): JsonResponse
    {
        $this->commentManager->spam($comment);

        return $this->json(['ok' => true, 'comment' => $this->commentSerializer->serialize($comment)]);
    }

    #[Route('/{id}/delete', name: '_delete', methods: [HttpMethodEnum::Post->value])]
    public function delete(Comment $comment): JsonResponse
    {
        $this->commentManager->delete($comment);

        return $this->json(['ok' => true]);
    }
}
