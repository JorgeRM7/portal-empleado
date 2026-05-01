<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewPostsFound implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $post;

    public function __construct($post)
    {
        $this->post = [
            'id'          => $post->id,
            'title'       => $post->title,
            'description' => $post->description,
            'path'        => $post->path,
            'likes_count' => $post->likes_count ?? 0,
            'date'        => $post->created_at->diffForHumans(),
            'user'        => [
                'id'            => $post->user->id,
                'employee_id'   => $post->user->employee->id ?? null,
                'name'          => $post->user->employee->full_name ?? 'Sin nombre',
                'position'      => $post->user->employee->position->name ?? 'Sin puesto',
            ]
        ];
    }

    public function broadcastOn()
    {
        return new Channel('social-wall');
    }

    public function broadcastAs()
    {
        return 'PostCreated';
    }
}