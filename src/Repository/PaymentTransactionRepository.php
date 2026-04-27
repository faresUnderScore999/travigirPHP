<?php

namespace App\Repository;

use App\Entity\PaymentTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaymentTransaction>
 */
class PaymentTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentTransaction::class);
    }

    /**
     * Security-critical lookup: find a transaction only when all three identifiers match.
     * Prevents a user from reusing a payment_id across different reservations.
     */
    public function findByPaymentIdAndReservation(
        string $flouciPaymentId,
        int $reservationId,
        int $userId,
    ): ?PaymentTransaction {
        return $this->createQueryBuilder('t')
            ->andWhere('t.flouciPaymentId = :pid')
            ->andWhere('t.reservationId  = :rid')
            ->andWhere('t.userId         = :uid')
            ->setParameter('pid', $flouciPaymentId)
            ->setParameter('rid', $reservationId)
            ->setParameter('uid', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return PaymentTransaction[] */
    public function findByReservation(int $reservationId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.reservationId = :rid')
            ->setParameter('rid', $reservationId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
