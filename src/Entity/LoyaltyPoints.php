<?php

namespace App\Entity;

use App\Repository\LoyaltyPointsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoyaltyPointsRepository::class)]
#[ORM\Table(name: 'loyalty_points')]
#[ORM\HasLifecycleCallbacks]
class LoyaltyPoints
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', unique: true)]
    private int $userId = 0;

    #[ORM\Column(name: 'points_balance')]
    private int $pointsBalance = 0;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: 'string', length: 64)]
    private string $signature = '';

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUserId(): int { return $this->userId; }
    public function setUserId(int $userId): self { $this->userId = $userId; return $this; }

    public function getPointsBalance(): int { return $this->pointsBalance; }
    public function setPointsBalance(int $pointsBalance): self { $this->pointsBalance = $pointsBalance; return $this; }

    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeInterface $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    public function getSignature(): string { return $this->signature; }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function computeSignature(): void
    {
        $pepper = $_ENV['LOYALTY_PEPPER'] ?? '';
        $this->signature = hash('sha256', $this->userId . '|' . $this->pointsBalance . '|' . $pepper);
    }

    public function isSignatureValid(): bool
    {
        $pepper = $_ENV['LOYALTY_PEPPER'] ?? '';
        $expected = hash('sha256', $this->userId . '|' . $this->pointsBalance . '|' . $pepper);
        return hash_equals($expected, $this->signature);
    }
}
