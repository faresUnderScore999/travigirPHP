<?php

namespace App\Service;

use App\Entity\Reclamation;
use App\Repository\ReclamationRepository;
use App\Service\AuthService;
use App\Service\TwilioService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ReclamationService
{
    public function __construct(
        private readonly ReclamationRepository $reclamationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthService $authService,
        private readonly TwilioService $twilioService,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createReclamation(array $data): Reclamation
    {
        $reclamation = new Reclamation();
        $reclamation->setReservationId($data['reservation_id'] ?? 0);
        $reclamation->setUserId($data['user_id'] ?? 0);
        $reclamation->setTitle($data['title'] ?? '');
        $reclamation->setDescription($data['description'] ?? '');
        $reclamation->setStatus($data['status'] ?? 'OPEN');
        $reclamation->setPriority($data['priority'] ?? 'MEDIUM');
        $reclamation->setReclamationDate(new \DateTime());
        $reclamation->setCreatedAt(new \DateTime());
        $reclamation->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($reclamation);
        $this->entityManager->flush();

        return $reclamation;
    }

    /**
     * Update reclamation status
     */
    public function updateStatus(int $id, string $status): ?Reclamation
    {
        $reclamation = $this->reclamationRepository->find($id);
        if (!$reclamation) {
            return null;
        }

        $reclamation->setStatus($status);
        $reclamation->setUpdatedAt(new \DateTime());

        if ($status === 'RESOLVED' || $status === 'CLOSED') {
            $reclamation->setResolutionDate(new \DateTime());
        }

        $this->entityManager->flush();

        $this->twilioService->notifyReclamationStatus($reclamation->getUserId(), $status, $reclamation->getId() ?? 0);

        return $reclamation;
    }

    /**
     * Add admin response to reclamation
     */
    public function addResponse(int $id, string $response): ?Reclamation
    {
        $reclamation = $this->reclamationRepository->find($id);
        if (!$reclamation) {
            return null;
        }

        $reclamation->setAdminResponse($response);
        $reclamation->setResponseDate(new \DateTime());
        $reclamation->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();
        return $reclamation;
    }
    /**
     * @return array{data: array<int, \App\Entity\Reclamation>, totalItems: int, totalPages: int, currentPage: int, limit: int}
     */
    public function getPaginatedReclamations(int $page, int $limit, ?string $email = null): array
{
    return $this->safeExecute(function() use ($page, $limit, $email) {
        $userId = null;
        
        // If an email is provided, try to find the user ID
        if ($email) {
            $user = $this->authService->getUserByEmail($email);
            $userId = $user ? $user['id'] : -1;
        }

        $result = $this->reclamationRepository->findPaginated($page, $limit, $userId);
        
        // Count total items
        $countQb = $this->reclamationRepository->createQueryBuilder('r');
        if ($userId !== null) {
            $countQb->andWhere('r.userId = :userId')
               ->setParameter('userId', $userId);
        }
        $totalItems = (int) $countQb->select('COUNT(r.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();
        
        return [
            'data' => $result,
            'totalItems' => $totalItems,
            'totalPages' => (int) ceil($totalItems / $limit),
            'currentPage' => $page,
            'limit' => $limit,
        ];
    }, [
        'data' => [], 'totalItems' => 0, 'totalPages' => 0, 
        'currentPage' => $page, 'limit' => $limit
    ]);
}
    /**
     * @return Reclamation[]
     */
    public function getOpenReclamations(): array
    {
        return $this->safeExecute(fn () => $this->reclamationRepository->findOpenReclamations(), []);
    }

    /**
     * @return Reclamation[]
     */
    public function getUrgentReclamations(): array
    {
        return $this->safeExecute(fn () => $this->reclamationRepository->findUrgentReclamations(), []);
    }

    /**
     * @return Reclamation[]
     */
    public function getReclamationsByUser(int $userId): array
    {
        return $this->safeExecute(fn () => $this->reclamationRepository->findByUserId($userId), []);
    }

    /**
     * Get reclamation by ID
     */
    public function getReclamationById(int $id): ?Reclamation
    {
        return $this->safeExecute(fn () => $this->reclamationRepository->find($id));
    }

    /**
     * Count open reclamations
     */
    public function countOpenReclamations(): int
    {
        return $this->safeExecute(fn () => $this->reclamationRepository->countOpenReclamations(), 0);
    }

    /**
     * Safely execute a callback with error handling
     */
    private function safeExecute(callable $callback, mixed $default = []): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            $this->logger?->error('ReclamationService error', ['error' => $e->getMessage()]);
            return $default;
        }
    }
}