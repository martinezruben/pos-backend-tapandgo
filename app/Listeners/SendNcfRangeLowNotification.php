<?php

namespace App\Listeners;

use App\Events\NcfRangeLow;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NcfRangeLow as NcfRangeLowNotification;

/**
 * Al detectar rango NCF bajo, notifica a todos los admins activos.
 */
class SendNcfRangeLowNotification
{
    public function handle(NcfRangeLow $event): void
    {
        AdminUser::where('is_active', true)->each(function (AdminUser $admin) use ($event) {
            Notification::send($admin, new NcfRangeLowNotification($event->sequence, $event->type, $event->remaining));
        });
    }
}
