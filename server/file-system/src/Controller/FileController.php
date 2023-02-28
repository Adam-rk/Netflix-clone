<?php

namespace App\Controller;


use App\Entity\File;
use App\Repository\FileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/file', name: 'app_file_')]
class FileController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $em,
        private HttpClientInterface    $client,
        private FileRepository $fileRepository
    )
    {
        $this->fileRepository = $this->em->getRepository(File::class);
    }


    #[Route('/create', name: 'create')]
    public function create(Request $request): JsonResponse
    {

        $fileSystem = new Filesystem();

        $localFile = $request->files->get('file');

        $localName = $localFile->getClientOriginalName();

        $file = new File();

            $extension = pathinfo($localName, PATHINFO_EXTENSION);

            $serverName = hash('md5', $localName . $extension);

            $serverPath = './server/files/' . $serverName . '.' . $extension;

            $relativePath = '../../files/' . $serverName . '.' . $extension;

            $file
                ->setName($serverName)
                ->setExtension($extension)
                ->setCreationDate(new \DateTime())
                ->setServerPath($serverPath)
                ->setRelativePath($relativePath);

            $fileInDb = $this->fileRepository->findOneBy(['name' => $file->getName()]);

            if (null === $fileInDb) {
                $this->em->persist($file);

                $this->em->flush();

                $fileSystem->copy($localFile, $relativePath);

                return $this->json([
                    'success' => 'The file has been added',
                    'serverPath' => $serverPath,
                    'relativePath' => $relativePath
                ], Response::HTTP_OK);
            }

            return $this->json(['error' => 'The file is already in the database'], Response::HTTP_CONFLICT);
    }

    #[Route('/delete', name: 'delete')]
    public function delete(Request $request): JsonResponse
    {
        $fileSystem = new Filesystem();


        $serverPath = json_decode($request->getContent(), true)['serverPath'];

        $file = $this->fileRepository->findOneBy(['serverPath' => $serverPath]);

        if (null === $file) {
            return $this->json([
                'message' => 'The file does not exist'
            ], Response::HTTP_NOT_FOUND);
        }

        $fileSystem->remove('../../files/' . $file->getName() . '.' . $file->getExtension());

        $this->fileRepository->remove($file);

        $this->em->flush();

        return $this->json([
            'message' => 'The file has been deleted'
        ], Response::HTTP_OK);
    }

    #[Route('/show', name: 'show')]
    public function show(Request $request)
    {
        $name = json_decode($request->getContent(), true)['name'];


        $file = $this->fileRepository->findOneBy(['name' => $name]);

        return $this->json(['file' => (array)$file]);
    }


    #[Route('/showall', name: 'showall')]
    public function showAll(): JsonResponse
    {

        $files = $this->fileRepository->findAll();

        $jsonFiles = [];

        foreach ($files as $file) {

            $jsonFiles[] = (array)$file;

        }
        return $this->json(['files' => $jsonFiles]);
    }
}
