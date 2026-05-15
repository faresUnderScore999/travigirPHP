<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TranslationService
{
    private const TRANSLATE_URL = 'https://translation.googleapis.com/language/translate/v2';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function translate(string $text, string $targetLang, string $sourceLang = ''): string
    {
        if (trim($text) === '') {
            return $text;
        }

        $results = $this->callApi([$text], $targetLang, $sourceLang);
        return $results[0] ?? $text;
    }

    /**
     * @param string[] $texts
     * @return string[]
     */
    public function translateBatch(array $texts, string $targetLang, string $sourceLang = ''): array
    {
        if (empty($texts)) {
            return $texts;
        }

        $results = $this->callApi($texts, $targetLang, $sourceLang);
        // Fall back to originals for any missing translations
        foreach ($texts as $i => $original) {
            if (!isset($results[$i]) || $results[$i] === '') {
                $results[$i] = $original;
            }
        }
        return $results;
    }

    /**
     * @param string[] $texts
     * @return string[]
     */
    private function callApi(array $texts, string $targetLang, string $sourceLang): array
    {
        try {
            $body = ['q' => $texts, 'target' => $targetLang, 'key' => $this->apiKey, 'format' => 'text'];
            if ($sourceLang !== '') {
                $body['source'] = $sourceLang;
            }

            $response = $this->httpClient->request('POST', self::TRANSLATE_URL, ['json' => $body]);
            $data = $response->toArray();
            $translations = $data['data']['translations'] ?? [];

            return array_map(static fn($t) => $t['translatedText'] ?? '', $translations);
        } catch (\Throwable $e) {
            $this->logger?->error('Google Translate API error', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
