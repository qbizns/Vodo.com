<?php

namespace HelloWorld\Listeners;

use HelloWorld\Events\GreetingCreated;
use HelloWorld\Events\GreetingDeleted;
use Illuminate\Support\Facades\Log;

class LogGreetingActivity
{
    /**
     * Handle greeting created events.
     */
    public function handleCreated(GreetingCreated $event): void
    {
        Log::info('Greeting created', [
            'greeting_id' => $event->greeting->id,
            'message' => $event->greeting->message,
            'author' => $event->greeting->author,
        ]);

        // Trigger workflow if available
        if (app()->bound('workflow')) {
            app('workflow')->trigger('greeting.created', [
                'greeting_id' => $event->greeting->id,
                'message' => $event->greeting->message,
                'author' => $event->greeting->author,
            ]);
        }
    }

    /**
     * Handle greeting deleted events.
     */
    public function handleDeleted(GreetingDeleted $event): void
    {
        Log::info('Greeting deleted', [
            'greeting_id' => $event->greetingId,
        ]);

        // Trigger workflow if available
        if (app()->bound('workflow')) {
            app('workflow')->trigger('greeting.deleted', [
                'greeting_id' => $event->greetingId,
            ]);
        }
    }
}
