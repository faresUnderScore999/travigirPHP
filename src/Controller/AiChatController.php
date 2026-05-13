<?php
// src/Controller/AiChatController.php

namespace App\Controller;

use App\Service\AiChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AiChatController extends AbstractController
{
    #[Route('/ai-chat/message', name: 'ai_chat_message', methods: ['POST'])]
    public function message(Request $request, AiChatService $aiChatService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userMessage = trim($data['message'] ?? '');

        if (empty($userMessage)) {
            return $this->json(['error' => 'Message cannot be empty.'], 400);
        }

        $reply = $aiChatService->getResponse($userMessage);

        return $this->json(['reply' => $reply]);
    }

    // Optional: render the chat widget as a separate route (not strictly needed)
    #[Route('/ai-chat/widget', name: 'ai_chat_widget')]
    public function widget(): JsonResponse
    {
        // This could return the HTML snippet, but we'll embed directly in base template.
        return $this->json(['status' => 'ok']);
    }
}