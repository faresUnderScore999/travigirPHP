<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Payment> */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /** @return Payment[] */
    public function findByReservationId(int $reservationId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.reservationId = :rid')
            ->setParameter('rid', $reservationId)
            ->orderBy('p.attemptedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findSuccessfulByReservationId(int $reservationId): ?Payment
    {
        return $this->findOneBy(['reservationId' => $reservationId, 'status' => 'SUCCESS']);
    }

    public function countAttemptsByReservationId(int $reservationId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.reservationId = :rid')
            ->setParameter('rid', $reservationId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
