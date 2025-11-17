<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role): string
    {
        $user = User::factory()->create([
            'email'    => $role . '@teste.com',
            'password' => Hash::make('123456'),
            'role'     => $role
        ]);

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => '123456'
        ]);

        return $login->json('token');
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    /* ======================================================
     * ADMIN TESTS — pode tudo
     * ====================================================== */
    public function test_admin_can_list_users()
    {
        $token = $this->actingAsRole('ADMIN');

        User::factory(3)->create();

        $response = $this->getJson('/api/users', $this->authHeader($token));
        $response->assertStatus(200);
        $response->assertJsonCount(4, 'data'); // 3 criados + o admin
    }

    public function test_admin_can_create_user()
    {
        $token = $this->actingAsRole('ADMIN');

        $payload = [
            'name' => 'João',
            'email' => 'novo@teste.com',
            'password' => '123456',
            'role' => 'USER'
        ];

        $response = $this->postJson('/api/users', $payload, $this->authHeader($token));
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'novo@teste.com']);
    }

    public function test_admin_can_update_user()
    {
        $token = $this->actingAsRole('ADMIN');

        $user = User::factory()->create();

        $response = $this->putJson('/api/users/' . $user->id, [
            'name' => 'João',
            'email' => 'updated@teste.com',
            'role'  => 'FINANCE'
        ], $this->authHeader($token));

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['email' => 'updated@teste.com']);
    }

    public function test_admin_can_delete_user()
    {
        $token = $this->actingAsRole('ADMIN');

        $user = User::factory()->create();

        $response = $this->deleteJson('/api/users/' . $user->id, [], $this->authHeader($token));
        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /* ======================================================
     * MANAGER TESTS — pode tudo EXCETO ADMIN
     * ====================================================== */

    public function test_manager_can_create_non_admin_users()
    {
        $token = $this->actingAsRole('MANAGER');

        $response = $this->postJson('/api/users', [
            'name' => 'X',
            'email' => 'x@teste.com',
            'password' => '123456',
            'role' => 'USER',
        ], $this->authHeader($token));

        $response->assertStatus(201);
    }

    public function test_manager_cannot_create_admin()
    {
        $token = $this->actingAsRole('MANAGER');

        $response = $this->postJson('/api/users', [
            'name' => 'X',
            'email' => 'x@teste.com',
            'password' => '123456',
            'role' => 'ADMIN',
        ], $this->authHeader($token));

        $response->assertStatus(403);
    }

    public function test_manager_cannot_view_admin()
    {
        $token = $this->actingAsRole('MANAGER');

        $admin = User::factory()->create(['role' => 'ADMIN']);

        $response = $this->getJson('/api/users/' . $admin->id, $this->authHeader($token));
        $response->assertStatus(403);
    }

    public function test_manager_cannot_update_admin()
    {
        $token = $this->actingAsRole('MANAGER');

        $admin = User::factory()->create(['role' => 'ADMIN']);

        $response = $this->putJson('/api/users/' . $admin->id, [
            'email' => 'x@teste.com',
            'role'  => 'USER'
        ], $this->authHeader($token));

        $response->assertStatus(403);
    }

    public function test_manager_cannot_delete_admin()
    {
        $token = $this->actingAsRole('MANAGER');

        $admin = User::factory()->create(['role' => 'ADMIN']);

        $response = $this->deleteJson('/api/users/' . $admin->id, [], $this->authHeader($token));

        $response->assertStatus(403);
    }

    /* ======================================================
     * FINANCE / USER — proibidos
     * ====================================================== */

    public function test_finance_cannot_access_user_crud()
    {
        $token = $this->actingAsRole('FINANCE');

        $response = $this->getJson('/api/users', $this->authHeader($token));
        $response->assertStatus(403);
    }

    public function test_user_cannot_access_user_crud()
    {
        $token = $this->actingAsRole('USER');

        $response = $this->getJson('/api/users', $this->authHeader($token));
        $response->assertStatus(403);
    }

    /* ======================================================
     * Validação
     * ====================================================== */
    public function test_validation_fields()
    {
        $token = $this->actingAsRole('ADMIN');

        $response = $this->postJson('/api/users', [], $this->authHeader($token));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password', 'role']);
    }
}
