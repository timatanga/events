<?php

namespace Tests;

use timatanga\Events\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    /**
     * @var Event
     */
    private $event;


    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        $this->event = new Event(null, ['name' => 'Event']);
    }


    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown(): void
    {
        $this->event = null;
    }

    public function testConstruct()
    {
        $this->assertEquals($this->event, new Event(null, ['name' => 'Event']));
    }


    /**
     * Tests Event->getArgs().
     */
    public function testGetArguments()
    {
        // test getting all
        $this->assertSame(['name' => 'Event'], $this->event->getArguments());
    }


    public function testSetArguments()
    {
        $result = $this->event->setArguments(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $this->event->getArguments());
        $this->assertSame($this->event, $result);
    }


    public function testSetArgument()
    {
        $result = $this->event->setArgument('foo2', 'bar2');
        $this->assertSame(['name' => 'Event', 'foo2' => 'bar2'], $this->event->getArguments());
        $this->assertEquals($this->event, $result);
    }


    public function testGetArgument()
    {
        // test getting key
        $this->assertEquals('Event', $this->event->getArgument('name'));
    }


    public function testGetArgException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->event->getArgument('nameNotExist');
    }


    public function testOffsetGet()
    {
        // test getting key
        $this->assertEquals('Event', $this->event['name']);

        // test getting invalid arg
        $this->expectException(\InvalidArgumentException::class);
        $this->assertFalse($this->event['nameNotExist']);
    }


    public function testOffsetSet()
    {
        $this->event['foo2'] = 'bar2';
        $this->assertSame(['name' => 'Event', 'foo2' => 'bar2'], $this->event->getArguments());
    }


    public function testOffsetUnset()
    {
        unset($this->event['name']);
        $this->assertSame([], $this->event->getArguments());
    }


    public function testOffsetIsset()
    {
        $this->assertArrayHasKey('name', $this->event);
        $this->assertArrayNotHasKey('nameNotExist', $this->event);
    }


    public function testHasArgument()
    {
        $this->assertTrue($this->event->hasArgument('name'));
        $this->assertFalse($this->event->hasArgument('nameNotExist'));
    }


    public function testHasIterator()
    {
        $data = [];
        foreach ($this->event as $key => $value) {
            $data[$key] = $value;
        }
        $this->assertEquals(['name' => 'Event'], $data);
    }
}
