<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_requires_session_and_updates_only_current_user(): void
    {
        $this->withHeader('Origin', 'http://localhost');
        $this->getJson('/api/me')->assertUnauthorized();
        $this->patchJson('/api/me', ['name' => 'Novo nome'])->assertUnauthorized();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->getJson('/api/me')->assertOk()->assertJsonPath('data.id', $user->id)
            ->assertJsonMissingPath('data.password')->assertJsonMissingPath('data.remember_token');
        $this->patchJson('/api/me', ['name' => 'Novo nome', 'users_id' => $other->id])->assertOk()
            ->assertJsonPath('data.name', 'Novo nome')->assertJsonPath('data.role', 'cliente');
        $this->assertSame('Novo nome', $user->fresh()->name);
        $this->assertNotSame('Novo nome', $other->fresh()->name);
        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.name', 'Novo nome');
    }

    public function test_profile_validates_fields_and_prevents_role_and_password_changes(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user);
        $this->patchJson('/api/me', ['role' => null, 'password' => 'newpassword', 'id' => $other->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['role', 'password', 'id']);
        $this->patchJson('/api/me', ['name' => '', 'email' => 'invalido'])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
        $this->patchJson('/api/me', ['name' => str_repeat('a', 256)])->assertUnprocessable();
        $this->patchJson('/api/me', ['email' => $other->email, 'current_password' => 'password'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->patchJson('/api/me', ['email' => $user->email])->assertOk();
        $this->assertSame('cliente', $user->fresh()->role);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_email_change_requires_current_password_and_keeps_session_and_role(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $this->patchJson('/api/me', ['email' => 'novo@example.com'])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->patchJson('/api/me', ['email' => 'novo@example.com', 'current_password' => 'errada'])->assertUnprocessable();
        $this->patchJson('/api/me', ['email' => 'novo@example.com', 'current_password' => 'password'])->assertOk()
            ->assertJsonPath('data.email', 'novo@example.com')->assertJsonPath('data.role', 'admin')
            ->assertJsonMissingPath('data.current_password');
        $this->assertNull($user->fresh()->email_verified_at);
        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.email', 'novo@example.com');
    }

    public function test_login_limit_counts_each_visitor_behind_the_proxy_separately(): void
    {
        $this->withHeader('Origin', 'http://localhost');
        $login = fn (string $ip) => $this->withHeader('X-Forwarded-For', $ip)
            ->postJson('/api/login', ['email' => 'ninguem@example.com', 'password' => 'errada']);
        for ($i = 0; $i < 6; $i++) {
            $login('203.0.113.1')->assertUnprocessable();
        }
        $login('203.0.113.1')->assertTooManyRequests();
        $login('203.0.113.2')->assertUnprocessable();
    }
}
