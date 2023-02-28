<?php

namespace App\Controller;

use App\Entity\Content;
use App\Entity\TimeStamp;
use App\Repository\ContentRepository;
use App\Repository\TimeStampRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
#[Route('/time-stamp', name: 'app_time_stamp_')]
class TimeStampController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TimeStampRepository    $resumeContentRepository,
        private ContentRepository      $contentRepository
    )
    {
        $this->resumeContentRepository = $this->em->getRepository(TimeStamp::class);
        $this->contentRepository = $this->em->getRepository(Content::class);
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $contentId = $data['contentId'];
        $accountId = $data['accountId'];
        $timeStamp = $data['timeStamp'];

        $content = $this->contentRepository->findOneBy(['id' => $contentId]);

        $resumeContent = $this->resumeContentRepository->findOneBy([
            'content' => $content,
            'accountId' => $accountId
        ]);

        if (null === $resumeContent)
        {
            $resumeContent = new TimeStamp();

            $resumeContent
                ->setContent($content)
                ->setAccountId($accountId);
        }
        $resumeContent->setTimeStamp($timeStamp);

        $this->em->persist($resumeContent);
        $this->em->flush();

        return new JsonResponse(['message' => 'Timestamp saved']);
    }

    #[Route('/delete/{contentId}/{accountId}', name: 'delete')]
    public function delete($contentId, $accountId): JsonResponse
    {
        $content = $this->contentRepository->findOneBy(['id' => $contentId]);

        $resumeContent = $this->resumeContentRepository->findOneBy([
            'content' => $content,
            'accountId' => $accountId
        ]);

        if (null === $resumeContent)
        {
            throw new HttpException(Response::HTTP_NOT_FOUND, "Timestamp not found");
        }

        $this->em->remove($resumeContent);
        $this->em->flush();

        return new JsonResponse(['message' => 'Timestamp deleted']);
    }
}
