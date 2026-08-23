<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Date;

abstract class TestCase extends BaseTestCase
{
    /**
     * Los tests corren en UTC (phpunit.xml APP_TIMEZONE=UTC) pero el entrypoint
     * de Docker puede cargar .env con APP_TIMEZONE=America/La_Paz que anula el
     * putenv() de phpunit.xml → los timestamps fallan por offset de 4h.
     *
     * Forzamos UTC a nivel PHP + config y limpiamos config cache + CSRF.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Fuerza zona horaria UTC para tests (consistente con phpunit.xml)
        date_default_timezone_set('UTC');
        config(['app.timezone' => 'UTC']);
        Date::setTestNow(now());

        // Limpia cualquier config cache generado por entrypoint
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');

        // Excluye todas las rutas de CSRF
        PreventRequestForgery::except(['*']);
    }
}
