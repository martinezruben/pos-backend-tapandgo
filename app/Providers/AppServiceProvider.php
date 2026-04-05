<?php

namespace App\Providers;

use App\Models\AdminUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.tailwind');
        Paginator::defaultSimpleView('vendor.pagination.simple-tailwind');

        Gate::define('viewPulse', function (?Authenticatable $user = null) {
            return $user instanceof AdminUser && $user->is_active;
        });

        Pulse::user(function (Authenticatable $user) {
            if ($user instanceof AdminUser) {
                return [
                    'name' => $user->name,
                    'extra' => $user->email ?? '',
                    'avatar' => null,
                ];
            }

            return [
                'name' => $user->name ?? '—',
                'extra' => $user->email ?? '',
                'avatar' => null,
            ];
        });

        Livewire::setUpdateRoute(function ($handle) {
            return Route::post(EndpointResolver::updatePath(), $handle)
                ->middleware(['web', 'auth:admin']);
        });

        Gate::before(function ($user, ?string $ability = null) {
            if (! $user instanceof AdminUser || $ability === null) {
                return null;
            }

            foreach (config('admin.super_admin_roles', []) as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }

            return null;
        });
    }
}
