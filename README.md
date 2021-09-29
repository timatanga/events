# Events
Bringing together various application packages requires a common foundation for communication needs. For that reason events serve best to decouple packages while allowing to hook in to state changes.

This package is heavily influenced by Laravel and Symfony. The passion for great code and influence they provide for the community is massive. Please see there documentation for more:
https://symfony.com/doc/current/components/event_dispatcher.html#events
https://laravel.com/docs/8.x/events


Why not using there components and providing an alternate component? There are various reasons why I've choosen this path:
- Laravel provides an inspiring, expressive and easy to use interface. On the other hand the cascade of required depencencies to other packages is what worries me. While building a web application I wanted to have a footprint without or at least a minimal footprint of dependencies. Also some capabilities might not be required.
- The Symfony EventDispatcher Component provides a massive documentation and fantastic explanation of use cases for event usagages. In regard to minimal depencencies it fulfills what is expected of a foundation package. On the other hand the interface needs some brainware if you're not using it on a daily basis.   

So why not bringing these advantages together in a single, easy and lightweight events package.



## Installation
composer require timatanga/events



## Dispatcher
The Dispatcher class is like the heartbeat of an event notification system. Events and listeners
Creating an event dispatcher instance is as easy as:

	use timatanga\Events\Dispatcher;

	$dispatcher = new Dispatcher();


Supported capabilities by the event dispatcher are

	// register one event (as string) or multiple events (as array of strings) for listener
	$dispatcher->listen( $events, $listener );

	// unregister event listeners
	$dispatcher->unlisten( $events, $listener );

	// subscribe subscriber implementing EventSubscriberInterface 
	$dispatcher->subscribe( $subscriber );

	// unregister subscriber
	$dispatcher->unsubscribe( $subscriber );

	// dispatch event class (payload is then event itself) or event name as string with optional payload argument
	$dispatcher->dispatch( $event, $payload );

	// check if any listener is registered for event (or all listeners when argument left away )
	$dispatcher->hasListeners( $event );

	// get event listeners for event (or all listeners when argument left away )
	$dispatcher->getListeners( $event );



## Events
It is common to dispatch event classes for communication between application packages. An event class serves as container for related datasets. Depending on the use case, building event classes for any event might be too much of overhead. What is common for both cases, they share an event name and event data.


### Event Naming
When an event is dispatched, it’s identified by a unique name, which any number of listeners might be listening to. 
The unique event name can be any string, but optionally follows a few naming conventions:
* use only lowercase letters, numbers, dots (.) and underscores (_);
* prefix names with a namespace followed by a dot (e.g. order.*, user.*);
* end names with a verb that indicates what action has been taken (e.g. order.placed, order_placed).

To ensure these naming conventions, the event dispatcher includes some checks and transformations:
* for event classes with a 'name' property, the name property is transformed to snake case
* for event classes without a 'name' property, event names are built by class name (without namespace), transformed to snake case
  e.g. event class 'User\UserProfileCreated' is transformed to event name 'user_profile_created'
* for events registered and dispatches as string, a snake case transformation is applied
  e.g. event named 'userProfileCreated' is transformed to event name 'user_profile_created'


### Event Payload
Events dispatched as event classes as well as in the lightweight mode are capable of transfering payload.

Event playload for dispatches event classes:

	// dispatching event class
	$dispatcher->dispatch(new Event($subject, [..]))

	// event name: 'event'
	// event payload: event class itself

Please consider the event class chapter for further details about the generic event class

Event playload for lightweight mode:

	// dispatching simple event
	$dispatcher->dispatch('event', [...])

	// event name: 'event'
	// event payload: [...]


### Event Class
The base timatanga\Events\Event class is available for convenience for those who wish to use just one event object throughout their application. It is suitable for most purposes straight out of the box, because it follows the standard observer pattern where the event object encapsulates an event ‘subject’, but has the addition of optional extra arguments.

    __construct($subject, $arguments): Constructor takes the event subject and any arguments;
    getSubject(): Get the subject;
    setArgument(): Sets an argument by key;
    setArguments(): Sets arguments array;
    getArgument(): Gets an argument by key;
    getArguments(): Getter for all arguments;
    hasArgument(): Returns true if the argument key exists;


The Event class implements ArrayAccess and IteratorAggregate which makes it very convenient to pass extra arguments regarding the event subject.

The Event class implements the StoppableEventInterface. Since a listener has access to the event itself, it can stop further progagation by using $e->stopPropagation(). Any listener waiting to be processed for that event name will not get notified by the event dispatcher.



## Listeners
To take advantage of an existing event, you need to connect a listener to the dispatcher so that it can be notified when the event is dispatched. A call to the dispatcher’s listen() method associates any valid PHP callable to an event. The listen() method takes up to two arguments:
* the event name (string) that this listener wants to listen to;
* a PHP callable that will be executed when the specified event is dispatched;

### Listener Callables
A PHP callable is a PHP variable that can be used by the call_user_func() function and returns true when passed to the is_callable() function. It can be one of the following scenarios:

* a \Closure instance, 
		
		$dispatcher->listen('foo', function () use ($a) { ... do something ... }; );

* an object implementing an invoke() method

		$dispatcher->listen('foo', new CallableClass() { public function __invoke(...$args) { ... do something ... } } );

* a string representing a function or an array representing an object method or a class method.

		$dispatcher->listen('foo', [$listener, $method] );


### Wildcard Listeners
The event dispatcher provided by this package supports wildcard events for more generic listeners. E.g.
* a listener registered for order.created acts only on this exact event
* a listener registered for order.* acts on all events machting the dotted prefix notation like order.processed, order.closed



## Subscribers
The most common way to listen to an event is to register an event listener with the dispatcher. This listener can listen to one or more events and is notified each time those events are dispatched.

Another way to listen to events is via an event subscriber. An event subscriber is a PHP class that’s able to tell the dispatcher exactly which events it should subscribe to. It implements the timatanga\Events\Contracts\EventSubscriberInterface interface, which requires a single static method called getSubscribedEvents(). Based on the subscriber class, the dispatcher resolves and registeres all events the listener should be executed.

	class mySubscriber implements EventSubscriberInterface
	{
		/*
		 * Only method required by the EventSubscriberInterface
		 */
	    public static function getSubscribedEvents()
	    {
	        return [
	            order.created => [ onOrderCreated, onOrderCreatedPost ],
	            order.closed => [],
	        ];
	    }

	    public function onOrderCreated( $event )
	    {
	        // ...
	    }

	    public function onOrderCreatedPostfix( $event, $dispatcher )
	    {
	        // ...
	    }


The dispatcher invokes the registered methods if an event gets dispatched. The dispatcher allows for two listener arguments
* payload: event class itself or payload provided when registered an event via the listen method
* dispatcher: the dispatcher instance to allow for advanced use cases



## Auto Discovery
Instead of programmatically register all event listeners and event subscribers individually the package provides an event discovery feature. Within the config/events.php file, directories containing listeners and subscribers can be added for auto discovery. 

	return [

	    'listeners' => [
	        'timatanga/events/tests/Data'
	    ],

	    'subscribers' => [
	        'timatanga/events/tests/Data'
	    ],
	];


The above configuration would look for event listeners and subscribers within the timatanga/events/tests/Data directories.

Event listeners must implement the `timatanga\Events\Contracts\EventListenerInterface` which enforced a static handle method to execute on the event.
Event subscribers must implement the `timatanga\Events\Contracts\EventSubscriberInterface` as described in the previous chapters.

After identifying classes implementing according Interfaces, the listeners and subscribers get registered in the event dispatcher.
If you wand to avoid auto discovery, please create a dispatcher instance like 

	$dispatcher = new Dispatcher(['autoDiscovery' => false]);


