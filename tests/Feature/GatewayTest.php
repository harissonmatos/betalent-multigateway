<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Models\User;
use Database\Seeders\GatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_gateways()
    {
        $this->seed(GatewaySeeder::class);

        $token = $this->actingAsRole('ADMIN');

        $res = $this->getJson('/api/gateways', $this->authHeader($token));

        $res->assertStatus(200);
        $res->assertJsonCount(2);
    }

    public function test_admin_can_activate_gateway()
    {
        $token = $this->actingAsRole('ADMIN');

        $this->seed(GatewaySeeder::class);

        Gateway::where('id', 1)->update(['is_active' => false]);

        $res = $this->putJson(
            "/api/gateways/1/activate",
            [],
            $this->authHeader($token)
        );

        $res->assertStatus(200);
        $this->assertDatabaseHas('gateways', [
            'id' => 1,
            'is_active' => true,
        ]);
    }

    /* ======================================================
     *  ADMIN — PODE TUDO
     * ====================================================== */

    public function test_admin_can_deactivate_gateway()
    {
        $token = $this->actingAsRole('ADMIN');

        $this->seed(GatewaySeeder::class);

        $res = $this->putJson(
            "/api/gateways/1/deactivate",
            [],
            $this->authHeader($token)
        );

        $res->assertStatus(200);
        $this->assertDatabaseHas('gateways', [
            'id' => 1,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_update_priority_and_reorder_correctly()
    {
        $token = $this->actingAsRole('ADMIN');

        $this->seed(GatewaySeeder::class);

        // Move g3 para prioridade 1
        $res = $this->putJson(
            "/api/gateways/1/priority",
            ['priority' => 2],
            $this->authHeader($token)
        );

        $res->assertStatus(200);

        // Recarregar todos
        $ordered = Gateway::orderBy('priority')->get();

        $this->assertEquals(2, $ordered[0]->id); // g2 agora é prioridade 1
        $this->assertEquals(1, $ordered[1]->id); // g1 vira 2
    }

    public function test_priority_cannot_be_invalid()
    {
        $token = $this->actingAsRole('ADMIN');

        $this->seed(GatewaySeeder::class);

        $res = $this->putJson(
            "/api/gateways/1/priority",
            ['priority' => 'abc'],
            $this->authHeader($token)
        );

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['priority']);
    }

    /* ======================================================
     * TESTE DA LÓGICA DE PRIORIDADE
     * ====================================================== */

    public function test_priority_above_count_puts_gateway_at_end()
    {
        $token = $this->actingAsRole('ADMIN');

        $this->seed(GatewaySeeder::class);

        // mover g1 para uma prioridade muito grande (ex: 999)
        $res = $this->putJson(
            "/api/gateways/1/priority",
            ['priority' => 999],
            $this->authHeader($token)
        );

        $res->assertStatus(200);

        $ordered = Gateway::orderBy('priority')->get();

        $this->assertEquals(2, $ordered[0]->id);
        $this->assertEquals(1, $ordered[1]->id); // foi para o final
    }

    public function test_manager_cannot_manage_gateways()
    {
        $token = $this->actingAsRole('MANAGER');

        $this->seed(GatewaySeeder::class);

        $res = $this->putJson(
            "/api/gateways/1/activate",
            [],
            $this->authHeader($token)
        );

        $res->assertStatus(403);
    }

    public function test_finance_cannot_manage_gateways()
    {
        $token = $this->actingAsRole('FINANCE');

        $this->seed(GatewaySeeder::class);

        $res = $this->putJson(
            "/api/gateways/1/deactivate",
            [],
            $this->authHeader($token)
        );

        $res->assertStatus(403);
    }

    /* ======================================================
     * ROLE CHECK — SOMENTE ADMIN
     * ====================================================== */

    public function test_user_cannot_manage_gateways()
    {
        $token = $this->actingAsRole('USER');

        $this->seed(GatewaySeeder::class);

        $res = $this->putJson(
            "/api/gateways/1/priority",
            ['priority' => 1],
            $this->authHeader($token)
        );

        $res->assertStatus(403);
    }

    private function actingAsRole(string $role): string
    {
        $user = User::factory()->create([
            'email' => $role.'@teste.com',
            'password' => Hash::make('123456'),
            'role' => $role,
        ]);

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => '123456',
        ]);

        return $login->json('token');
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }
}
