<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'payment_transactions')]
#[ORM\Index(name: 'idx_pt_flouci_id', columns: ['flouci_payment_id'])]
#[ORM\Index(name: 'idx_pt_reservation', columns: ['reservation_id'])]
#[ORM\Index(name: 'idx_pt_user', columns: ['user_id'])]
class PaymentTransaction
{
    public const STATUS_INITIATED = 'INITIATED';
    public const STATUS_SUCCESS   = 'SUCCESS';
    public const STATUS_FAILED    = 'FAILED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'reservation_id')]
    private int $reservationId;

    #[ORM\Column(name: 'user_id')]
    private int $userId;

    /** Set after Flouci API responds with a payment link */
    #[ORM\Column(name: 'flouci_payment_id', length: 200, nullable: true, unique: true)]
    private ?string $flouciPaymentId = null;

    #[ORM\Column(name: 'amount_millimes', type: Types::INTEGER)]
    private int $amountMillimes;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_INITIATED;

    #[ORM\Column(name: 'ip_address', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    public function getId(): ?int { return $this->id; }

    public function getReservationId(): int { return $this->reservationId; }
    public function setReservationId(int $v): self { $this->reservationId = $v; return $this; }

    public function getUserId(): int { return $this->userId; }
    public function setUserId(int $v): self { $this->userId = $v; return $this; }

    public function getFlouciPaymentId(): ?string { return $this->flouciPaymentId; }
    public function setFlouciPaymentId(?string $v): self { $this->flouciPaymentId = $v; return $this; }

    public function getAmountMillimes(): int { return $this->amountMillimes; }
    public function setAmountMillimes(int $v): self { $this->amountMillimes = $v; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): self { $this->status = $v; return $this; }

    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function setIpAddress(?string $v): self { $this->ipAddress = $v; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $v): self { $this->createdAt = $v; return $this; }

    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeInterface $v): self { $this->updatedAt = $v; return $this; }
}
