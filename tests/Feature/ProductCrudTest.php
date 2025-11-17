<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;


    public function test_admin_can_list_products()
    {
        $token = $this->actingAsRole('ADMIN');

        Product::factory(3)->create();

        $response = $this->getJson('/api/products', $this->authHeader($token));

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_product()
    {
        $token = $this->actingAsRole('ADMIN');

        $payload = [
            'name' => 'Produto X',
            'amount' => 99.90,
        ];

        $response = $this->postJson('/api/products', $payload, $this->authHeader($token));

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['name' => 'Produto X']);
    }

    /* ======================================================
     * ADMIN CAN DO EVERYTHING
     * ====================================================== */

    public function test_admin_can_view_product()
    {
        $token = $this->actingAsRole('ADMIN');

        $product = Product::factory()->create();

        $response = $this->getJson('/api/products/'.$product->id, $this->authHeader($token));

        $response->assertStatus(200)
            ->assertJson(['name' => $product->name]);
    }

    public function test_admin_can_update_product()
    {
        $token = $this->actingAsRole('ADMIN');

        $product = Product::factory()->create();

        $response = $this->putJson('/api/products/'.$product->id, [
            'name' => 'Atualizado',
            'amount' => 200,
        ], $this->authHeader($token));

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', ['name' => 'Atualizado']);
    }

    public function test_admin_can_delete_product()
    {
        $token = $this->actingAsRole('ADMIN');

        $product = Product::factory()->create();

        $response = $this->deleteJson('/api/products/'.$product->id, [], $this->authHeader($token));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_manager_can_create_product()
    {
        $token = $this->actingAsRole('MANAGER');

        $response = $this->postJson('/api/products', [
            'name' => 'Produto M',
            'amount' => 20.50,
        ], $this->authHeader($token));

        $response->assertStatus(201);
    }

    public function test_manager_can_update_product()
    {
        $token = $this->actingAsRole('MANAGER');

        $product = Product::factory()->create();

        $response = $this->putJson('/api/products/'.$product->id, [
            'name' => 'Novo Nome',
            'amount' => 10,
        ], $this->authHeader($token));

        $response->assertStatus(200);
    }

    /* ======================================================
     * MANAGER CAN ALSO MANAGE PRODUCTS
     * ====================================================== */

    public function test_user_can_list_products()
    {
        $token = $this->actingAsRole('USER');

        Product::factory(3)->create();

        $response = $this->getJson('/api/products', $this->authHeader($token));

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_user_can_show_product()
    {
        $token = $this->actingAsRole('USER');

        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}", $this->authHeader($token));

        $response->assertStatus(200);

        $response->assertJson([
            'id' => $product->id,
            'name' => $product->name,
        ]);
    }

    /* ======================================================
     * USER CAN LIST AND SHOW PRODUCTS
     * ====================================================== */


    public function test_validation_errors_on_create()
    {
        $token = $this->actingAsRole('ADMIN');

        $response = $this->postJson('/api/products', [], $this->authHeader($token));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'amount']);
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

    /* ======================================================
     * Validation tests
     * ====================================================== */

    private function authHeader(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }
}
