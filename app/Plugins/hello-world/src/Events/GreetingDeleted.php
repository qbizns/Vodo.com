<?php

namespace HelloWorld\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GreetingDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The deleted greeting ID.
     */
    public int $greetingId;

    /**
     * Create a new event instance.
     */
    public function __construct(int $greetingId)
    {
        $this->greetingId = $greetingId;
    }
}
