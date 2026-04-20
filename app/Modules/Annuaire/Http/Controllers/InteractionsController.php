<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Http\Controllers;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\HostoLike;
use App\Modules\Annuaire\Models\HostoRecommendation;
use App\Modules\Core\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Social interactions on health structures.
 *
 * Only available for partner structures (is_partner = true).
 * Authentication required for all actions.
 */
final class InteractionsController
{
    /**
     * Toggle like on a structure.
     */
    public function toggleLike(Request $request, string $uuid, AuditLogger $audit): JsonResponse
    {
        $hosto = Hosto::where('uuid', $uuid)->where('is_partner', true)->firstOrFail();
        $user = $request->user();

        $existing = HostoLike::where('user_id', $user->id)->where('hosto_id', $hosto->id)->first();

        if ($existing) {
            $existing->delete();
            $hosto->decrement('likes_count');
            $audit->record('like.removed', 'hosto', $hosto->uuid);

            return response()->json(['data' => ['liked' => false, 'likes_count' => $hosto->fresh()->likes_count]]);
        }

        HostoLike::create(['user_id' => $user->id, 'hosto_id' => $hosto->id]);
        $hosto->increment('likes_count');
        $audit->record('like.added', 'hosto', $hosto->uuid);

        return response()->json(['data' => ['liked' => true, 'likes_count' => $hosto->fresh()->likes_count]]);
    }

    /**
     * Check if the current user has liked a structure.
     */
    public function likeStatus(Request $request, string $uuid): JsonResponse
    {
        $hosto = Hosto::where('uuid', $uuid)->firstOrFail();
        $liked = HostoLike::where('user_id', $request->user()->id)->where('hosto_id', $hosto->id)->exists();

        return response()->json(['data' => ['liked' => $liked, 'likes_count' => $hosto->likes_count]]);
    }

    /**
     * Post a recommendation on a structure.
     */
    public function recommend(Request $request, string $uuid, AuditLogger $audit): JsonResponse
    {
        $hosto = Hosto::where('uuid', $uuid)->where('is_partner', true)->firstOrFail();

        $data = $request->validate([
            'content' => 'required|string|max:500',
        ]);

        // One recommendation per user per structure.
        $existing = HostoRecommendation::where('user_id', $request->user()->id)
            ->where('hosto_id', $hosto->id)
            ->first();

        if ($existing) {
            return response()->json([
                'error' => ['code' => 'ALREADY_RECOMMENDED', 'message' => 'Vous avez deja recommande cette structure.'],
            ], 409);
        }

        $reco = HostoRecommendation::create([
            'user_id' => $request->user()->id,
            'hosto_id' => $hosto->id,
            'content' => $data['content'],
            'is_approved' => false, // pending moderation
        ]);

        $audit->record(AuditLogger::ACTION_CREATE, 'recommendation', $reco->uuid, [
            'hosto' => $hosto->uuid,
        ]);

        return response()->json([
            'data' => [
                'uuid' => $reco->uuid,
                'content' => $reco->content,
                'status' => 'pending_moderation',
                'message' => 'Votre recommandation sera publiee apres moderation.',
            ],
        ], 201);
    }

    /**
     * List approved recommendations for a structure (public).
     */
    public function recommendations(string $uuid): JsonResponse
    {
        $hosto = Hosto::where('uuid', $uuid)->firstOrFail();

        $recos = HostoRecommendation::where('hosto_id', $hosto->id)
            ->approved()
            ->with('user:id,name,uuid')
            ->orderByDesc('approved_at')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'uuid' => $r->uuid,
                'content' => $r->content,
                'author' => $r->user->name,
                'date' => $r->approved_at?->toDateString(),
            ]);

        return response()->json(['data' => $recos]);
    }
}
