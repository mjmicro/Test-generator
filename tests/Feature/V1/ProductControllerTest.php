<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('passport:install');
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $token = $user->createToken('test-token')->accessToken;
        Auth::loginUsingId($user->id);
        $this->headers = [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json'
        ];
    }

    /**
     * Test Product Index API
     *
     * @return void
     */
    public function test_product_index_api()
    {
        Product::factory()->create([
            'title' => 'Product1',
        ]);

        Product::factory()->create([
            'title' => 'Product2',
        ]);

        $response = $this->json('GET', route('products.index'), [], $this->headers);
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                ]
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total',
            ],
        ]);
    }

    /**
     * Test Product Store API
     *
     * @return void
     */
    public function test_product_store_api()
    {
        $data = [
            'title' => 'Product3',
        ];

        $response = $this->json('POST', route('products.store'), $data, $this->headers);
        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
            ],
        ]);
    }

    /**
     * Test Product Show API
     *
     * @return void
     */
    public function test_product_show_api()
    {
        $product = Product::factory()->create([
            'title' => 'Product4',
        ]);

        $response = $this->json('GET', route('products.show', $product->id), [], $this->headers);
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
            ],
        ]);
    }

    /**
     * Test Product Update API
     *
     * @return void
     */
    public function test_product_update_api()
    {
        $product = Product::factory()->create([
            'title' => 'Product5',
        ]);

        $data = [
            'title' => 'New Product5',
        ];

        $response = $this->json('PUT', route('products.update', $product->id), $data, $this->headers);
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
            ],
        ]);
    }

    /**
     * Test Product Delete API
     *
     * @return void
     */
    public function test_product_delete_api()
    {
        $product = Product::factory()->create([
            'title' => 'Product6',
        ]);

        $response = $this->json('DELETE', route('products.destroy', $product->id), [], $this->headers);
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
            ],
        ]);
    }

    /**
     * Test Product Search API
     *
     * @return void
     */
    public function test_product_search_api()
    {
        Product::factory()->create([
            'title' => 'Product7',
        ]);

        $product = Product::factory()->create([
            'title' => 'Product8',
        ]);

        $response = $this->json('GET', route('products.search', ['title' => 'Product8']), [], $this->headers);
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                ]
            ]
        ]);
    }
}
