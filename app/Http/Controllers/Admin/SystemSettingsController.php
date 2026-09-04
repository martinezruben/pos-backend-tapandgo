<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemParameter;
use App\Support\AdminRbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SystemSettingsController extends Controller
{
    public function edit(): Response
    {
        $this->authorizeSettings('view');

        $params = SystemParameter::query()->firstOrFail();

        return response()->view('admin.system-settings.edit', [
            'params' => $params,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeSettings('edit');

        $validated = $request->validate([
            'admin_password_min_length' => ['required', 'integer', 'min:6', 'max:128'],
            'pos_password_min_length' => ['required', 'integer', 'min:3', 'max:32'],
            'admin_max_failed_login_attempts' => ['required', 'integer', 'min:1', 'max:100'],
            'admin_lockout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        foreach ([
            'admin_password_require_uppercase',
            'admin_password_require_lowercase',
            'admin_password_require_digit',
            'admin_password_require_symbol',
            'pos_password_require_uppercase',
            'pos_password_require_lowercase',
            'pos_password_require_digit',
            'pos_password_require_symbol',
            'sync_paused',
        ] as $boolField) {
            $validated[$boolField] = $request->boolean($boolField);
        }

        $params = SystemParameter::query()->firstOrFail();
        $params->update($validated);

        return redirect()
            ->route('admin.system-settings.edit')
            ->with('status', 'Parámetros guardados correctamente.');
    }

    private function authorizeSettings(string $action): void
    {
        $user = auth('admin')->user();
        abort_unless($user, 403);
        $p = AdminRbac::permissionsForScreen('system-settings');
        $perm = $action === 'edit' ? $p['edit'] : $p['view'];
        abort_unless($user->can($perm), 403);
    }
}
