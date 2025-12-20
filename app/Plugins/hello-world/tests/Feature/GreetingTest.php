<?php

namespace HelloWorld\Tests\Feature;

use HelloWorld\Models\Greeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GreetingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the greetings index page is accessible.
     */
    public function test_greetings_index_page_is_accessible(): void
    {
        $response = $this->get('/plugins/hello-world');

        $response->assertStatus(200);
    }

    /**
     * Test that the greetings list page is accessible.
     */
    public function test_greetings_list_page_is_accessible(): void
    {
        $response = $this->get('/plugins/hello-world/greetings');

        $response->assertStatus(200);
    }

    /**
     * Test that a greeting can be created.
     */
    public function test_greeting_can_be_created(): void
    {
        $response = $this->post('/plugins/hello-world/greetings', [
            'message' => 'Test Greeting Message',
            'author' => 'Test Author',
        ]);

        $response->assertRedirect('/plugins/hello-world/greetings');

        $this->assertDatabaseHas('hello_world_greetings', [
            'message' => 'Test Greeting Message',
            'author' => 'Test Author',
        ]);
    }

    /**
     * Test that a greeting can be created with default author.
     */
    public function test_greeting_can_be_created_with_default_author(): void
    {
        $response = $this->post('/plugins/hello-world/greetings', [
            'message' => 'Test Greeting Without Author',
        ]);

        $response->assertRedirect('/plugins/hello-world/greetings');

        $this->assertDatabaseHas('hello_world_greetings', [
            'message' => 'Test Greeting Without Author',
            'author' => 'Anonymous',
        ]);
    }

    /**
     * Test that greeting message is required.
     */
    public function test_greeting_message_is_required(): void
    {
        $response = $this->post('/plugins/hello-world/greetings', [
            'author' => 'Test Author',
        ]);

        $response->assertSessionHasErrors('message');
    }

    /**
     * Test that a greeting can be deleted.
     */
    public function test_greeting_can_be_deleted(): void
    {
        $greeting = Greeting::create([
            'message' => 'Greeting to be deleted',
            'author' => 'Test Author',
        ]);

        $response = $this->delete("/plugins/hello-world/greetings/{$greeting->id}");

        $response->assertRedirect('/plugins/hello-world/greetings');

        $this->assertDatabaseMissing('hello_world_greetings', [
            'id' => $greeting->id,
        ]);
    }

    /**
     * Test greeting model scopes.
     */
    public function test_greeting_search_scope(): void
    {
        Greeting::create(['message' => 'Hello World', 'author' => 'John']);
        Greeting::create(['message' => 'Goodbye World', 'author' => 'Jane']);
        Greeting::create(['message' => 'Test Message', 'author' => 'Test']);

        $results = Greeting::search('World')->get();

        $this->assertCount(2, $results);
    }

    /**
     * Test greeting model by author scope.
     */
    public function test_greeting_by_author_scope(): void
    {
        Greeting::create(['message' => 'Hello', 'author' => 'John']);
        Greeting::create(['message' => 'Hi', 'author' => 'John']);
        Greeting::create(['message' => 'Hey', 'author' => 'Jane']);

        $results = Greeting::byAuthor('John')->get();

        $this->assertCount(2, $results);
    }

    /**
     * Test greeting model recent scope.
     */
    public function test_greeting_recent_scope(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Greeting::create([
                'message' => "Greeting {$i}",
                'author' => 'Test',
            ]);
        }

        $results = Greeting::recent(5)->get();

        $this->assertCount(5, $results);
    }

    /**
     * Test greeting short message accessor.
     */
    public function test_greeting_short_message_accessor(): void
    {
        $greeting = Greeting::create([
            'message' => 'This is a very long greeting message that should be truncated to fifty characters with ellipsis',
            'author' => 'Test',
        ]);

        $this->assertLessThanOrEqual(53, strlen($greeting->short_message)); // 50 + "..."
    }
}
