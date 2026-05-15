<?php

namespace App\Service;

use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Twilio\Rest\Client;

class TwilioService
{
    private ?Client $client = null;

    public function __construct(
        private readonly string $accountSid,
        private readonly string $authToken,
        private readonly string $messagingServiceSid,
        private readonly UserRepository $userRepository,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    private function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = new Client($this->accountSid, $this->authToken);
        }
        return $this->client;
    }

    public function sendSms(string $to, string $body): bool
    {
        try {
            $this->getClient()->messages->create($to, [
                'messagingServiceSid' => $this->messagingServiceSid,
                'body' => $body,
            ]);
            return true;
        } catch (\Throwable $e) {
            $this->logger?->error('Twilio SMS failed', ['to' => $to, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function notifyReclamationStatus(int $userId, string $status, int $reclamationId): void
    {
        $user = $this->userRepository->find($userId);
        if (!$user || !$user->getTel()) {
            return;
        }

        $messages = [
            'OPEN'        => "Your reclamation #$reclamationId has been received and is under review.",
            'IN_PROGRESS' => "Your reclamation #$reclamationId is now being processed.",
            'RESOLVED'    => "Your reclamation #$reclamationId has been resolved. Thank you for your patience.",
            'CLOSED'      => "Your reclamation #$reclamationId has been closed.",
        ];

        $body = $messages[$status] ?? "Your reclamation #$reclamationId status has changed to: $status.";
        $this->sendSms($user->getTel(), $body);
    }

    public function notifyRefundStatus(int $userId, string $status, string $amount): void
    {
        $user = $this->userRepository->find($userId);
        if (!$user || !$user->getTel()) {
            return;
        }

        $messages = [
            'APPROVED'  => "Your refund request of {$amount} TND has been approved and is being processed.",
            'REJECTED'  => "Your refund request of {$amount} TND has been rejected. Contact support for details.",
            'PROCESSED' => "Your refund of {$amount} TND has been successfully sent.",
        ];

        $body = $messages[$status] ?? "Your refund request status has been updated to: $status.";
        $this->sendSms($user->getTel(), $body);
    }
}
