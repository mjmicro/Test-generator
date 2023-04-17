<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Passport\Passport;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;

class ProductControllerZTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_can_list_products()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->get('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_can_create_product()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $product = Product::factory()->make();

        $data = [
            'title' => $product->title,
        ];

        $response = $this->post('/api/products', $data);

        $response->assertStatus(201);
            // ->assertJsonStructure([
            //     'data' => [
            //         'id',
            //         'title',
            //         'created_at',
            //         'updated_at',
            //     ],
            // ]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_can_show_product()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $product = Product::factory()->create();

        $response = $this->get("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $product->id,
                    'title' => $product->title,
                ],
            ]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_can_update_product()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $product = Product::factory()->create();

        $data = [
            'title' => 'Updated Title',
        ];

        $response = $this->put("/api/products/{$product->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => $data['title'],
                ],
            ]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_can_delete_product()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $product = Product::factory()->create();

        $response = $this->delete("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $product->id,
                ],
            ]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_can_search_product()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $product1 = Product::factory()->create(['title' => 'Product 1']);
        $product2 = Product::factory()->create(['title' => 'Product 2']);

        $response = $this->get("/api/products/search/{$product1->title}");

        $response->assertStatus(200)
            // ->assertJsonStructure([
            //     'data' => [
            //         '*' => [
            //             'id',
            //             'title',
            //             'created_at',
            //             'updated_at',
            //         ]
            //     ],
            // ])
            ->assertJsonCount(1, 'data');
    }
}
