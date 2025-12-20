<?php

namespace HelloWorld\Tests\Feature;

use HelloWorld\Models\Greeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GreetingApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the greetings API returns a list of greetings.
     */
    public function test_api_returns_greetings_list(): void
    {
        Greeting::create(['message' => 'Hello', 'author' => 'John']);
        Greeting::create(['message' => 'Hi', 'author' => 'Jane']);

        $response = $this->getJson('/api/v1/plugins/hello-world/greetings');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                     'meta' => [
                         'current_page',
                         'per_page',
                         'total',
                     ],
                 ]);
    }

    /**
     * Test that a greeting can be created via API.
     */
    public function test_api_can_create_greeting(): void
    {
        $response = $this->postJson('/api/v1/plugins/hello-world/greetings', [
            'message' => 'API Test Greeting',
            'author' => 'API Tester',
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Greeting created successfully',
                 ]);

        $this->assertDatabaseHas('hello_world_greetings', [
            'message' => 'API Test Greeting',
            'author' => 'API Tester',
        ]);
    }

    /**
     * Test that a specific greeting can be retrieved via API.
     */
    public function test_api_can_get_single_greeting(): void
    {
        $greeting = Greeting::create([
            'message' => 'Test Message',
            'author' => 'Test Author',
        ]);

        $response = $this->getJson("/api/v1/plugins/hello-world/greetings/{$greeting->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $greeting->id,
                         'message' => 'Test Message',
                         'author' => 'Test Author',
                     ],
                 ]);
    }

    /**
     * Test that a greeting can be updated via API.
     */
    public function test_api_can_update_greeting(): void
    {
        $greeting = Greeting::create([
            'message' => 'Original Message',
            'author' => 'Original Author',
        ]);

        $response = $this->putJson("/api/v1/plugins/hello-world/greetings/{$greeting->id}", [
            'message' => 'Updated Message',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Greeting updated successfully',
                 ]);

        $this->assertDatabaseHas('hello_world_greetings', [
            'id' => $greeting->id,
            'message' => 'Updated Message',
        ]);
    }

    /**
     * Test that a greeting can be deleted via API.
     */
    public function test_api_can_delete_greeting(): void
    {
        $greeting = Greeting::create([
            'message' => 'To Be Deleted',
            'author' => 'Test',
        ]);

        $response = $this->deleteJson("/api/v1/plugins/hello-world/greetings/{$greeting->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Greeting deleted successfully',
                 ]);

        $this->assertDatabaseMissing('hello_world_greetings', [
            'id' => $greeting->id,
        ]);
    }

    /**
     * Test that API returns 404 for non-existent greeting.
     */
    public function test_api_returns_404_for_non_existent_greeting(): void
    {
        $response = $this->getJson('/api/v1/plugins/hello-world/greetings/99999');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'error' => [
                         'code' => 'GREETING_NOT_FOUND',
                     ],
                 ]);
    }

    /**
     * Test that API validates message is required.
     */
    public function test_api_validates_message_required(): void
    {
        $response = $this->postJson('/api/v1/plugins/hello-world/greetings', [
            'author' => 'Test Author',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['message']);
    }

    /**
     * Test that API can search greetings.
     */
    public function test_api_can_search_greetings(): void
    {
        Greeting::create(['message' => 'Hello World', 'author' => 'John']);
        Greeting::create(['message' => 'Goodbye World', 'author' => 'Jane']);
        Greeting::create(['message' => 'Test Message', 'author' => 'Test']);

        $response = $this->getJson('/api/v1/plugins/hello-world/greetings?search=World');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * Test that API can filter greetings by author.
     */
    public function test_api_can_filter_by_author(): void
    {
        Greeting::create(['message' => 'Hello', 'author' => 'John']);
        Greeting::create(['message' => 'Hi', 'author' => 'John']);
        Greeting::create(['message' => 'Hey', 'author' => 'Jane']);

        $response = $this->getJson('/api/v1/plugins/hello-world/greetings?author=John');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);
    }
}
