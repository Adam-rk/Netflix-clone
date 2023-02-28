<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Content;
use App\Entity\Episode;
use App\Entity\TimeStamp;
use App\Entity\Tag;
use App\Repository\CategoryRepository;
use App\Repository\ContentRepository;
use App\Repository\EpisodeRepository;
use App\Repository\TagRepository;
use App\Repository\TimeStampRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/content', name: 'app_content_')]
class ContentController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $em,
        private CategoryRepository     $categoryRepository,
        private TagRepository          $tagRepository,
        private ContentRepository      $contentRepository,
        private EpisodeRepository $episodeRepository,
        private TimeStampRepository $timeStampRepository

    )
    {
        $this->categoryRepository = $this->em->getRepository(Category::class);
        $this->tagRepository = $this->em->getRepository(Tag::class);
        $this->contentRepository = $this->em->getRepository(Content::class);
        $this->episodeRepository = $this->em->getRepository(Episode::class);
        $this->timeStampRepository = $this->em->getRepository(TimeStamp::class);
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);


        $content = new Content();

        $content
            ->setTitle($data['title'])
            ->setType($data['contentType'])
            ->setFilePath($data['fileServerPath'])
            ->setCoverPath($data['coverServerPath'])
            ->setCreatorUsername($data['creatorUsername'])
            ->setStudio($data['studio'])
            ->setDescription($data['description'])
            ->setDuration(new \DateTime($data['duration']))
            ->setRegulation($data['regulation'])
            ->setLanguage($data['language'])
            ->setCreationDate(new \DateTime());

        $categoryLabel = $data['categoryLabel'];

        $this->setCategoryToContent($categoryLabel, $content);

        $tags = $data['tags'];

        $this->setTagsToContent($tags, $content);

        if ('series' === $data['contentType'])
        {
            $this->createEpisode($content, $data['seriesTitle'], $data['episodeNumber'], $data['season']);
        }


        $this->em->persist($content);
        $this->em->flush();

        return new JsonResponse(['message' => 'Content created'], Response::HTTP_OK);
    }

    #[Route('/update', name: 'update')]
    public function update(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $content = $this->contentRepository->findOneBy(['id' => $data['contentId']]);


        foreach ($data as $key => $value) {
            switch ($key) {
                case 'title':
                    $content->setTitle($value);
                    break;
                case 'studio':
                    $content->setStudio($value);
                    break;
                case 'description':
                    $content->setDescription($value);
                    break;
                case 'regulation':
                    $content->setRegulation($value);
                    break;
                case 'language':
                    $content->setLanguage($value);
                    break;
                case 'fileServerPath':
                    $content->setFilePath($value);
                    break;

                case 'coverServerPath':
                    $content->setCoverPath($value);
                    break;
                case 'categoryLabel' :
                    $oldCategory = $content->getCategory();
                    $this->setCategoryToContent($value, $content);
                    $this->deleteCategory($oldCategory, $content);
                    break;
                case 'tags':
                    $this->setTagsToContent($value, $content);
                    $oldTags = $this->getOldTags($content);
                    $this->deleteTags($oldTags, $content);
                    break;
                case'duration':
                    $content->setDuration(DateTime::createFromFormat('H:i:s', $value));
                    break;
                case 'seriesTitle':
                    $this->updateEpisode($content->getEpisode(), 'seriesTitle', $value);
                     break;
                case 'episodeNumber':
                    $this->updateEpisode($content->getEpisode(), 'episodeNumber', $value);
                    break;
                case 'season':
                    $this->updateEpisode($content->getEpisode(), 'season', $value);
                    break;
                default:
                    break;
            }
        }

        $this->em->persist($content);
        $this->em->flush();
        return new JsonResponse(['message' => 'Content updated'], Response::HTTP_OK);
    }

    private function createEpisode(Content $content, $seriesTitle, $episodeNumber, $season) {
        $episode = new Episode();

        $episode
            ->setContent($content)
            ->setSeriesTitle($seriesTitle)
            ->setEpisodeNumber($episodeNumber)
            ->setSeason($season);

        $this->em->persist($episode);
    }

    private function setCategoryToContent(string $categoryLabel, Content $content): void
    {
        $category = $this->categoryRepository->findOneBy(['label' => $categoryLabel]);

        if (null === $category) {

            $category = new Category();
            $category->setLabel($categoryLabel);
            $this->em->persist($category);
        }

        $content->setCategory($category);
        $this->em->flush();
    }

    private function deleteCategory(Category $oldCategory, Content $content): void
    {
        if ($oldCategory->getLabel() !== $content->getCategory()->getLabel())
        {
            $oldCategory->removeContent($content);
            if (empty($oldCategory->getContents()->toArray())) {
                $this->em->remove($oldCategory);
            }
        }

    }

    private function setTagsToContent(string $tags, Content $content): void
    {
        $tags = explode(" ", $tags);

        foreach ($tags as $tagLabel) {
            $tag = $this->tagRepository->findOneBy(['label' => $tagLabel]);

            if (null === $tag) {

                $tag = new Tag();
                $tag->setLabel($tagLabel);
            }
            $this->em->persist($tag);
            $content->addTag($tag);
        }
    }
    private function getOldTags(Content $content)
    {
        $tags = $content->getTag();
        return $tags->toArray();
    }

    private function deleteTags(array $tags, Content $content): void
    {
        foreach ($tags as $tag) {
            $tag->removeContent($content);
            if (null === $tag->getContents()) {
                $this->em->remove($tag);
            }
        }
    }

    #[Route('/delete/{contentId}', name: 'delete')]
    public function delete($contentId): JsonResponse
    {

        $content = $this->contentRepository->findOneBy(['id' => $contentId]);

        if (null === $content) {
            return new JsonResponse([
                'error' => 'The content does not exist'
            ]);

        }

        $this->contentRepository->remove($content);

        $this->em->flush();

        return new JsonResponse([
            'success' => 'The content has been deleted'
        ]);
    }

    #[Route('/show/{contentId}/{accountId}', name: 'show')]
    public function show($contentId, $accountId): JsonResponse
    {

        $content = $this->contentRepository->findOneBy(['id' => $contentId]);

        if (null === $content)
        {
            return new JsonResponse(['message' => 'Content not found'], Response::HTTP_NOT_FOUND);
        }

        $content = $this->makeContentArray($content, $accountId);

        return new JsonResponse(['content' => $content]);
    }

    #[Route('/show-by-title/{title}', name: 'show_by_title')]
    public function showByTitle($title): JsonResponse
    {
        $contents = $this->contentRepository->findContentsByTitle($title);

        $jsonContents = [];

        foreach ($contents as $content) {
            $jsonContents[] = [
                "id" => $content->getId(),
                "cover" => $content->getCoverPath()
            ];
        }
        return new JsonResponse($jsonContents);
    }

    #[Route('/show-all-by-category/{category}', name: 'show_all_by_category')]
    public function showAllByCategory($category): JsonResponse
    {

        $contents = $this->categoryRepository->findOneBy(['label' => $category])->getContents();

        $jsonContents = [];

        foreach ($contents as $content) {
            $jsonContents[] = $content->getId();
        }
        return new JsonResponse($jsonContents);
    }
    private function makeContentArray(Content $content, int $accountId): array
    {
        $tags = $this->getTagsLabel($content);
        $category = $content->getCategory()->getLabel();



        $contentArray = (array)$content;

        if ("series" === $content->getType()){
            $episode = $this->getEpisodeData($content);
            $contentArray["\x00App\Entity\Content\x00episode"] = $episode;
        }

        if (0 !== $accountId)
        {
            $timeStamp = $this->timeStampRepository->findOneBy([
                'content' => $content,
                'accountId' => $accountId
            ]);

            if (null !== $timeStamp)
            {
                $contentArray["timeStamp"] = $timeStamp->getTimeStamp();
            }
        }

        $contentArray["\x00App\Entity\Content\x00tag"] = $tags;
        $contentArray["\x00App\Entity\Content\x00category"] = $category;
        unset($contentArray["\x00App\Entity\Content\x00timeStamps"]);

        return $contentArray;
    }

    private function getTagsLabel(Content $content): array
    {
        $tags = $content->getTag()->toArray();

        $cleanArray = [];

        foreach ($tags as $tag)
        {
            $cleanArray[] = $tag->getLabel();
        }

        return $cleanArray;
    }
    private function getEpisodeData(Content $content): ?array
    {
        $episode = $content->getEpisode();

        $episodeArray = [];
        $episodeArray['seriesTitle'] = $episode->getSeriesTitle();
        $episodeArray['episodeNumber'] = $episode->getEpisodeNumber();
        $episodeArray['season'] = $episode->getSeason();

        return $episodeArray;
    }

    private function updateEpisode(?Episode $episode, string $string, $value)
    {
        if (null !== $episode)
        {
            match ($string) {
                'seriesTitle' => $episode->setSeriesTitle($value),
                'episodeNumber' => $episode->setEpisodeNumber($value),
                'season' => $episode->setSeason($value)
            };
        }
    }
}
