<?php

namespace App\Controller;

use App\Service\AccountAuth;
use App\Service\CleanArray;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CommentLikeServiceController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $client,
        private AccountAuth $accountAuth,
        private CleanArray $cleanArray
    )
    {
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[Route('/like/comment', name: 'app_like_comment')]
    public function likeComment(Request $request): JsonResponse
    {
        $action = $request->headers->get('Action');
        $commentId = $request->query->get('commentId');
        $accountId = $request->query->get('accountId');

        $token = $request->headers->get('Authorization');

        return match ($action)
        {
            'Like Comment' => $this->createLikeComment($commentId, $token),
            'Delete Like' => $this->deleteLike($commentId, $token),
            'Show By Comment Id' => $this->showByCommentId($commentId),
            'Show By Account Id' => $this->showByAccountId($accountId)
        };
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function createLikeComment(int $commentId, string $token): JsonResponse
    {

        if ($this->accountAuth->seeIfAccountBanned($token))
        {
            throw new HttpException(Response::HTTP_FORBIDDEN, "You can't like this comment, you are banned");
        }

        $accountId = $this->accountAuth->getAccountByToken($token)['id'];

        $url = 'http://127.0.0.1:8005/like/comment/create/' . $commentId . '/' . $accountId;

        $response = $this->client->request('GET', $url);

        return new JsonResponse($response->getContent());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function deleteLike(int $commentId, string $token): JsonResponse
    {
        if ($this->accountAuth->seeIfAccountBanned($token))
        {
            throw new HttpException(Response::HTTP_FORBIDDEN, "You can't unlike this comment, you are banned");
        }

        $accountId = $this->accountAuth->getAccountByToken($token)['id'];

        $url = 'http://127.0.0.1:8005/like/comment/delete/' . $commentId . '/' . $accountId;

        $response = $this->client->request('GET', $url);

        return new JsonResponse($response->getContent());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function showByCommentId(int $commentId): JsonResponse
    {
        $response = $this->client->request('GET', 'http://127.0.0.1:8005/like/comment/show-by-comment-id/'.$commentId);

        $likes = json_decode($response->getContent(), true);

        $likes = $this->cleanArray->cleanCommentLikeMatrix($likes);

        return new JsonResponse($likes);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function showByAccountId(int $accountId): JsonResponse
    {
        $response = $this->client->request('GET', 'http://127.0.0.1:8005/like/comment/show-by-account-id/'.$accountId);

        $likes = json_decode($response->getContent(), true);

        $likes = $this->cleanArray->cleanCommentLikeMatrix($likes);

        return new JsonResponse($likes);
    }


}
