<?php
declare(strict_types = 1);

namespace app\commands;

use app\models\Tasks;
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
}