<?php
declare(strict_types = 1);

namespace app\commands;

use app\models\Tasks;
use parallel\Channel;
use parallel\Future;
use Yii;
use yii\console\Application;
use yii\console\Controller;
use parallel\Runtime;
use yii\helpers\Console;

/**
 * Parallel PHP samples
 */
class ParallelController extends Controller {

	/**
	 * This example runs two parallel task simultaneously
	 * @return void
	 */
	public function actionSampleOne():void {
		$runtime = new Runtime();

		$future = $runtime->run(function() {
			for ($i = 0; $i < 500; $i++)
				echo "*";

			return "easy";
		});

		for ($i = 0; $i < 500; $i++) {
			echo ".";
		}

		printf("\nUsing \\parallel\\Runtime is %s\n", $future->value());
	}

	/**
	 * This example allows to run some parallel tasks simultaneously. It also shows that tasks execution order is not deterministic.
	 * @param int $threadsCnt
	 * @param null|int $pause Pass null to random wait time for every task
	 * @return void
	 */
	public function actionSampleTwo(int $threadsCnt = 10, ?int $pause = null):void {
		$task = static function(int $threadNumber, ?int $pause):void {
			//Console::output("[enter: $threadNumber]");// ?!
			echo("[enter: $threadNumber]\n");
			sleep($pause??random_int(5, 20));
			echo("[done: $threadNumber]\n");
		};
		/** @var Runtime[] $runtimeList */
		$runtimeList = [];

		for ($i = 0; $i < $threadsCnt; $i++) {
			$runtimeList[] = new Runtime();
		}

		foreach ($runtimeList as $i => $runtime) {
			echo("[run: $i]\n");
			$runtime->run($task, [$i, $pause]);
		}
	}

	/**
	 * This example shows how to pass bootstrap script to parallel tasks and how to use framework inside tasks.
	 * @param int $threadsCnt
	 * @param null|int $pause Pass null to random wait time for every task
	 * @return void
	 */
	public function actionSampleTree(int $threadsCnt = 10, ?int $pause = null):void {
		$config = Yii::getAlias('@app/config/console.php');
		$task = static function(int $threadNumber, ?int $pause) use ($config):void {
			new Application(require $config);
			Console::output("[enter: $threadNumber]");
			Console::output(Tasks::waiter($pause??random_int(5, 20), "[exit: $threadNumber]"));
		};
		/** @var Runtime[] $runtimeList */
		$runtimeList = [];
		for ($i = 0; $i < $threadsCnt; $i++) {
			$runtimeList[] = new Runtime(Yii::getAlias('@app/bootstrap_console.php'));
		}

		foreach ($runtimeList as $i => $runtime) {
			echo("[run: $i]\n");
			$runtime->run($task, [$i, $pause]);
		}
	}

	/**
	 * This example shows how to receive data from tasks
	 * @param int $threadsCnt
	 * @param null|int $pause Pass null to random wait time for every task
	 * @return void
	 *
	 * todo
	 */
	public function actionSampleFour(int $threadsCnt = 10, ?int $pause = null):void {
		$ch = new Channel(Channel::Infinite);

		$task = static function(Channel $ch, int $threadNumber, ?int $pause):void {
			$ch->send(sprintf("[enter: %s]", $threadNumber));
			sleep($pause??random_int(5, 20));
			$ch->send(sprintf("[exit: %s]", $threadNumber));
		};
		/** @var Runtime[] $runtimeList */
		$runtimeList = [];
		for ($i = 0; $i < $threadsCnt; $i++) {
			$runtimeList[] = new Runtime();
		}

		/** @var Future[] $futuresList */
		$futuresList = [];
		foreach ($runtimeList as $i => $runtime) {
			echo("[run: $i]\n");
			$futuresList[] = $runtime->run($task, [$ch, $i, $pause]);
		}

		for ($i = 0; $i < $threadsCnt; $i++) {
			Console::output($ch->recv());
		}
//		$ch->close();
	}

	/**
	 * This example demonstrates parallel task effectiveness
	 * @param int $tasksCount
	 * @return void
	 */
	public function actionSampleSix(int $tasksCount = 3):void {
		$syncResults = [];
		$asyncResults = [];
		/** @var Runtime[] $runtimeList */
		$runtimeList = [];
		/** @var Future[] $futuresList */
		$futuresList = [];

		/*Let say we have a set of long operations (e.g. DB requests). At first, run them synchronously: */
		$startTime = microtime(true);
		for ($i = 0; $i < $tasksCount; $i++) {
			$syncResults[] = call_user_func(static function() {
				sleep(5);
				return true;
			});
		}
		/* Measure execution time */
		$synchronousTime = microtime(true) - $startTime;
		Console::output(Console::renderColoredString("%bSynchronous%n summary time for %b{$tasksCount}%n tasks is %g{$synchronousTime}%n seconds"));

		/*At second, run each task in a separate process: */
		$startTime = microtime(true);

		for ($i = 0; $i < $tasksCount; $i++) {
			$runtimeList[] = new Runtime();
		}

		foreach ($runtimeList as $i => $runtime) {
			$futuresList[] = $runtime->run(static function() {
				sleep(5);
				return true;
			});
		}

		while ([] !== $futuresList) {
			foreach ($futuresList as $index => $future) {
				if ($future->done()) {
					$asyncResults[] = $future->value();
					unset($futuresList[$index]);
				}
			}
		}

		$asynchronousTime = microtime(true) - $startTime;
		Console::output(Console::renderColoredString("%bAsynchronous%n summary time for %b{$tasksCount}%n tasks is %g{$asynchronousTime}%n seconds"));
		Console::output(sprintf("Difference is %s seconds", $synchronousTime - $asynchronousTime));
		Console::output(Console::renderColoredString(sprintf("Results are %s", ($syncResults === $asyncResults)?"%gequal%n":"%gnot equal%n")));
	}
}