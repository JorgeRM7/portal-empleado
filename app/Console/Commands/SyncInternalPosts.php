<?php

namespace App\Console\Commands;

use App\Events\NewPostsFound;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncInternalPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-internal-posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Busca nuevos registros en la tabla news y los transmite por Reverb';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lastProcessedId = Cache::get('last_synced_post_id', 0);
    
        // Agregamos un log para ver qué ID está buscando
        $this->info("Buscando posts con ID mayor a: " . $lastProcessedId);

        $newPosts = Post::with(['user.employee', 'likes.employee'])
            ->withCount('likes')
            ->where('id', '>', $lastProcessedId)
            ->get();

        if ($newPosts->isEmpty()) {
            $this->warn('No se encontraron posts nuevos en la DB.');
            return;
        }

        foreach ($newPosts as $post) {
            $this->info("Enviando Post ID: " . $post->id);
            
            // Disparamos el evento
            event(new NewPostsFound($post)); 
            
            Cache::put('last_synced_post_id', $post->id);
        }
    }
}
