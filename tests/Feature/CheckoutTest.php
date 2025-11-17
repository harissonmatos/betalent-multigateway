<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_processes_checkout_with_gateway_fallback()
    {
        Gateway::insert([
            'id' => 1,
            'name' => 'Gateway 1',
            'slug' => 'gateway1',
            'priority' => 1, // prioridade mais alta
            'is_active' => true,
        ]);

        Gateway::insert([
            'id' => 2,
            'name' => 'Gateway 2',
            'slug' => 'gateway2',
            'priority' => 2, // segunda opção
            'is_active' => true,
        ]);

        // Arrange – criar produtos
        $product1 = Product::factory()->create([
            'id' => 1,
            'amount' => 100.50
        ]);

        $product2 = Product::factory()->create([
            'id' => 2,
            'amount' => 154.41
        ]);

        $uuid = (string)Str::uuid();

        /**
         * GATEWAYS:
         *
         * gateway1 → falha
         * gateway2 → sucesso 201
         */
        Http::fake([
            'http://gateway1:3001/*' => Http::response(null, 400),
            'http://gateway2:3002/transacoes' => Http::response(['id' => $uuid], 201),
        ]);

        // Act – requisição de checkout
        $response = $this->postJson('/api/checkout', [
            "client" => [
                "name" => "Maria",
                "email" => "maria@example.com"
            ],
            "payment" => [
                "cardNumber" => "4111111111111111",
                "cvv" => "123",
                "expiry" => "12/30"
            ],
            "products" => [
                ["id" => 1, "quantity" => 1],
                ["id" => 2, "quantity" => 3],
            ]
        ]);

        // Assert – HTTP ok
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Assert – cliente criado
        $client = Client::first();
        $this->assertNotNull($client);

        // Assert – transação salva
        $transaction = Transaction::first();
        $this->assertNotNull($transaction);

        $this->assertEquals($client->id, $transaction->client_id);
        $this->assertEquals(2, $transaction->gateway_id); // gateway2 aprovado
        $this->assertEquals('paid', $transaction->status);
        $this->assertEquals('563.73', $transaction->amount);
        $this->assertEquals('1111', $transaction->card_last_numbers);
        $this->assertEquals($uuid, $transaction->external_id);

        // Assert – produtos da transação
        $this->assertDatabaseHas('transaction_products', [
            'transaction_id' => $transaction->id,
            'product_id' => 1,
            'quantity' => 1
        ]);

        $this->assertDatabaseHas('transaction_products', [
            'transaction_id' => $transaction->id,
            'product_id' => 2,
            'quantity' => 3
        ]);

        // Assert – estrutura do JSON
        $response->assertJsonStructure([
            'success',
            'transaction' => [
                'id',
                'client_id',
                'gateway_id',
                'external_id',
                'status',
                'amount',
                'card_last_numbers',
                'created_at',
                'updated_at',
            ]
        ]);
    }


    public function test_processes_checkout_with_gateway1()
    {
        Gateway::insert([
            'id' => 1,
            'name' => 'Gateway 1',
            'slug' => 'gateway1',
            'priority' => 1, // prioridade mais alta
            'is_active' => true,
        ]);

        Gateway::insert([
            'id' => 2,
            'name' => 'Gateway 2',
            'slug' => 'gateway2',
            'priority' => 2, // segunda opção
            'is_active' => true,
        ]);

        // Arrange – criar produtos
        $product1 = Product::factory()->create([
            'id' => 1,
            'amount' => 100
        ]);

        $product2 = Product::factory()->create([
            'id' => 2,
            'amount' => 154.41
        ]);

        $uuid = (string)Str::uuid();

        Http::fake([
            'http://gateway1:3001/transactions' => Http::response(['id' => $uuid], 201),
            'http://gateway2:3002/*' => Http::response([], 400),
        ]);

        // Act – requisição de checkout
        $response = $this->postJson('/api/checkout', [
            "client" => [
                "name" => "Maria",
                "email" => "maria@example.com"
            ],
            "payment" => [
                "cardNumber" => "4111111111112222",
                "cvv" => "123",
                "expiry" => "12/30"
            ],
            "products" => [
                ["id" => 1, "quantity" => 1],
                ["id" => 2, "quantity" => 3],
            ]
        ]);

        // Assert – HTTP ok
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Assert – cliente criado
        $client = Client::first();
        $this->assertNotNull($client);

        // Assert – transação salva
        $transaction = Transaction::first();
        $this->assertNotNull($transaction);

        $this->assertEquals($client->id, $transaction->client_id);
        $this->assertEquals(1, $transaction->gateway_id); // gateway1 aprovado
        $this->assertEquals('paid', $transaction->status);
        $this->assertEquals('563.23', $transaction->amount);
        $this->assertEquals('2222', $transaction->card_last_numbers);
        $this->assertEquals($uuid, $transaction->external_id);

        // Assert – produtos da transação
        $this->assertDatabaseHas('transaction_products', [
            'transaction_id' => $transaction->id,
            'product_id' => 1,
            'quantity' => 1
        ]);

        $this->assertDatabaseHas('transaction_products', [
            'transaction_id' => $transaction->id,
            'product_id' => 2,
            'quantity' => 3
        ]);

        // Assert – estrutura do JSON
        $response->assertJsonStructure([
            'success',
            'transaction' => [
                'id',
                'client_id',
                'gateway_id',
                'external_id',
                'status',
                'amount',
                'card_last_numbers',
                'created_at',
                'updated_at',
            ]
        ]);
    }

    public function test_fails_with_invalid_email()
    {
        // Nenhum gateway precisa existir para este teste
        Product::factory()->create([
            'id' => 1,
            'amount' => 100
        ]);

        $response = $this->postJson('/api/checkout', [
            "client" => [
                "name" => "Maria",
                "email" => "email-invalido"
            ],
            "payment" => [
                "cardNumber" => "4111111111111111",
                "cvv" => "123",
                "expiry" => "12/30"
            ],
            "products" => [
                ["id" => 1, "quantity" => 1],
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['client.email']);
    }

    public function test_fails_when_product_does_not_exist()
    {
        // Criar apenas um produto
        Product::factory()->create([
            'id' => 1,
            'amount' => 100
        ]);

        $response = $this->postJson('/api/checkout', [
            "client" => [
                "name" => "Maria",
                "email" => "maria@example.com"
            ],
            "payment" => [
                "cardNumber" => "4111111111111111",
                "cvv" => "123",
                "expiry" => "12/30"
            ],
            "products" => [
                ["id" => 1, "quantity" => 1],
                ["id" => 999, "quantity" => 1], // NÃO existe
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['products.1.id']);
        $response->assertJsonFragment(['Produto não encontrado.']);
    }


    public function test_fails_when_all_gateways_return_error()
    {
        Gateway::create([
            'id' => 1,
            'name' => 'Gateway 1',
            'slug' => 'gateway1',
            'priority' => 1,
            'is_active' => true,
        ]);

        Gateway::create([
            'id' => 2,
            'name' => 'Gateway 2',
            'slug' => 'gateway2',
            'priority' => 2,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'id' => 1,
            'amount' => 100
        ]);

        // Ambos gateways retornam erro
        Http::fake([
            'http://gateway1:3001/*' => Http::response([], 500),
            'http://gateway2:3002/*' => Http::response([], 500),
        ]);

        $response = $this->postJson('/api/checkout', [
            "client" => [
                "name" => "Maria",
                "email" => "maria@example.com"
            ],
            "payment" => [
                "cardNumber" => "4111111111111111",
                "cvv" => "123",
                "expiry" => "12/30"
            ],
            "products" => [
                ["id" => 1, "quantity" => 1],
            ]
        ]);

        $response->assertStatus(200); // Bad Gateway
        $response->assertJson([
            'success' => true,
            'transaction' => [
                'status' => 'failed',
            ]
        ]);

        // Assert – transação salva
        $transaction = Transaction::first();
        $this->assertNotNull($transaction);

        $this->assertEquals('failed', $transaction->status);
    }
}
