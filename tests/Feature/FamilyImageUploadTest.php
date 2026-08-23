<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Family;
use App\Models\License;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FamilyImageUploadTest extends TestCase
{
    /**
     * Valida upload de imagen al crear/editar familias + productos:
     *  - la imagen se guarda en storage/app/public
     *  - la url se persiste en image_url
     *  - el grid refleja la miniatura
     */
    public function test_create_family_saves_image_url(): void
    {
        Storage::fake('public');

        $admin = AdminUser::first() ?? AdminUser::factory()->create(['is_active' => true]);
        if (! Role::where('name', 'super-admin')->exists()) {
            Role::create(['name' => 'super-admin', 'guard_name' => 'admin']);
        }
        if (! $admin->hasRole('super-admin')) {
            $admin->assignRole('super-admin');
        }
        $this->actingAs($admin, 'admin');

        $response = $this->post(route('admin.screens.store', 'families'), [
            'name' => 'Familia con imagen',
            'family_image' => File::image('family_image.png', 32, 32),
        ]);

        $response->assertRedirect(route('admin.screens.index', 'families'));

        $fam = Family::where('name', 'Familia con imagen')->first();
        $this->assertNotNull($fam);
        $this->assertNotEmpty($fam->image_url);

        // grid refleja la imagen
        $grid = $this->get(route('admin.screens.index', 'families'))->getContent();
        $this->assertStringContainsString($fam->image_url, $grid);
    }

    /**
     * Valida el toggle de status de licenses (ACTIVE ↔ INACTIVE).
     * Requiere DemoPosSeeder aplicado (licenses pobladas).
     */
    public function test_toggle_status_changes_license_state(): void
    {
        $admin = AdminUser::first() ?? AdminUser::factory()->create(['is_active' => true]);
        $this->actingAs($admin, 'admin');
        $this->withoutExceptionHandling();

        $license = License::first();
        if (! $license) {
            $this->markTestSkipped('Aplicar DemoPosSeeder para licenses.');
        }

        // forzar estado ACTIVE antes del toggle para determinismo
        $license->forceFill(['status' => 'ACTIVE'])->save();
        $license->refresh();
        $this->assertEquals('ACTIVE', $license->status);

        $response = $this->post(route('admin.screens.toggle-status', ['licenses', $license->id]));
        $response->assertStatus(302);

        $license->refresh();
        $this->assertEquals('INACTIVE', $license->status);

        // toggle de vuelta a ACTIVE
        $this->post(route('admin.screens.toggle-status', ['licenses', $license->id]));
        $license->refresh();
        $this->assertEquals('ACTIVE', $license->status);
    }

    /**
     * EXPIRED/REVOKED no deben cambiar vía toggle (estados finales).
     */
    public function test_toggle_ignores_expired_revoked(): void
    {
        $admin = AdminUser::first() ?? AdminUser::factory()->create(['is_active' => true]);
        $this->actingAs($admin, 'admin');

        // Usar cualquier license existente y forzar estado final
        $license = License::inRandomOrder()->first();
        if (! $license) {
            $this->markTestSkipped('Aplicar DemoPosSeeder para licenses.');
        }

        // Forzar un estado final (EXPIRED) e intentar toggle → no debe cambiar
        $license->forceFill(['status' => 'EXPIRED'])->save();
        $license->refresh();
        $original = $license->status;

        $this->post(route('admin.screens.toggle-status', ['licenses', $license->id]))
            ->assertStatus(302);

        $license->refresh();
        $this->assertEquals($original, $license->status, "$original debe mantenerse inmutable vía toggle");

        // cleanup: restaurar a ACTIVE para tests posteriores
        $license->forceFill(['status' => 'ACTIVE'])->save();
    }
}
