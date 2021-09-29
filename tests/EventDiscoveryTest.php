<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use timatanga\Events\Dispatcher;
use timatanga\Events\Event;

class EventDiscoveryTest extends TestCase
{

    public function testDiscoverSubscribers()
    {
        $dispatcher = new Dispatcher();

        // auto disover
        // - TestEvent Listener
        // - TestSubscriber
        $this->assertTrue($dispatcher->hasListeners('test'));
        $this->assertTrue($dispatcher->hasListeners('test_event'));

        $response = $dispatcher->dispatch('test', 'a message');
        $this->assertTrue($response[0] == 'a message');

        $response = $dispatcher->dispatch('test_event', 'a test event');
        $this->assertTrue($response[0] == 'a test event');
    }
}
