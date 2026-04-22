<?php

declare(strict_types=1);

namespace App\Modules\AI\Services;

use App\Modules\AI\Models\ChatbotConversation;
use App\Modules\AI\Models\ChatbotMessage;

final class ChatbotService
{
    /**
     * Start a new chatbot conversation.
     */
    public function startConversation(int $userId, string $topic): ChatbotConversation
    {
        return ChatbotConversation::create([
            'user_id' => $userId,
            'topic' => $topic,
            'status' => 'active',
            'messages_count' => 0,
        ]);
    }

    /**
     * Send a user message and generate an assistant response.
     *
     * TODO: replace stub with real NLP model integration.
     */
    public function sendMessage(ChatbotConversation $conversation, string $userMessage): ChatbotMessage
    {
        // Store user message.
        ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userMessage,
            'created_at' => now(),
        ]);

        // TODO: replace with real NLP model call.
        $assistantReply = 'Merci pour votre message. Cette fonctionnalité est en cours de développement.';

        $assistantMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $assistantReply,
            'metadata' => ['model' => 'stub-v0', 'latency_ms' => 0],
            'created_at' => now(),
        ]);

        $conversation->increment('messages_count', 2);

        return $assistantMessage;
    }
}
