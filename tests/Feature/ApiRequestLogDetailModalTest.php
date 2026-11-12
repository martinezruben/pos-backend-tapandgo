<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\ApiRequestLog;
use App\Models\Device;
use App\Models\Family;
use App\Models\License;
use App\Models\Location;
use App\Models\User;
use App\Support\SensitiveData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiRequestLogDetailModalTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): AdminUser
    {
        $admin = AdminUser::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('super-admin', 'admin'));

        return $admin;
    }

    private function log(array $attrs = []): ApiRequestLog
    {
        return ApiRequestLog::create($attrs + [
            'method' => 'POST',
            'path' => '/api/auth/register-device',
            'parameters' => '{"device_fingerprint":"E2E-FP-1"}',
            'response_status' => 500,
            'response_summary' => '{"message":"Server Error"}',
        ]);
    }

    // ---- El modal de cada pantalla: uno solo, el suyo

    public function test_api_request_logs_screen_has_only_its_own_detail_modal(): void
    {
        $this->log();
        $this->actingAs($this->superAdmin(), 'admin');

        $this->get(route('admin.screens.index', 'api-request-logs'))
            ->assertOk()
            ->assertSee('apiRequestLogDetail', false)
            ->assertSee('Detalle de llamada API')
            ->assertDontSee('Detalle de auditoría')
            ->assertDontSee('auditLogDetail', false);
    }

    public function test_audit_log_screen_has_its_detail_modal(): void
    {
        $this->actingAs($this->superAdmin(), 'admin');

        $this->get(route('admin.screens.index', 'audit-log'))
            ->assertOk()
            ->assertSee('auditLogDetail', false)
            ->assertSee('Detalle de auditoría')
            ->assertDontSee('Detalle de llamada API');
    }

    public function test_transactions_screen_keeps_only_the_line_items_modal(): void
    {
        $this->actingAs($this->superAdmin(), 'admin');

        $this->get(route('admin.screens.index', 'transactions'))
            ->assertOk()
            ->assertDontSee('Detalle de llamada API')
            ->assertDontSee('Detalle de auditoría');
    }

    public function test_detail_modal_markup_is_balanced_on_the_screens_that_have_one(): void
    {
        $this->actingAs($this->superAdmin(), 'admin');

        foreach (['api-request-logs', 'audit-log', 'transactions', 'locations'] as $screen) {
            $html = $this->get(route('admin.screens.index', $screen))->assertOk()->getContent();
            $this->assertSame(
                substr_count($html, '<div'),
                substr_count($html, '</div>'),
                "El HTML de la pantalla {$screen} tiene <div> sin cerrar"
            );
        }
    }

    public function test_modal_offers_scrollable_response_copy_and_truncation_notice(): void
    {
        $this->actingAs($this->superAdmin(), 'admin');

        $html = $this->get(route('admin.screens.index', 'api-request-logs'))->assertOk()->getContent();

        $this->assertStringContainsString('Respuesta del sistema', $html);
        $this->assertStringContainsString('overflow-y-auto', $html);
        $this->assertStringContainsString("copy('response'", $html);
        $this->assertStringContainsString('la respuesta se guardó recortada', mb_strtolower($html));
    }

    // ---- Qué devuelve el detalle

    public function test_detail_returns_what_the_system_answered(): void
    {
        $log = $this->log(['response_status' => 422, 'response_summary' => '{"message":"Código de vinculación inválido o caducado."}']);
        $this->actingAs($this->superAdmin(), 'admin');

        $this->getJson(route('admin.api-request-logs.show', $log->getKey()))
            ->assertOk()
            ->assertJsonPath('response_status', 422)
            ->assertJsonPath('response_truncated', false)
            ->assertJsonPath('parameters_truncated', false)
            ->assertJsonPath('response_json', json_encode(['message' => 'Código de vinculación inválido o caducado.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function test_detail_flags_truncated_responses(): void
    {
        $log = $this->log(['response_summary' => '{"data":{"products":[{"id":"x","name":"Pro…']);
        $this->actingAs($this->superAdmin(), 'admin');

        $this->getJson(route('admin.api-request-logs.show', $log->getKey()))
            ->assertOk()
            ->assertJsonPath('response_truncated', true)
            ->assertJsonPath('response_chars', mb_strlen($log->response_summary));
    }

    public function test_a_response_that_merely_ends_in_an_ellipsis_is_not_flagged_when_it_is_valid_json(): void
    {
        $log = $this->log(['response_summary' => '{"message":"Cargando…"}']);
        $this->actingAs($this->superAdmin(), 'admin');

        $this->getJson(route('admin.api-request-logs.show', $log->getKey()))->assertJsonPath('response_truncated', false);
    }

    // ---- Qué se guarda de la respuesta

    public function test_error_responses_are_stored_whole_up_to_the_larger_limit(): void
    {
        $location = Location::factory()->create(['is_active' => true]);
        $device = Device::factory()->create(['location_id' => $location->id, 'is_enabled' => true]);
        License::factory()->create(['device_id' => $device->id, 'status' => 'ACTIVE', 'valid_from' => now()->subDay(), 'valid_to' => now()->addDay()]);
        $user = User::factory()->create();
        $token = $device->createToken('t')->plainTextToken;

        // 30 errores de validación: respuesta de varios miles de caracteres, que antes se recortaba a 2000
        $txs = collect(range(1, 30))->map(fn ($i) => ['external_id' => "ext-{$i}", 'user_id' => $user->id, 'turn_number' => 'no-es-numero', 'status' => 'PAID', 'total' => 1, 'occurred_at' => now()->toIso8601String()])->all();
        $this->postJson('/api/sync/push', ['transactions' => $txs], ['Authorization' => "Bearer {$token}"])->assertStatus(422);

        $log = ApiRequestLog::where('path', '/api/sync/push')->latest('created_at')->first();
        $this->assertGreaterThan(2000, mb_strlen($log->response_summary));
        $this->assertNotNull(json_decode($log->response_summary), 'La respuesta guardada debe seguir siendo JSON completo y válido');
        $this->assertStringContainsString('transactions.29.turn_number', $log->response_summary);
    }

    public function test_pull_is_logged_as_a_readable_summary_of_counts_not_a_truncated_catalog(): void
    {
        $location = Location::factory()->create(['is_active' => true]);
        $device = Device::factory()->create(['location_id' => $location->id, 'is_enabled' => true]);
        License::factory()->create(['device_id' => $device->id, 'status' => 'ACTIVE', 'valid_from' => now()->subDay(), 'valid_to' => now()->addDay()]);
        User::create(['username' => 'cajero1', 'password' => 'Clave-Cajero-1!', 'role' => 'CASHIER', 'is_active' => true, 'location_id' => $location->id]);
        foreach (['A', 'B', 'C'] as $n) {
            Family::create(['name' => $n]);
        }
        $token = $device->createToken('t')->plainTextToken;

        $this->getJson('/api/sync/pull?'.http_build_query(['device_fingerprint' => $device->device_fingerprint]), ['Authorization' => "Bearer {$token}"])->assertOk();

        $log = ApiRequestLog::where('path', '/api/sync/pull')->latest('created_at')->first();
        $summary = json_decode($log->response_summary, true);

        $this->assertNotNull($summary, 'El resumen debe ser JSON válido');
        $this->assertSame(['registros' => 1], $summary['data']['users']);
        $this->assertSame(['registros' => 3], $summary['data']['families']);
        $this->assertArrayHasKey('syncTimestamp', $summary);
        $this->assertSame($device->device_fingerprint, $summary['data']['license']['deviceFingerprint']);
        $this->assertStringNotContainsString('cajero1', $log->response_summary);
        $this->assertStringNotContainsString(hash('sha384', 'Clave-Cajero-1!'), $log->response_summary);
    }

    public function test_catalog_summary_masks_secrets_and_rejects_unexpected_shapes(): void
    {
        $json = json_encode(['syncTimestamp' => 'x', 'data' => ['license' => ['licenseKey' => 'SECRETA', 'isActive' => true], 'users' => [['id' => 1], ['id' => 2]]]]);

        $summary = SensitiveData::summarizeCatalog($json, 8000);

        $this->assertStringNotContainsString('SECRETA', $summary);
        $this->assertSame(['registros' => 2], json_decode($summary, true)['data']['users']);

        $this->assertNull(SensitiveData::summarizeCatalog('{"message":"otra cosa"}', 8000));
        $this->assertNull(SensitiveData::summarizeCatalog('no es json', 8000));
        $this->assertNull(SensitiveData::summarizeCatalog(null, 8000));
    }
}
