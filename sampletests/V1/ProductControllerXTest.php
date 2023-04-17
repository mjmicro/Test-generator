<?php

namespace Tests\Feature\API;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProductControllerXTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // create and authenticate a user with Passport
        Passport::actingAs(User::factory()->create(), ['api']);
    }

    public function test_it_returns_a_list_of_products()
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title']
                ],
                'links' => [],
                'meta' => []
            ]);
    }

    public function test_it_creates_a_product()
    {
        $data = ['title' => 'New Product'];

        $response = $this->postJson('/api/products', $data);

        $product = Product::find(1);

        $response->assertCreated()
            ->assertJson(['data' => ['id' => $product->id]])
            ->assertJsonStructure([
                'data' => ['id']
            ]);
    }

    public function test_it_shows_a_product()
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertOk()
            ->assertJson(['data' => ['id' => $product->id]])
            ->assertJsonStructure([
                'data' => ['id', 'title']
            ]);
    }

    public function test_it_updates_a_product()
    {
        $product = Product::factory()->create(['title' => 'Old Title']);

        $data = ['title' => 'New Title'];

        $response = $this->putJson("/api/products/{$product->id}", $data);

        $product->refresh();

        $response->assertOk()
            ->assertJson(['data' => ['id' => $product->id]])
            ->assertJsonStructure([
                'data' => ['id', 'title']
            ]);

        $this->assertEquals($data['title'], $product->title);
    }

    public function test_it_deletes_a_product()
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertOk()
            ->assertJson(['data' => ['id' => $product->id]])
            ->assertJsonStructure([
                'data' => ['id', 'title']
            ]);

        $this->assertDeleted($product);
    }

    public function test_it_searches_for_a_product_by_title()
    {
        Product::factory()->create(['title' => 'Laravel Book']);
        Product::factory()->create(['title' => 'PHP Book']);
        Product::factory()->create(['title' => 'JavaScript Book']);

        $response = $this->getJson('/api/products/search/Laravel');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Laravel Book']);
    }
}
