<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/category', name: 'app_category_')]
class CategoryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CategoryRepository $categoryRepository
    )
    {
        $this->categoryRepository = $this->em->getRepository(Category::class);

    }

    #[Route('/create', name: 'create')]
    public function create(Request $request): JsonResponse
    {
        $label = json_decode($request->getContent(), true)['label'];
        if (null === $this->categoryRepository->findOneBy(['label' => $label])) {
            $category = new Category();
            $category->setLabel($label);
            $this->em->persist($category);
            $this->em->flush();

            return $this->json(['message' => 'Category created'], RESPONSE::HTTP_OK);

        }

        throw new \Error('Category already exist', Response::HTTP_CONFLICT);
        //return $this->json(['message' => 'Category already exist'], RESPONSE::HTTP_CONFLICT);

    }

    #[Route('/delete', name: 'delete')]
    public function delete(Request $request): JsonResponse
    {
        $label = json_decode($request->getContent(), true)['label'];
        $category = $this->categoryRepository->findOneBy(['label' => $label]);

        if (null === $category) {

            throw new \Error('Category does not exist', Response::HTTP_NOT_FOUND);
            //return $this->json(['message' => 'Category does not exist'], RESPONSE::HTTP_NOT_FOUND);

        }

        $this->em->remove($category);
        $this->em->flush();

        return $this->json(['message' => 'Category deleted'], RESPONSE::HTTP_OK);

    }

    #[Route('/update', name: 'update')]
    public function update(Request $request)
    {
        $oldLabel = json_decode($request->getContent(), true)['oldLabel'];
        $newLabel = json_decode($request->getContent(), true)['newLabel'];

        $category = $this->categoryRepository->findOneBy(['label' => $oldLabel]);

        if (null === $category)
        {
            throw new \Error('Category does not exist', Response::HTTP_NOT_FOUND);
        }

        if ($oldLabel === $newLabel) {
            throw new \Error('Cannot set the same label', Response::HTTP_UNAUTHORIZED);
        }

        $category->setLabel($newLabel);
        $this->em->flush();

        return new JsonResponse(['message' => 'Category updated'], Response::HTTP_OK);
    }

    #[Route('/showall', name: 'showall')]
    public function showAll(Request $request)
    {


        $categories = $this->categoryRepository->findAll();

        $jsonCategories = [];

        foreach ($categories as $category) {

            $jsonCategories[] = (array)$category;

        }
        return new JsonResponse($jsonCategories);
    }

    #[Route('/show', name: 'show')]
    public function show(Request $request)
    {
        $label = json_decode($request->getContent(), true)['label'];


        $category = $this->categoryRepository->findOneBy(['label' => $label]);


        if (null === $category) {

            throw new \Error('Category not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse((array)$category);
    }

}
