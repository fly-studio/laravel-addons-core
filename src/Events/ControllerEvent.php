<?php

namespace Addons\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Addons\Core\Contracts\Events\ControllerEvent as BaseControllerEvent;

class ControllerEvent extends BaseControllerEvent
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('controller');
    }
}
