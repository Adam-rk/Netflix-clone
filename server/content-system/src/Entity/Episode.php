<?php

namespace App\Entity;

use App\Repository\EpisodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EpisodeRepository::class)]
class Episode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $season = null;

    #[ORM\Column]
    private ?int $episodeNumber = null;

    #[ORM\OneToOne(mappedBy: 'episode', cascade: ['persist', 'remove'])]
    private ?Content $content = null;

    #[ORM\Column(length: 255)]
    private ?string $seriesTitle = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeason(): ?int
    {
        return $this->season;
    }

    public function setSeason(int $season): self
    {
        $this->season = $season;

        return $this;
    }

    public function getEpisodeNumber(): ?int
    {
        return $this->episodeNumber;
    }

    public function setEpisodeNumber(int $episodeNumber): self
    {
        $this->episodeNumber = $episodeNumber;

        return $this;
    }

    public function getContent(): ?Content
    {
        return $this->content;
    }

    public function setContent(?Content $content): self
    {
        // unset the owning side of the relation if necessary
        if ($content === null && $this->content !== null) {
            $this->content->setEpisode(null);
        }

        // set the owning side of the relation if necessary
        if ($content !== null && $content->getEpisode() !== $this) {
            $content->setEpisode($this);
        }

        $this->content = $content;

        return $this;
    }

    public function getSeriesTitle(): ?string
    {
        return $this->seriesTitle;
    }

    public function setSeriesTitle(string $seriesTitle): self
    {
        $this->seriesTitle = $seriesTitle;

        return $this;
    }
}
