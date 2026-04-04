<?php

namespace App\Http\Middleware;

use App\Models\ApiRequestLog;
use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    private const SENSITIVE_KEYS = [
        'password', 'token', 'plainTextToken', 'pairing_token', 'authorization',
        'current_password', 'credit_card',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $startNs = hrtime(true);

        /** @var Response $response */
        $response = $next($request);

        try {
            $durationMs = (int) round((hrtime(true) - $startNs) / 1_000_000);
            $this->persist($request, $response, $durationMs);
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }

    private function persist(Request $request, Response $response, int $durationMs): void
    {
        $user = $request->user();
        $locationId = null;
        $deviceId = null;
        $fingerprint = null;

        if ($user instanceof Device) {
            $deviceId = $user->getKey();
            $locationId = $user->location_id;
        } else {
            $fingerprint = $request->input('device_fingerprint')
                ?? $request->header('Device-Fingerprint');
            if (is_string($fingerprint) && $fingerprint === '') {
                $fingerprint = null;
            }
            if ($fingerprint !== null) {
                $resolved = Device::query()->where('device_fingerprint', $fingerprint)->first();
                if ($resolved !== null) {
                    $deviceId = $resolved->getKey();
                    $locationId = $resolved->location_id;
                }
            }
        }

        $params = $request->query();
        $body = $request->request->all();
        $merged = array_merge($params, $body);
        $sanitized = $this->sanitizeParams($merged);
        $parametersJson = null;
        if ($sanitized !== []) {
            $parametersJson = json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if ($parametersJson === false) {
                $parametersJson = '{"_error":"encode"}';
            } elseif (strlen($parametersJson) > 16000) {
                $parametersJson = substr($parametersJson, 0, 16000).'…';
            }
        }

        $status = $response->getStatusCode();
        $summary = $this->summarizeResponse($response);

        ApiRequestLog::create([
            'method' => strtoupper($request->method()),
            'path' => '/'.$request->path(),
            'parameters' => $parametersJson,
            'response_status' => $status,
            'response_summary' => $summary,
            'location_id' => $locationId,
            'device_id' => $deviceId,
            'device_fingerprint' => $fingerprint,
            'ip_address' => $request->ip(),
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function sanitizeParams(array $params): array
    {
        $out = [];
        foreach ($params as $key => $value) {
            $lower = strtolower((string) $key);
            $masked = false;
            foreach (self::SENSITIVE_KEYS as $sk) {
                if (str_contains($lower, $sk)) {
                    $out[$key] = '***';
                    $masked = true;
                    break;
                }
            }
            if ($masked) {
                continue;
            }
            if (is_array($value)) {
                $out[$key] = $this->sanitizeParams($value);
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private function summarizeResponse(Response $response): ?string
    {
        $content = $response->getContent();
        if (! is_string($content) || $content === '') {
            return null;
        }

        if (strlen($content) > 2000) {
            return substr($content, 0, 2000).'…';
        }

        return $content;
    }
}
