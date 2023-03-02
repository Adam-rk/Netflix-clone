<?php

namespace App\Controller;

use App\Service\AccountAuth;
use App\Service\CleanArray;
use App\Service\FileService;
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

class AccountServiceController extends AbstractController
{

    public function __construct(
        private HttpClientInterface $client,
        private CleanArray $cleanArray,
        private FileService $fileService,
        private AccountAuth $accountAuth
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/account', name: 'app_account')]
    public function account(Request $request): JsonResponse
    {

        $action = $request->headers->get('Action');


        return match ($action) {
            'Create Account' => $this->createAccount($request),
            'Update Account' => $this->updateAccount($request),
            'Show Account By Email' => $this->showAccountByEmail($request),
            'Show All Accounts' => $this->showAllAccounts($request),
            'Delete Account' => $this->deleteAccount($request),
            'Login' => $this->login($request),
            'Manage Account' => $this->manageAccount($request),
            null => throw new HttpException(Response::HTTP_FORBIDDEN, "Enter an action")
        };
    }

    private function createAccount(Request $request): JsonResponse
    {
        $body = $request->request->all();

        $profilePic = $request->files->get('profilePic');

        if (null === $profilePic)
        {
            $profilePicPath = "./server/files/defaultprofilepic.png";
        } else {
            $profilePicPath = $this->fileService->uploadFileAndGetPath($profilePic)['serverPath'];
        }

        $body['profilePicPath'] = $profilePicPath;

        $response = $this->client->request('POST', 'http://127.0.0.1:8001/account/create', [
            'body' => json_encode($body)
        ]);

        if (200 === $response->getStatusCode())
        {
            return new JsonResponse(["Account created"]);
        }

        return new JsonResponse([
            'message' => $response->getContent()
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function updateAccount(Request $request): JsonResponse
    {
        $token = $request->headers->get('Authorization');

        $data = $request->request->all();

        $account = $this->accountAuth->getAccountByToken($token);

        $profilePic = $request->files->get('profilePic');
        if (null !==$profilePic){
            $profilePicPath = $this->fileService->uploadFileAndGetPath($profilePic)['serverPath'];

            $data['profilePicPath'] = $profilePicPath;
        }

        $response = $this->client->request('POST', 'http://127.0.0.1:8001/account/update', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $token
            ],
            'body' => json_encode($data)
        ]);

        if (200 === $response->getStatusCode())
        {
            if (null !== $profilePic && './server/files/defaultprofilepic.png' !== $account['profilePicPath']) {

                $this->fileService->deleteFile($account['profilePicPath']);
            }
            return new JsonResponse(['message' => 'Account updated']);
        }

        if (null !== $profilePic)
        {
            $this->fileService->deleteFile($profilePicPath);
        }

        return new JsonResponse([
            'status code' => $response->getStatusCode(),
             'message' => $response->getContent()
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function showAccountByEmail(Request $request): JsonResponse
    {
        $email = $request->query->get('email');
        $response = $this->client->request('GET', 'http://127.0.0.1:8001/account/show-by-email/' . $email);

        $account = json_decode($response->getContent(), true);

        return new JsonResponse($this->cleanArray->cleanAccount($account));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function showAllAccounts($request): JsonResponse
    {
        $response = $this->client->request('GET', 'http://127.0.0.1:8001/account/show-all');

        $accounts = json_decode($response->getContent(), true);

        return new JsonResponse($this->cleanArray->cleanAccountMatrix($accounts));

    }

    /**
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function deleteAccount(Request $request): JsonResponse
    {
        $accountId = $request->query->get('accountId');
        $response = $this->client->request('GET', 'http://127.0.0.1:8001/account/delete/'.$accountId, [
            'headers' => ['Authorization' => $request->headers->get('Authorization')]
        ]);
        return new JsonResponse($response->getContent(), Response::HTTP_OK);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function login($request): JsonResponse
    {
        $response = $this->client->request('POST', 'http://127.0.0.1:8001/account/login', [
            'body' => $request->getContent()
        ]);

        return new JsonResponse(json_decode($response->getContent(), true));
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    private function manageAccount($request): JsonResponse
    {
        $response = $this->client->request('POST', 'http://127.0.0.1:8001/account/manage', [
            'headers' => [
                'Authorization' => $request->headers->get('Authorization')
            ],
            'body' => $request->getContent()
        ]);

        if (404 === $response->getStatusCode())
        {
            return new JsonResponse(['message' => 'No account with this email'], Response::HTTP_NOT_FOUND);
        }

        if (401 === $response->getStatusCode())
        {
            return new JsonResponse($response->getContent(), Response::HTTP_UNAUTHORIZED);
        }

        if (304 === $response->getStatusCode())
        {
            return new JsonResponse($response->getContent(), Response::HTTP_NOT_MODIFIED);
        }

        if (403 === $response->getStatusCode())
        {
            return new JsonResponse(['message' => 'cannot ban yourself'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(['message' => 'Account updated'], Response::HTTP_OK);
    }

}
