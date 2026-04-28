<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PostController
{
    public function index()
    {
        $posts = Post::with(['user'])
            ->withCount('likes')
            ->latest()
            ->get()
            ->map(function ($post) {
                return [
                    'id'          => $post->id,
                    'title'       => $post->title,
                    'description' => $post->description,
                    'path'        => $post->path,
                    'likes_count' => (int) $post->likes()->count(),
                    'user_liked'  => (bool) $post->likes()->where('user_id', auth()->id())->exists(),
                    'user'        => $post->user,
                ];
            });

        return Inertia::render('Social/Index', ['posts' => $posts]);
    }
}
