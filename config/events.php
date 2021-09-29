<?php

/*
 * This file is part of the Events package.
 *
 * (c) Mark Fluehmann dbiz.apps@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    // Instead of manually registering each event listener individually,
    // the following array of pathes are scaned for classes matching 
    // the event listeners matching the predefined conditions.
    'listeners' => [
        'timatanga/events/tests/Data'
    ],

    // Instead of manually registering each event subscriber individually,
    // the following array of pathes are scaned for classes matching 
    // the event subscriber matching the predefined conditions.
    'subscribers' => [
        'timatanga/events/tests/Data'
    ],
];