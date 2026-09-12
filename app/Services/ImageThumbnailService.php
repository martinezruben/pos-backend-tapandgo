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
     * Extrae la ruta relativa al disco «public» de una image_url almacenada.
     * Acepta rutas locales (/storage/…) y URLs absolutas legacy cuyo host ya no
     * es el actual (ej. https://posbackend.test/storage/…); las URL verdaderamente
     * externas (CDN, unsplash…) devuelven null.
     */
    protected static function localRelativePath(?string $imageUrl): ?string
    {
        if ($imageUrl === null || $imageUrl === '') {
            return null;
        }

        if (preg_match('#/storage/(.+)$#', $imageUrl, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Ruta del disco de la miniatura si existe; si no, la ruta de la original.
     */
    protected static function bestLocalPath(string $path): string
    {
        $thumbPath = dirname($path).'/thumbs/'.pathinfo($path, PATHINFO_FILENAME).'.webp';

        return Storage::disk('public')->exists($thumbPath) ? $thumbPath : $path;
    }

    /**
     * URL para el panel admin: RELATIVA al host actual (/storage/…), de modo que
     * las imágenes carguen visite el panel desde donde lo visite (IP:puerto,
     * dominio, localhost), sin importar el APP_URL con el que se almacenaron.
     */
    public static function displayUrl(?string $imageUrl): ?string
    {
        $path = self::localRelativePath($imageUrl);
        if ($path === null) {
            return $imageUrl !== null && $imageUrl !== '' ? $imageUrl : null;
        }

        return '/storage/'.self::bestLocalPath($path);
    }

    /**
     * URL de sincronización para el POS: ABSOLUTA usando APP_URL (el POS necesita
     * un host completo para descargar la imagen). Legacy absolutas con host viejo
     * se reescriben al APP_URL actual.
     */
    public static function syncUrl(?string $imageUrl): ?string
    {
        $path = self::localRelativePath($imageUrl);
        if ($path === null) {
            return $imageUrl !== null && $imageUrl !== '' ? $imageUrl : null;
        }

        return rtrim(config('app.url'), '/').'/storage/'.self::bestLocalPath($path);
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
