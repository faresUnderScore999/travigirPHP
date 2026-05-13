<?php
// src/Service/AiChatService.php

namespace App\Service;

use App\Service\Analytics\NvidiaAIClient;
use App\Service\VoyageService;
use App\Service\OfferService;
use Psr\Log\LoggerInterface;

class AiChatService
{
    public function __construct(
        private readonly NvidiaAIClient $aiClient,
        private readonly VoyageService $voyageService,
        private readonly OfferService $offerService,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get a response from the AI assistant based on user message.
     */
    public function getResponse(string $userMessage): string
    {
        // Fetch current voyages and active offers for context
        $voyages = $this->voyageService->getAllVoyages();
        $offers  = $this->offerService->getActiveOffers();

        // Build a compact summary of voyages
        $voyagesSummary = array_map(function ($v) {
            return sprintf(
                "- %s: %s (destination: %s, price: $%.2f, dates: %s to %s)",
                $v['title'],
                substr($v['description'], 0, 100),
                $v['destination'],
                $v['price'],
                $v['start_date'] ?? 'TBD',
                $v['end_date'] ?? 'TBD'
            );
        }, $voyages);

        // Build summary of active offers
        $offersSummary = array_map(function ($o) {
            return sprintf(
                "- %s: %d%% off on %s (valid until %s)",
                $o['title'],
                $o['discount_percentage'],
                $o['voyage_title'],
                $o['end_date'] ?? 'end date not set'
            );
        }, $offers);

        $context = "You are a helpful travel assistant for Travagir, a travel agency.\n";
        $context .= "Use the following information about our voyages and offers to answer the user's questions.\n";
        $context .= "Be concise, friendly, and professional.\n\n";
        $context .= "=== CURRENT VOYAGES ===\n";
        $context .= implode("\n", $voyagesSummary) . "\n\n";
        $context .= "=== ACTIVE OFFERS ===\n";
        $context .= implode("\n", $offersSummary) . "\n\n";
        $context .= "User question: " . $userMessage . "\n";
        $context .= "Assistant:";

        try {
            $response = $this->aiClient->chat([
                ['role' => 'system', 'content' => $context]
            ]);
            // Extract the reply from the NVIDIA response structure
            return $response['choices'][0]['message']['content'] ?? "Sorry, I couldn't generate a response.";
        } catch (\Throwable $e) {
            $this->logger?->error('AI Chat error', ['error' => $e->getMessage()]);
            return "I'm having trouble connecting to my brain right now. Please try again later.";
        }
    }
}