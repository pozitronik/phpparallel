<?php /** @noinspection UsingInclusionReturnValueInspection */
declare(strict_types = 1);

namespace app\commands;

use parallel\Channel;
use parallel\Channel\Error\Existence;
use parallel\Channel\Error\IllegalValue;
use parallel\Events;
use parallel\Events\Error\Timeout;
use parallel\Future;
use parallel\Future\Error;
use parallel\Future\Error\Cancelled;
use parallel\Future\Error\Foreign;
use parallel\Future\Error\Killed;
use parallel\Runtime;
use parallel\Runtime\Error\Closed;
use Throwable;
use Yii;
use yii\console\Application;
use yii\console\Controller;
use parallel\Events\Event\Type;
use yii\helpers\Console;

/**
 * Example of parallel/Events usage
 */
class EventsController extends Controller {

	/**
	 * This example shows a basic principle of how to work with events
	 * @return void
	 * @throws Cancelled
	 * @throws Closed
	 * @throws Error
	 * @throws Existence
	 * @throws Foreign
	 * @throws IllegalValue
	 * @throws Killed
	 * @throws Throwable
	 */
	public function actionExampleOne():void {
		$runtime = new Runtime();
		$channel = new Channel();
		$future = $runtime->run(function(Channel $channel):string {
			$events = new Events();
			$events->addChannel($channel);
			while (true) {
				if (null !== $event = $events->poll()) {
					$events->addChannel($channel);//It seems, the target gets deleted after returning an event, so add it again.
					if (Type::Read === $event->type) {
						printf("\nReceived: %s\n", $event->value);
					} elseif (Type::Close === $event->type) {
						printf("\nReceived close event\n");
						return "done!";
					}
				}
				echo "*";
				sleep(1);
			}
		}, [$channel]);
		$channel->send('message1');
		sleep(2);
		$channel->send('message2');
		sleep(5);
		printf("\nclosing channel\n");
		$channel->close();
		printf("future said: %s\n", $future->value());
	}

	/**
	 * This example shows the difference between a blocking and non-blocking events and blocking timeout meaning
	 * @return void
	 * @throws Cancelled
	 * @throws Closed
	 * @throws Error
	 * @throws Foreign
	 * @throws IllegalValue
	 * @throws Killed
	 * @throws Throwable
	 */
	public function actionExampleTwo():void {
		$runtime = new Runtime();
		$channel = new Channel();
		$future = $runtime->run(function(Channel $channel):string {
			$events = new Events();
			$events->addChannel($channel);
			$events->setBlocking(true);
			$events->setTimeout(1500000);//2,5 seconds
			while (true) {
				try {
					if (null !== $event = $events->poll()) {
						$events->addChannel($channel);//It seems, the target gets deleted after returning an event, so add it again.
						if (Type::Read === $event->type) {
							printf("\nReceived: %s\n", $event->value);
						} elseif (Type::Close === $event->type) {
							printf("\nReceived close event\n");
							return "done!";
						}
						echo "*";
						sleep(1);
					}
				} catch (Timeout $error) {
					/* When the timeout expired, event will throw an exception */
					printf("\nTimeout error occurred: %s\n", $error->getMessage());
				}
			}
		}, [$channel]);
		$channel->send('message1');
		sleep(2);
		$channel->send('message2');
		sleep(5);
		$channel->send('message3');
		sleep(3);
		printf("\nclosing channel\n");
		$channel->close();
		printf("future said: %s\n", $future->value());
	}

	/**
	 * This example shows how to use event values to pass data to tasks and use events parameters
	 * @param int $workersCount
	 * @param int $producersCount
	 * @return void
	 * @throws Existence
	 */
	public function actionExampleThree(int $workersCount = 10, int $producersCount = 3):void {
		$config = require Yii::getAlias('@app/config/console.php');
		/** @var Runtime[] $workerRuntimesList */
		$workerRuntimesList = [];
		/** @var Runtime[] $producerRuntimesList */
		$producerRuntimesList = [];
		/** @var Future[] $futureList */
		$futureList = [];

		/** Used to send data to workers */
		$workerChannel = Channel::make('workerChannel', Channel::Infinite);
		/** Used to send answers to producers */
		$producerChannel = Channel::make('producerChannel', Channel::Infinite);

		$task = function(Channel $workerChannel, Channel $producerChannel, int $i) use ($config) {
			new Application($config);
			$events = new Events();
			$events->addChannel($workerChannel);
			$events->setBlocking(true);
			while ((null !== $event = $events->poll())) {
				$events->addChannel($workerChannel);//It seems, the target gets deleted after returning an event, so add it again.
				switch ($event->type) {
					case Type::Read:
						Console::output(Console::renderColoredString("Worker %y{$i}%n, received from producer %b{$event->value['sender']}%n: %g{$event->value['message']}%n"));
						$producerChannel->send([
							'sender' => $i,
							'message' => $event->value['message'].'=>'.md5($event->value['message'])
						]);
					break;
					default:
						Console::output(Console::renderColoredString("Worker %r{$i}%n, received event %r{$event->type}%n from producer %b{$event->value['sender']}%n"));
					break;
					case Type::Close:
						Console::output(Console::renderColoredString("Worker %r{$i}%n, received close event"));
						return "done!";
				}
			}
		};
		/* Create and run set of workers tasks */
		for ($i = 0; $i < $workersCount; $i++) {
			$workerRuntimesList[] = new Runtime(Yii::getAlias('@app/bootstrap_console.php'));
		}

		foreach ($workerRuntimesList as $i => $runtime) {
			$futureList[] = $runtime->run($task, [$workerChannel, $producerChannel, $i]);
		}

		/* Create and run set of producers tasks */
		for ($i = 0; $i < $producersCount; $i++) {
			$producerRuntimesList[] = new Runtime(Yii::getAlias('@app/bootstrap_console.php'));
		}

		foreach ($producerRuntimesList as $i => $runtime) {
			$runtime->run(function(Channel $workerChannel, Channel $producerChannel, int $i) use ($config):bool {
				new Application($config);
				$events = new Events();
				$events->addChannel($producerChannel);
				$events->setBlocking(false);
				while (true) {
					$workerChannel->send([
						'sender' => $i,
						'message' => Yii::$app->security->generateRandomString(10)
					]);
					if ((null !== $event = $events->poll()) && Type::Read === $event->type) {
						$events->addChannel($producerChannel);//It seems, the target gets deleted after returning an event, so add it again.
						Console::output(Console::renderColoredString("Producer %b{$i}%n, received from worker %y{$event->value['sender']}%n: %p{$event->value['message']}%n"));
					}
					sleep(1);
//					$workerChannel->close();
				}
			}, [$workerChannel, $producerChannel, $i]);
		}

//		sleep(5);
//		Console::output("Closing worker channel");
//		$workerChannel->close();
	}

}