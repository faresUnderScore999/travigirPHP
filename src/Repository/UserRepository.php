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
    // src/Repository/UserRepository.php

// Add these methods inside the class

    /**
     * @return array{users: User[], total: int}
     */
    public function searchPaginated(array $filters, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('u');

        // Apply filters
        $this->applyUserFilters($qb, $filters);

        // Get total count before pagination
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Apply sorting
        $this->applyUserSorting($qb, $filters);

        // Apply pagination
        $qb->setMaxResults($limit)
            ->setFirstResult($offset);

        $users = $qb->getQuery()->getResult();

        return ['users' => $users, 'total' => $total];
    }

    private function applyUserFilters(QueryBuilder $qb, array $filters): void
    {
        // Search: match username OR email (partial)
        if (!empty($filters['search'])) {
            $qb->andWhere('u.username LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

   
    }

    // In src/Repository/UserRepository.php

    private const ALLOWED_SORT_FIELDS = ['createdAt', 'username', 'email']; // ← use 'createdAt' not 'created_at'

    private function applyUserSorting(QueryBuilder $qb, array $filters): void
    {
        $sortField = $filters['sort_by'] ?? 'createdAt';   // ← use 'createdAt'
        $sortOrder = strtoupper($filters['sort_order'] ?? 'DESC');

        // Allowed fields - match property names
        $allowed = ['createdAt', 'username', 'email'];
        if (!in_array($sortField, $allowed, true)) {
            $sortField = 'createdAt';
        }
        $sortOrder = $sortOrder === 'ASC' ? 'ASC' : 'DESC';

        $qb->orderBy('u.' . $sortField, $sortOrder);  // ← now 'u.createdAt' is correct
    }
}
