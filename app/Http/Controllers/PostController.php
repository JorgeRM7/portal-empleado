<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PostController
{
    public function index()
    {
        $branch_office_id = Employee::select('branch_office_id')->where('id', Auth::id());
        $posts = Post::with(['user.employee.position', 'likes.employee'])
            ->withCount('likes')
            ->latest()
            ->where('branch_office_id', $branch_office_id)
            ->get()
            ->map(function ($post) {
                return [
                    'id'          => $post->id,
                    'anonymous'   => $post->anonymous,
                    'title'       => $post->title,
                    'description' => $post->description,
                    'path'        => $post->path,
                    'likes_count' => (int) $post->likes()->count(),
                    'user_liked'  => (bool) $post->likes()->where('user_id', Auth::id())->exists(),
                    'created_at' => $post->created_at->locale('es')->diffForHumans(),
                    'likers'      => $post->likes->map(function($like) {
                        return [
                            'name' => $like->employee->full_name ?? 'Usuario',
                            'id'   => $like->user_id
                        ];
                    }),
                    'user' => [
                        'id'     => $post->user->id,
                        'employee_id' => $post->user->employee->id ?? null,
                        'name'   => $post->user->employee->full_name ?? 'Sin nombre',
                        'position' => $post->user->employee->position->name ?? 'Sin puesto',
                    ],

                ];  
            });

        return Inertia::render('Social/Index', ['posts' => $posts]);
    }

    public function showImg($path)
    {
        // El disco 'hostinger_private' es el que ya configuraste en filesystems.php
        if (!Storage::disk('remote_sftp')->exists($path)) {
            abort(404);
        }

        return Storage::disk('remote_sftp')->response($path);
    }

    public function show(Post $post)
    {
        $posts = Post::with(['user.employee.position', 'likes.employee'])
            ->withCount('likes')
            ->where('id', $post->id)
            ->get()
            ->map(function ($post) {
                return [
                    'id'          => $post->id,
                    'title'       => $post->title,
                    'anonymous'   => $post->anonymous,
                    'description' => $post->description,
                    'path'        => $post->path,
                    'likes_count' => (int) $post->likes()->count(),
                    'user_liked'  => (bool) $post->likes()->where('user_id', Auth::id())->exists(),
                    'created_at' => $post->created_at->locale('es')->diffForHumans(),
                    'likers'      => $post->likes->map(function($like) {
                        return [
                            'name' => $like->employee->full_name ?? 'Usuario',
                            'id'   => $like->user_id
                        ];
                    }),
                    'user' => [
                        'id'     => $post->user->id,
                        'employee_id' => $post->user->employee->id ?? null,
                        'name'   => $post->user->employee->full_name ?? 'Sin nombre',
                        'position' => $post->user->employee->position->name ?? 'Sin puesto',
                    ],

                ];  
            });
        return Inertia::render('Social/Show', [
            'post' => $posts
        ]);
    }
}
