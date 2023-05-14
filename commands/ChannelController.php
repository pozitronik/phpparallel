<?php /** @noinspection UsingInclusionReturnValueInspection */
declare(strict_types = 1);

namespace app\commands;

use parallel\Channel;
use parallel\Runtime;
use Yii;
use yii\console\Application;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Examples of sharing data between tasks
 */
class ChannelController extends Controller {

	/**
	 * This example shows how to use unbuffered channels to set blocking communications between tasks
	 * @param int $tasksCount
	 * @return void
	 */
	public function actionExampleOne(int $tasksCount = 3):void {
		$config = require Yii::getAlias('@app/config/console.php');
		Console::clearScreen();
		/** @var Runtime[] $runtimeList */
		$runtimeList = [];
		/* Create a unbuffered Channel instance to communicate between task */
		$dataChannel = new Channel();

		/* Create set of senders tasks */
		for ($i = 0; $i < $tasksCount; $i++) {
			$runtimeList[] = new Runtime();
		}
		foreach ($runtimeList as $i => $runtime) {
			$runtime->run(static function(Channel $channel) use ($i, $tasksCount):void {
				$rnd = 0;
				$pause = random_int(1, $tasksCount);//Add a random pause
				while (true) {
					$rnd++;
					$channel->send([$i, $rnd]);
					sleep($pause);
				}
			}, [$dataChannel]);
		}

		/* Create the reader (receiver) task */
		$reader = new Runtime(Yii::getAlias('@app/bootstrap_console.php'));
		$reader->run(static function(Channel $channel) use ($config):void {
			new Application($config);
			$taskData = [];
			$iterations = 0;
			while (true) {
				Console::moveCursorTo(1, 1);
				Console::output(sprintf("updates received: %s", $iterations));
				[$name, $value] = $channel->recv();
				$taskData[$name] = $value;// Console::renderColoredString(Console::ansiFormatCode([random_int(Console::FG_RED, Console::FG_GREY)]).$value."%n");

				foreach ($taskData as $name => $value) {
					Console::moveCursorTo(1, $name + 1);
					Console::output(sprintf("%02s: %010s", $name, $value));
				}
				$iterations++;
			}
		}, [$dataChannel]);
	}

	/**
	 * This example shows difference between buffered and unbuffered channels
	 * @param int $tasksCount
	 * @param bool $buffered
	 * @return void
	 */
	public function actionExampleTwo(int $tasksCount = 3, bool $buffered = true):void {
		$config = require Yii::getAlias('@app/config/console.php');
		Console::clearScreen();
		/** @var Runtime[] $runtimeList */
		$runtimeList = [];
		/* Create a buffered Channel instance to communicate between task */
		$dataChannel = $buffered
			?new Channel(Channel::Infinite)
			:new Channel();

		/* Create set of receivers tasks */
		for ($i = 0; $i < $tasksCount; $i++) {
			$runtimeList[] = new Runtime(Yii::getAlias('@app/bootstrap_console.php'));
		}
		foreach ($runtimeList as $i => $runtime) {
			$runtime->run(static function(Channel $channel) use ($i, $config):void {
				new Application($config);
				while (true) {
					$data = $channel->recv();
					Console::moveCursorTo(1, $i + 2);
					Console::output(sprintf("%02s: %s", $i, $data));
					sleep(2);
				}
			}, [$dataChannel]);
		}

		/* Create the sender task */
		$reader = new Runtime(Yii::getAlias('@app/bootstrap_console.php'));
		$reader->run(static function(Channel $channel) use ($config):void {
			new Application($config);
			$iterations = 0;
			while (true) {
				Console::moveCursorTo(1, 1);
				Console::output(sprintf("Messages written: %s", $iterations));
				$channel->send(strtr(base64_encode(random_bytes(32)), '+/', '-_'));
				$iterations++;
			}
		}, [$dataChannel]);
	}
}