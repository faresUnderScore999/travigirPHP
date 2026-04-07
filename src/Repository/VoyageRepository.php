<?php

namespace App\Repository;

use App\Entity\Voyage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Voyage>
 */
class VoyageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voyage::class);
    }

    /** @return Voyage[] */
    public function findFeatured(int $limit = 3): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.createdAt', 'DESC')
            ->addOrderBy('v.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

/** @return Voyage[] */
public function findAllOrdered(): array
{
    return $this->createQueryBuilder('v')
        ->orderBy('v.startDate', 'ASC')
        ->addOrderBy('v.id', 'DESC')
        ->getQuery()
        ->getResult();
}

/**
 * Advanced search with filters
 * @return Voyage[]
 */
public function search(array $filters): array
{
    $qb = $this->createQueryBuilder('v');

    // Search by destination (partial match)
    if (!empty($filters['destination'])) {
        $qb->andWhere('v.destination LIKE :destination')
           ->setParameter('destination', '%' . $filters['destination'] . '%');
    }

    // Search by title (partial match)
    if (!empty($filters['title'])) {
        $qb->andWhere('v.title LIKE :title')
           ->setParameter('title', '%' . $filters['title'] . '%');
    }

    // Filter by price range
    if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
        $qb->andWhere('v.price >= :minPrice')
           ->setParameter('minPrice', (float) $filters['min_price']);
    }
    if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
        $qb->andWhere('v.price <= :maxPrice')
           ->setParameter('maxPrice', (float) $filters['max_price']);
    }

    // Filter by start date range
    if (!empty($filters['start_date_from'])) {
        $qb->andWhere('v.startDate >= :startDateFrom')
           ->setParameter('startDateFrom', new \DateTime($filters['start_date_from']));
    }
    if (!empty($filters['start_date_to'])) {
        $qb->andWhere('v.startDate <= :startDateTo')
           ->setParameter('startDateTo', new \DateTime($filters['start_date_to']));
    }

    // Filter by end date range
    if (!empty($filters['end_date_from'])) {
        $qb->andWhere('v.endDate >= :endDateFrom')
           ->setParameter('endDateFrom', new \DateTime($filters['end_date_from']));
    }
    if (!empty($filters['end_date_to'])) {
        $qb->andWhere('v.endDate <= :endDateTo')
           ->setParameter('endDateTo', new \DateTime($filters['end_date_to']));
    }

    // Sort by field
    $sortField = $filters['sort_by'] ?? 'startDate';
    $sortOrder = $filters['sort_order'] ?? 'ASC';
    
    // Validate sort field to prevent SQL injection
    $allowedSortFields = ['id', 'title', 'destination', 'startDate', 'endDate', 'price', 'createdAt'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'startDate';
    }
    $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
    
    $qb->orderBy('v.' . $sortField, $sortOrder);

    // Pagination
    if (isset($filters['limit'])) {
        $qb->setMaxResults((int) $filters['limit']);
    }
    if (isset($filters['offset'])) {
        $qb->setFirstResult((int) $filters['offset']);
    }

    return $qb->getQuery()->getResult();
}

/**
 * Count search results
 */
public function countSearch(array $filters): int
{
    $qb = $this->createQueryBuilder('v');
    $qb->select('COUNT(v.id)');

    // Search by destination (partial match)
    if (!empty($filters['destination'])) {
        $qb->andWhere('v.destination LIKE :destination')
           ->setParameter('destination', '%' . $filters['destination'] . '%');
    }

    // Search by title (partial match)
    if (!empty($filters['title'])) {
        $qb->andWhere('v.title LIKE :title')
           ->setParameter('title', '%' . $filters['title'] . '%');
    }

    // Filter by price range
    if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
        $qb->andWhere('v.price >= :minPrice')
           ->setParameter('minPrice', (float) $filters['min_price']);
    }
    if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
        $qb->andWhere('v.price <= :maxPrice')
           ->setParameter('maxPrice', (float) $filters['max_price']);
    }

    // Filter by start date range
    if (!empty($filters['start_date_from'])) {
        $qb->andWhere('v.startDate >= :startDateFrom')
           ->setParameter('startDateFrom', new \DateTime($filters['start_date_from']));
    }
    if (!empty($filters['start_date_to'])) {
        $qb->andWhere('v.startDate <= :startDateTo')
           ->setParameter('startDateTo', new \DateTime($filters['start_date_to']));
    }

    // Filter by end date range
    if (!empty($filters['end_date_from'])) {
        $qb->andWhere('v.endDate >= :endDateFrom')
           ->setParameter('endDateFrom', new \DateTime($filters['end_date_from']));
    }
    if (!empty($filters['end_date_to'])) {
        $qb->andWhere('v.endDate <= :endDateTo')
           ->setParameter('endDateTo', new \DateTime($filters['end_date_to']));
    }

    return (int) $qb->getQuery()->getSingleScalarResult();
}
}
