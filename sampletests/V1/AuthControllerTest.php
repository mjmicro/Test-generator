<?php

namespace Tests\Feature\API\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use DatabaseMigrations, WithFaker;
    public function setUp() : void {
                parent::setUp();
                $this->artisan('passport:install');
        }
    public function test_user_can_register()
    {
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'password' => $this->faker->password
        ];

        $response = $this->postJson('/api/register', $data);

        $response
            ->assertStatus(201);
            // ->assertJsonStructure([
            //     'user' => [
            //         'id',
            //         'name',
            //         'email',
            //         'created_at',
            //         'updated_at'
            //     ],
            //     'access_token',
            // ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create(['password' => bcrypt($password = $this->faker->password)]);

        $data = [
            'email' => $user->email,
            'password' => $password
        ];

        $response = $this->postJson('/api/login', $data);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ],
                'access_token',
            ]);
    }

    public function test_user_can_logout()
    {
        Passport::actingAs(User::factory()->create(), ['*']);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)->assertJson(['message' => 'Logged out']);
    }
}
