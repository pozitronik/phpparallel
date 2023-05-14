<?php
declare(strict_types = 1);

namespace app\commands;

use parallel\Channel;
use parallel\Events;
use parallel\Events\Error\Timeout;
use parallel\Runtime;
use yii\console\Controller;
use parallel\Events\Event\Type;

/**
 * Example of parallel/Events usage
 */
class EventsController extends Controller {
	private Runtime $runtime;
	private Channel $channel;
	private mixed $future;

	/**
	 * @return void
	 */
	public function actionStart():void {
		$this->runtime = new Runtime();
		$this->channel = new Channel(Channel::Infinite);
		$this->future = $this->runtime->run(function(Channel $channel):void {
			$events = new Events();
			$events->addChannel($channel);
			//$events->setBlocking(false); //Uncomment to don't block on Events::poll()
			$events->setTimeout(1000000); //Comment when not blocking

			while (true) {
				/*
				...
				Your code.
				...
				*/

				//Read all available events
				try {
					$event = null;
					do {
						$event = $events->poll(); //Returns non-null if there is an event
						if ($event && 'myChannel' === $event->source) {
							//It seems, the target gets deleted after returning an event,
							//so add it again.
							$events->addChannel($channel);
							if (Type::Read === $event->type) {
								if (is_array($event->value) && count($event->value) > 0) {
									if ('stop' === $event->value['name']) {
										echo 'Stopping thread';
										return; //Stop
									}

									echo 'Event: '.$event->value['name'].' => '.$event->value['value'].PHP_EOL;
								}
							} elseif (Type::Close === $event->type) return; //Stop
						}
					} while ($event);
				} catch (Timeout $ex) {
					echo $ex->getMessage().PHP_EOL;
				}
			}
		}, [$this->channel]);
	}

	/**
	 * @return void
	 */
	public function actionStop():void {
		$this->channel->send(['name' => 'stop', 'value' => true]);

		$this->future->value(); //Wait for thread to finish
		$this->channel->close();
	}

	/**
	 * @param string $name
	 * @param $value
	 * @return void
	 */
	public function actionEmit(string $name, $value):void {
		$this->channel->send(compact('name', 'value'));
	}
}