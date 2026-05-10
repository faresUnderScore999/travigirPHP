<?php
namespace App\Service\Analytics;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class NvidiaAIClient
{
    private HttpClientInterface $httpClient;
    private string $apiKeyNvidia;  // Now holds OpenRouter API key, name unchanged
    private string $baseUrl = 'https://openrouter.ai/api/v1';
    private string $defaultModel = 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free';

    public function __construct(string $apiKeyNvidia, ?HttpClientInterface $httpClient = null)
    {
        $this->apiKeyNvidia = $apiKeyNvidia;
        $this->httpClient = $httpClient ?? \Symfony\Component\HttpClient\HttpClient::create();
    }

    public function chat(array $messages, array $tools = [], bool $enableReasoning = false, ?string $model = null): array 
    {
        $body = [
            'model' => $model ?: $this->defaultModel,
            'messages' => $messages,
            'temperature' => 0.2,
        ];

        if (!empty($tools)) {
            $body['tools'] = $tools;
        }

        if ($enableReasoning) {
            $body['reasoning'] = ['enabled' => true];
        }

        $response = $this->httpClient->request('POST', $this->baseUrl . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKeyNvidia,
                'Content-Type' => 'application/json',
      
            ],
            'json' => $body
        ]);

        return $response->toArray();
    }
}