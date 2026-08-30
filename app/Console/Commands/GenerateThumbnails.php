<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\Product;
use App\Services\ImageThumbnailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateThumbnails extends Command
{
    protected $signature = 'pos:generate-thumbnails';

    protected $description = 'Genera miniaturas WebP para imágenes locales de familias y productos (backfill incluido)';

    public function handle(): int
    {
        $created = 0;
        $skipped = 0;

        foreach ([Family::class, Product::class] as $model) {
            $model::query()
                ->whereNotNull('image_url')
                ->where('image_url', 'like', '%/storage/%')
                ->each(function ($row) use (&$created, &$skipped): void {
                    preg_match('#/storage/(.+)$#', $row->image_url, $m);
                    $thumbPath = dirname($m[1]).'/thumbs/'.pathinfo($m[1], PATHINFO_FILENAME).'.webp';
                    if (Storage::disk('public')->exists($thumbPath)) {
                        $skipped++;

                        return;
                    }
                    $created += ImageThumbnailService::generate($m[1]) !== null ? 1 : 0;
                });
        }

        $this->info("Miniaturas generadas: {$created}. Ya existentes: {$skipped}.");

        return self::SUCCESS;
    }
}
