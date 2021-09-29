<?php

/*
 * This file is part of the Events package.
 *
 * (c) Mark Fluehmann dbiz.apps@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use timatanga\Events\Dispatcher;

if (! function_exists('listen')) {

    /**
     * Register an event listener with the dispatcher.
     * When in use without a container, the listen method accept the dispatcher as third argument
     *
     * @param string  $event 
     * @param mixed  $payload 
     * @param Dispatcher  $dispatcher 
     * @return void
     */
    function listen($event, $listener, $dispatcher = null )
    {
    	if (! $dispatcher )
    		throw new DispatcherException('Unable to locate dispatcher');

    	return $dispatcher->listen($event, $listener);
    }
}


if (! function_exists('dispatch')) {

    /**
     * Dispatch the event with the given arguments.
     * When in use without a container, the dispatch method accept the dispatcher as third argument
     *
     * @param string  $event 
     * @param mixed  $payload 
     * @param Dispatcher  $dispatcher 
     * @return void
     */
    function dispatch($event, $payload, $dispatcher = null )
    {
    	if (! $dispatcher )
    		throw new DispatcherException('Unable to locate dispatcher');

    	return $dispatcher->dispatch($event, $payload);
    }
}


if (! function_exists('camel_to_snake')) {

    /**
     * Convert camel case string to snake case 
     *
     * @param string  $input 
     * @return string
     */
    function camel_to_snake($input)
    {
        if ( is_null($input) )
            return null;

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}


if (! function_exists('snakeToCamel')) {

    /**
     * Convert snake case string to camel case 
     *
     * @param string  $input 
     * @return string
     */
    function snakeToCamel($input)
    {
        if ( is_null($input) )
            return null;
        
        return ucfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }
}


