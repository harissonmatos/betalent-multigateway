<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use App\Models\User;
use Database\Seeders\GatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_clients()
    {
        $token = $this->actingAsRole('ADMIN');

        Client::factory()->count(3)->create();

        $res = $this->getJson('/api/clients', $this->authHeader($token));

        $res->assertStatus(200);
        $res->assertJsonStructure(['data' => [['id']]]);
        $res->assertJsonCount(3, 'data');
    }

    public function test_manager_can_list_clients()
    {
        $token = $this->actingAsRole('MANAGER');

        Client::factory()->count(2)->create();

        $res = $this->getJson('/api/clients', $this->authHeader($token));

        $res->assertStatus(200);
        $res->assertJsonCount(2, 'data');
    }

    /* ======================================================
     * LISTAR CLIENTES
     * ====================================================== */

    public function test_finance_cannot_list_clients()
    {
        $token = $this->actingAsRole('FINANCE');

        $res = $this->getJson('/api/clients', $this->authHeader($token));

        $res->assertStatus(403);
    }

    public function test_user_cannot_list_clients()
    {
        $token = $this->actingAsRole('USER');

        $res = $this->getJson('/api/clients', $this->authHeader($token));

        $res->assertStatus(403);
    }

    public function test_unauthenticated_cannot_list_clients()
    {
        $res = $this->getJson('/api/clients', ['Accept' => 'application/json']);

        $res->assertStatus(401);
    }

    public function test_admin_can_view_client_with_all_purchases()
    {
        $token = $this->actingAsRole('ADMIN');

        $this->seed(GatewaySeeder::class);

        $client = Client::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        // Duas transações para esse cliente
        $t1 = Transaction::factory()->create([
            'client_id' => $client->id,
            'gateway_id' => 1,
            'amount' => 100,
            'status' => 'paid',
        ]);

        $t2 = Transaction::factory()->create([
            'client_id' => $client->id,
            'gateway_id' => 1,
            'amount' => 200,
            'status' => 'paid',
        ]);

        // Itens da transação 1
        TransactionProduct::factory()->create([
            'transaction_id' => $t1->id,
            'product_id' => $product1->id,
            'quantity' => 1,
        ]);

        // Itens da transação 2
        TransactionProduct::factory()->create([
            'transaction_id' => $t2->id,
            'product_id' => $product2->id,
            'quantity' => 2,
        ]);

        $res = $this->getJson("/api/clients/{$client->id}", $this->authHeader($token));

        $res->assertStatus(200);

        // Estrutura básica do cliente
        $res->assertJsonFragment([
            'id' => $client->id,
            'name' => $client->name,
        ]);

        // Garante que vieram as transações
        $res->assertJsonStructure([
            'id',
            'name',
            'email',
            'transactions' => [
                '*' => [
                    'id',
                    'amount',
                    'status',
                    'gateway_id',
                    'products' => [
                        '*' => [
                            'id',
                            'quantity',
                        ],
                    ],
                ],
            ],
        ]);

        // Deve ter exatamente 2 compras
        $this->assertCount(2, $res->json('transactions'));

        // Garante que os IDs das transações são os que criamos
        $transactionIds = collect($res->json('transactions'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$t1->id, $t2->id], $transactionIds);
    }

    public function test_client_with_no_purchases_returns_empty_transactions()
    {
        $token = $this->actingAsRole('ADMIN');

        $client = Client::factory()->create();

        $res = $this->getJson("/api/clients/{$client->id}", $this->authHeader($token));

        $res->assertStatus(200);
        $res->assertJsonFragment([
            'id' => $client->id,
            'name' => $client->name,
        ]);

        $this->assertIsArray($res->json('transactions'));
        $this->assertCount(0, $res->json('transactions'));
    }

    /* ======================================================
     * DETALHAR CLIENTE + TODAS AS COMPRAS
     * ====================================================== */

    public function test_manager_can_view_client_with_purchases()
    {
        $token = $this->actingAsRole('MANAGER');

        $client = Client::factory()->create();

        $res = $this->getJson("/api/clients/{$client->id}", $this->authHeader($token));

        $res->assertStatus(200);
    }

    public function test_finance_cannot_view_client()
    {
        $token = $this->actingAsRole('FINANCE');

        $client = Client::factory()->create();

        $res = $this->getJson("/api/clients/{$client->id}", $this->authHeader($token));

        $res->assertStatus(403);
    }

    public function test_user_cannot_view_client()
    {
        $token = $this->actingAsRole('USER');

        $client = Client::factory()->create();

        $res = $this->getJson("/api/clients/{$client->id}", $this->authHeader($token));

        $res->assertStatus(403);
    }

    public function test_client_not_found_returns_404()
    {
        $token = $this->actingAsRole('ADMIN');

        $res = $this->getJson('/api/clients/999999', $this->authHeader($token));

        $res->assertStatus(404);
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
