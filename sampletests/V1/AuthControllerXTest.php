<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuthControllerXTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    private $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = (new ClientRepository())->createPersonalAccessClient(
            null, 'Test Personal Access Client', url('/')
        );
    }

    /** @test */
    public function register_user_with_valid_credentials()
    {
        $email = $this->faker->email;
        $password = $this->faker->password;

        $response = $this->postJson('/api/register', [
            'email' => $email,
            'password' => $password,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'user' => [
                    'email' => $email
                ]
            ]);
    }

    /** @test */
    public function register_user_with_invalid_credentials()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/register', [
            'email' => $user->email,
            'password' => $this->faker->password,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function login_user_with_valid_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at'
                ],
                'access_token'
            ]);
    }

    /** @test */
    public function login_user_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'User does not exist, please check your details'
            ]);
    }

    /** @test */
    public function logout_user()
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['*']);

        $response = $this->postJson('/api/logout');
        $response->assertOk()
            ->assertJson([
                'message' => 'Logged out'
            ]);
    }
}
