<?php

namespace App\Service;

use App\Entity\RefundRequest;
use App\Repository\RefundRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Service\AuthService;

class RefundRequestService
{
    public function __construct(
        private readonly RefundRequestRepository $refundRequestRepository,
        private readonly EntityManagerInterface $entityManager,
        /** @phpstan-ignore property.onlyWritten */
        private readonly AuthService $authService,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createRefundRequest(array $data): RefundRequest
    {
        $refundRequest = new RefundRequest();
        $refundRequest->setReclamationId($data['reclamation_id'] ?? 0);
        $refundRequest->setRequesterId($data['requester_id'] ?? 0);
        $refundRequest->setAmount($data['amount'] ?? '0.00');
        $refundRequest->setReason($data['reason'] ?? null);
        $refundRequest->setStatus('PENDING');
        $refundRequest->setCreatedAt(new \DateTime());

        $this->entityManager->persist($refundRequest);
        $this->entityManager->flush();

        return $refundRequest;
    }

    /**
     * Update refund request status
     */
    public function updateStatus(int $id, string $status): ?RefundRequest
    {
        $refundRequest = $this->refundRequestRepository->find($id);
        if (!$refundRequest) {
            return null;
        }

        $refundRequest->setStatus($status);
        $this->entityManager->flush();

        return $refundRequest;
    }

    /**
     * @return RefundRequest[]
     */
    public function getPendingRequests(): array
    {
        return $this->safeExecute(fn () => $this->refundRequestRepository->findPendingRequests(), []);
    }

    /**
     * @return RefundRequest[]
     */
    public function getRequestsByRequester(int $requesterId): array
    {
        return $this->safeExecute(fn () => $this->refundRequestRepository->findByRequesterId($requesterId), []);
    }

    /**
     * Get total pending refund amount
     */
    public function getTotalPendingAmount(): float
    {
        return $this->safeExecute(fn () => $this->refundRequestRepository->getTotalPendingAmount(), 0.0);
    }

    /**
     * Count pending requests
     */
    public function countPendingRequests(): int
    {
        return $this->safeExecute(fn () => $this->refundRequestRepository->countPendingRequests(), 0);
    }

    /**
     * Safely execute a callback with error handling
     */
    private function safeExecute(callable $callback, mixed $default = []): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            $this->logger?->error('RefundRequestService error', ['error' => $e->getMessage()]);
            return $default;
        }
    }
}