<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payments')]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'reservation_id')]
    private int $reservationId = 0;

    /** PENDING | SUCCESS | FAILED */
    #[ORM\Column(length: 20)]
    private string $status = 'PENDING';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amount = '0.00';

    /** e.g. flouci, stripe */
    #[ORM\Column(length: 50)]
    private string $gateway = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(name: 'failure_reason', type: Types::TEXT, nullable: true)]
    private ?string $failureReason = null;

    #[ORM\Column(name: 'attempted_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $attemptedAt;

    public function __construct()
    {
        $this->attemptedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getReservationId(): int { return $this->reservationId; }
    public function setReservationId(int $reservationId): self { $this->reservationId = $reservationId; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $amount): self { $this->amount = $amount; return $this; }

    public function getGateway(): string { return $this->gateway; }
    public function setGateway(string $gateway): self { $this->gateway = $gateway; return $this; }

    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $reference): self { $this->reference = $reference; return $this; }

    public function getFailureReason(): ?string { return $this->failureReason; }
    public function setFailureReason(?string $failureReason): self { $this->failureReason = $failureReason; return $this; }

    public function getAttemptedAt(): \DateTimeInterface { return $this->attemptedAt; }
    public function setAttemptedAt(\DateTimeInterface $attemptedAt): self { $this->attemptedAt = $attemptedAt; return $this; }

    public function isSuccess(): bool { return $this->status === 'SUCCESS'; }
    public function isFailed(): bool { return $this->status === 'FAILED'; }
}
