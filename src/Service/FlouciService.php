<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FlouciService
{
    private const API_BASE = 'https://developers.flouci.com/api';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(FLOUCI_APP_TOKEN)%')]
        private readonly string $appToken,
        #[Autowire('%env(FLOUCI_APP_SECRET)%')]
        private readonly string $appSecret,
    ) {}

    /**
     * Initialize a payment session and return the Flouci payment link.
     *
     * @param int    $amountInMillimes  Amount in millimes (1 TND = 1000 millimes)
     * @param string $successUrl        Absolute URL Flouci redirects to on success
     * @param string $failUrl           Absolute URL Flouci redirects to on failure
     * @param string $trackingId        Your internal identifier (e.g. reservation ID)
     */
    public function generatePaymentLink(int $amountInMillimes, string $successUrl, string $failUrl, string $trackingId): array
    {
        $response = $this->httpClient->request('POST', self::API_BASE . '/generate_token', [
            'json' => [
                'app_token' => $this->appToken,
                'app_secret' => $this->appSecret,
                'amount' => $amountInMillimes,
                'accept_card' => true,
                'session_timeout_secs' => 1200,
                'success_link' => $successUrl,
                'fail_link' => $failUrl,
                'developer_tracking_id' => $trackingId,
            ],
        ]);

        return $response->toArray();
    }

    /**
     * Verify a payment by its Flouci payment ID.
     */
    public function verifyPayment(string $paymentId): array
    {
        $response = $this->httpClient->request('GET', self::API_BASE . '/verify_payment/' . $paymentId, [
            'headers' => [
                'app_token' => $this->appToken,
                'app_secret' => $this->appSecret,
            ],
        ]);

        return $response->toArray();
    }
}
