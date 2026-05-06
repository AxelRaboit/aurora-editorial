<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Comment\Controller\Front;

use Aurora\Core\Frontend\Controller\FrontLocaleTrait;
use Aurora\Core\Frontend\Controller\JsonResponseTrait;
use Aurora\Core\Frontend\Service\FrontContext;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Editorial\Comment\Contract\CommentManagerInterface;
use Aurora\Module\Editorial\Comment\Entity\Comment;
use Aurora\Module\Editorial\Comment\Enum\ReactionTypeEnum;
use Aurora\Module\Editorial\Comment\Manager\CommentReactionManager;
use Aurora\Module\Editorial\Comment\Repository\CommentReactionRepository;
use Aurora\Module\Editorial\Comment\Repository\CommentRepository;
use Aurora\Module\Editorial\Comment\Serializer\CommentSerializer;
use Aurora\Module\Editorial\Comment\Service\CommentSubmissionValidator;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Service\PostPageRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CommentController extends AbstractController
{
    use FrontLocaleTrait;
    use JsonResponseTrait;

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly CommentRepository $commentRepository,
        private readonly CommentReactionRepository $commentReactionRepository,
        private readonly CommentManagerInterface $commentManager,
        private readonly CommentReactionManager $commentReactionManager,
        private readonly CommentSerializer $commentSerializer,
        private readonly CommentSubmissionValidator $commentValidator,
        private readonly SettingRepository $settingRepository,
        private readonly FrontContext $frontContext,
        private readonly PostPageRenderer $postPageRenderer,
    ) {}

    #[Route('/{locale}/editorial/{postTypeSlug}/{slug}/comment', name: 'editorial_post_comment', requirements: ['locale' => '[a-z]{2}'], methods: ['POST'], priority: 6)]
    public function submit(string $locale, string $postTypeSlug, string $slug, Request $request): Response
    {
        $this->assertActiveLocale($this->frontContext, $locale);
        $request->setLocale($locale);

        $post = $this->postRepository->findPublishedBySlug($slug, $locale);
        if (!$post instanceof Post) {
            throw $this->createNotFoundException();
        }

        if (!$this->areCommentsEnabled($post)) {
            return $this->redirectToRoute('editorial_post', ['locale' => $locale, 'postTypeSlug' => $postTypeSlug, 'slug' => $slug]);
        }

        $isJson = str_contains((string) $request->headers->get('Content-Type', ''), 'application/json');
        $payload = $isJson ? $request->toArray() : $request->request->all();

        $authorName = mb_trim((string) ($payload['authorName'] ?? ''));
        $authorEmail = mb_trim((string) ($payload['authorEmail'] ?? ''));
        $content = mb_trim((string) ($payload['content'] ?? ''));

        $errors = $this->commentValidator->validate($authorName, $authorEmail, $content);
        if ([] !== $errors) {
            return $isJson
                ? $this->jsonInvalidInput($errors, Response::HTTP_OK)
                : $this->postPageRenderer->render($post, $locale, $errors);
        }

        $parentComment = $this->resolveParent($post, (int) ($payload['parent_id'] ?? 0));
        $this->commentManager->submit($post, $authorName, $authorEmail, $content, $parentComment);

        if ($isJson) {
            return $this->jsonSuccess();
        }

        $this->addFlash('commentSuccess', 'comment.success');

        return $this->redirectToRoute('editorial_post', ['locale' => $locale, 'postTypeSlug' => $postTypeSlug, 'slug' => $slug]);
    }

    #[Route('/{locale}/editorial/{postTypeSlug}/{slug}/comments', name: 'editorial_post_comments_list', requirements: ['locale' => '[a-z]{2}'], methods: ['GET'], priority: 5)]
    public function list(string $locale, string $postTypeSlug, string $slug): JsonResponse
    {
        $this->assertActiveLocale($this->frontContext, $locale);

        $post = $this->postRepository->findPublishedBySlug($slug, $locale);
        if (!$post instanceof Post) {
            return $this->json(['success' => false], Response::HTTP_NOT_FOUND);
        }

        if (!$this->areCommentsEnabled($post)) {
            return $this->jsonSuccess(['roots' => [], 'replies' => [], 'reactionEmojis' => []]);
        }

        $allComments = $this->commentRepository->findApprovedByPost($post->getId());

        $allCommentIds = array_map(static fn (Comment $comment): int => (int) $comment->getId(), $allComments);
        $reactionCountsMap = [] !== $allCommentIds
            ? $this->commentReactionRepository->countByComments($allCommentIds)
            : [];

        $tree = $this->commentSerializer->buildFrontTree($allComments, $reactionCountsMap);

        return $this->jsonSuccess($tree);
    }

    #[Route('/{locale}/editorial/{postTypeSlug}/{slug}/comment/{commentId}/react', name: 'editorial_comment_react', requirements: ['locale' => '[a-z]{2}'], methods: ['POST'], priority: 5)]
    public function react(string $locale, string $postTypeSlug, string $slug, int $commentId, Request $request): JsonResponse
    {
        $this->assertActiveLocale($this->frontContext, $locale);

        $post = $this->postRepository->findPublishedBySlug($slug, $locale);
        if (!$post instanceof Post) {
            return $this->json(['success' => false], Response::HTTP_NOT_FOUND);
        }

        $comment = $this->commentRepository->find($commentId);
        if (!$this->isPubliclyOnPost($comment, $post)) {
            return $this->json(['success' => false], Response::HTTP_NOT_FOUND);
        }

        $typeValue = str_contains((string) $request->headers->get('Content-Type', ''), 'application/json')
            ? (string) ($request->toArray()['type'] ?? '')
            : (string) $request->request->get('type', '');

        $reactionType = ReactionTypeEnum::tryFrom($typeValue);
        if (null === $reactionType) {
            return $this->jsonFailure('Invalid reaction type');
        }

        $fingerprint = $this->commentReactionManager->generateFingerprint($request);
        $updatedCounts = $this->commentReactionManager->toggle($comment, $reactionType, $fingerprint);

        return $this->jsonSuccess(['counts' => $updatedCounts]);
    }

    private function areCommentsEnabled(Post $post): bool
    {
        return $this->settingRepository->getBoolean('comments_enabled') && $post->isCommentsEnabled();
    }

    private function resolveParent(Post $post, int $parentId): ?Comment
    {
        if ($parentId <= 0) {
            return null;
        }

        $parent = $this->commentRepository->find($parentId);

        return $this->isPubliclyOnPost($parent, $post) ? $parent : null;
    }

    private function isPubliclyOnPost(?Comment $comment, Post $post): bool
    {
        return $comment instanceof Comment
            && $comment->getPost()->getId() === $post->getId()
            && 'approved' === $comment->getStatus()->value;
    }
}
