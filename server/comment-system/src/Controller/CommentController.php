<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/comment', name: 'app_comment_')]
class CommentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CommentRepository $commentRepository
    )
    {
        $this->commentRepository = $this->em->getRepository(Comment::class);
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $accountId = $data['accountId'];
        $contentId = $data['contentId'];
        $content = $data['content'];
        $creationDate = new \DateTime();

        $comment = new Comment();

        $comment
            ->setAccountId($accountId)
            ->setContentId($contentId)
            ->setContent($content)
            ->setCreationDate($creationDate);

        $this->em->persist($comment);
        $this->em->flush();

        return new JsonResponse(['message' => 'Comment created'], Response::HTTP_OK);
    }

    #[Route('/update', name: 'update')]
    public function update(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $commentId = $data['commentId'];
        $newContent = $data['content'];
        $updateDate = new \DateTime();

        $comment = $this->commentRepository->findOneBy(['id' => $commentId]);

        if (null === $comment)
        {
            throw new NotFoundHttpException('No comment with this id');
        }

        $comment
            ->setContent($newContent)
            ->setCreationDate($updateDate);

        $this->em->persist($comment);
        $this->em->flush();

        return new JsonResponse(['message' => 'Comment updated'], Response::HTTP_OK);
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function delete($id): JsonResponse
    {
        $comment = $this->commentRepository->findOneBy(['id' => $id]);

        if (null === $comment)
        {
            throw new NotFoundHttpException('No comment with this id');
        }

        $this->commentRepository->remove($comment);
        $this->em->flush();

        return new JsonResponse(['message' => 'Comment deleted'], Response::HTTP_OK);
    }

    #[Route('/show/{commentId}', name: 'show')]
    public function show($commentId): JsonResponse
    {
        $comment = $this->commentRepository->findOneBy(['id' => $commentId]);

        if (null === $comment)
        {
            throw new NotFoundHttpException('No comment with this id');
        }

        return new JsonResponse((array)$comment);
    }


    #[Route('/showall/{contentId}', name: 'showall')]
    public function showAll($contentId): JsonResponse
    {
        $comments = $this->commentRepository->findBy(['contentId' => $contentId]);

        if (null === $comments)
        {
            throw new NotFoundHttpException('Content has no comment');
        }

        $jsonComments = [];

        foreach ($comments as $comment) {
            $jsonComments[] = (array)$comment;
        }
        return $this->json($jsonComments);
    }
}
