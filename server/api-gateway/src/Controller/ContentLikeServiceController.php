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

class ContentLikeServiceController extends AbstractController
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
    #[Route('/like/content', name: 'app_like_content')]
    public function likeContent(Request $request): JsonResponse
    {
        $action = $request->headers->get('Action');
        $contentId = $request->query->get('contentId');
        $accountId = $request->query->get('accountId');

        $token = $request->headers->get('Authorization');

       return match ($action)
       {
           'Like Content' => $this->createLikeContent($contentId, $token),
           'Delete Like' => $this->deleteLike($contentId,  $token),
           'Show By Content Id' => $this->showByContentId($contentId),
           'Show By Account Id' => $this->showByAccountId($accountId)
       };
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function createLikeContent(int $contentId, string $token): JsonResponse
    {

        if ($this->accountAuth->seeIfAccountBanned($token))
        {
            throw new HttpException(Response::HTTP_FORBIDDEN, "You can't like this content, you are banned");
        }

        $accountId = $this->accountAuth->getAccountByToken($token)['id'];

        $url = 'http://127.0.0.1:8005/like/content/create/' . $contentId . '/' . $accountId;

        $response = $this->client->request('GET', $url);

        return new JsonResponse($response->getContent());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function deleteLike(int $contentId, string $token): JsonResponse
    {
        if ($this->accountAuth->seeIfAccountBanned($token))
        {
            throw new HttpException(Response::HTTP_FORBIDDEN, "You can't unlike this content, you are banned");
        }

        $accountId = $this->accountAuth->getAccountByToken($token)['id'];

        $url = 'http://127.0.0.1:8005/like/content/delete/' . $contentId . '/' . $accountId;

        $response = $this->client->request('GET', $url);

        return new JsonResponse($response->getContent());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function showByContentId(int $contentId): JsonResponse
    {
        $response = $this->client->request('GET', 'http://127.0.0.1:8005/like/content/show-by-content-id/'.$contentId);

        $likes = json_decode($response->getContent(), true);

        $likes = $this->cleanArray->cleanContentLikeMatrix($likes);

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
        $response = $this->client->request('GET', 'http://127.0.0.1:8005/like/content/show-by-account-id/'.$accountId);

        $likes = json_decode($response->getContent(), true);

        $likes = $this->cleanArray->cleanContentLikeMatrix($likes);

        return new JsonResponse($likes);
    }


}
