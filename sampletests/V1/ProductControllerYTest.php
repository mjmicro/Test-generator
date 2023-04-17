<?php

namespace Tests\Feature\API;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProductControllerYTest extends TestCase
{
    use RefreshDatabase;

    public function testUserCanListProducts()
    {
        Passport::actingAs(User::factory()->create(), ['create-servers']);

        Product::factory()->create(['title' => 'Product 1']);
        Product::factory()->create(['title' => 'Product 2']);
        Product::factory()->create(['title' => 'Product 3']);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonFragment(['title' => 'Product 3']);
    }

    public function testGuestCannotCreateProduct()
    {
        $product = Product::factory()->make();

        $response = $this->postJson('/api/products', $product->toArray());

        $response->assertUnauthorized();
    }

    public function testUserCanCreateProduct()
    {
        Passport::actingAs(User::factory()->create());

        $product = Product::factory()->make();

        $response = $this->postJson('/api/products', $product->toArray());

        $response->assertStatus(201);
        $response->assertJsonFragment(['title' => $product->title]);
        $this->assertDatabaseHas('products', ['title' => $product->title]);
    }

    public function testUserCanUpdateProduct()
    {
        Passport::actingAs(User::factory()->create());

        $product = Product::factory()->create(['title' => 'Product 1']);

        $response = $this->putJson("/api/products/{$product->id}", ['title' => 'Product 2']);

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'Product 2']);
        $this->assertDatabaseHas('products', ['title' => 'Product 2']);
    }

    public function testUserCanDeleteProduct()
    {
        Passport::actingAs(User::factory()->create());

        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => $product->title]);
        $this->assertDeleted($product);
    }

    public function testUserCanSearchProduct()
    {
        Passport::actingAs(User::factory()->create());

        Product::factory()->create(['title' => 'Product 1']);
        Product::factory()->create(['title' => 'Product 2']);

        $response = $this->getJson('/api/products/search/Product%202');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['title' => 'Product 2']);
    }
}
