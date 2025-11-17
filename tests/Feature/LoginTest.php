<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_token()
    {
        // Arrange: cria usuário
        $user = User::factory()->create([
            'email' => 'teste@example.com',
            'password' => Hash::make('senha123')
        ]);

        // Act: tenta login
        $response = $this->postJson('/api/login', [
            'email' => 'teste@example.com',
            'password' => 'senha123'
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'email', 'role'],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_user_cannot_login_with_wrong_password()
    {
        User::factory()->create([
            'email' => 'teste@example.com',
            'password' => Hash::make('senha123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'teste@example.com',
            'password' => 'errada'
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Credenciais inválidas']);
    }

    public function test_user_cannot_login_with_nonexistent_email()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'naoexiste@example.com',
            'password' => 'senha123'
        ]);

        $response->assertStatus(401);
    }

    public function test_login_requires_email_and_password()
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_authenticated_user_can_access_protected_route()
    {
        $user = User::factory()->create([
            'email' => 'teste@example.com',
            'password' => Hash::make('senha123')
        ]);

        // login (pega token)
        $login = $this->postJson('/api/login', [
            'email' => 'teste@example.com',
            'password' => 'senha123'
        ]);

        $token = $login->json('token');

        // acessar rota protegida
        $response = $this->getJson('/api/me', [
            'Authorization' => "Bearer {$token}"
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'email' => 'teste@example.com'
            ]);
    }

    public function test_protected_route_requires_token()
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }
}
