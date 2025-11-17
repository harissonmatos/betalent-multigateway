<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use Database\Seeders\GatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransactionRefundTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role): string
    {
        $user = User::factory()->create([
            'email'    => $role . '@teste.com',
            'password' => Hash::make('123456'),
            'role'     => $role,
        ]);

        $login = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => '123456',
        ]);

        return $login->json('token');
    }

    private function auth(string $token): array
    {
        return [
            'Accept'        => 'application/json',
            'Authorization' => "Bearer {$token}",
        ];
    }

    /* =======================================================
     * ADMIN & FINANCE — SUCESSO
     * ======================================================= */

    public function test_admin_can_refund_paid_transaction()
    {
        $token = $this->actingAsRole('ADMIN');

        $this->seed(GatewaySeeder::class);

        $transaction = $this->makePaidTransaction();

        $res = $this->putJson(
            "/api/transactions/{$transaction->id}/refund",
            [],
            $this->auth($token)
        );

        $res->assertStatus(200)
            ->assertJsonFragment([
                'status' => 'refunded'
            ]);

        $this->assertDatabaseHas('transactions', [
            'id'     => $transaction->id,
            'status' => 'refunded',
        ]);
    }

    public function test_finance_can_refund_paid_transaction()
    {
        $token = $this->actingAsRole('FINANCE');

        $this->seed(GatewaySeeder::class);

        $transaction = $this->makePaidTransaction();

        $res = $this->putJson(
            "/api/transactions/{$transaction->id}/refund",
            [],
            $this->auth($token)
        );

        $res->assertStatus(200);
        $this->assertDatabaseHas('transactions', [
            'id'     => $transaction->id,
            'status' => 'refunded',
        ]);
    }

    /* =======================================================
     * PROIBIR OUTROS ROLES
     * ======================================================= */

    public function test_manager_cannot_refund()
    {
        $token = $this->actingAsRole('MANAGER');

        $this->seed(GatewaySeeder::class);

        $transaction = $this->makePaidTransaction();

        $res = $this->putJson(
            "/api/transactions/{$transaction->id}/refund",
            [],
            $this->auth($token)
        );

        $res->assertStatus(403);
    }

    public function test_user_cannot_refund()
    {
        $token = $this->actingAsRole('USER');

        $this->seed(GatewaySeeder::class);

        $transaction = $this->makePaidTransaction();

        $res = $this->putJson(
            "/api/transactions/{$transaction->id}/refund",
            [],
            $this->auth($token)
        );

        $res->assertStatus(403);
    }

    /* =======================================================
     * RESTRIÇÕES DE STATUS
     * ======================================================= */

    public function test_cannot_refund_non_paid_transaction()
    {
        $token = $this->actingAsRole('ADMIN');

        $this->seed(GatewaySeeder::class);

        $transaction = Transaction::factory()->create([
            'status'     => 'pending',
            'client_id'  => Client::factory()->create()->id,
            'gateway_id' => 1,
        ]);

        $res = $this->putJson(
            "/api/transactions/{$transaction->id}/refund",
            [],
            $this->auth($token)
        );

        $res->assertStatus(422);
        $res->assertJsonFragment([
            'message' => 'Esta transação não pode ser reembolsada.'
        ]);
    }

    public function test_cannot_refund_twice()
    {
        $token = $this->actingAsRole('ADMIN');

        $this->seed(GatewaySeeder::class);

        $transaction = Transaction::factory()->create([
            'status'     => 'refunded',
            'client_id'  => Client::factory()->create()->id,
            'gateway_id' => 1,
        ]);

        $res = $this->putJson(
            "/api/transactions/{$transaction->id}/refund",
            [],
            $this->auth($token)
        );

        $res->assertStatus(422);
    }

    /* =======================================================
     * GATEWAY INATIVO
     * ======================================================= */

    public function test_cannot_refund_if_gateway_inactive()
    {
        $token = $this->actingAsRole('ADMIN');

        $this->seed(GatewaySeeder::class);

        Gateway::where('id', 1)->update(['is_active' => false]);

        $transaction = Transaction::factory()->create([
            'status'     => 'paid',
            'gateway_id' => 1,
            'client_id'  => Client::factory()->create()->id,
        ]);

        $res = $this->putJson(
            "/api/transactions/{$transaction->id}/refund",
            [],
            $this->auth($token)
        );

        $res->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Gateway indisponível para reembolso.'
            ]);
    }

    /* =======================================================
     * NOT FOUND
     * ======================================================= */

    public function test_refund_transaction_not_found()
    {
        $token = $this->actingAsRole('ADMIN');

        $res = $this->putJson(
            '/api/transactions/999999/refund',
            [],
            $this->auth($token)
        );

        $res->assertStatus(404);
    }

    /* =======================================================
     * HELPERS
     * ======================================================= */

    private function makePaidTransaction(): Transaction
    {
        return Transaction::factory()->create([
            'status'     => 'paid',
            'client_id'  => Client::factory()->create()->id,
            'gateway_id' => 1,
        ]);
    }
}
