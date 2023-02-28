<?php

namespace App\Entity;

use App\Repository\TimeStampRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TimeStampRepository::class)]
class TimeStamp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'resumeContents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Content $content = null;

    #[ORM\Column]
    private ?int $accountId = null;

    #[ORM\Column(length: 255)]
    private ?string $timeStamp = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?Content
    {
        return $this->content;
    }

    public function setContent(?Content $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function setAccountId(int $accountId): self
    {
        $this->accountId = $accountId;

        return $this;
    }

    public function getTimeStamp(): ?string
    {
        return $this->timeStamp;
    }

    public function setTimeStamp(string $timeStamp): self
    {
        $this->timeStamp = $timeStamp;

        return $this;
    }
}
