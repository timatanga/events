<?php

namespace Tests\Data;

use timatanga\Events\Contracts\EventSubscriberInterface;
use timatanga\Events\Event;


class EventByName extends Event
{
}


class EventByPropertyName extends Event
{
    public $name = 'EventByProperty';
}


class CallableClass
{
    public function __invoke()
    {
    }
}


class TestWithDispatcher
{
    public $name;
    public $dispatcher;
    public $invoked = false;

    public function foo($name, $dispatcher)
    {
        $this->name = $name;
        $this->dispatcher = $dispatcher;
    }

    public function __invoke($name, $dispatcher)
    {
        $this->name = $name;
        $this->dispatcher = $dispatcher;
        $this->invoked = true;
    }
}


class TestEventListener
{
    public $preFooInvoked = false;
    public $postFooInvoked = false;
    public $counter = 0;

    /* Listener methods */

    public function preFoo($e)
    {
        $this->preFooInvoked = true;
    }

    public function postFoo($e)
    {
        $this->postFooInvoked = true;
        $this->counter = ++$this->counter;

        if (!$this->preFooInvoked) {
            $e->stopPropagation();
        }
    }

    public function __invoke()
    {
    }
}


class TestEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['pre.foo' => 'preFoo', 'post.foo' => 'postFoo'];
    }
}


class TestEventSubscriberWithPriorities implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'pre.foo' => 'preFoo',
            'post.foo' => 'postFoo',
        ];
    }
}


class TestEventSubscriberWithMultipleListeners implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'pre.foo' => [
                'preFoo1', 'preFoo2'
            ],
        ];
    }
}