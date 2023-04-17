<?php

namespace Tests\Feature\API;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Post;
use App\Models\User;
use Laravel\Passport\Passport;

class PostControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_list_posts()
    {
        $posts = Post::factory()->count(5)->create();

        Passport::actingAs(User::factory()->create());

        $response = $this->get('/api/posts');

        $response->assertStatus(200);

        $response->assertJson([
            'status' => true,
            'posts' => $posts->toArray(),
        ]);
    }

    public function test_can_create_post()
    {
        $user = User::factory()->create();

        Passport::actingAs($user);

        $data = [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph,
        ];

        $response = $this->post('/api/posts', $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('posts', $data);

        $response->assertJson([
            'status' => true,
            'message' => 'Post Created successfully!',
        ]);
    }

    public function test_can_update_post()
    {
        $user = User::factory()->create();

        Passport::actingAs($user);

        $post = Post::factory()->create([
            // 'user_id' => $user->id,
            'description' => $this->faker->paragraph,
        ]);

        $data = [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph,
        ];

        $response = $this->put('/api/posts/' . $post->id, $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('posts', $data);

        $response->assertJson([
            'status' => true,
            'message' => 'Post Updated successfully!',
        ]);
    }

    public function test_can_delete_post()
    {
        $user = User::factory()->create();

        Passport::actingAs($user);

        $post = Post::factory()->create([
            // 'user_id' => $user->id,
            'description' => $this->faker->paragraph,
        ]);

        $response = $this->delete('/api/posts/' . $post->id);

        $response->assertStatus(200);

        $this->assertDeleted($post);

        $response->assertJson([
            'status' => true,
            'message' => 'Post Deleted successfully!',
        ]);
    }
}
