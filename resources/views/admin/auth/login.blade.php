<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-canvas font-sans text-sm text-slate-600 antialiased">
<div class="flex min-h-full flex-col lg:flex-row">
    <div class="flex flex-1 flex-col justify-between gap-8 bg-primary-900 px-8 py-12 text-white lg:max-w-[50%] lg:px-16 lg:py-12">
        <div class="flex items-center gap-3">
            <span class="flex h-[42px] w-[42px] items-center justify-center rounded-xl bg-primary-400 text-primary-900"><svg class="h-[22px] w-[22px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 2 3 14h9l-1 8 10-12h-9l1-8Z" /></svg></span>
            <span class="text-xl font-bold tracking-wide">{{ strtoupper(config('app.name', 'Tap&Go')) }}</span>
        </div>
        <div class="flex max-w-lg flex-col gap-7">
            <h1 class="text-4xl font-bold leading-[1.08] tracking-tight lg:text-[44px]">Todo tu negocio, en un solo panel.</h1>
            <p class="text-[17px] text-primary-200">Administra localidades, catálogo, ventas y dispositivos desde cualquier lugar.</p>
            <ul class="flex flex-col gap-4 text-[15px] text-primary-100">
                <li class="flex items-center gap-3.5"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10"><svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M18 17V9M13 17V5M8 17v-3" /></svg></span>Ventas y márgenes en tiempo real</li>
                <li class="flex items-center gap-3.5"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10"><svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8M21 3v5h-5M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16M8 16H3v5" /></svg></span>Sincronización de todos tus POS</li>
                <li class="flex items-center gap-3.5"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10"><svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" /></svg></span>Roles, licencias y auditoría</li>
            </ul>
        </div>
        <p class="text-xs text-[#a8998a]">© {{ date('Y') }} {{ config('app.name', 'Tap&Go') }} · Acceso seguro</p>
    </div>

    <div class="flex flex-1 items-center justify-center bg-snow-25 px-4 py-10 sm:px-8">
        <form method="POST" action="{{ route('admin.login.store') }}" class="flex w-full max-w-[400px] flex-col gap-5">
            @csrf
            <div>
                <h2 class="text-[28px] font-semibold tracking-tight text-slate-800">Inicia sesión</h2>
                <p class="mt-1 text-slate-500">Ingresa con tu cuenta de administrador.</p>
            </div>
            <div>
                <label class="snow-label" for="email">Correo electrónico</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-500"><svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6ZM22 7l-10 6L2 7" /></svg></span>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="snow-input pl-11" required autocomplete="username" autofocus>
                </div>
                @error('email')
                    <p class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="snow-label" for="password">Contraseña</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-500"><svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 11V7a5 5 0 0 1 10 0v4M5 11h14a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2Z" /></svg></span>
                    <input id="password" name="password" type="password" class="snow-input pl-11" required autocomplete="current-password">
                </div>
            </div>
            <button type="submit" class="snow-btn-primary h-[46px] w-full text-[15px]">Entrar al panel</button>
            @if(isset($dbStatus))
                <div class="flex justify-center">
                    @if($dbStatus === 'OK')
                        <span class="snow-tag bg-green-50 text-green-700"><span class="h-2 w-2 rounded-full bg-green-500"></span>Base de datos conectada</span>
                    @else
                        <span class="snow-tag bg-yellow-50 text-yellow-700"><span class="h-2 w-2 rounded-full bg-yellow-500"></span>Sin administradores activos</span>
                    @endif
                </div>
            @endif
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-snow-50 px-3 py-2.5 text-slate-600">
                <span class="shrink-0 text-slate-500"><svg class="h-[18px] w-[18px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" /></svg></span>
                <span>Conexión segura · tus accesos quedan registrados en la auditoría.</span>
            </div>
        </form>
    </div>
</div>
</body>
</html>
