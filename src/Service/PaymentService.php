<?php

namespace App\Service;

use App\Entity\PaymentTransaction;
use App\Repository\PaymentTransactionRepository;
use Psr\Log\LoggerInterface;

class PaymentService
{
    public function __construct(
        private readonly FlouciService $flouciService,
        private readonly ReservationService $reservationService,
        private readonly PaymentTransactionRepository $transactionRepo,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Initialize a Flouci payment session for a reservation.
     *
     * @return string The Flouci redirect URL
     * @throws \LogicException  When the reservation is already paid or cancelled
     * @throws \RuntimeException When the Flouci API rejects the request
     */
    public function initiate(
        int $reservationId,
        int $userId,
        string $clientIp,
        string $successUrl,
        string $failUrl,
    ): string {
        $reservation = $this->reservationService->getReservationById($reservationId, $userId);

        if (!$reservation) {
            throw new \InvalidArgumentException('Reservation not found or access denied.');
        }

        if ($reservation['payment_status'] === 'PAID') {
            throw new \LogicException('This reservation is already paid.');
        }

        if ($reservation['status'] === 'CANCELLED') {
            throw new \LogicException('Cannot pay for a cancelled reservation.');
        }

        $amountMillimes = (int) round((float) $reservation['total_price'] * 1000);

        $em = $this->transactionRepo->getEntityManager();

        $transaction = (new PaymentTransaction())
            ->setReservationId($reservationId)
            ->setUserId($userId)
            ->setAmountMillimes($amountMillimes)
            ->setStatus(PaymentTransaction::STATUS_INITIATED)
            ->setIpAddress($clientIp)
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());

        $em->persist($transaction);
        $em->flush();

        try {
            $result = $this->flouciService->generatePaymentLink(
                $amountMillimes,
                $successUrl,
                $failUrl,
                (string) $reservationId,
            );
        } catch (\Throwable $e) {
            $this->failTransaction($transaction);
            $this->logger->error('Flouci API error during initiation', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Payment service unavailable. Please try again later.', 0, $e);
        }

        if (!($result['result']['success'] ?? false)) {
            $this->failTransaction($transaction);
            throw new \RuntimeException('Payment initialization failed. Please try again.');
        }

        $transaction
            ->setFlouciPaymentId($result['result']['payment_id'])
            ->setUpdatedAt(new \DateTime());

        $em->flush();

        return $result['result']['link'];
    }

    /**
     * Verify the Flouci callback and mark the reservation as paid.
     *
     * Security: We look up the transaction using all three keys (payment_id + reservation_id + user_id)
     * to prevent a user from reusing a foreign payment_id on another reservation.
     */
    public function verifyAndComplete(int $reservationId, int $userId, string $paymentId): bool
    {
        $transaction = $this->transactionRepo->findByPaymentIdAndReservation($paymentId, $reservationId, $userId);

        if (!$transaction) {
            $this->logger->warning('Payment callback with mismatched identifiers — possible tampering', [
                'payment_id'     => $paymentId,
                'reservation_id' => $reservationId,
                'user_id'        => $userId,
            ]);
            return false;
        }

        // Idempotent: already processed
        if ($transaction->getStatus() === PaymentTransaction::STATUS_SUCCESS) {
            return true;
        }

        try {
            $verification = $this->flouciService->verifyPayment($paymentId);
        } catch (\Throwable $e) {
            $this->logger->error('Flouci verification API error', [
                'payment_id' => $paymentId,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }

        $em = $this->transactionRepo->getEntityManager();

        if (($verification['result']['status'] ?? '') === 'SUCCESS') {
            $transaction
                ->setStatus(PaymentTransaction::STATUS_SUCCESS)
                ->setUpdatedAt(new \DateTime());
            $em->flush();

            return $this->reservationService->markAsPaid($reservationId, $userId);
        }

        $this->failTransaction($transaction);

        return false;
    }

    /**
     * Mark the transaction as failed when Flouci redirects to the fail URL.
     */
    public function markFailed(int $reservationId, int $userId, string $paymentId): void
    {
        $transaction = $this->transactionRepo->findByPaymentIdAndReservation($paymentId, $reservationId, $userId);

        if ($transaction && $transaction->getStatus() === PaymentTransaction::STATUS_INITIATED) {
            $this->failTransaction($transaction);
        }
    }

    private function failTransaction(PaymentTransaction $transaction): void
    {
        $transaction
            ->setStatus(PaymentTransaction::STATUS_FAILED)
            ->setUpdatedAt(new \DateTime());

        $this->transactionRepo->getEntityManager()->flush();
    }
}
