<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
#[Route('/tag', name: 'app_tag_')]
class TagController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TagRepository $tagRepository
    )
    {
        $this->tagRepository = $this->em->getRepository(Tag::class);
    }

    #[Route('/show', name: 'show')]
    public function show(Request $request)
    {
        $contentId = json_decode($request->getContent(), true)['contentId'];




        $tag = $this->tagRepository->findOneBy(['label' => $label]);


        if (null === $tag) {

            throw new \Error('Tag not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse((array)$tag);
    }
}
