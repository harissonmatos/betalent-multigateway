<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use App\Models\User;
use Database\Seeders\GatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_transactions()
    {
        $token = $this->actingAsRole('ADMIN');

        $this->seed(GatewaySeeder::class);

        Transaction::factory()->count(3)->create(['gateway_id' => 1]);

        $res = $this->getJson('/api/transactions', $this->authHeader($token));

        $res->assertStatus(200);
        $res->assertJsonStructure(['data', 'links']);
        $res->assertJsonCount(3, 'data');
    }

    public function test_manager_can_list_transactions()
    {
        $token = $this->actingAsRole('MANAGER');

        $this->seed(GatewaySeeder::class);

        Transaction::factory()->count(2)->create(['gateway_id' => 1]);

        $res = $this->getJson('/api/transactions', $this->authHeader($token));

        $res->assertStatus(200);
        $res->assertJsonCount(2, 'data');
    }

    public function test_unauthenticated_cannot_list_transactions()
    {
        $res = $this->getJson('/api/transactions', ['Accept' => 'application/json']);

        $res->assertStatus(401);
    }

    public function test_admin_can_view_transaction_detail_with_products()
    {
        $token = $this->actingAsRole('ADMIN');

        $this->seed(GatewaySeeder::class);

        $client = Client::factory()->create();
        $product1 = Product::factory()->create(['amount' => 50]);
        $product2 = Product::factory()->create(['amount' => 30]);

        $t = Transaction::factory()->create([
            'client_id' => $client->id,
            'gateway_id' => 1,
            'amount' => 200,
            'status' => 'paid',
        ]);

        TransactionProduct::factory()->create([
            'transaction_id' => $t->id,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);

        TransactionProduct::factory()->create([
            'transaction_id' => $t->id,
            'product_id' => $product2->id,
            'quantity' => 1,
        ]);

        $res = $this->getJson("/api/transactions/{$t->id}", $this->authHeader($token));

        $res->assertStatus(200);

        $res->assertJsonStructure([
            'id',
            'client_id',
            'gateway_id',
            'amount',
            'status',
            'gateway' => [
                'id',
                'name',
                'slug',
            ],
            'products' => [
                '*' => [
                    'id',
                    'product_id',
                    'quantity',
                ]
            ]
        ]);

        $this->assertCount(2, $res->json('products'));
    }

    public function test_manager_can_view_transaction()
    {
        $token = $this->actingAsRole('MANAGER');

        $this->seed(GatewaySeeder::class);

        $t = Transaction::factory()->create(['gateway_id' => 1]);

        $res = $this->getJson("/api/transactions/{$t->id}", $this->authHeader($token));

        $res->assertStatus(200);
    }

    public function test_transaction_not_found_returns_404()
    {
        $token = $this->actingAsRole('ADMIN');

        $res = $this->getJson('/api/transactions/999999', $this->authHeader($token));

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
            'password' => '123456'
        ]);

        return $login->json('token');
    }

    private function authHeader(string $token): array
    {
        return [
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$token}",
        ];
    }
}
