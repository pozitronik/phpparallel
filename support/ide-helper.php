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
	use Traversable;
	use parallel\Events\Input;

	/**
	 * @see https://www.php.net/manual/en/class.parallel-runtime.php
	 * @method run(Closure $task, array $argv = []):?Future
	 * @method close():void
	 * @method kill():void
	 */
	final class Runtime {

		/**
		 * @param string $bootstrap
		 */
		public function __construct(string $bootstrap = '') {
		}
	}

	/**
	 * @see https://www.php.net/manual/en/class.parallel-future.php
	 * @method value():mixed
	 * @method cancelled():bool
	 * @method done():bool
	 * @method cancel():bool
	 */
	final class Future {
	}

	/**
	 * @see https://www.php.net/manual/en/class.parallel-channel.php
	 * @method make(string $name, void|int $capacity):Channel
	 * @method open(string $name):Channel
	 * @method recv():mixed
	 * @method send(mixed $value):void
	 * @method close():void
	 * @const Infinite
	 */
	final class Channel {
		public const Infinite = -1;

		/**
		 * @param int $capacity
		 */
		public function __construct(int $capacity = self::Infinite) {
		}
	}

	/**
	 * @see https://www.php.net/manual/en/class.parallel-events.php
	 * @method setInput(Input $input):void
	 * @method addChannel(Channel $channel):void
	 * @method addFuture(string $name, Future $future):void
	 * @method remove(string $target):void
	 * @method setBlocking(bool $blocking):void
	 * @method setTimeout(int $timeout):void
	 * @method poll():?Event
	 */
	final class Events implements Countable, Traversable {
	}

	/**
	 * @see https://www.php.net/manual/en/class.parallel-events.php
	 * @method get():bool|int|float|string
	 * @method set(bool|int|float|string $value)
	 * @method notify(bool $all)
	 * @method __invoke(callable $critical)
	 */
	final class Sync {
		/**
		 * @param bool|int|float|string $value
		 */
		public function __construct(bool|int|float|string $value) {
		}
	}
}

namespace parallel\Events {
	/**
	 * @see https://www.php.net/manual/en/class.parallel-events-input.php
	 * @method add(string $target, mixed $value):void
	 * @method remove(string $target):void
	 * @method clear():void
	 */
	final class Input {
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