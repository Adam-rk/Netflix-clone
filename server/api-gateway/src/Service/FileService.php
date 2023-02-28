<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FileService
{
    public function __construct(
        private HttpClientInterface $client
    )
    {
    }

    public function uploadFileAndGetPath($file): array
    {
        $client = new Client();

        $multipart = [
            [
                'name' => 'file',
                'contents' => fopen($file->getPathname(), 'r'),
                'filename' => $file->getClientOriginalName(),
            ],
        ];

        $response = $client->post(
            'http://127.0.0.1:8002/file/create',
            [
                'multipart' => $multipart,
            ]
        );

        $file = json_decode($response->getBody(), true);
        return [
            'serverPath' => $file['serverPath'],
            'relativePath' => $file['relativePath']
        ];
    }

    public function deleteFile($path): void
    {
        $response = $this->client->request('POST', 'http://127.0.0.1:8002/file/delete', [
            'body' => json_encode([
                'serverPath' => $path
            ])
        ]);
    }
}