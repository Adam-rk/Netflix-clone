<?php

namespace App\Security;

use App\Entity\Token;
use App\Entity\Account;
use App\Repository\AccountRepository;
use App\Repository\TokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class AccountTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private TokenRepository $tokenRepository,
        private AccountRepository $accountRepository

    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->headers->get('Authorization');
        if (null === $token) {
            throw new CustomUserMessageAuthenticationException('No user token provided');
        }

        if ('bearer ' !== strtolower(substr($token, 0, 7))) {
            throw new CustomUserMessageAuthenticationException('Token should be formated as "Bearer $token"');
        }

        $token = substr($token, 7);

        if ($this->tokenRepository->findOneByValue($token) === null) {
            throw new CustomUserMessageAuthenticationException('No token in db');
        }

        if ($this->tokenRepository->findOneByValue($token)->isExpired()) {
            throw new CustomUserMessageAuthenticationException('Token expired');
        }


        if ($this->tokenRepository->findOneByValue($token)->getIpAddress() === $request->getClientIp()) {

            return new SelfValidatingPassport(new UserBadge($token, function($token) {
                $account = $this->accountRepository->findByToken($token);

                if (!$account) {
                    throw new UserNotFoundException();
                }
                return $account;
            }));
        }

    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /*
        $account = $token->getUser();
        return new JsonResponse(
            ['userId' => $account->getId()]
        );
        */
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {

        $data = [
            'message' => strtr ($exception->getMessageKey () , $exception->getMessageData())
        ];
        return new JsonResponse ($data, Response::HTTP_UNAUTHORIZED) ;

    }
}
