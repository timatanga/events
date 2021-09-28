<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use timatanga\Events\Event;
use timatanga\Events\Dispatcher;

class HelperTest extends TestCase
{

    public function test_dispatch_helper()
    {
        $event = new Event(['name' => 'Event', 'data' => ['first', 'second']]);

        $dispatcher = new Dispatcher;

        listen('test', function($arg) { return 'just a test'; }, $dispatcher);

        $result = dispatch('test', $event, $dispatcher);

        $this->assertSame( $result[0], 'just a test');
    }


    public function test_snake_to_camel_case()
    {
        $snake = 'create_user_profile';

        $camel = snakeToCamel($snake);
        $this->assertSame( $camel, 'CreateUserProfile');

        $check = snakeToCamel($camel);        
        $this->assertSame( $check, 'CreateUserProfile');
    }


    public function test_camel_to_snake_case()
    {
        $camel = 'CreateUserProfile';

        $snake = camel_to_snake($camel);
        $this->assertSame( $snake, 'create_user_profile');

        $check = camel_to_snake($snake);        
        $this->assertSame( $check, 'create_user_profile');
    }
}