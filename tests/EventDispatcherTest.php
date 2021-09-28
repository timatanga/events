<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use timatanga\Events\Dispatcher;
use timatanga\Events\Event;

class EventDispatcherTest extends TestCase
{
    /* Some pseudo events */
    private const preFoo = 'pre.foo';
    private const postFoo = 'post.foo';
    private const preBar = 'pre.bar';

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    private $listener;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createEventDispatcher();
        $this->listener = new TestEventListener();
    }

    protected function tearDown(): void
    {
        $this->dispatcher = null;
        $this->listener = null;
    }

    protected function createEventDispatcher()
    {
        return new Dispatcher();
    }

    public function testInitialState()
    {
        $this->assertEquals([], $this->dispatcher->getListeners());
        $this->assertFalse($this->dispatcher->hasListeners(self::preFoo));
        $this->assertFalse($this->dispatcher->hasListeners(self::postFoo));
    }

    public function testRegisterListenerByName()
    {
        $this->dispatcher->listen('fooEvent', function($arg) { return $arg; });

        $result = $this->dispatcher->dispatch('foo_event', 'test');

        $this->assertTrue( $result[0] == 'test');
    }

    public function testRegisterListenerByClassPropertyName()
    {
        $this->dispatcher->listen('event_by_property', function($arg) { return $arg->getSubject(); });

        $result = $this->dispatcher->dispatch(new EventByPropertyName('test'));

        $this->assertTrue( $result[0] == 'test');
    }

    public function testRegisterListenerByClassName()
    {
        $this->dispatcher->listen('event_by_name', function($arg) { return $arg->getSubject(); });

        $result = $this->dispatcher->dispatch(new EventByName());

        $this->assertTrue( $result[0] == 'test');
    }
}