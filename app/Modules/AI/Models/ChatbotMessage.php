<?php

declare(strict_types=1);

namespace App\Modules\AI\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $conversation_id
 * @property string $role
 * @property string $content
 * @property array<string, mixed>|null $metadata
 * @property CarbonImmutable $created_at
 * @property-read ChatbotConversation $conversation
 */
class ChatbotMessage extends Model
{
    public $timestamps = false;

    protected $table = 'chatbot_messages';

    protected $fillable = [
        'conversation_id', 'role', 'content', 'metadata', 'created_at',
    ];

    /** @return BelongsTo<ChatbotConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
