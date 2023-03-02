<?php

namespace App\Entity;

use App\Repository\ContentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContentRepository::class)]
class Content
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'contents')]
    private ?Category $category = null;

    #[ORM\OneToOne(inversedBy: 'content', cascade: ['persist', 'remove'])]
    private ?Episode $episode = null;

    #[ORM\Column(length: 255)]
    private ?string $studio = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $duration = null;

    #[ORM\Column(length: 255)]
    private ?string $regulation = null;

    #[ORM\Column(length: 255)]
    private ?string $language = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'contents')]
    private Collection $tag;

    #[ORM\Column(length: 255)]
    private ?string $filePath = null;

    #[ORM\Column(length: 255)]
    private ?string $creatorUsername = null;

    #[ORM\Column(length: 255)]
    private ?string $coverPath = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'content', targetEntity: TimeStamp::class, orphanRemoval: true)]
    private Collection $timeStamps;

    public function __construct()
    {
        $this->tag = new ArrayCollection();
        $this->timeStamps = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getEpisode(): ?Episode
    {
        return $this->episode;
    }

    public function setEpisode(?Episode $episode): self
    {
        $this->episode = $episode;

        return $this;
    }

    public function getStudio(): ?string
    {
        return $this->studio;
    }

    public function setStudio(string $studio): self
    {
        $this->studio = $studio;

        return $this;
    }

    public function getDuration(): ?\DateTimeInterface
    {
        return $this->duration;
    }

    public function setDuration(\DateTimeInterface $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getRegulation(): ?string
    {
        return $this->regulation;
    }

    public function setRegulation(string $regulation): self
    {
        $this->regulation = $regulation;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeInterface $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTag(): Collection
    {
        return $this->tag;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tag->contains($tag)) {
            $this->tag->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        $this->tag->removeElement($tag);

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): self
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getCreatorUsername(): ?string
    {
        return $this->creatorUsername;
    }

    public function setCreatorUsername(string $creatorUsername): self
    {
        $this->creatorUsername = $creatorUsername;

        return $this;
    }

    public function getCoverPath(): ?string
    {
        return $this->coverPath;
    }

    public function setCoverPath(string $coverPath): self
    {
        $this->coverPath = $coverPath;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, TimeStamp>
     */
    public function getTimeStamps(): Collection
    {
        return $this->timeStamps;
    }

    public function addResumeContent(TimeStamp $resumeContent): self
    {
        if (!$this->timeStamps->contains($resumeContent)) {
            $this->timeStamps->add($resumeContent);
            $resumeContent->setContent($this);
        }

        return $this;
    }

    public function removeResumeContent(TimeStamp $resumeContent): self
    {
        if ($this->timeStamps->removeElement($resumeContent)) {
            // set the owning side to null (unless already changed)
            if ($resumeContent->getContent() === $this) {
                $resumeContent->setContent(null);
            }
        }

        return $this;
    }
}
