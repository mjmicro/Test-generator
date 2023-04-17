<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function testCreateProduct()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $data = [
            'title' => 'new product',
        ];

        $response = $this->json('POST', '/api/products', $data);

        $response->assertStatus(201)
            ->assertJson([
                'data' => $data
            ]);

        $this->assertDatabaseHas('products', $data);
    }

    public function testUpdateProduct()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $product = Product::factory()->create();

        $data = [
            'title' => 'updated product',
        ];

        $response = $this->json('PUT', '/api/products/' . $product->id, $data);

        $response->assertStatus(200)
            ->assertJson([
                'data' => $data
            ]);

        $this->assertDatabaseHas('products', $data);
    }

    public function testDeleteProduct()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $product = Product::factory()->create();

        $response = $this->json('DELETE', '/api/products/' . $product->id);

        $response->assertStatus(200);

        $this->assertSoftDeleted('products', [
            'id' => $product->id
        ]);
    }

    public function testGetProducts()
    {
        $products = Product::factory(5)->create();

        $response = $this->json('GET', '/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function testGetSingleProduct()
    {
        $product = Product::factory()->create();

        $response = $this->json('GET', '/api/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $product->id,
                    'title' => $product->title,
                ]
            ]);
    }

    public function testSearchForProduct()
    {
        $products = Product::factory()->count(2)->create([
            'title' => 'new product',
        ]);

        $response = $this->json('GET', '/api/products/search/new product');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
