<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\SystemParameter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::factory()->create(['role' => 'super-admin']);

        SystemParameter::factory()->create([
            'mail_driver' => 'smtp',
            'mail_from_address' => 'noreply@tapandgo.local',
            'mail_from_name' => 'Tap&Go',
            'smtp_host' => null,
            'smtp_port' => null,
            'smtp_username' => null,
            'smtp_password' => null,
            'smtp_encryption' => null,
        ]);
    }

    public function test_system_settings_page_loads()
    {
        $this->actingAs($this->admin, 'admin')
            ->get('/admin')
            ->assertStatus(200);
    }

    public function test_mail_driver_can_be_updated()
    {
        $this->actingAs($this->admin, 'admin')
            ->patchJson('/admin/system-settings/1', [
                'mail_driver' => '365',
            ])
            ->assertOk();

        $this->assertDatabaseHas('system_parameters', [
            'id' => 1,
            'mail_driver' => '365',
        ]);
    }

    public function test_smtp_configuration_can_be_saved()
    {
        $this->actingAs($this->admin, 'admin')
            ->patchJson('/admin/system-settings/1', [
                'mail_driver' => 'smtp',
                'mail_from_address' => 'test@example.com',
                'mail_from_name' => 'Test Sender',
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 587,
                'smtp_username' => 'test@gmail.com',
                'smtp_password' => 'secretpass',
                'smtp_encryption' => 'tls',
            ])
            ->assertOk();

        $this->assertDatabaseHas('system_parameters', [
            'id' => 1,
            'mail_driver' => 'smtp',
            'mail_from_address' => 'test@example.com',
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
        ]);
    }

    public function test_office365_configuration_can_be_saved()
    {
        $this->actingAs($this->admin, 'admin')
            ->patchJson('/admin/system-settings/1', [
                'mail_driver' => '365',
                'office365_tenant_id' => 'tenant-123',
                'office365_client_id' => 'client-456',
                'office365_client_secret' => 'secret-789',
            ])
            ->assertOk();

        $this->assertDatabaseHas('system_parameters', [
            'id' => 1,
            'mail_driver' => '365',
            'office365_tenant_id' => 'tenant-123',
        ]);
    }

    public function test_sensitive_fields_are_encrypted()
    {
        $this->actingAs($this->admin, 'admin')
            ->patchJson('/admin/system-settings/1', [
                'mail_driver' => 'smtp',
                'smtp_password' => 'mypassword123',
                'office365_client_secret' => 'mysecret456',
            ])
            ->assertOk();

        $parameter = SystemParameter::find(1);

        // Values should be encrypted in database
        $this->assertNotEquals('mypassword123', $parameter->getRawOriginal('smtp_password'));
        $this->assertNotEquals('mysecret456', $parameter->getRawOriginal('office365_client_secret'));

        // But accessible decrypted through model
        $this->assertEquals('mypassword123', $parameter->smtp_password);
        $this->assertEquals('mysecret456', $parameter->office365_client_secret);
    }

    public function test_validate_smtp_connection_endpoint()
    {
        $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/mail/validate-smtp')
            ->assertOk()
            ->assertJsonStructure(['success', 'message']);
    }

    public function test_validate_office365_connection_endpoint()
    {
        $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/mail/validate-office365')
            ->assertOk()
            ->assertJsonStructure(['success', 'message']);
    }

    public function test_send_test_email_requires_valid_email()
    {
        $this->actingAs($this->admin, 'admin')
            ->postJson('/admin/mail/test', ['to_address' => 'invalid-email'])
            ->assertStatus(422);
    }

    public function test_admin_user_cannot_access_mail_endpoints_without_permission()
    {
        $normalAdmin = AdminUser::factory()->create(['role' => 'admin']);

        $this->actingAs($normalAdmin, 'admin')
            ->postJson('/admin/mail/validate-smtp')
            ->assertStatus(403);
    }
}
