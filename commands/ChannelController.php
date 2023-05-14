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
	 * This example shows how to use unbuferred channels to set blocking communications between tasks
	 * @param int $tasksCount
	 * @return void
	 */
	public function actionExampleOne(int $tasksCount = 3):void {
		$config = require Yii::getAlias('@app/config/console.php');
		Console::clearScreen();
		/** @var Runtime[] $runtimeList */
		$runtimeList = [];
		/* Create a Channel instance to communicate between task */
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

		/*Create the reader (receiver) task. */
		$reader = new Runtime(Yii::getAlias('@app/bootstrap_console.php'));
		$reader->run(static function(Channel $channel) use ($config):void {
			new Application($config);
			$taskData = [];
			while (true) {
				[$name, $value] = $channel->recv();
				$taskData[$name] = $value;// Console::renderColoredString(Console::ansiFormatCode([random_int(Console::FG_RED, Console::FG_GREY)]).$value."%n");

				foreach ($taskData as $name => $value) {
					Console::moveCursorTo(1, $name + 1);
					Console::output(sprintf("%02s: %010s", $name, $value));
				}
			}
		}, [$dataChannel]);
	}

}