<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Data\CallableClass;
use Tests\Data\TestEventListener;
use Tests\Data\TestEventSubscriber;
use Tests\Data\TestEventSubscriberWithMultipleListeners;
use Tests\Data\TestEventSubscriberWithPriorities;
use Tests\Data\TestWithDispatcher;
use timatanga\Events\Contracts\EventSubscriberInterface;
use timatanga\Events\Dispatcher;
use timatanga\Events\Event;

class EventSubscriberTest extends TestCase
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
        return new Dispatcher(false);
    }

    public function testAddSubscriber()
    {
        $eventSubscriber = new TestEventSubscriber();
        $this->dispatcher->subscribe($eventSubscriber);
        $this->assertTrue($this->dispatcher->hasListeners('pre.foo'));
        $this->assertTrue($this->dispatcher->hasListeners('post.foo'));
    }

    public function testAddSubscriberWithMultipleListeners()
    {
        $eventSubscriber = new TestEventSubscriberWithMultipleListeners();
        $this->dispatcher->subscribe($eventSubscriber);

        $listeners = $this->dispatcher->getListeners('pre.foo');
        $this->assertTrue($this->dispatcher->hasListeners('pre.foo'));
        $this->assertCount(2, $listeners);
        $this->assertEquals('preFoo2', $listeners[1][1]);
    }

    public function testRemoveSubscriber()
    {
        $eventSubscriber = new TestEventSubscriber();
        $this->dispatcher->subscribe($eventSubscriber);
        $this->assertTrue($this->dispatcher->hasListeners('pre.foo'));
        $this->assertTrue($this->dispatcher->hasListeners('post.foo'));

        $this->dispatcher->unsubscribe($eventSubscriber);
        $this->assertFalse($this->dispatcher->hasListeners('pre.foo'));
        $this->assertFalse($this->dispatcher->hasListeners('post.foo'));
    }

    public function testRemoveSubscriberWithPriorities()
    {
        $eventSubscriber = new TestEventSubscriberWithPriorities();
        $this->dispatcher->subscribe($eventSubscriber);
        $this->assertTrue($this->dispatcher->hasListeners('pre.foo'));

        $this->dispatcher->unsubscribe($eventSubscriber);
        $this->assertFalse($this->dispatcher->hasListeners('pre.foo'));
    }

    public function testRemoveSubscriberWithMultipleListeners()
    {
        $eventSubscriber = new TestEventSubscriberWithMultipleListeners();
        $this->dispatcher->subscribe($eventSubscriber);
        $this->assertTrue($this->dispatcher->hasListeners('pre.foo'));
        $this->assertCount(2, $this->dispatcher->getListeners('pre.foo'));

        $this->dispatcher->unsubscribe($eventSubscriber);
        $this->assertFalse($this->dispatcher->hasListeners('pre.foo'));
    }

    public function testEventReceivesTheDispatcherInstanceAsArgument()
    {
        $listener = new TestWithDispatcher();
        $this->dispatcher->listen('test', [$listener, 'foo']);
        $this->assertNull($listener->name);
        $this->assertNull($listener->dispatcher);

        $this->dispatcher->dispatch('test', 'test');
        $this->assertEquals('test', $listener->name);
    }

    /**
     * @see https://bugs.php.net/62976
     *
     * This bug affects:
     *  - The PHP 5.3 branch for versions < 5.3.18
     *  - The PHP 5.4 branch for versions < 5.4.8
     *  - The PHP 5.5 branch is not affected
     */
    public function testWorkaroundForPhpBug62976()
    {
        $dispatcher = $this->createEventDispatcher();
        $dispatcher->listen('bug.62976', new CallableClass());
        $dispatcher->unlisten('bug.62976', function () {});
        $this->assertTrue($dispatcher->hasListeners('bug.62976'));
    }

    public function testHasListenersWhenAddedCallbackListenerIsRemoved()
    {
        $listener = function () {};
        $this->dispatcher->listen('foo', $listener);
        $this->dispatcher->unlisten('foo', $listener);
        $this->assertFalse($this->dispatcher->hasListeners());
    }

    public function testGetListenersWhenAddedCallbackListenerIsRemoved()
    {
        $listener = function () {};
        $this->dispatcher->listen('foo', $listener);
        $this->dispatcher->unlisten('foo', $listener);
        $this->assertSame([], $this->dispatcher->getListeners());
    }

    public function testHasListenersWithoutEventsReturnsFalseAfterHasListenersWithEventHasBeenCalled()
    {
        $this->assertFalse($this->dispatcher->hasListeners('foo'));
        $this->assertFalse($this->dispatcher->hasListeners());
    }

    public function testHasListenersIsLazy()
    {
        $called = 0;
        $listener = [function () use (&$called) { ++$called; }, 'onFoo'];
        $this->dispatcher->listen('foo', $listener);
        $this->assertTrue($this->dispatcher->hasListeners());
        $this->assertTrue($this->dispatcher->hasListeners('foo'));
        $this->assertSame(0, $called);
    }

    public function testDispatchLazyListener()
    {
        $dispatcher = new TestWithDispatcher();
        $called = 0;
        $factory = function () use (&$called, $dispatcher) {            
            ++$called;
            return $dispatcher;
        };
        $this->dispatcher->listen('foo', [$factory, 'foo']);
        $this->assertSame(0, $called);

        $this->dispatcher->dispatch('foo');

        $this->assertSame(1, $called);
        $this->assertFalse($dispatcher->invoked);

        $this->dispatcher->dispatch('foo');
        $this->assertSame(1, $called);

        $this->dispatcher->listen('bar', [$factory]);
        $this->assertSame(1, $called);

        $this->dispatcher->dispatch('bar');
        $this->assertTrue($dispatcher->invoked);

        $this->dispatcher->dispatch('bar');
        $this->assertSame(2, $called);
    }

    public function testRemoveFindsLazyListeners()
    {
        $test = new TestWithDispatcher();
        $factory = function () use ($test) { return $test; };

        $this->dispatcher->listen('foo', [$factory, 'foo']);
        $this->assertTrue($this->dispatcher->hasListeners('foo'));

        $this->dispatcher->unlisten('foo', [$test, 'foo']);
        $this->assertFalse($this->dispatcher->hasListeners('foo'));

        $this->dispatcher->listen('foo', [$test, 'foo']);
        $this->assertTrue($this->dispatcher->hasListeners('foo'));

        $this->dispatcher->unlisten('foo', [$factory, 'foo']);
        $this->assertFalse($this->dispatcher->hasListeners('foo'));
    }

    public function testGetLazyListeners()
    {
        $test = new TestWithDispatcher();
        $factory = function () use ($test) { return $test; };

        $this->dispatcher->listen('foo', [$factory, 'foo']);
        $this->assertSame([[$test, 'foo']], $this->dispatcher->getListeners('foo'));
        $this->dispatcher->unlisten('foo', [$test, 'foo']);

        $this->dispatcher->listen('bar', [$factory, 'foo']);
        $this->assertSame(['bar' => [[$test, 'foo']]], $this->dispatcher->getListeners());
    }

    public function testMutatingWhilePropagationIsStopped()
    {
        $testLoaded = false;
        $test = new TestEventListener();

        $this->dispatcher->listen('Event', [$test, 'postFoo']);
        $this->dispatcher->listen('Event', [function () use ($test, &$testLoaded) {
            $testLoaded = true;

            return $test;
        }, 'preFoo']);

        $this->dispatcher->dispatch(new Event());
        $this->assertTrue($test->postFooInvoked);
        $this->assertFalse($test->preFooInvoked);

        $test->preFoo(new Event());
        $this->dispatcher->dispatch(new Event());
        $this->assertTrue($testLoaded);
    }
}