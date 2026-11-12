<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\License;
use App\Models\Location;
use App\Support\DevicePairingToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegisterDeviceRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_fingerprint_race_returns_the_existing_device_instead_of_500(): void
    {
        $location = Location::factory()->create(['is_active' => true]);
        $code = DevicePairingToken::generateNew($location->id)['code'];
        $raced = false;

        // Simula la otra petición que inserta la misma huella después de nuestra consulta de existencia y antes del INSERT (ya confirmada).
        Location::retrieved(function () use (&$raced, $location) {
            if ($raced) {
                return;
            }
            $raced = true;
            $id = (string) Str::uuid();
            DB::table('devices')->insert([
                'id' => $id, 'location_id' => $location->id,
                'device_fingerprint' => 'FP-RACE-1', 'is_enabled' => true,
                'registered_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            License::create(['device_id' => $id, 'valid_from' => now(), 'valid_to' => now()->addYear(), 'status' => 'ACTIVE']);
        });

        $this->postJson('/api/auth/register-device', ['device_fingerprint' => 'FP-RACE-1', 'pairing_token' => $code])
            ->assertOk()
            ->assertJsonStructure(['token']);

        $this->assertSame(1, Device::where('device_fingerprint', 'FP-RACE-1')->count());
    }

    public function test_pairing_code_is_single_use(): void
    {
        $location = Location::factory()->create(['is_active' => true]);
        $code = DevicePairingToken::generateNew($location->id)['code'];

        $this->assertSame($location->id, DevicePairingToken::validateAndConsume($code));
        $this->assertNull(DevicePairingToken::validateAndConsume($code));
    }

    public function test_registering_same_fingerprint_twice_with_new_codes_is_idempotent(): void
    {
        $location = Location::factory()->create(['is_active' => true]);

        $c1 = DevicePairingToken::generateNew($location->id)['code'];
        $this->postJson('/api/auth/register-device', ['device_fingerprint' => 'FP-2', 'pairing_token' => $c1])->assertOk();

        $c2 = DevicePairingToken::generateNew($location->id)['code'];
        $this->postJson('/api/auth/register-device', ['device_fingerprint' => 'FP-2', 'pairing_token' => $c2])->assertOk();

        $this->assertSame(1, Device::where('device_fingerprint', 'FP-2')->count());
    }
}
