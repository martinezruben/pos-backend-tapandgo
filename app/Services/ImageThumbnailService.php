<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

/**
 * Miniaturas WebP para imágenes mostradas en el POS Android a tamaño reducido.
 *
 * La miniatura se guarda por convención en `{carpeta}/thumbs/{nombre}.webp`
 * del disco «public»; no hay columna adicional en base de datos.
 */
class ImageThumbnailService
{
    public const int MAX_SIZE = 400;

    public const int QUALITY = 80;

    /**
     * Genera (o regenera) la miniatura de un archivo ya almacenado en el disco «public».
     *
     * @return string|null Ruta del disco de la miniatura, o null si no pudo generarse.
     */
    public static function generate(string $diskPath): ?string
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($diskPath)) {
            return null;
        }

        $thumbPath = dirname($diskPath).'/thumbs/'.pathinfo($diskPath, PATHINFO_FILENAME).'.webp';

        try {
            $image = (new ImageManager(new GdDriver))->decodePath($disk->path($diskPath));
        } catch (\Throwable) {
            return null;
        }

        $disk->makeDirectory(dirname($thumbPath));
        $image->scaleDown(width: self::MAX_SIZE, height: self::MAX_SIZE)
            ->save($disk->path($thumbPath), quality: self::QUALITY);

        return $thumbPath;
    }

    /**
     * URL de sincronización para una `image_url` de familia/producto:
     * - URL externa (http/https): se devuelve intacta.
     * - Ruta local (/storage/...): URL absoluta de la miniatura si existe; si no, de la original.
     */
    public static function syncUrl(?string $imageUrl): ?string
    {
        if ($imageUrl === null || $imageUrl === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $imageUrl)) {
            return $imageUrl;
        }

        if (preg_match('#/storage/(.+)$#', $imageUrl, $m)) {
            $path = $m[1];
            $thumbPath = dirname($path).'/thumbs/'.pathinfo($path, PATHINFO_FILENAME).'.webp';
            $chosen = Storage::disk('public')->exists($thumbPath) ? $thumbPath : $path;

            return Storage::disk('public')->url($chosen);
        }

        return url($imageUrl);
    }

    /**
     * Elimina la miniatura asociada a una `image_url` pública (la original la borra el llamador).
     */
    public static function deleteFor(?string $publicUrl): void
    {
        if ($publicUrl === null || $publicUrl === '') {
            return;
        }
        if (preg_match('#/storage/(.+)$#', $publicUrl, $m)) {
            $thumbPath = dirname($m[1]).'/thumbs/'.pathinfo($m[1], PATHINFO_FILENAME).'.webp';
            Storage::disk('public')->delete($thumbPath);
        }
    }
}
