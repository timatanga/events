<?php

/*
 * This file is part of the Dispatcher package.
 *
 * (c) Mark Fluehmann dbiz.apps@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace timatanga\Events;

use Psr\EventDispatcher\StoppableEventInterface;
use timatanga\Events\Contracts\EventDispatcherInterface;
use timatanga\Events\Contracts\EventSubscriberInterface;
use timatanga\Events\Exceptions\RegisterEventException;

class Dispatcher implements EventDispatcherInterface
{

    /**
     * The registered event listeners.
     * 
     * @var array
     */
    private $listeners = [];

    /**
     * The registered wildcard listeners.
     * 
     * @var array
     */
    private $wildcardListeners = [];

    /**
     * Cached Listeners
     * 
     * @var array
     */
    private $cached = [];

    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct()
    { 
    } 


    /**
     * Register an event listener with the dispatcher.
     *
     * @param  string|array  $events
     * @param  callable|array  $listener
     * @return void
     */
    public function listen( $events, $listener = null )
    {
        if ( !is_string($events) && !is_array($events) )
            throw new RegisterEventException('Failed to register event. Events should be provided as string or array of strings');

        foreach ((array) $events as $event) {

            // convert event name to snake case
            $event = camel_to_snake($event);

            // register event
            if ( strpos($event, '.*') == false )
                $this->listeners[$event][] = $listener;

            // register wildcard listener
            if ( strpos($event, '.*') != false )
                $this->wildcardListeners[$event][] = $listener;

            // remove cached event listeners
            unset($this->cached[$event]);
        }
    }


    /**
     * Removes one or all event listener from the specified event.
     *
     * @param  string  $event
     * @param  mixed  $listener
     * @return void
     */
    public function unlisten( string $event, $listener = null )
    {
        // convert event name to snake case
        $event = camel_to_snake($event);

        // while listener is not set, remove all listeners for event
        if ( is_null($listener) ) 
            unset($this->listeners[$event], $this->wildcardListeners[$event], $this->wildcardListeners[$event.'.*']);

        // unregister listeners
        if ( strpos($event, '*') == false )
            $this->unregisterListener($event, $listener);

        // unregister listeners
        if ( strpos($event, '*') != false )
            $this->unregisterWildcardListener($event, $listener);
    }


    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param  EventSubscriberInterface  $subscriber
     * @return void
     */
    public function subscribe( EventSubscriberInterface $subscriber )
    {
        foreach ( $subscriber->getSubscribedEvents() as $event => $params ) {

            // convert event name to snake case
            $event = camel_to_snake($event);

            if ( is_string($params) ) {
                $this->listen( $event, [$subscriber, $params] );
            }

            if ( is_array($params) ) {
                foreach ($params as $method) {
                    $this->listen( $event, [$subscriber, $method] );
                }
            }
        }
    }


    /**
     * Removes a registered subscriber
     *
     * @param  EventSubscriberInterface  $subscriber
     * @return void
     */
    public function unsubscribe( EventSubscriberInterface $subscriber )
    {
        foreach ( $subscriber->getSubscribedEvents() as $event => $params ) {

            // convert event name to snake case
            $event = camel_to_snake($event);

            if ( is_string($params) ) {
                $this->unlisten( $event, [$subscriber, $params] );
            }

            if ( is_array($params) ) {
                foreach ($params as $method) {
                    $this->unlisten( $event, [$subscriber, $method] );
                }
            }
        }
    }


    /**
     * Fire an event and call the listeners.
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @return array|null
     */
    public function dispatch( $event, $payload = [] )
    {
        // resolve stoppable capability
        $stoppable = $event instanceof StoppableEventInterface;

        // When the given "event" is an object the event class is assumed as event name
        [$eventName, $payload] = $this->parseEventAndPayload($event, $payload);
dump($eventName);
        $responses = [];

        foreach ($this->getListeners($eventName) as $listener) {

            // If an event supports the StoppableEventInterface is has set propagation stopped
            // we will stop propagating the event to any further listeners down in the chain.
            if ( $stoppable && $event->isPropagationStopped() )
                break;

            $responses[] = $listener($payload, $this);
        }

        return $responses;
    }


    /**
     * Checks whether an event has any registered listeners.
     *
     * @param string  $event
     * @return bool  true if the specified event has any listeners, false otherwise
     */
    public function hasListeners( string $event = null )
    {
        // convert event name to snake case
        $event = camel_to_snake($event);

        // check for listeners by given event name
        if (! is_null($event) ) 
            return (!empty($this->listeners[$event]) || !empty($this->wildcardListeners[$event]));

        // combine listeners
        $listeners = array_replace($this->listeners ?? [], $this->wildcardListeners ?? []);

        return count($listeners) > 0 ? true : false;
    }


    /**
     * Get all of the listeners for a given event name
     *
     * @param string  $event
     * @return array  The event listeners for the specified event, or all event listeners by event name
     */
    public function getListeners( string $event = null )
    {
        // convert event name to snake case
        $event = camel_to_snake($event);

        // build wildcard event pattern
        $wEvent = $this->buildWildcardEvent($event);

        // resolve event listeners for given event
        if (! is_null($event) ) {

            if ( empty($this->listeners[$event]) && empty($this->wildcardListeners[$wEvent]) )
                return [];

            if (! isset($this->cached[$event]) )
                $this->buildCache($event);

            return array_replace($this->cached[$event] ?? [], $this->cached[$wEvent] ?? []);
        }

        // resolve all event listeners
        $listeners = array_merge($this->listeners, $this->wildcardListeners);

        // resolve lazy listeners
        foreach ($listeners as $event => $elements) {

            if (! isset($this->cached[$event]) )
                $this->buildCache($event);
        }

        return array_replace($this->cached ?? [], $this->cached ?? []);
    }


    /**
     * Build cache of listeners including resolved lazy listeners
     *
     * @param mixed  $listener
     * @return void
     */
    protected function buildCache( string $event = null )
    {
        // build wildcard event pattern
        $wEvent = $this->buildWildcardEvent($event);

        // resolve lazy listeners
        if ( isset($this->listeners[$event]) )
            foreach ($this->listeners[$event] as $key => $listener) {
                $this->cached[$event][$key] = $this->rebuildListener($listener);
            }

        // resolve lazy listeners
        if ( isset($this->wildcardListeners[$wEvent]) )
            foreach ($this->wildcardListeners[$wEvent] as $key => $listener) {
                $this->cached[$wEvent][$key] = $this->rebuildListener($listener);
            }

    }


    /**
     * Rebuild listener for closure based listeners
     *
     * @param mixed  $listener
     * @return callable|array  
     */
    protected function rebuildListener( $listener )
    {
        if ( is_array($listener) && isset($listener[0]) && $listener[0] instanceof \Closure && count($listener) <= 2 ) {
            $listener[0] = $listener[0]();
            $listener[1] = $listener[1] ?? '__invoke';
        }

        return $listener;
    }


    /**
     * Parse the given event and payload and prepare them for dispatching.
     *
     * @param  mixed  $event
     * @param  mixed  $payload
     * @return array
     */
    protected function parseEventAndPayload($event, $payload)
    {
        if (is_object($event)) {

            if ( property_exists($event, 'name') && isset($event->{'name'}) ) {

                [$event, $payload] = [$event->{'name'}, $event];                

            } else {

                $classPath = explode('\\', get_class($event));

                [$event, $payload] = [end($classPath), $event];                
            }

        }

        return [camel_to_snake($event), $payload];
    }


    /**
     * Unregister listener
     *
     * @param string  $event
     * @param mixed  $listener
     * @return void
     */
    protected function unregisterListener( string $event, $listener )
    {
        if (! isset($this->listeners[$event]) )
            return;

        // resolve listener if it's a lazy listener
        $listener = $this->rebuildListener($listener);

        // check for presence of listener for given event
        foreach ($this->listeners[$event] as $key => $fn) {

            // resolve listener if it's a lazy listener
            $fn = $this->rebuildListener($fn);

            if ( $fn === $listener )
                unset($this->listeners[$event][$key], $this->cached[$event]);
        }

        if ( empty($this->listeners[$event]) )
            unset($this->listeners[$event], $this->cached[$event]);
    }


    /**
     * Unregister Wildcard listener
     *
     * @param string  $event
     * @param mixed  $listener
     * @return void
     */
    protected function unregisterWildcardListener( string $event, $listener )
    {
        if (! isset($this->wildcardListeners[$event]) )
            return;

        // check for presence of listener for given event
        foreach ($this->wildcardListeners[$event] as $key => $fn) {

            if ( $fn === $listener )
                unset($this->wildcardListeners[$event][$key], $this->cached[$event]);
        }

        if ( empty($this->wildcardListeners[$event]) )
            unset($this->wildcardListeners[$event], $this->cached[$event]);
    }


    /**
     * Build wildcard event it given event does not include the wildcard pattern itself
     * 
     * @param string|null  $event
     */
    protected function buildWildcardEvent( $event )
    {
        if ( is_null($event) )
            return $event;

        if ( strpos($event, '.') == false )
            return $event;

        if ( strpos($event, '.*') != false )
            return $event;

        return substr($event, 0, strpos($event, '.')) . '.*';
    }
}