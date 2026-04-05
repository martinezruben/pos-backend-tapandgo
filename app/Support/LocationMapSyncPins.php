<?php

namespace App\Support;

use App\Models\Location;
use App\Models\SyncLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Datos de pins del mapa de localidades, incluyendo si hubo sync exitoso por ventana temporal.
 */
final class LocationMapSyncPins
{
    /**
     * @param  Collection<int, Location>  $mapLocations
     * @return list<array<string, mixed>>
     */
    public static function build(Collection $mapLocations, bool $canEdit): array
    {
        $locationIds = $mapLocations->pluck('id');

        $syncToday = self::locationIdsWithSuccessfulSyncBetween(
            $locationIds,
            now()->startOfDay(),
            now()->copy()->endOfDay(),
        );
        $syncYesterday = self::locationIdsWithSuccessfulSyncBetween(
            $locationIds,
            now()->copy()->subDay()->startOfDay(),
            now()->copy()->subDay()->endOfDay(),
        );
        $syncLastWeek = self::locationIdsWithSuccessfulSyncBetween(
            $locationIds,
            now()->copy()->subDays(6)->startOfDay(),
            now()->copy()->endOfDay(),
        );
        $syncLastMonth = self::locationIdsWithSuccessfulSyncBetween(
            $locationIds,
            now()->copy()->subDays(29)->startOfDay(),
            now()->copy()->endOfDay(),
        );

        return $mapLocations
            ->map(static function (Location $loc) use ($canEdit, $syncToday, $syncYesterday, $syncLastWeek, $syncLastMonth): array {
                $id = (string) $loc->getKey();

                return [
                    'id' => $id,
                    'name' => $loc->name,
                    'lat' => $loc->latitude !== null ? (float) $loc->latitude : null,
                    'lng' => $loc->longitude !== null ? (float) $loc->longitude : null,
                    'activeDevices' => (int) ($loc->active_devices_count ?? 0),
                    'isActive' => (bool) $loc->is_active,
                    'editUrl' => $canEdit ? route('admin.screens.edit', ['locations', $loc->getKey()]) : '',
                    'syncInPeriod' => [
                        'today' => $syncToday->contains($id),
                        'yesterday' => $syncYesterday->contains($id),
                        'last_week' => $syncLastWeek->contains($id),
                        'last_month' => $syncLastMonth->contains($id),
                    ],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, mixed>  $locationIds
     * @return Collection<int, string>
     */
    private static function locationIdsWithSuccessfulSyncBetween(Collection $locationIds, Carbon $from, Carbon $to): Collection
    {
        if ($locationIds->isEmpty()) {
            return collect();
        }

        return SyncLog::query()
            ->whereIn('location_id', $locationIds)
            ->where('status', 'SUCCESS')
            ->whereBetween('started_at', [$from, $to])
            ->distinct()
            ->pluck('location_id')
            ->map(static fn ($id): string => (string) $id)
            ->values();
    }
}
