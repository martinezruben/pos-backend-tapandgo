<x-admin.layouts.app title="Parámetros del sistema">
    <div class="snow-card overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-50/80 px-3 py-2 sm:px-4">
            <h1 class="text-sm font-semibold text-slate-900">Parámetros del sistema</h1>
            <p class="mt-0.5 text-[10px] text-slate-500">
                Políticas de contraseña para usuarios del panel y del POS, y bloqueo por intentos fallidos en el login del panel.
            </p>
        </div>

        <form method="POST" action="{{ route('admin.system-settings.update') }}" class="space-y-6 p-3 sm:p-4">
            @csrf
            @method('PUT')

            <section>
                <h2 class="mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Usuarios backend (panel admin)</h2>
                <div class="space-y-3 rounded-lg border border-slate-100 bg-white p-3">
                    <div class="flex flex-col gap-0.5 sm:max-w-xs">
                        <label for="admin_password_min_length" class="text-[8px] font-bold uppercase tracking-widest text-slate-400">Longitud mínima</label>
                        <input
                            type="number"
                            name="admin_password_min_length"
                            id="admin_password_min_length"
                            value="{{ old('admin_password_min_length', $params->admin_password_min_length) }}"
                            min="6"
                            max="128"
                            required
                            class="snow-input text-xs"
                        >
                        @error('admin_password_min_length')
                            <p class="text-[10px] text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <p class="text-[9px] text-slate-500">Complejidad requerida (marca lo que la contraseña debe incluir):</p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ([
                            'admin_password_require_uppercase' => 'Al menos una mayúscula (A-Z)',
                            'admin_password_require_lowercase' => 'Al menos una minúscula (a-z)',
                            'admin_password_require_digit' => 'Al menos un dígito (0-9)',
                            'admin_password_require_symbol' => 'Al menos un carácter especial',
                        ] as $field => $label)
                            <label class="flex cursor-pointer items-center gap-2 text-[11px] text-slate-700">
                                <input
                                    type="checkbox"
                                    name="{{ $field }}"
                                    value="1"
                                    class="h-3.5 w-3.5 rounded border-slate-300 text-primary-600"
                                    @checked(old($field, $params->{$field}))
                                >
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </section>

            <section>
                <h2 class="mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Usuarios POS (app / Android)</h2>
                <div class="space-y-3 rounded-lg border border-slate-100 bg-white p-3">
                    <div class="flex flex-col gap-0.5 sm:max-w-xs">
                        <label for="pos_password_min_length" class="text-[8px] font-bold uppercase tracking-widest text-slate-400">Longitud mínima</label>
                        <input
                            type="number"
                            name="pos_password_min_length"
                            id="pos_password_min_length"
                            value="{{ old('pos_password_min_length', $params->pos_password_min_length) }}"
                            min="3"
                            max="32"
                            required
                            class="snow-input text-xs"
                        >
                        @error('pos_password_min_length')
                            <p class="text-[10px] text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <p class="text-[9px] text-slate-500">Complejidad requerida:</p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ([
                            'pos_password_require_uppercase' => 'Al menos una mayúscula (A-Z)',
                            'pos_password_require_lowercase' => 'Al menos una minúscula (a-z)',
                            'pos_password_require_digit' => 'Al menos un dígito (0-9)',
                            'pos_password_require_symbol' => 'Al menos un carácter especial',
                        ] as $field => $label)
                            <label class="flex cursor-pointer items-center gap-2 text-[11px] text-slate-700">
                                <input
                                    type="checkbox"
                                    name="{{ $field }}"
                                    value="1"
                                    class="h-3.5 w-3.5 rounded border-slate-300 text-primary-600"
                                    @checked(old($field, $params->{$field}))
                                >
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </section>

            <section>
                <h2 class="mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Login panel (backend)</h2>
                <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-100 bg-white p-3 sm:grid-cols-2">
                    <div class="flex flex-col gap-0.5">
                        <label for="admin_max_failed_login_attempts" class="text-[8px] font-bold uppercase tracking-widest text-slate-400">Intentos fallidos antes de bloquear</label>
                        <input
                            type="number"
                            name="admin_max_failed_login_attempts"
                            id="admin_max_failed_login_attempts"
                            value="{{ old('admin_max_failed_login_attempts', $params->admin_max_failed_login_attempts) }}"
                            min="1"
                            max="100"
                            required
                            class="snow-input text-xs"
                        >
                        @error('admin_max_failed_login_attempts')
                            <p class="text-[10px] text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <label for="admin_lockout_minutes" class="text-[8px] font-bold uppercase tracking-widest text-slate-400">Duración del bloqueo (minutos)</label>
                        <input
                            type="number"
                            name="admin_lockout_minutes"
                            id="admin_lockout_minutes"
                            value="{{ old('admin_lockout_minutes', $params->admin_lockout_minutes) }}"
                            min="1"
                            max="1440"
                            required
                            class="snow-input text-xs"
                        >
                        @error('admin_lockout_minutes')
                            <p class="text-[10px] text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <p class="mt-1 text-[9px] text-slate-500">Tras superar los intentos, el acceso queda bloqueado temporalmente para esa combinación de correo e IP.</p>
            </section>

            <section>
                <h2 class="mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">Sincronización</h2>
                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-100 bg-white p-3 text-[11px] text-slate-700">
                    <input
                        type="checkbox"
                        name="sync_paused"
                        value="1"
                        class="h-3.5 w-3.5 rounded border-slate-300 text-primary-600"
                        @checked(old('sync_paused', $params->sync_paused))
                    >
                    <span>
                        <span class="font-semibold">Pausar sincronización del POS</span>
                        <span class="block text-[9px] text-slate-500">Detiene push/pull de los dispositivos (login y reportes siguen operativos). Útil durante migraciones o incidencias.</span>
                    </span>
                </label>
            </section>

            <div class="flex justify-end border-t border-slate-100 pt-3">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-700"
                >
                    Guardar
                </button>
            </div>
        </form>
    </div>
</x-admin.layouts.app>
