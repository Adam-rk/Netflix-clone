<?php

namespace App\Controller;

use App\Service\AccountAuth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class TimeStampServiceController extends AbstractController
{

    public function __construct(
        private readonly AccountAuth $accountAuth,
        private readonly HttpClientInterface $client
    )
    {
    }

    #[Route('/time-stamp', name: 'app_time_stamp')]
    public function timeStamp(Request $request): JsonResponse
    {
        $action = $request->headers->get('Action');
        $token = $request->headers->get('Authorization');

        $accountId = $this->accountAuth->getAccountByToken($token)['id'];

        return match ($action)
        {
            'Create TimeStamp' => $this->createTimeStamp($request, $accountId),
            'Delete TimeStamp' => $this->deleteTimeStamp($request, $accountId)
        };
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function createTimeStamp(Request $request, int $accountId): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $contentId = $data['contentId'];
        $timeStamp = $data['timeStamp'];
        $response = $this->client->request('POST', 'http://127.0.0.1:8003/time-stamp/create', [
            'body' => json_encode([
                'contentId' => $contentId,
                'accountId' => $accountId,
                'timeStamp' => $timeStamp
            ])
        ]);

        return new JsonResponse($response->getContent());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function deleteTimeStamp(Request $request, int $accountId): JsonResponse
    {
        $contentId = $request->query->get('contentId');
        $response = $this->client->request('GET', 'http://127.0.0.1:8003/time-stamp/delete/' . $contentId . '/' . $accountId);

        return new JsonResponse($response->getContent());
    }
}
