<?php

namespace HelloWorld\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use HelloWorld\Models\Greeting;

class GreetingCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The greeting instance.
     */
    public Greeting $greeting;

    /**
     * Create a new event instance.
     */
    public function __construct(Greeting $greeting)
    {
        $this->greeting = $greeting;
    }
}
