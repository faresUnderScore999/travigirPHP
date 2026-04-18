<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MailerService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly string $fromEmail
    ) {}

    public function sendReservationConfirmation(
        string $toEmail,
        string $userName,
        string $voyageTitle,
        string $destination,
        int $numberOfPeople,
        float $totalPrice,
        string $reservationDate
    ): void {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('✅ Reservation Confirmed — ' . $voyageTitle)
            ->html($this->twig->render('emails/reservation_confirmation.html.twig', [
                'userName'       => $userName,
                'voyageTitle'    => $voyageTitle,
                'destination'    => $destination,
                'numberOfPeople' => $numberOfPeople,
                'totalPrice'     => $totalPrice,
                'reservationDate'=> $reservationDate,
            ]));

        $this->mailer->send($email);
    }

    public function sendReservationCancellation(
        string $toEmail,
        string $userName,
        string $voyageTitle
    ): void {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('❌ Reservation Cancelled — ' . $voyageTitle)
            ->html($this->twig->render('emails/reservation_cancellation.html.twig', [
                'userName'    => $userName,
                'voyageTitle' => $voyageTitle,
            ]));

        $this->mailer->send($email);
    }

    public function sendAdminConfirmation(
        string $toEmail,
        string $userName,
        string $voyageTitle,
        string $destination
    ): void {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('🎉 Your Trip is Confirmed! — ' . $voyageTitle)
            ->html($this->twig->render('emails/admin_confirmation.html.twig', [
                'userName'    => $userName,
                'voyageTitle' => $voyageTitle,
                'destination' => $destination,
            ]));

        $this->mailer->send($email);
    }

    public function sendRefundReceived(
        string $toEmail,
        string $userName,
        string $voyageTitle,
        float $amount
    ): void {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($toEmail)
            ->subject('💰 Refund Request Received — ' . $voyageTitle)
            ->html($this->twig->render('emails/refund_received.html.twig', [
                'userName'    => $userName,
                'voyageTitle' => $voyageTitle,
                'amount'      => $amount,
            ]));

        $this->mailer->send($email);
    }
}