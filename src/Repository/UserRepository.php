<?php

namespace App\Repository;

use Doctrine\ORM\QueryBuilder;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', strtolower(trim($email)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function findById(int $id): ?User
    {
        return $this->find($id);
    }

    /**
     * @param int[] $ids
     * @return User[]
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        return $this->createQueryBuilder('u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{users: User[], total: int}
     */
    public function searchPaginated(array $filters, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('u');

        $this->applyUserFilters($qb, $filters);

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $this->applyUserSorting($qb, $filters);

        $qb->setMaxResults($limit)
            ->setFirstResult($offset);

        $users = $qb->getQuery()->getResult();

        return ['users' => $users, 'total' => $total];
    }

    /** @param array<string, mixed> $filters */
    private function applyUserFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['search'])) {
            $qb->andWhere('u.username LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }
    }

    /** @param array<string, mixed> $filters */
    private function applyUserSorting(QueryBuilder $qb, array $filters): void
    {
        $sortField = $filters['sort_by'] ?? 'createdAt';
        $sortOrder = strtoupper($filters['sort_order'] ?? 'DESC');

        $allowed = ['createdAt', 'username', 'email'];
        if (!in_array($sortField, $allowed, true)) {
            $sortField = 'createdAt';
        }
        $sortOrder = $sortOrder === 'ASC' ? 'ASC' : 'DESC';

        $qb->orderBy('u.' . $sortField, $sortOrder);
    }
}
