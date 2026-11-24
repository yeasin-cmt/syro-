<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_register_pages_render(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign in');
        $this->get('/register')->assertOk()->assertSee('Create account');
    }

    public function test_user_can_register(): void
    {
        $this->post('/register', [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect('/account');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'customer@example.com', 'role' => 'customer']);
    }

    public function test_registration_cannot_escalate_role(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'sneaky@example.com', 'role' => 'customer']);
    }

    public function test_customer_logs_in_to_account_and_admin_to_dashboard(): void
    {
        $customer = User::factory()->create(['password' => 'secret-password']);
        $this->post('/login', ['email' => $customer->email, 'password' => 'secret-password'])->assertRedirect('/account');
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        $admin = User::factory()->admin()->create(['password' => 'secret-password']);
        $this->post('/login', ['email' => $admin->email, 'password' => 'secret-password'])->assertRedirect('/admin');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['password' => 'secret-password', 'is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
