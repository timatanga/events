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

interface EventDispatcherInterface
{

    /**
     * Register an event listener with the dispatcher.
     *
     * @param  \Closure|string|array  $events
     * @param  \Closure|string|array|null  $listener
     * @return void
     */
    public function listen( $events, $listener = null );


    /**
     * Removes one or all event listener from the specified event.
     *
     * @param  string  $event
     * @param  mixed  $listener
     * @return void
     */
    public function unlisten( string $event, $listener = null );


    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param  EventSubscriberInterface  $subscriber
     * @return void
     */
    public function subscribe( EventSubscriberInterface $subscriber );


    /**
     * Removes one or all event listener from the specified event.
     *
     * @param  EventSubscriberInterface  $subscriber
     * @return void
     */
    public function unsubscribe( EventSubscriberInterface $subscriber );


    /**
     * Fire an event and call the listeners.
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @return array|null
     */
    public function dispatch( $event, $payload = [] );


    /**
     * Checks whether an event has any registered listeners.
     *
     * @param string  $eventName
     * @return bool  true if the specified event has any listeners, false otherwise
     */
    public function hasListeners( string $event = null );


    /**
     * Get all of the listeners for a given event name sorted by descending priority.
     *
     * @param string  $eventName
     * @return array  The event listeners for the specified event, or all event listeners by event name
     */
    public function getListeners( string $event = null );

}