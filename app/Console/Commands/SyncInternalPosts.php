<?php

namespace App\Console\Commands;

use App\Events\NewPostsFound;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncInternalPosts extends Command
{
    protected $signature = 'app:sync-internal-posts';
    protected $description = 'Sincroniza nuevos posts y actualiza el puntero de caché';

    public function handle()
    {
        // 1. Obtener el puntero actual
        $lastSyncedId = Cache::get('last_synced_post_id', 0);
        
        $this->info("Puntero actual: " . $lastSyncedId);

        // 2. Traer los nuevos posts ordenados
        $newPosts = Post::with(['user.employee', 'likes.employee'])
            ->withCount('likes')
            ->where('id', '>', $lastSyncedId)
            ->orderBy('id', 'asc')
            ->get();

        if ($newPosts->isEmpty()) {
            $this->warn('No hay nada nuevo. Saliendo...');
            return;
        }

        foreach ($newPosts as $post) {
            $this->info("Procesando Post ID: " . $post->id);

            // 3. Emitir el evento
            event(new NewPostsFound($post));

            // 4. ACTUALIZACIÓN CRÍTICA: Forzamos la persistencia del ID
            // Usamos forever para asegurar que no expire
            Cache::forever('last_synced_post_id', $post->id);
            
            // Verificación inmediata en consola
            $this->line("Puntero actualizado a: " . Cache::get('last_synced_post_id'));
        }
    }
}