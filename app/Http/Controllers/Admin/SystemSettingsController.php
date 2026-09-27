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
            'mail_driver' => ['required', 'in:smtp,365'],
            'mail_from_address' => ['nullable', 'email'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string'],
            'smtp_encryption' => ['nullable', 'in:tls,ssl'],
            'office365_tenant_id' => ['nullable', 'string', 'max:255'],
            'office365_client_id' => ['nullable', 'string', 'max:255'],
            'office365_client_secret' => ['nullable', 'string'],
            'office365_scopes' => ['nullable', 'string'],
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

        if ($validated['office365_scopes']) {
            $validated['office365_scopes'] = json_decode($validated['office365_scopes'], true);
        }

        $params = SystemParameter::query()->firstOrFail();
        $params->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'Parámetros guardados correctamente.']);
        }

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
