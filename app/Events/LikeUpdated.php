<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LikeUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public $postId,
        public $likeCount
    ) {}

    public function broadcastAs(): string
    {
        return 'LikeUpdated';
    }

    public function broadcastOn(): array
    {
        // Canal público para que todos vean la actualización
        return [new Channel('posts')];
    }
}