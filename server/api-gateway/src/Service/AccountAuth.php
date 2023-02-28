<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AccountAuth
{
    public function __construct(
        private HttpClientInterface $client,
        private CleanArray $cleanArray
    )
    {
    }

    public function seeIfAccountBanned(string $token): bool
    {
        $account = $this->getAccountByToken($token);

        if ("ROLE_BANNED" === $account['role']) {
            return true;
        }

        return false;
    }

    public function getAccountByToken(string $token): array
    {
        $response = $this->client->request('POST', 'http://127.0.0.1:8001/account/show-by-token', [
            'headers' => [
                'Authorization' => $token
            ]
        ]);

        $account = json_decode($response->getContent(), true);

        return $this->cleanArray->cleanAccount($account);
    }

    public function getAccountById(int $id): array
    {
        $response = $this->client->request('GET', 'http://127.0.0.1:8001/account/show-by-id/'.$id);

        $account = json_decode($response->getContent(), true);

        return $this->cleanArray->cleanAccount($account);
    }
}