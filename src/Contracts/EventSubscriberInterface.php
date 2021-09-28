<?php

/*
 * This file is part of the Events package.
 *
 * (c) Mark Fluehmann dbiz.apps@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace timatanga\Events\Contracts;

interface EventSubscriberInterface
{
    
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
    public static function getSubscribedEvents();

}