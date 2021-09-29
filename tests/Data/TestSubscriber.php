<?php

namespace Tests\Data;

use timatanga\Events\Contracts\EventSubscriberInterface;

class TestSubscriber implements EventSubscriberInterface
{

    protected static $listeners = [
        'test' => [
            'onTestPre'
        ]
    ];

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * A method name to call
     *  * An array composed of the method names to call
     *
     * For instance:
     *  * ['eventName' => 'methodName']
     *  * ['eventName' => ['methodName1', 'methodName2']]
     *
     * The code must not depend on runtime state as it will only be called at compile time.
     * All logic depending on runtime state must be put into the individual methods handling the events.
     *
     * @return array  array of methods to execute on event
     */
    public static function getSubscribedEvents()
    {
        return static::$listeners;
    }


    public function onTestPre($payload, $dispatcher)
    {
        return $payload;
    }
}
