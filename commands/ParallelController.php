<?php
declare(strict_types = 1);

namespace app\commands;

use app\models\Tasks;
use parallel\Channel;
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
	 * This example allows to run some parallel tasks simultaneously
	 * @param int $threadsCnt
	 * @param null|int $pause Pass null to random wait time for every task
	 * @return void
	 */
	public function actionSampleTwo(int $threadsCnt = 10, ?int $pause = null):void {
		$ch = new Channel();

		$task = static function(Channel $ch, int $threadNumber, ?int $pause):void {
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
			$runtime->run($task, [$ch, $i, $pause]);
		}

		for ($i = 0; $i < $threadsCnt * $pause; $i++) {
			Console::output($ch->recv());
		}
		$ch->close();
	}

	/**
	 * This example shows how to pass bootstrap script to parallel tasks
	 * @param int $threadsCnt
	 * @param null|int $pause Pass null to random wait time for every task
	 * @return void
	 */
	public function actionSampleTree(int $threadsCnt = 10, ?int $pause = 5):void {
		$ch = new Channel();

		$task = static function(Channel $ch, int $threadNumber, ?int $pause):void {
			Console::output("[enter: $threadNumber]");
			Console::output(Tasks::waiter($pause??random_int(5, 20), "[enter: $threadNumber]"));
		};
		/** @var Runtime[] $runtimeList */
		$runtimeList = [];
		for ($i = 0; $i < $threadsCnt; $i++) {
			$runtimeList[] = new Runtime(__DIR__.'/../vendor/autoload.php');
		}

		foreach ($runtimeList as $i => $runtime) {
			echo("[run: $i]\n");
			$runtime->run($task, [$ch, $i, $pause]);
		}

		for ($i = 0; $i < $threadsCnt * $pause; $i++) {
			Console::output($ch->recv());
		}
		$ch->close();
	}
}