<?php

namespace App\Controller;

use App\Service\AccountAuth;
use App\Service\CleanArray;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class CommentServiceController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $client,
        private CleanArray          $cleanArray,
        private AccountAuth         $accountAuth
    )
    {
    }

    #[Route('/comment', name: 'app_comment')]
    public function comment(Request $request): JsonResponse
    {
        $action = $request->headers->get('Action');

        return match ($action) {
            'Create Comment' => $this->createComment($request),
            'Update Comment' => $this->updateComment($request),
            'Show Comments' => $this->showComments($request),
            'Delete Comment' => $this->deleteComment($request)
        };
    }

    private function createComment(Request $request): JsonResponse
    {
        $token = $request->headers->get('Authorization');

        if ($this->accountAuth->seeIfAccountBanned($token)) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Not allow to comment');
        }

        $account = $this->accountAuth->getAccountByToken($token);
        $data = json_decode($request->getContent(), true);

        $response = $this->client->request('POST', 'http://127.0.0.1:8004/comment/create', [
            'body' => json_encode([
                'accountId' => $account['id'],
                'contentId' => $data['contentId'],
                'content' => $data['content']
            ])
        ]);

        if (200 === $response->getStatusCode()) {
            return new JsonResponse(['Comment created']);
        }

        return new JsonResponse($response->getContent());

    }

    private function updateComment(Request $request)
    {
        $token = $request->headers->get('Authorization');

        $account = $this->accountAuth->getAccountByToken($token);
        $id = json_decode($request->getContent(), true)['commentId'];
        $comment = $this->getComment($id);



        if ($account['id'] !== $comment['accountId'] && 'ROLE_ADMIN' !== $account['role'])
        {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Cannot update if not your comment');
        }

        if ($this->accountAuth->seeIfAccountBanned($token))
        {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Not allowed to update comment you're banned");
        }

        $content = json_decode($request->getContent(), true)['content'];

        $response = $this->client->request('POST', 'http://127.0.0.1:8004/comment/update', [
            'body' => json_encode([
                'commentId' => $id,
                'content' => $content
            ])
        ]);

        if (200 === $response->getStatusCode()) {
            return new JsonResponse(['Comment updated']);
        }

        return new JsonResponse($response->getContent());
    }

    private function showComments(Request $request): JsonResponse
    {
        $contentId = $request->query->get('contentId');

        $response = $this->client->request('GET', 'http://127.0.0.1:8004/comment/showall/'.$contentId);

        $comments = json_decode($response->getContent(), true);

        $comments = $this->cleanArray->cleanCommentMatrix($comments);

        $comments = $this->addUsernameAndProfilePic($comments);

        return new JsonResponse($comments);
    }

    private function deleteComment(Request $request)
    {
        $token = $request->headers->get('Authorization');
        $commentId = $request->query->get('commentId');
        $comment = $this->getComment($commentId);
        $account = $this->accountAuth->getAccountByToken($token);

        if ($this->accountAuth->seeIfAccountBanned($token))
        {
            throw new HttpException(Response::HTTP_FORBIDDEN, "You can't delete a comment, you are banned");
        }

        if ('ROLE_ADMIN' !== $account['role'] && $comment['accountId'] !== $account['id'])
        {
            throw new HttpException(Response::HTTP_FORBIDDEN, "You can't deleted this comment");
        }

        $response = $this->client->request('GET', 'http://127.0.0.1:8004/comment/delete/'.$commentId);

        return new JsonResponse($response->getContent());
    }

    private function getComment($id): array
    {
        $response = $this->client->request('GET', 'http://127.0.0.1:8004/comment/show/'.$id);

        $comment = json_decode($response->getContent(), true);

        return $this->cleanArray->cleanComment($comment);
    }

    private function addUsernameAndProfilePic(mixed $comments): mixed
    {
        foreach ($comments as &$comment)
        {
            $account = $this->accountAuth->getAccountById($comment['accountId']);

            unset($comment['accountId']);
            $comment['account'] = [
                'accountId' => $account['id'],
                'username' => $account['username'],
                'profilePicPath' => $account['profilePicPath']
            ];

        }
        return $comments;
    }


}
