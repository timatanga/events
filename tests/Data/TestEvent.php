<?php

namespace Tests\Data;

use timatanga\Events\Contracts\EventListenerInterface;

class TestEvent implements EventListenerInterface
{

    /**
     * Auto discovered event listeners are listening to events
     * named like class name and executing the handle method
     *
     * @param Event  $event
     */
    public static function handle( $event )
    {
        return $event;
    }

}
