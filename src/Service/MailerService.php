<?php

namespace App\Service;

use Resend;

class MailerService
{
    private \Resend\Client $resend;

    public function __construct(string $apiKey)
    {
        $this->resend = Resend::client($apiKey);
    }

    public function sendMailTo(string $mailAddress): void
    {
        $this->resend->emails->send([
            'from'    => 'Travigir Bot <onboarding@resend.dev>',
            'to'      => [$mailAddress],
            'subject' => 'Message from Travigir Bot',
            'html'    => '<p>Hello! you successfully registered in a voyage.</p>',
        ]);
    }

    public function sendRefundConfirmation(string $mailAddress, string $username, float $amount, int $reservationId): void
    {
        $this->resend->emails->send([
            'from'    => 'Travigir Bot <onboarding@resend.dev>',
            'to'      => [$mailAddress],
            'subject' => 'Refund Request Received – Travagir',
            'html'    => sprintf(
                '<div style="font-family:Arial,sans-serif;max-width:540px;margin:auto;padding:32px;background:#f9f9f9;border-radius:12px;">
                    <h2 style="color:#131a22;">Refund Request Submitted</h2>
                    <p>Hello <strong>%s</strong>,</p>
                    <p>We received your refund request for <strong>Reservation #%d</strong> of <strong>%.2f TND</strong>.</p>
                    <p>Our team will review it and notify you once a decision is made (usually within 3–5 business days).</p>
                    <p style="color:#888;font-size:0.85em;margin-top:24px;">– The Travagir Team</p>
                </div>',
                htmlspecialchars($username),
                $reservationId,
                $amount
            ),
        ]);
    }

    public function sendRefundStatusUpdate(string $mailAddress, string $username, string $status, float $amount, int $reservationId): void
    {
        $approved = strtoupper($status) === 'APPROVED';
        $subject  = $approved ? 'Refund Approved – Travagir' : 'Refund Request Update – Travagir';
        $body     = $approved
            ? sprintf('Good news, <strong>%s</strong>! Your refund of <strong>%.2f TND</strong> for Reservation #%d has been <span style="color:#16a34a;">APPROVED</span> and will be processed in 3–5 business days.', htmlspecialchars($username), $amount, $reservationId)
            : sprintf('Hello <strong>%s</strong>, unfortunately your refund request for Reservation #%d has been <span style="color:#dc2626;">REJECTED</span>. Please contact support for more details.', htmlspecialchars($username), $reservationId);

        $this->resend->emails->send([
            'from'    => 'Travigir Bot <onboarding@resend.dev>',
            'to'      => [$mailAddress],
            'subject' => $subject,
            'html'    => sprintf(
                '<div style="font-family:Arial,sans-serif;max-width:540px;margin:auto;padding:32px;background:#f9f9f9;border-radius:12px;">%s<p style="color:#888;font-size:0.85em;margin-top:24px;">– The Travagir Team</p></div>',
                $body
            ),
        ]);
    }
}