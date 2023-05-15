<?php
/** @noinspection PhpHierarchyChecksInspection */
/** @noinspection EmptyClassInspection */
declare(strict_types = 1);

namespace {
	exit("This file should not be included, it is a only IDE helper");
}

namespace parallel {
	use Closure;
	use Countable;
	use parallel\Channel\Error\Existence;
	use parallel\Channel\Error\IllegalValue;
	use parallel\Events\Error\Timeout;
	use parallel\Events\Event;
	use parallel\Future\Error;
	use parallel\Future\Error\Cancelled;
	use parallel\Future\Error\Foreign;
	use parallel\Future\Error\Killed;
	use parallel\Runtime\Error\Closed;
	use Throwable;
	use Traversable;
	use parallel\Events\Input;

	/**
	 * @see https://www.php.net/manual/en/class.parallel-runtime.php
	 */
	final class Runtime {

		/**
		 * @param string $bootstrap There is a overloaded method call without this parameter, so it can be omitted (and have no default value at all)
		 * Because PHP doesn't support overloading, only one signature is documented
		 */
		abstract public function __construct(string $bootstrap = '');

		/**
		 * @param Closure $task A Closure with specific characteristics.
		 * @param array $argv An array of arguments with specific characteristics to be passed to task at execution time
		 * There is a overloaded method call without this parameter, so it can be omitted (and have no default value at all)
		 * Because PHP doesn't support overloading, only one signature is documented
		 * @return Future|null
		 * @see https://www.php.net/manual/en/parallel-runtime.run.php
		 */
		abstract public function run(Closure $task, array $argv = []):?Future;

		/**
		 * Runtime Graceful Join
		 * @return void
		 * @throws Closed
		 * @see https://www.php.net/manual/en/parallel-runtime.close.php
		 */
		abstract public function close():void;

		/**
		 * Attempt to force the runtime to shutdown.
		 * @return void
		 * @throws Closed
		 * @see https://www.php.net/manual/en/parallel-runtime.kill.php
		 */
		abstract public function kill():void;
	}

	/**
	 * @see https://www.php.net/manual/en/class.parallel-future.php
	 */
	final class Future {
		/**
		 * Return (and if necessary wait for) return from task
		 * @return mixed
		 * @throws Error If waiting failed (internal error)
		 * @throws Killed If Runtime executing task was killed
		 * @throws Cancelled If task was cancelled
		 * @throws Foreign If task raised an unrecognized uncaught exception
		 * @throws Throwable uncaught in task
		 * @see https://www.php.net/manual/en/parallel-future.value.php
		 */
		abstract public function value():mixed;

		/**
		 * Indicate if the task was cancelled
		 * @return bool
		 * @see https://www.php.net/manual/en/parallel-future.cancelled.php
		 */
		abstract public function cancelled():bool;

		/**
		 * Indicate if the task is completed
		 * @return bool
		 * @see https://www.php.net/manual/en/parallel-future.done.php
		 */
		abstract public function done():bool;

		/**
		 * Try to cancel the task
		 * @return bool
		 * @throws Killed If Runtime executing task was killed
		 * @throws Cancelled If task was already cancelled
		 */
		abstract public function cancel():bool;
	}

	/**
	 * @see https://www.php.net/manual/en/class.parallel-channel.php
	 */
	final class Channel {
		public const Infinite = -1;

		/**
		 * @param int $capacity There is a overloaded method call without this parameter, so it can be omitted (and have no default value at all)
		 * Because PHP doesn't support overloading, only one signature is documented
		 */
		abstract public function __construct(int $capacity = self::Infinite);

		/**
		 * Create a named channel
		 * @param string $name The name of the channel
		 * @param int $capacity There is a overloaded method call without this parameter, so it can be omitted (and have no default value at all)
		 * Because PHP doesn't support overloading, only one signature is documented
		 * @return Channel
		 * @throws Existence If channel already exists
		 * @see https://www.php.net/manual/en/parallel-channel.make.php
		 */
		abstract public function make(string $name, int $capacity):Channel;

		/**
		 * Open the channel with the given name
		 * @param string $name
		 * @return Channel
		 * @throws Existence If channel does not exists
		 * @see https://www.php.net/manual/en/parallel-channel.open.php
		 */
		abstract public function open(string $name):Channel;

		/**
		 * Receive a value from this channel
		 * @return mixed
		 * @see https://www.php.net/manual/en/parallel-channel.recv.php
		 */
		abstract public function recv():mixed;

		/**
		 * Send the given value on this channel
		 * @param mixed $value
		 * @return void
		 * @throws Closed If channel is closed
		 * @throws IllegalValue If value is illegal
		 * @see https://www.php.net/manual/en/parallel-channel.send.php
		 */
		abstract public function send(mixed $value):void;

		/**
		 * Close this channel
		 * @return void
		 * @throws Closed If channel is already closed
		 * @see https://www.php.net/manual/en/parallel-channel.close.php
		 */
		abstract public function close():void;
	}

	/**
	 * @see https://www.php.net/manual/en/class.parallel-events.php
	 */
	final class Events implements Countable, Traversable {

		/**
		 * Set input for this event loop
		 * @param Input $input
		 * @return void
		 * @see https://www.php.net/manual/en/parallel-events.setinput.php
		 */
		abstract public function setInput(Input $input):void;

		/**
		 * Watch for events on the given channel
		 * @param Channel $channel
		 * @return void
		 * @throws Existence If channel was already added
		 * @see https://www.php.net/manual/en/parallel-events.addchannel.php
		 */
		abstract public function addChannel(Channel $channel):void;

		/**
		 * Watch for events on the given future
		 * @param string $name
		 * @param Future $future
		 * @return void
		 * @throws Existence If target with the given name was already added
		 * @see https://www.php.net/manual/en/parallel-events.addfuture.php
		 */
		abstract public function addFuture(string $name, Future $future):void;

		/**
		 * Remove the given target
		 * @param string $target
		 * @return void
		 * @throws Existence If target with the given name was not found
		 * @see https://www.php.net/manual/en/parallel-events.remove.php
		 */
		abstract public function remove(string $target):void;

		/**
		 * Set blocking mode for the event
		 * @param bool $blocking
		 * @return void
		 * @throws Events\Error If loop has timeout set
		 * @see https://www.php.net/manual/en/parallel-events.setblocking.php
		 */
		abstract public function setBlocking(bool $blocking):void;

		/**
		 * Set the timeout in microseconds
		 * @param int $timeout
		 * @return void
		 * @throws Events\Error If loop is non-blocking
		 * @see https://www.php.net/manual/en/parallel-events.settimeout.php
		 */
		abstract public function setTimeout(int $timeout):void;

		/**
		 * Poll for the next event
		 * @return Event|null
		 * @throws Timeout If timeout is used and reached
		 * @see https://www.php.net/manual/en/parallel-events.poll.php
		 */
		abstract public function poll():?Event;
	}

	/**
	 * Provides access to low level synchronization primitives, mutex, condition variables, and allows the implementation of semaphores
	 * @see https://www.php.net/manual/en/class.parallel-events.php
	 */
	final class Sync {
		/**
		 * Construct a new synchronization object containing the given scalar value
		 * @param bool|int|float|string $value here is a overloaded method call without this parameter, so it can be omitted (and have no default value at all)
		 * Because PHP doesn't support overloading, only one signature is documented
		 * @throws Sync\Error\IllegalValue If value is non-scalar
		 * @see https://www.php.net/manual/en/parallel-sync.construct.php
		 */
		abstract public function __construct(bool|int|float|string $value);

		/**
		 * Atomically return the synchronization objects value
		 * @return bool|int|float|string
		 * @see https://www.php.net/manual/en/parallel-sync.get.php
		 */
		abstract public function get():bool|int|float|string;

		/**
		 * Atomically set the value of the synchronization object
		 * @param bool|int|float|string $value
		 * @return void
		 * @throws Sync\Error\IllegalValue If value is non-scalar
		 * @set https://www.php.net/manual/en/parallel-sync.set.php
		 */
		abstract public function set(bool|int|float|string $value):void;

		/**
		 * Wait for notification on this synchronization object
		 * @return void
		 * @throws https://www.php.net/manual/en/parallel-sync.wait.php
		 */
		abstract public function wait():void;

		/**
		 * Notify one (by default) or all threads waiting on the synchronization object
		 * @param bool $all
		 * @return void
		 * @see https://www.php.net/manual/en/parallel-sync.notify.php
		 */
		abstract public function notify(bool $all = false):void;

		/**
		 * Exclusively enter into the critical code
		 * @param callable $critical
		 * @return mixed
		 * @see https://www.php.net/manual/en/parallel-sync.invoke.php
		 */
		abstract public function __invoke(callable $critical);
	}
}

namespace parallel\Events {
	use parallel\Events\Input\Error\Existence;
	use parallel\Events\Input\Error\IllegalValue;

	/**
	 * An Input object is a container for data that the Events object will write to Channel objects as they become available
	 * @see https://www.php.net/manual/en/class.parallel-events-input.php
	 */
	final class Input {
		/**
		 * Set input for the given target
		 * @param string $target
		 * @param mixed $value
		 * @return void
		 * @throws Existence If input for target already exists
		 * @throws IllegalValue If value is illegal (object, null)
		 * @see https://www.php.net/manual/en/parallel-events-input.add.php
		 */
		abstract public function add(string $target, mixed $value):void;

		/**
		 * Remove input for the given target
		 * @param string $target
		 * @return void
		 * @throws Existence If input for target does not exists
		 * @see https://www.php.net/manual/en/parallel-events-input.remove.php
		 */
		abstract public function remove(string $target):void;

		/**
		 * Remove input for all targets
		 * @return void
		 * @see https://www.php.net/manual/en/parallel-events-input.clear.php
		 */
		abstract public function clear():void;
	}

	/**
	 * @see https://www.php.net/manual/en/class.parallel-events-event.php
	 * @property int $type
	 * @property string $source
	 * @property object $object
	 * @property mixed $value
	 */
	final class Event {
	}
}

namespace parallel\Events\Event {

	/**
	 * @see https://www.php.net/manual/en/class.parallel-events-event-type.php
	 */
	final class Type {
		public const Read = 1;
		public const Write = 2;
		public const Close = 3;
		public const Cancel = 5;
		public const Kill = 6;
		public const Error = 4;
	}
}

namespace parallel\Events {
	use Throwable;

	/**
	 * General event error
	 */
	final class Error implements Throwable {
	}
}

namespace parallel\Events\Error {
	use Throwable;

	/**
	 * Event timeout exception
	 */
	final class Timeout implements Throwable {
	}
}

namespace parallel\Runtime\Error {
	use Throwable;

	/**
	 * Runtime already closed exception
	 */
	final class Closed implements Throwable {
	}
}

namespace parallel\Future {
	use Throwable;

	/**
	 * Future waiting failed (internal error)
	 */
	final class Error implements Throwable {
	}
}

namespace parallel\Future\Error {
	use Throwable;

	/**
	 * Runtime executing task was killed
	 */
	final class Killed implements Throwable {
	}

	/**
	 * Task was cancelled
	 */
	final class Cancelled implements Throwable {
	}

	/**
	 * Tsk raised an unrecognized uncaught exception
	 */
	final class Foreign implements Throwable {
	}
}

namespace parallel\Channel\Error {
	use Throwable;

	/**
	 * Channel existence error
	 */
	final class Existence implements Throwable {
	}

	/**
	 * Channel is closed
	 */
	final class Closed implements Throwable {
	}

	/**
	 * Value is illegal
	 */
	final class IllegalValue implements Throwable {
	}
}

namespace parallel\Sync\Error {
	use Throwable;

	/**
	 * Value is non-scalar
	 */
	final class IllegalValue implements Throwable {
	}
}

namespace parallel\Events\Input\Error {
	use Throwable;

	/**
	 * Target existence error
	 */
	final class Existence implements Throwable {
	}

	/**
	 * Value is illegal
	 */
	final class IllegalValue implements Throwable {
	}
}