<?php

namespace App\Observers;

use App\Jobs\SendContingencyNotificationJob;
use App\Jobs\SendContingencyResolvedJob;
use App\Models\Location;
use Illuminate\Support\Facades\Log;

class LocationContingencyObserver
{
    public function updated(Location $location): void
    {
        $wasActive = $location->getOriginal('is_active');
        $isNowActive = $location->is_active;

        if ($wasActive === $isNowActive) {
            return;
        }

        if ($wasActive === false && $isNowActive === true) {
            // Location activated - entering contingency
            $this->activateContingency($location);
        } elseif ($wasActive === true && $isNowActive === false) {
            // Location deactivated - resolving contingency
            $this->resolveContingency($location);
        }
    }

    private function activateContingency(Location $location): void
    {
        $location->update([
            'contingency_started_at' => now(),
            'contingency_reminder_sent_at' => now(),
        ]);

        Log::info("Contingency activated for location: {$location->name}");

        SendContingencyNotificationJob::dispatch($location);
    }

    private function resolveContingency(Location $location): void
    {
        $location->update([
            'contingency_started_at' => null,
            'contingency_reminder_sent_at' => null,
        ]);

        Log::info("Contingency resolved for location: {$location->name}");

        SendContingencyResolvedJob::dispatch($location);
    }
}
