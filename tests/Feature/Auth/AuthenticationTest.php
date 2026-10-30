<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_admins_can_authenticate_using_email_and_password(): void
    {
        $admin = Admin::factory()->create([
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_unauthenticated_admins_are_redirected_to_login_when_attempting_to_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_admins_can_logout(): void
    {
        $admin = Admin::factory()->create([
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->actingAs($admin)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_invalid_credentials_fail(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertBack();
        $response->assertSessionHasErrors('email');
    }
}
