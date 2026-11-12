<?php

namespace App\Support;

/**
 * Enmascara credenciales antes de guardar datos de la API en logs:
 * contraseñas, tokens, claves de licencia, PIN y secretos, en cualquier
 * nivel del JSON y con cualquier estilo de nombre (license_key, licenseKey).
 */
class SensitiveData
{
    public const MASK = '***';

    /** Fragmentos de nombre de campo (en minúsculas, sin _ ni -) que se ocultan. */
    private const KEY_FRAGMENTS = ['password', 'token', 'authorization', 'licensekey', 'secret', 'apikey', 'creditcard', 'cvv'];

    /** Tope de tamaño para decodificar una respuesta y poder enmascararla. */
    private const MAX_DECODE_BYTES = 1_048_576;

    public static function isSensitiveKey(string|int $key): bool
    {
        $k = str_replace(['_', '-'], '', strtolower((string) $key));
        if ($k === '') {
            return false;
        }

        foreach (self::KEY_FRAGMENTS as $fragment) {
            if (str_contains($k, $fragment)) {
                return true;
            }
        }

        // pin, pin4, pinSha384… (sin tocar palabras como "shipping")
        return str_starts_with($k, 'pin');
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    public static function mask(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (self::isSensitiveKey($key)) {
                $out[$key] = self::MASK;
            } else {
                $out[$key] = is_array($value) ? self::mask($value) : $value;
            }
        }

        return $out;
    }

    /**
     * Resumen legible de la respuesta del catálogo (GET /api/sync/pull): marca de tiempo, licencia
     * (enmascarada) y cuántos registros trae cada lista, en vez del catálogo completo recortado a
     * media lista. Devuelve null si la respuesta no tiene la forma esperada.
     */
    public static function summarizeCatalog(?string $json, int $limit): ?string
    {
        if ($json === null || $json === '' || strlen($json) > self::MAX_DECODE_BYTES * 16) {
            return null;
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded) || ! is_array($decoded['data'] ?? null)) {
            return null;
        }

        $data = [];
        foreach ($decoded['data'] as $name => $value) {
            $data[$name] = is_array($value) && array_is_list($value)
                ? ['registros' => count($value)]
                : $value;
        }

        $summary = self::mask(array_merge($decoded, ['data' => $data, '_resumen' => 'El log guarda solo el conteo de registros del catálogo, no su contenido.']));
        $encoded = json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        return $encoded === false ? null : (mb_strlen($encoded) > $limit ? mb_substr($encoded, 0, $limit).'…' : $encoded);
    }

    /**
     * JSON enmascarado y recortado a `$limit` caracteres. Si el contenido no es
     * JSON (o es demasiado grande para revisarlo) no se guarda: podría contener
     * credenciales que no se pueden ocultar.
     */
    public static function maskJson(?string $json, int $limit): ?string
    {
        if ($json === null || $json === '') {
            return null;
        }
        if (strlen($json) > self::MAX_DECODE_BYTES) {
            return '[respuesta de '.round(strlen($json) / 1024).' KB no guardada]';
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return is_scalar($decoded) ? mb_substr($json, 0, $limit) : '[respuesta no JSON no guardada]';
        }

        $encoded = json_encode(self::mask($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($encoded === false) {
            return null;
        }

        return mb_strlen($encoded) > $limit ? mb_substr($encoded, 0, $limit).'…' : $encoded;
    }
}
