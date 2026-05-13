<?php

namespace App\Service;

use App\Entity\Reclamation;
use App\Service\Analytics\NvidiaAIClient;
use Psr\Log\LoggerInterface;

class AiResponseSuggestionService
{
    public function __construct(
        private readonly NvidiaAIClient $nvidiaClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function suggestForReclamation(Reclamation $reclamation): ?string
    {
        $prompt = <<<PROMPT
You are a senior customer support agent for Travagir, a travel agency.
Your job is to write a personalised, ready-to-send admin reply to a customer complaint.

Rules:
- Read the title and description carefully and address the SPECIFIC issue raised.
- Adapt your tone to the priority: URGENT/HIGH → more urgent, empathetic, and action-oriented. LOW/NORMAL → calm and reassuring.
- If the complaint is about a REFUND → acknowledge the cancellation, confirm the refund process is being initiated, give an estimated timeframe (3-5 business days).
- If the complaint is about a DELAY or SCHEDULE → apologise, explain general causes, offer an update timeline.
- If the complaint is about SERVICE QUALITY (hotel, guide, transport) → apologise specifically, mention an internal review will be conducted.
- If the complaint is about BOOKING/TECHNICAL issues → reassure, mention the technical team will look into it.
- If none of the above match, write a warm, specific reply that directly references the user's words.
- Start with "Dear valued customer," — never use placeholders like [Name].
- Do NOT invent specific dates, names, order numbers, or amounts not given to you.
- Keep it between 60 and 130 words. No bullet points. Plain paragraph only.
PROMPT;

        $userMessage = sprintf(
            "Title: %s\nDescription: %s\nPriority: %s\nStatus: %s",
            $reclamation->getTitle(),
            $reclamation->getDescription(),
            $reclamation->getPriority(),
            $reclamation->getStatus()
        );

     try {
        $response = $this->nvidiaClient->chat([
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'user', 'content' => $userMessage],
        ]);

        $content = $response['choices'][0]['message']['content'] ?? null;
        return $content ? trim($content) : null;
    } catch (\Exception $e) {
        $this->logger->error('AI suggestion failed', ['error' => $e->getMessage()]);
        // 🔻 TEMPORARY: re-throw to see the error in the JSON response
        throw $e;
    }
    }
}