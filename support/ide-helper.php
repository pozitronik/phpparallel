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
	 * @method __construct(void|string $bootstrap)
	 * @method run(Closure $task, void|array $argv = []):?Future
	 * @method close():void
	 * @method kill():void
	 */
	final class Runtime {
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
	 * @method __construct(void|int $capacity)
	 * @method make(string $name, void|int $capacity):Channel
	 * @method open(string $name):Channel
	 * @method recv():mixed
	 * @method send(mixed $value):void
	 * @method close():void
	 * @const Infinite
	 */
	final class Channel {
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
	 * @method __construct(bool|int|float|string $value)
	 * @method get():bool|int|float|string
	 * @method set(bool|int|float|string $value)
	 * @method notify(bool $all)
	 * @method __invoke(callable $critical)
	 */
	final class Sync {
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

namespace parallel\Events\Type {

	/**
	 * @see https://www.php.net/manual/en/class.parallel-events-event-type.php
	 * @const Read = 1
	 * @const Write = 2
	 * @const Close = 3
	 * @const Cancel = 5
	 * @const Kill = 6
	 * @const Error = 4
	 */
	final class Type {

	}
}