<?php

namespace App\Service;

use App\Entity\Association;
use App\Repository\AssociationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AssociationService
{
    public function __construct(
        private readonly AssociationRepository $associationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createAssociation(array $data): Association
    {
        $association = new Association();
        $association->setName($data['name'] ?? '');
        $association->setCompanyCode($data['company_code'] ?? '');
        $association->setDiscountRate($data['discount_rate'] ?? '0.00');

        $this->entityManager->persist($association);
        $this->entityManager->flush();

        return $association;
    }

    /**
     * Get association by company code
     */
    public function getByCompanyCode(string $companyCode): ?Association
    {
        return $this->safeExecute(fn () => $this->associationRepository->findByCompanyCode($companyCode));
    }

    /**
     * @return Association[]
     */
    public function getAllAssociations(): array
    {
        return $this->safeExecute(fn () => $this->associationRepository->findAllOrdered(), []);
    }

    /**
     * @return Association[]
     */
    public function getAssociationsWithDiscount(): array
    {
        return $this->safeExecute(fn () => $this->associationRepository->findWithDiscount(), []);
    }

    /**
     * Safely execute a callback with error handling
     */
    private function safeExecute(callable $callback, mixed $default = []): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            $this->logger?->error('AssociationService error', ['error' => $e->getMessage()]);
            return $default;
        }
    }
}