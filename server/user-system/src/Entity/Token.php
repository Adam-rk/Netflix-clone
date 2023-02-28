<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Request;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
class Token
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 255)]
    private ?string $value = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\ManyToOne(inversedBy: 'token')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): self
    {

        $this->expiresAt = $expiresAt->modify('+24 hours');

        return $this;
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt !== null && new \DateTime() >= $this->expiresAt) {
            return true;
        }
        return false;
    }





    public function resetValue(): self
    {
        $tokenValue = uniqid() . bin2hex(random_bytes(5));
        $this->setValue($tokenValue);

        return $this;
    }

    public function resetExpiresAt(): self
    {
        $this->setExpiresAt(new \DateTime());
        return $this;
    }

    public function resetIpAddress($ipAddress): self
    {
        $this->setIpAddress($ipAddress);

        return $this;
    }


    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): self
    {
        $this->account = $account;

        return $this;
    }
}
