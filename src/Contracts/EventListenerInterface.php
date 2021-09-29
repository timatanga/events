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

use timatanga\Events\Event;

interface EventListenerInterface
{
    
    /**
     * Auto discovered event listeners are listening to events
     * named like class name and executing the handle method
     *
     * @param Event  $event
     */
    public static function handle( $event );

}