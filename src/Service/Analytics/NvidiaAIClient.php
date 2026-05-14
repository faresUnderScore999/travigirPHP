<?php
namespace App\Service\Analytics;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class NvidiaAIClient
{
    private HttpClientInterface $httpClient;
    private string $apiKeyNvidia;

    public function __construct(string $apiKeyNvidia)
    {
        $this->apiKeyNvidia = $apiKeyNvidia;
        $this->httpClient = \Symfony\Component\HttpClient\HttpClient::create();
    }

    /**
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, mixed> $tools
     * @return array<string, mixed>
     */
    public function chat(array $messages, array $tools = []): array
    {
        $body = [
            'model' => 'nvidia/nemotron-3-nano-30b-a3b',
            'messages' => $messages,
            'temperature' => 0.2,
        ];

        if (!empty($tools)) {
            $body['tools'] = $tools;
        }

        $response = $this->httpClient->request('POST', 'https://integrate.api.nvidia.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKeyNvidia,
                'Content-Type' => 'application/json',
            ],
            'json' => $body
        ]);

        return $response->toArray();
    }
}