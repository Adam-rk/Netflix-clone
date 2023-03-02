<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Token;
use App\Form\CreateAccountType;
use App\Form\LoginAccountType;
use App\Form\LoginType;
use App\Repository\AccountRepository;
use App\Repository\TokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;


#[Route('/account', name: 'app_account_')]
class AccountController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface      $em,
        private UserPasswordHasherInterface $hasher,
        private ValidatorInterface          $validator,
        private AccountRepository           $accountRepository,
        private TokenRepository             $tokenRepository
    )
    {
        $this->accountRepository = $this->em->getRepository(Account::class);
        $this->tokenRepository = $this->em->getRepository(Token::class);
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request): Response
    {
        $account = new Account();
        $data = json_decode($request->getContent(), true);

        $account
            ->setFirstName($data["firstName"])
            ->setLastName($data["lastName"])
            ->setUsername($data['username'])
            ->setEmail($data["email"])
            ->setPhoneNumber($data["phoneNumber"])
            ->setPassword($this->hasher->hashPassword($account, $data["password"]))
            ->setRole($data["role"])
            ->setAddress($data["address"])
            ->setProfilePicPath($data['profilePicPath']);

        $errors = $this->validator->validate($account);

        if (count($errors) > 0) {
            $errorsString = (string)$errors;
            throw new HttpException(Response::HTTP_FORBIDDEN, $errorsString);
        }

        $this->em->persist($account);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Account created'
        ], Response::HTTP_OK);
    }

    #[Route('/delete/{id}', 'delete')]
    public function delete(Request $request, $id): JsonResponse
    {
        $accountToDelete = $this->accountRepository->findOneBy(['id' => $id]);
        $tokenValue = str_replace('Bearer ', '', $request->headers->get('Authorization'));
        $token = $this->tokenRepository->findOneBy(['value' => $tokenValue]);
        $currentAccount = $token->getAccount();

        if (null === $currentAccount || null === $accountToDelete) {
            throw new HttpException(Response::HTTP_NOT_FOUND, "Account not found");
        }
        if ('ROLE_ADMIN' === $currentAccount->getRole()) {
            $this->accountRepository->remove($accountToDelete);
        } elseif ($accountToDelete === $currentAccount) {
            $this->accountRepository->remove($accountToDelete);
        } else {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, "Can't delete this account");
        }

        $this->em->flush();
        return new JsonResponse([
            'success' => 'The account has been deleted'
        ], Response::HTTP_OK);
    }

    #[Route('/update', name: 'update')]
    public function update(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $tokenValue = str_replace('Bearer ', '', $request->headers->get('Authorization'));

        $token = $this->tokenRepository->findOneBy(['value' => $tokenValue]);

        $account = $token->getAccount();


        foreach ($data as $key => $value) {
            switch ($key) {
                case 'firstName':
                    $account->setFirstName($value);
                    break;
                case 'lastName':
                    $account->setLastName($value);
                    break;
                case 'username':
                    $account->setUsername($value);
                    break;
                case 'address':
                    $account->setAddress($value);
                    break;
                case 'profilePicPath':
                    $account->setProfilePicPath($value);
                    break;
                case 'password':
                    $account->setPassword($this->hasher->hashPassword($account, $value));
                    break;
                case 'email':
                    $account->setEmail($value);
                    break;
                case 'phoneNumber':
                    $account->setPhoneNumber($value);
                    break;
                default:
                    break;
            }
        }

        $errors = $this->validator->validate($account);

        if (count($errors) > 0) {
            $errorsString = (string)$errors;
            throw new HttpException(Response::HTTP_FORBIDDEN, $errorsString);
        }

        $this->em->flush();

        return new JsonResponse([
            'message' => 'Account updated'
        ]);
    }


    #[Route('/manage', name: 'manage')]
    public function manage(Request $request, Security $security): JsonResponse
    {
        $action = json_decode($request->getContent(), true)['action'];
        $email = json_decode($request->getContent(), true)['email'];

        $this->accountRepository = $this->em->getRepository(Account::class);

        $account = $this->accountRepository->findOneBy(['email' => $email]);

        if (null === $account) {
            throw new HttpException(Response::HTTP_NOT_FOUND, "Account not found");
        }

        $authenticatedAccount = $security->getUser();

        if ($account !== $authenticatedAccount) {

            if ('BAN' === $action) {
                if ('ROLE_BANNED' !== $account->getRole()) {

                    $account->setRole('ROLE_BANNED');

                    $this->em->flush();

                    return new JsonResponse([
                        'message' => 'Account updated'
                    ], Response::HTTP_OK);
                } else {
                    throw new HttpException(Response::HTTP_NOT_MODIFIED, "Account already banned");
                }
            } elseif ('USER' === $action) {

                if ('ROLE_USER' !== $account->getRole()) {

                    $account->setRole('ROLE_USER');

                    $this->em->flush();

                    return new JsonResponse([
                        'message' => 'Account updated'
                    ], Response::HTTP_OK);
                } else {
                    throw new HttpException(Response::HTTP_NOT_MODIFIED, "Account not banned");
                }
            } elseif ('ADMIN' === $action) {

                if ('ROLE_USER' === $account->getRole()) {

                    $account->setRole('ROLE_ADMIN');
                    $this->em->flush();

                    return new JsonResponse([
                        'message' => 'Account updated'
                    ], Response::HTTP_OK);
                } else {
                    throw new HttpException(Response::HTTP_NOT_MODIFIED, "Cannot make a banned account ADMIN");
                }
            } else {
                throw new HttpException(Response::HTTP_UNAUTHORIZED, "No such action");
            }


        } elseif ("BAN" === $action) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Can't ban your own account");
        }

    }


    #[Route('/show-by-email/{email}', name: 'show_by_email')]
    public function showByEmail($email): JsonResponse
    {

        $account = $this->accountRepository->findOneBy(['email' => $email]);


        if (null === $account) {
            throw new HttpException(Response::HTTP_NOT_FOUND, "Account not found");
        }

        $account = (array)$account;
        unset($account["\x00App\Entity\Account\x00password"]);
        unset($account["\x00App\Entity\Account\x00plainPassword"]);

        dump($account);

        return new JsonResponse($account);
    }

    #[Route('/show-by-id/{id}', name: 'show_by_id')]
    public function showById($id): JsonResponse
    {
        $account = $this->accountRepository->findOneBy(['id' => $id]);

        if (null === $account) {
            throw new HttpException(Response::HTTP_NOT_FOUND, "Account not found");
        }

        $account = (array)$account;
        unset($account["\x00App\Entity\Account\x00password"]);
        unset($account["\x00App\Entity\Account\x00plainPassword"]);

        return new JsonResponse($account);
    }

    #[Route('/show-by-token', name: 'show_by_token')]
    public function showByToken(Request $request): JsonResponse
    {
        $tokenValue = str_replace("Bearer ", "", $request->headers->get('Authorization'));

        $token = $this->tokenRepository->findOneBy(['value' => $tokenValue]);


        if (null === $token) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, "You have to be connected");
        }

        $account = $token->getAccount();

        $account = (array)$account;
        unset($account["\x00App\Entity\Account\x00password"]);
        unset($account["\x00App\Entity\Account\x00plainPassword"]);

        return new JsonResponse($account);
    }


    #[Route('/show-all', name: 'show_all')]
    public function showAll(): JsonResponse
    {
        $this->accountRepository = $this->em->getRepository(Account::class);

        $accounts = $this->accountRepository->findAll();

        $jsonAccounts = [];

        foreach ($accounts as $account) {
            $account = (array)$account;
            unset($account["\x00App\Entity\Account\x00password"]);
            unset($account["\x00App\Entity\Account\x00plainPassword"]);

            $jsonAccounts[] = $account;

        }

        return new JsonResponse($jsonAccounts);
    }
}
