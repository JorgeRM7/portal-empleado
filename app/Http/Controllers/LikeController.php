<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Like;
use App\Events\LikeUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LikeController
{
    public function toggle(Post $post)
    {
        $userId = auth()->id();
        $like = $post->likes()->where('user_id', $userId)->first();

        if ($like) {
            $like->delete();
            $userLiked = false;
        } else {
            $post->likes()->create(['user_id' => $userId]);
            $userLiked = true;
        }
        $count = $post->likes()->count();

        broadcast(new LikeUpdated($post->id, $count));

        // Importante: Forzar tipos para evitar problemas en JS
        return response()->json([
            'likes_count' => $count,
            'user_liked'  => (bool) $userLiked
        ]);
    }
}
