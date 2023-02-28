<?php

namespace App\Security;

use App\Entity\Token;
use App\Entity\Account;
use App\Repository\TokenRepository;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class AccountAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher,
        private AccountRepository $accountRepository,
        private TokenRepository $tokenRepository,
        private EntityManagerInterface $em
    ) {
    }

    public function supports(Request $request): ?bool
    {

        return $request->attributes->get('_route') === 'app_account_login';
    }

    public function authenticate(Request $request): Passport
    {

        $body = json_decode($request->getContent(), true);

        $email = $body['email'];
        $password = $body['password'];


        return new Passport(new UserBadge($email), new PasswordCredentials($password));


    }
    public function onAuthenticationSuccess(Request $request, TokenInterface $tokenInterface, string $firewallName): ?Response
    {


        $account = $tokenInterface->getUser();


        $token = $this->tokenRepository->findOneBy([
            'account' => $account,
        ]);



        if (null === $token) {
            $token = new Token();

            $token
                ->resetValue()
                ->resetExpiresAt()
                ->resetIpAddress($request->getClientIp());

            $account->addToken($token);
            $this->em->persist($token);
        } else {
            if ($token->getExpiresAt() <= new \DateTime())
            {
                $token
                    ->resetValue()
                    ->resetExpiresAt()
                    ->resetIpAddress($request->getClientIp())
                ;
            }

        }

        $this->em->flush();

        return new JsonResponse(['token' => $token->getValue()], Response::HTTP_OK);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'errors' => [
                $exception->getMessage(),
            ],
        ], Response::HTTP_UNAUTHORIZED);
    }
}
