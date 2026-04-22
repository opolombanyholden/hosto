<?php

declare(strict_types=1);

namespace App\Modules\AI\Models;

use App\Models\User;
use App\Modules\Core\Traits\HasUuid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $status
 * @property string|null $topic
 * @property int $messages_count
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User $user
 * @property-read Collection<int, ChatbotMessage> $messages
 */
class ChatbotConversation extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'chatbot_conversations';

    protected $fillable = [
        'user_id', 'status', 'topic', 'messages_count',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ChatbotMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'conversation_id');
    }
}
