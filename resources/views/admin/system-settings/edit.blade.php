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
                <h2 class="mb-2 text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400">Usuarios backend (panel admin)</h2>
                <div class="space-y-3 rounded-lg border border-slate-100 bg-white p-3">
                    <div class="flex flex-col gap-0.5 sm:max-w-xs">
                        <label for="admin_password_min_length" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Longitud mínima</label>
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
                <h2 class="mb-2 text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400">Usuarios POS (app / Android)</h2>
                <div class="space-y-3 rounded-lg border border-slate-100 bg-white p-3">
                    <div class="flex flex-col gap-0.5 sm:max-w-xs">
                        <label for="pos_password_min_length" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Longitud mínima</label>
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
                <h2 class="mb-2 text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400">Login panel (backend)</h2>
                <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-100 bg-white p-3 sm:grid-cols-2">
                    <div class="flex flex-col gap-0.5">
                        <label for="admin_max_failed_login_attempts" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Intentos fallidos antes de bloquear</label>
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
                        <label for="admin_lockout_minutes" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Duración del bloqueo (minutos)</label>
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
                <h2 class="mb-2 text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400">Sincronización</h2>
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

            <section x-data="{ activeTab: 'general' }">
                <h2 class="mb-2 text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400">Correos (SMTP / Office 365)</h2>
                <div class="rounded-lg border border-slate-100 bg-white overflow-hidden">
                    <!-- Tabs -->
                    <div class="flex border-b border-slate-100 bg-slate-50/50">
                        <button type="button" @click="activeTab = 'general'" :class="{ 'border-b-2 border-primary-600 bg-white text-primary-600' : activeTab === 'general', 'text-slate-600 hover:text-slate-900' : activeTab !== 'general' }" class="flex-1 px-3 py-2 text-xs font-semibold transition">General</button>
                        <button type="button" @click="activeTab = 'smtp'" :class="{ 'border-b-2 border-primary-600 bg-white text-primary-600' : activeTab === 'smtp', 'text-slate-600 hover:text-slate-900' : activeTab !== 'smtp' }" class="flex-1 px-3 py-2 text-xs font-semibold transition">SMTP</button>
                        <button type="button" @click="activeTab = 'office365'" :class="{ 'border-b-2 border-primary-600 bg-white text-primary-600' : activeTab === 'office365', 'text-slate-600 hover:text-slate-900' : activeTab !== 'office365' }" class="flex-1 px-3 py-2 text-xs font-semibold transition">Office 365</button>
                    </div>

                    <!-- Tab Content -->
                    <div class="space-y-3 p-3">
                        <!-- General Tab -->
                        <div x-show="activeTab === 'general'" class="space-y-3">
                            <div class="flex flex-col gap-0.5">
                                <label for="mail_driver" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Proveedor de correos</label>
                                <select name="mail_driver" id="mail_driver" class="snow-input text-xs" required>
                                    <option value="smtp" @selected(old('mail_driver', $params->mail_driver) === 'smtp')>SMTP</option>
                                    <option value="365" @selected(old('mail_driver', $params->mail_driver) === '365')>Office 365 / Microsoft Graph</option>
                                </select>
                                @error('mail_driver')
                                    <p class="text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <label for="mail_from_address" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Correo de envío</label>
                                <input type="email" name="mail_from_address" id="mail_from_address" placeholder="noreply@example.com" value="{{ old('mail_from_address', $params->mail_from_address) }}" class="snow-input text-xs">
                                @error('mail_from_address')
                                    <p class="text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <label for="mail_from_name" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Nombre del remitente</label>
                                <input type="text" name="mail_from_name" id="mail_from_name" placeholder="Tap&Go" value="{{ old('mail_from_name', $params->mail_from_name) }}" class="snow-input text-xs">
                                @error('mail_from_name')
                                    <p class="text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- SMTP Tab -->
                        <div x-show="activeTab === 'smtp'" class="space-y-3">
                            <div class="flex flex-col gap-0.5">
                                <label for="smtp_host" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Servidor SMTP</label>
                                <input type="text" name="smtp_host" id="smtp_host" placeholder="smtp.gmail.com" value="{{ old('smtp_host', $params->smtp_host) }}" class="snow-input text-xs">
                                @error('smtp_host')
                                    <p class="text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="flex flex-col gap-0.5">
                                    <label for="smtp_port" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Puerto</label>
                                    <input type="number" name="smtp_port" id="smtp_port" placeholder="587" value="{{ old('smtp_port', $params->smtp_port) }}" min="1" max="65535" class="snow-input text-xs">
                                    @error('smtp_port')
                                        <p class="text-[10px] text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <label for="smtp_encryption" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Encriptación</label>
                                    <select name="smtp_encryption" id="smtp_encryption" class="snow-input text-xs">
                                        <option value="">Ninguna</option>
                                        <option value="tls" @selected(old('smtp_encryption', $params->smtp_encryption) === 'tls')>TLS</option>
                                        <option value="ssl" @selected(old('smtp_encryption', $params->smtp_encryption) === 'ssl')>SSL</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <label for="smtp_username" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Usuario SMTP</label>
                                <input type="text" name="smtp_username" id="smtp_username" value="{{ old('smtp_username', $params->smtp_username) }}" class="snow-input text-xs">
                                @error('smtp_username')
                                    <p class="text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <label for="smtp_password" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Contraseña SMTP</label>
                                <input type="password" name="smtp_password" id="smtp_password" placeholder="●●●●●●●●" class="snow-input text-xs" autocomplete="off">
                                @error('smtp_password')
                                    <p class="text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="text-[8px] text-slate-500">Dejar vacío para mantener la contraseña actual</p>
                            </div>
                            <button type="button" @click="$dispatch('test-smtp')" class="w-full rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">Validar conexión SMTP</button>
                        </div>

                        <!-- Office 365 Tab -->
                        <div x-show="activeTab === 'office365'" class="space-y-3">
                            <div class="flex flex-col gap-0.5">
                                <label for="office365_tenant_id" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">ID del Tenant</label>
                                <input type="text" name="office365_tenant_id" id="office365_tenant_id" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" value="{{ old('office365_tenant_id', $params->office365_tenant_id) }}" class="snow-input text-xs font-mono text-[10px]">
                                @error('office365_tenant_id')
                                    <p class="text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <label for="office365_client_id" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">ID de Cliente</label>
                                <input type="text" name="office365_client_id" id="office365_client_id" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" value="{{ old('office365_client_id', $params->office365_client_id) }}" class="snow-input text-xs font-mono text-[10px]">
                                @error('office365_client_id')
                                    <p class="text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <label for="office365_client_secret" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Secreto de Cliente</label>
                                <input type="password" name="office365_client_secret" id="office365_client_secret" placeholder="●●●●●●●●" class="snow-input text-xs" autocomplete="off">
                                @error('office365_client_secret')
                                    <p class="text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="text-[8px] text-slate-500">Dejar vacío para mantener el secreto actual</p>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <label for="office365_scopes" class="text-[8px] font-mono font-bold uppercase tracking-widest text-slate-400">Permisos (Scopes, JSON)</label>
                                <textarea name="office365_scopes" id="office365_scopes" placeholder='["Mail.Send", "User.Read"]' class="snow-input text-xs font-mono text-[10px]" rows="3">{{ old('office365_scopes', is_array($params->office365_scopes) ? json_encode($params->office365_scopes) : $params->office365_scopes) }}</textarea>
                                @error('office365_scopes')
                                    <p class="text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="button" @click="$dispatch('test-office365')" class="w-full rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">Validar credenciales Office 365</button>
                        </div>
                    </div>
                </div>
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
