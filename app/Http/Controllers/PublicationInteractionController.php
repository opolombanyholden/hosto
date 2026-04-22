<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Annuaire\Models\PractitionerPublication;
use App\Modules\Annuaire\Models\PublicationComment;
use App\Modules\Annuaire\Models\PublicationLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public interactions on practitioner publications.
 */
final class PublicationInteractionController
{
    /**
     * Toggle like on a publication.
     */
    public function toggleLike(Request $request, string $uuid): JsonResponse
    {
        $publication = PractitionerPublication::published()->whereUuid($uuid)->firstOrFail();
        $userId = $request->user()->id;

        $existing = PublicationLike::where('publication_id', $publication->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
            $publication->decrement('likes_count');
            $liked = false;
        } else {
            PublicationLike::create(['publication_id' => $publication->id, 'user_id' => $userId]);
            $publication->increment('likes_count');
            $liked = true;
        }

        return response()->json([
            'data' => [
                'liked' => $liked,
                'likes_count' => $publication->fresh()->likes_count,
            ],
        ]);
    }

    /**
     * Add a comment to a publication.
     */
    public function addComment(Request $request, string $uuid): JsonResponse
    {
        $publication = PractitionerPublication::published()->whereUuid($uuid)->firstOrFail();

        if (! $publication->allow_comments) {
            return response()->json([
                'error' => ['code' => 'COMMENTS_DISABLED', 'message' => 'Les commentaires sont desactives pour cette publication.'],
            ], 403);
        }

        $data = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = PublicationComment::create([
            'publication_id' => $publication->id,
            'user_id' => $request->user()->id,
            'content' => $data['content'],
        ]);

        $publication->increment('comments_count');

        return response()->json([
            'data' => [
                'message' => 'Commentaire ajoute.',
                'comment' => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'user_name' => $request->user()->name,
                    'created_at' => $comment->created_at->format('d/m/Y H:i'),
                ],
            ],
        ], 201);
    }
}
