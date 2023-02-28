<?php

namespace App\Controller;

use App\Entity\ContentLike;
use App\Repository\ContentLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use function Sodium\randombytes_uniform;

#[Route('/like/content', name: 'app_like_content')]
class ContentLikeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ContentLikeRepository $contentLikeRepository
    )
    {
        $this->contentLikeRepository = $this->em->getRepository(ContentLike::class);
    }

    #[Route('/create/{contentId}/{accountId}', name: 'create')]
    public function create($contentId, $accountId): JsonResponse
    {
        $like = $this->contentLikeRepository->findOneBy(['contentId' => $contentId, 'accountId' => $accountId]);

        if (null !== $like)
        {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Can't like twice");
        }

        $like = new ContentLike();
        $like
            ->setContentId($contentId)
            ->setAccountId($accountId)
            ->setCreationDate(new \DateTime());
        $this->em->persist($like);
        $this->em->flush();


        return new JsonResponse(['message' => "Like added to content"], Response::HTTP_OK);
    }

    #[Route('/delete/{contentId}/{accountId}', name: 'delete')]
    public function delete($contentId, $accountId): JsonResponse
    {
        $like = $this->contentLikeRepository->findOneBy(['contentId' => $contentId, 'accountId' => $accountId]);

        if (null === $like)
        {
            throw new NotFoundHttpException("You didn't like this content");
        }

        $this->em->remove($like);
        $this->em->flush();

        return new JsonResponse(["message" => "Like deleted"]);
    }

    #[Route('/show-by-content-id/{contentId}', name: 'show_by_content_id')]
    public function showByContent($contentId): JsonResponse
    {
        $likes = $this->contentLikeRepository->findBy(['contentId' => $contentId]);

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
        $likes = $this->contentLikeRepository->findBy(['accountId' => $accountId]);

        $numberOfLikedContents = 0;

        $jsonLikes = [];

        foreach ($likes as $like) {
            $numberOfLikedContents++;
            $jsonLikes[] = (array)$like;
        }

        $jsonLikes['numberOfLikedContents'] = $numberOfLikedContents;

        return new JsonResponse($jsonLikes);
    }

    #[Route('/show-by-popularity', name: 'show_by_popularity')]
    public function showByPopularity(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $contents = $data['contents'];
        $numberOfContents = $data['numberOfContents'];

        $contentsWithLikes = $this->getContentsWithLikes($contents);

        usort($contentsWithLikes, function($a, $b) {
            return $b["likes"] - $a["likes"];
        });

        array_splice($contentsWithLikes, $numberOfContents);

        return new JsonResponse($contentsWithLikes);
    }

    private function getContentsWithLikes (array $contents): array
    {
        $contentsWithLikes = [];

        foreach ($contents as $id)
        {
            $numberOfLikes = 0;
            $likes = $this->contentLikeRepository->findBy(['contentId' => $id]);
            foreach ($likes as $like)
            {
                $numberOfLikes++;
            }

            $contentsWithLikes[] = ['id' => $id, 'likes' => $numberOfLikes];
        }

        return $contentsWithLikes;
    }
}
