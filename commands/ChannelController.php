<?php /** @noinspection UsingInclusionReturnValueInspection */
declare(strict_types = 1);

namespace app\commands;

use parallel\Channel;
use parallel\Future;
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
	 * Идея: создаём n потоков, что-то считающих, и отправляющих данные в канал + поток, который получает данные из каналов,
	 * и выводящий данные на экран + поток, читающий команды, и управляющий потоками.
	 * @param int $tasksCount
	 * @return void
	 */
	public function actionOne(int $tasksCount = 3):void {
		$config = require Yii::getAlias('@app/config/console.php');
		Console::clearScreen();
		/** @var Runtime[] $runtimeList */
		$runtimeList = [];
		/** @var Future[] $futuresList */
		$futuresList = [];
		/* Create a Channel instance to communicate between task */
		$channel = new Channel();

		/* Create set of senders tasks */
		for ($i = 0; $i < $tasksCount; $i++) {
			$runtimeList[] = new Runtime();
		}
		foreach ($runtimeList as $i => $runtime) {
			$futuresList[] = $runtime->run(static function(Channel $channel) use ($i, $tasksCount):void {
				$rnd = strtr(base64_encode(random_bytes(32)), '+/', '-_');
				$pause = random_int(1, $tasksCount);//Add a random pause
				while (true) {
					$rnd = sha1($rnd);
					$channel->send([$i, $rnd]);
					sleep($pause);
				}
			}, [$channel]);
		}

		/*Create the reader (receiver) task. */
		$reader = new Runtime(Yii::getAlias('@app/bootstrap_console.php'));
		$reader->run(static function(Channel $channel) use ($config):void {
			new Application($config);
			$taskData = [];
			while (true) {
				[$name, $value] = $channel->recv();
				$taskData[$name] = Console::renderColoredString(Console::ansiFormatCode([random_int(Console::FG_RED, Console::FG_GREY)]).$value."%n");

				foreach ($taskData as $name => $value) {
					Console::moveCursorTo(1, $name + 1);
					Console::output(sprintf("%02s: %s", $name, $value));
				}
			}
		}, [$channel]);

	}

}