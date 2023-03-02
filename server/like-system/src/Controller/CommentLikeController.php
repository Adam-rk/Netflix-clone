<?php

namespace App\Controller;

use App\Entity\CommentLike;
use App\Repository\CommentLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
#[Route('/like/comment', name: 'app_like_comment')]
class CommentLikeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CommentLikeRepository $commentLikeRepository
    )
    {
        $this->commentLikeRepository = $this->em->getRepository(CommentLike::class);
    }

    #[Route('/create/{commentId}/{accountId}', name: 'create')]
    public function create($commentId, $accountId): JsonResponse
    {
        $like = $this->commentLikeRepository->findOneBy(['commentId' => $commentId, 'accountId' => $accountId]);

        if (null !== $like)
        {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Can't like twice");
        }

        $like = new CommentLike();
        $like
            ->setCommentId($commentId)
            ->setAccountId($accountId)
            ->setCreationDate(new \DateTime());
        $this->em->persist($like);
        $this->em->flush();


        return new JsonResponse(['message' => "Like added to comment"], Response::HTTP_OK);
    }

    #[Route('/delete/{commentId}/{accountId}', name: 'delete')]
    public function delete($commentId, $accountId): JsonResponse
    {
        $like = $this->commentLikeRepository->findOneBy(['commentId' => $commentId, 'accountId' => $accountId]);

        if (null === $like)
        {
            throw new NotFoundHttpException("You didn't like this comment");
        }

        $this->em->remove($like);
        $this->em->flush();

        return new JsonResponse(["message" => "Like deleted"]);
    }

    #[Route('/show-by-comment-id/{commentId}', name: 'show_by_comment_id')]
    public function showByComment($commentId): JsonResponse
    {
        $likes = $this->commentLikeRepository->findBy(['commentId' => $commentId]);

        $numberOfLikes = 0;

        $jsonLikes = [];

        foreach ($likes as $like) {
            $numberOfLikes++;
            $jsonLikes[] = (array)$like;
        }

        $jsonLikes['numberOfLikes'] = $numberOfLikes;

        return new JsonResponse($jsonLikes);
    }

    #[Route('/show-by-account-id/{accountId}', name: 'show_by_account_id')]
    public function showByAccount($accountId): JsonResponse
    {
        $likes = $this->commentLikeRepository->findBy(['accountId' => $accountId]);

        $numberOfLikedComments = 0;

        $jsonLikes = [];

        foreach ($likes as $like) {
            $numberOfLikedComments++;
            $jsonLikes[] = (array)$like;
        }

        $jsonLikes['numberOfLikedComments'] = $numberOfLikedComments;

        return new JsonResponse($jsonLikes);
    }
}
