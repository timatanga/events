<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use timatanga\Events\Dispatcher;
use timatanga\Events\Event;

class EventListenerTest extends TestCase
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

    public function testAddListener()
    {
        $this->dispatcher->listen('pre.foo', [$this->listener, 'preFoo']);
        $this->dispatcher->listen('post.foo', [$this->listener, 'postFoo']);
        $this->assertTrue($this->dispatcher->hasListeners());
        $this->assertTrue($this->dispatcher->hasListeners(self::preFoo));
        $this->assertTrue($this->dispatcher->hasListeners(self::postFoo));
        $this->assertCount(1, $this->dispatcher->getListeners(self::preFoo));
        $this->assertCount(1, $this->dispatcher->getListeners(self::postFoo));
        $this->assertCount(2, $this->dispatcher->getListeners());
    }

    public function testGetListenersByEvent()
    {
        $listener1 = new TestEventListener();
        $listener2 = new TestEventListener();
        $listener3 = new TestEventListener();
        $listener1->name = '1';
        $listener2->name = '2';
        $listener3->name = '3';

        $this->dispatcher->listen('pre.foo', [$listener1, 'preFoo']);
        $this->dispatcher->listen('pre.foo', [$listener2, 'preFoo']);
        $this->dispatcher->listen('pre.foo', [$listener3, 'preFoo']);

        $expected = [
            [$listener1, 'preFoo'],
            [$listener2, 'preFoo'],
            [$listener3, 'preFoo'],
        ];

        $this->assertSame($expected, $this->dispatcher->getListeners('pre.foo'));
    }

    public function testGetAllListeners()
    {
        $listener1 = new TestEventListener();
        $listener2 = new TestEventListener();
        $listener3 = new TestEventListener();
        $listener4 = new TestEventListener();
        $listener5 = new TestEventListener();
        $listener6 = new TestEventListener();

        $this->dispatcher->listen('pre.foo', $listener1);
        $this->dispatcher->listen('pre.foo', $listener2);
        $this->dispatcher->listen('pre.foo', $listener3);
        $this->dispatcher->listen('post.foo', $listener4);
        $this->dispatcher->listen('post.foo', $listener5);
        $this->dispatcher->listen('post.foo', $listener6);

        $expected = [
            'pre.foo' => [$listener1, $listener2, $listener3],
            'post.foo' => [$listener4, $listener5, $listener6],
        ];

        $this->assertSame($expected, $this->dispatcher->getListeners());
    }

    public function testDispatch()
    {
        $this->dispatcher->listen('pre.foo', [$this->listener, 'preFoo']);
        $this->dispatcher->listen('post.foo', [$this->listener, 'postFoo']);
        $this->dispatcher->dispatch('pre.foo', [new Event(), self::preFoo]);

        $this->assertTrue($this->listener->preFooInvoked);
        $this->assertFalse($this->listener->postFooInvoked);
    }

    public function testDispatchWildcard()
    {
        $this->dispatcher->listen('pre.foo', [$this->listener, 'preFoo']);
        $this->dispatcher->listen('post.*', [$this->listener, 'postFoo']);

        $this->dispatcher->dispatch('post.foo', new Event());
        $this->assertTrue($this->listener->counter == 1);
        $this->dispatcher->dispatch('pre.foo', new Event);
        $this->assertTrue($this->listener->preFooInvoked);
        $this->assertTrue($this->listener->postFooInvoked);
    }

    public function testDispatchForClosure()
    {
        $invoked = 0;
        $listener = function () use (&$invoked) {
            ++$invoked;
        };
        $this->dispatcher->listen('pre.foo', $listener);
        $this->dispatcher->listen('post.foo', $listener);
        $this->dispatcher->dispatch('pre.foo', new Event);
        $this->assertEquals(1, $invoked);
    }

    public function testStopEventPropagation()
    {
        $otherListener = new TestEventListener();

        // postFoo() stops the propagation, so only one listener should be executed
        // Manually set priority to enforce $this->listener to be called first
        $this->dispatcher->listen('Event', [$this->listener, 'postFoo']);
        $this->dispatcher->listen('Event', [$otherListener, 'postFoo']);
        $this->dispatcher->dispatch(new Event);

        $this->assertTrue($this->listener->postFooInvoked);
        $this->assertFalse($otherListener->postFooInvoked);
    }

    public function testDispatchByPriority()
    {
        $invoked = [];
        $listener1 = function () use (&$invoked) {
            $invoked[] = '1';
        };
        $listener2 = function () use (&$invoked) {
            $invoked[] = '2';
        };
        $listener3 = function () use (&$invoked) {
            $invoked[] = '3';
        };
        $this->dispatcher->listen('pre.foo', $listener1);
        $this->dispatcher->listen('pre.foo', $listener2);
        $this->dispatcher->listen('pre.foo', $listener3);
        $this->dispatcher->dispatch('pre.foo', new Event);
        $this->assertEquals(['1', '2', '3'], $invoked);
    }

    public function testRemoveListener()
    {
        $this->dispatcher->listen('pre.bar', $this->listener);
        $this->assertTrue($this->dispatcher->hasListeners('pre.bar'));

        $this->dispatcher->unlisten('pre.bar', $this->listener);
        $this->assertFalse($this->dispatcher->hasListeners('pre.bar'));

        $this->dispatcher->unlisten('notExists', $this->listener);
    }

    public function testRemoveWildcardListener()
    {
        $this->dispatcher->listen('pre.bar', $this->listener);
        $this->dispatcher->listen('pre.*', $this->listener);
        $this->assertTrue($this->dispatcher->hasListeners('pre.bar'));
        $this->assertTrue($this->dispatcher->hasListeners('pre.*'));

        $this->dispatcher->unlisten('pre.bar', $this->listener);
        $this->assertFalse($this->dispatcher->hasListeners('pre.bar'));

        $this->dispatcher->unlisten('pre.*', $this->listener);
        $this->assertFalse($this->dispatcher->hasListeners('pre.*'));

        $this->dispatcher->unlisten('notExists', $this->listener);
    }
}