<?php

namespace App\Controller;

use App\Service\TranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/translate')]
class TranslationController extends AbstractController
{
    public function __construct(
        private readonly TranslationService $translationService,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function translate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $text = $data['text'] ?? '';
        $target = $data['target'] ?? 'en';
        $source = $data['source'] ?? '';

        if (!is_string($text) || trim($text) === '') {
            return $this->json(['error' => 'Missing or empty text'], 400);
        }

        $translated = $this->translationService->translate($text, $target, $source);
        return $this->json(['translatedText' => $translated]);
    }

    #[Route('/batch', methods: ['POST'])]
    public function translateBatch(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $texts = $data['texts'] ?? [];
        $target = $data['target'] ?? 'en';
        $source = $data['source'] ?? '';

        if (!is_array($texts) || empty($texts)) {
            return $this->json(['error' => 'Missing or empty texts array'], 400);
        }

        $translations = $this->translationService->translateBatch($texts, $target, $source);
        return $this->json(['translations' => $translations]);
    }
}
