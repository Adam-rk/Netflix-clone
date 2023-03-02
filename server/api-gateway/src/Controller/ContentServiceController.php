<?php

namespace App\Controller;

use App\Service\AccountAuth;
use App\Service\CleanArray;


use App\Service\FileService;
use getID3;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ContentServiceController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CleanArray          $cleanArray,
        private readonly AccountAuth         $accountAuth,
        private readonly FileService $fileService
    )
    {
    }


    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[Route('/content', name: 'app_content')]
    public function content(Request $request): JsonResponse
    {
        $action = $request->headers->get('Action');

        return match ($action) {
            'Create Content' => $this->createContent($request),
            'Update Content' => $this->updateContent($request),
            'Show Content' => $this->showContent($request),
            'Show Content By Title' => $this->showContentByTitle($request),
            'Show Most Popular By Category' => $this->showMostPopularByCategory($request),
            'Delete Content' => $this->deleteContent($request)
        };
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function createContent($request): JsonResponse
    {
        $token = $request->headers->get('Authorization');
        $account = $this->accountAuth->getAccountByToken($token);


        if ('ROLE_ADMIN' !== $account['role']) {
            return new JsonResponse(['message' => 'Cannot create a content'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->request->all();
        $accountUsername = $this->accountAuth->getAccountByToken($token)['username'];


        $contentFile = $request->files->get('contentFile');
        $coverFile = $request->files->get('coverFile');

        $paths = $this->fileService->uploadFileAndGetPath($contentFile);
        $contentServerPath = $paths['serverPath'];
        $contentRelativePAth = $paths['relativePath'];
        $coverServerPath = $this->fileService->uploadFileAndGetPath($coverFile)['serverPath'];

        $duration = $this->getContentDuration($contentRelativePAth);


        $response = $this->client->request('POST', 'http://127.0.0.1:8003/content/create', [
            'body' => json_encode([
                'title' => $data['title'],
                'contentType' => $data['contentType'],
                'seriesTitle' => $data['seriesTitle'] ?? null,
                'episodeNumber' => $data['episodeNumber'] ?? null,
                'season' => $data['season'] ?? null,
                'studio' => $data['studio'],
                'description' => $data['description'],
                'regulation' => $data['regulation'],
                'language' => $data['language'],
                'creatorUsername' => $accountUsername,
                'fileServerPath' => $contentServerPath,
                'coverServerPath' => $coverServerPath,
                'categoryLabel' => $data['categoryLabel'],
                'tags' => $data['tags'],
                'duration' => $duration
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
    private function updateContent($request): JsonResponse
    {
        $data = $request->request->all();
        $token = $request->headers->get('Authorization');
        $id = $data['contentId'];

        $account = $this->accountAuth->getAccountByToken($token);
        $content = $this->getContent($id);

        if ('ROLE_ADMIN' === $account['role']) {
            $contentFile = $request->files->get('contentFile');
            $coverFile = $request->files->get('coverFile');
            $oldContentFilePath = $content['filePath'];
            $oldCoverFilePath = $content['coverPath'];


            if (null !== $contentFile) {
                $paths = $this->fileService->uploadFileAndGetPath($contentFile);
                $contentServerPath = $paths['serverPath'];
                $contentRelativePath = $paths['relativePath'];
                $duration = $this->getContentDuration($contentRelativePath);
                unset($data['contentFile']);
                $data['fileServerPath'] = $contentServerPath;
                $data['duration'] = $duration;
            }

            if (null !== $coverFile) {
                $coverServerPath = $this->fileService->uploadFileAndGetPath($coverFile)['serverPath'];
                unset($data['coverFile']);
                $data['coverServerPath'] = $coverServerPath;
            }

            $response = $this->client->request('POST', 'http://127.0.0.1:8003/content/update', [
                'body' => json_encode($data)
            ]);

            if (200 === $response->getStatusCode() && isset($contentServerPath)) {
                $oldContentFile = $this->getFileByPath($oldContentFilePath);
                $this->fileService->deleteFile($oldContentFile['serverPath']);
            }

            if (200 === $response->getStatusCode() && isset($coverServerPath)) {
                $oldCoverFile = $this->getFileByPath($oldCoverFilePath);
                $this->fileService->deleteFile($oldCoverFile['serverPath']);
            }

            return new JsonResponse([
                'status code' => $response->getStatusCode(),
                'message' => $response->getContent()
            ]);
        }

        return new JsonResponse(['message' => 'You cannot change this content'], Response::HTTP_FORBIDDEN);
    }

    private function showContent(Request $request): JsonResponse
    {
        $contentId = $request->query->get('contentId');
        $token = $request->headers->get('Authorization');
        $accountId = 0;
        if (null !== $token)
        {
            $accountId = $this->accountAuth->getAccountByToken($token)['id'];
        }
        $content = $this->getContent($contentId, $accountId);

        return new JsonResponse($content);
    }

    private function showContentByTitle(Request $request): JsonResponse
    {
        $title = $request->query->get('title');

        $response = $this->client->request('GET', 'http://127.0.0.1:8003/content/show-by-title/'.$title);

        $contents = json_decode($response->getContent(), true);
        return new JsonResponse($contents);
    }

    private function showMostPopularByCategory(Request $request): JsonResponse
    {
        $category = $request->query->get('category');

        $numberOfContents = $request->query->get('numberOfContents');

        $contents = $this->getAllContentsByCategory($category);

        $response = $this->client->request('POST', 'http://127.0.0.1:8005/like/content/show-by-popularity', [
            'body' => json_encode([
                'contents' => $contents,
                'numberOfContents' => $numberOfContents
            ])
        ]);

        $popularContents = json_decode($response->getContent(), true);

        foreach ($popularContents as &$popularContent)
        {
            $popularContent['coverPath'] = $this->getContent($popularContent['id'])['coverPath'];
            unset($popularContent['likes']);
        }

        return new JsonResponse($popularContents);
    }

    private function getAllContentsByCategory(string $category): array
    {
        $response = $this->client->request('GET', 'http://127.0.0.1:8003/content/show-all-by-category/'.$category);

        return json_decode($response->getContent(), true);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function deleteContent(Request $request): JsonResponse
    {
        $token = $request->headers->get('Authorization');
        $contentId = $request->query->get('contentId');

        $account = $this->accountAuth->getAccountByToken($token);

        if ($account['role'] !== 'ROLE_ADMIN')
        {
            return new JsonResponse(['message' => 'You do not have the right to delete this content'], Response::HTTP_FORBIDDEN);
        }

        $response = $this->client->request('GET', 'http://127.0.0.1:8003/content/delete/'.$contentId);

        $content = $this->getContent($contentId);

        if (200 === $response->getStatusCode())
        {
            $this->fileService->deleteFile($content['filePath']);
            $this->fileService->deleteFile($content['coverPath']);
        }

        return new JsonResponse(['message' => 'Content deleted'], Response::HTTP_OK);
    }

    private function getContent(int $id, int $accountId = 0): array
    {
        $response = $this->client->request('GET', 'http://127.0.0.1:8003/content/show/'. $id . '/' . $accountId);

        $content = json_decode($response->getContent(), true)['content'];

        return $this->cleanArray->cleanContent($content);
    }


    private function getContentDuration(string $relativePath): string
    {
        $videoFile = new File($relativePath);

        $getID3 = new getID3;

        $fileInfo = $getID3->analyze($videoFile->getRealPath());

        $duration = $fileInfo['playtime_seconds'];

        if ($duration >= 60) {
            $duration = floor($duration / 60);
        }


        $datetime = new \DateTime('@' . $duration);



        return $datetime->format('H:i:s');
    }


    private function getFileByPath(string $oldContentFilePath): array
    {
        $fileName = basename($oldContentFilePath);
        $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);

        $response = $this->client->request('POST', 'http://127.0.0.1:8002/file/show', [
            'body' => json_encode([
                'name' => $fileNameWithoutExtension
            ])
        ]);

        $file = json_decode($response->getContent(), true)['file'];

        return $this->cleanArray->cleanFile($file);
    }



}
