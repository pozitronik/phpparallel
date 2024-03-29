<?php /** @noinspection UsingInclusionReturnValueInspection */
declare(strict_types = 1);

namespace app\commands;

use app\models\Tasks;
use Exception;
use parallel\Future;
use parallel\Future\Error;
use parallel\Future\Error\Cancelled;
use parallel\Future\Error\Foreign;
use parallel\Future\Error\Killed;
use Throwable;
use Yii;
use yii\base\Exception as BaseException;
use yii\console\Application;
use yii\console\Controller;
use parallel\Runtime;
use yii\helpers\Console;

/**
 * Those examples show how to operate with parallel\Runtime class.
 */
class ParallelController extends Controller {

	/**
	 * This example runs two parallel task simultaneously
	 * @return void
	 * @throws Cancelled
	 * @throws Error
	 * @throws Foreign
	 * @throws Killed
	 * @throws Throwable
	 */
	public function actionExampleOne():void {
		$runtime = new Runtime();

		$future = $runtime->run(function() {
			for ($i = 0; $i < 500; $i++) echo "*";
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
	public function actionExampleTwo(int $threadsCnt = 10, ?int $pause = null):void {
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
	public function actionExampleThree(int $threadsCnt = 10, ?int $pause = null):void {
		$config = require Yii::getAlias('@app/config/console.php');
		$task = static function(int $threadNumber, ?int $pause) use ($config):void {
			new Application($config);//!important
			Console::output("[enter: $threadNumber]");
			Console::output(Tasks::waiter($pause??random_int(5, 20), "[exit: $threadNumber]"));
		};
		/** @var Runtime[] $runtimeList */
		$runtimeList = [];
		for ($i = 0; $i < $threadsCnt; $i++) {
			$runtimeList[] = new Runtime(Yii::getAlias('@app/bootstrap_console.php'));//!also important
		}

		foreach ($runtimeList as $i => $runtime) {
			echo("[run: $i]\n");
			$runtime->run($task, [$i, $pause]);
		}
	}

	/**
	 * This example demonstrates parallel task effectiveness
	 * @param int $tasksCount
	 * @return void
	 * @throws Cancelled
	 * @throws Error
	 * @throws Foreign
	 * @throws Killed
	 * @throws Throwable
	 */
	public function actionExampleFour(int $tasksCount = 3):void {
		$syncResults = [];
		$asyncResults = [];
		/** @var Runtime[] $runtimeList */
		$runtimeList = [];
		/** @var Future[] $futuresList */
		$futuresList = [];
		/** @var float[] $results */
		$results = [];
		/* Generate random `results` */
		for ($i = 0; $i < $tasksCount; $i++) {
			$results[] = random_int(1, 100) / random_int(1, 100);
		}

		/*Let say we have a set of long operations (e.g. DB requests). At first, run them synchronously: */
		$startTime = microtime(true);
		for ($i = 0; $i < $tasksCount; $i++) {
			$syncResults[] = call_user_func(static function(float $result):float {
				sleep(5);
				return $result;
			}, $results[$i]);
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
			$futuresList[] = $runtime->run(static function(float $result):float {
				sleep(5);
				return $result;
			}, [$results[$i]]);
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
		Console::output(Console::renderColoredString(sprintf("Results are %s", (array_sum($syncResults) === array_sum($asyncResults))?"%gequal%n":"%rnot equal%n")));
	}

	/**
	 * This example demonstrates parallel task effectiveness (with additional bootstrapping)
	 * @param int $tasksCount
	 * @return void
	 * @throws Cancelled
	 * @throws Error
	 * @throws Foreign
	 * @throws Killed
	 * @throws Throwable
	 */
	public function actionExampleFive(int $tasksCount = 3):void {
		$syncResults = [];
		$asyncResults = [];
		/** @var Runtime[] $runtimeList */
		$runtimeList = [];
		/** @var Future[] $futuresList */
		$futuresList = [];
		/** @var float[] $results */
		$results = [];
		/* Generate random `results` */
		for ($i = 0; $i < $tasksCount; $i++) {
			$results[] = random_int(1, 100) / random_int(1, 100);
		}

		/*Let say we have a set of long operations (e.g. DB requests). At first, run them synchronously: */
		$startTime = microtime(true);
		for ($i = 0; $i < $tasksCount; $i++) {
			$syncResults[] = Tasks::simulateDBRequest($results[$i]);
		}
		/* Measure execution time */
		$synchronousTime = microtime(true) - $startTime;
		Console::output(Console::renderColoredString("%bSynchronous%n summary time for %b{$tasksCount}%n tasks is %g{$synchronousTime}%n seconds"));

		/*At second, run each task in a separate process: */
		$startTime = microtime(true);

		for ($i = 0; $i < $tasksCount; $i++) {
			$runtimeList[] = new Runtime(Yii::getAlias('@app/bootstrap_console.php'));
		}
		$config = require Yii::getAlias('@app/config/console.php');
		foreach ($runtimeList as $i => $runtime) {
			$futuresList[] = $runtime->run(static function(float $result) use ($config):float {
				new Application($config);
				return Tasks::simulateDBRequest($result);
			}, [$results[$i]]);
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
		Console::output(Console::renderColoredString(sprintf("Results are %s", (array_sum($syncResults) === array_sum($asyncResults))?"%gequal%n":sprintf("%%rnot equal%%n: %s|%s", array_sum($syncResults), array_sum($asyncResults)))));
	}

	/**
	 * This test demonstrates real calculation performance (it is nice to look at the task manager, while it runs)
	 * @param int $tasksCount
	 * @param int $runTime
	 * @param bool $skipSyncTest
	 * @return void
	 * @throws Error
	 * @throws Cancelled
	 * @throws Foreign
	 * @throws Killed
	 * @throws Throwable
	 * @throws BaseException
	 */
	public function actionExampleSix(int $tasksCount = 3, int $runTime = 5, bool $skipSyncTest = false):void {
		$syncResults = [];
		$asyncResults = [];
		/** @var Runtime[] $runtimeList */
		$runtimeList = [];
		/** @var Future[] $futuresList */
		$futuresList = [];
		/** @var float[] $results */
		$results = [];
		/* Generate random `results` */
		for ($i = 0; $i < $tasksCount; $i++) {
			$results[] = random_int(1, 100) / random_int(1, 100);
		}

		/*Let say we have a set of long operations (e.g. DB requests). At first, run them synchronously: */
		$startTime = microtime(true);
		if (!$skipSyncTest) {
			for ($i = 0; $i < $tasksCount; $i++) {
				$syncResults[] = Tasks::simulateCalculation($results[$i], $runTime);
			}
		}
		/* Measure execution time */
		$synchronousTime = microtime(true) - $startTime;
		if ($skipSyncTest) {
			Console::output(Console::renderColoredString("%bSynchronous%n run is %rskipped%n"));
		} else {
			Console::output(Console::renderColoredString("%bSynchronous%n summary time for %b{$tasksCount}%n tasks is %g{$synchronousTime}%n seconds"));
		}


		/*At second, run each task in a separate process: */
		$startTime = microtime(true);

		for ($i = 0; $i < $tasksCount; $i++) {
			$runtimeList[] = new Runtime(Yii::getAlias('@app/bootstrap_console.php'));
		}
		/*It is required to retrieve config here, because there's no Yii stuff inside a task */
		$config = require Yii::getAlias('@app/config/console.php');
		foreach ($runtimeList as $i => $runtime) {
			$futuresList[] = $runtime->run(static function(float $result) use ($config, $runTime):float {
				new Application($config);//Create a new Yii::$app instance to initialize all
				return Tasks::simulateCalculation($result, $runTime);
			}, [$results[$i]]);
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
		Console::output(Console::renderColoredString(sprintf("Results are %s", (array_sum($syncResults) === array_sum($asyncResults))?"%gequal%n":sprintf("%%rnot equal%%n: %s|%s", array_sum($syncResults), array_sum($asyncResults)))));
	}

	/**
	 * This example shows how to catch exceptions inside parallel tasks
	 * @return void
	 */
	public function actionExampleSeven():void {
		$runtime = new Runtime();

		$future = $runtime->run(function() {
			sleep(5);
			throw new Exception('Exception inside a task');
		});

		for ($i = 0; $i < 500; $i++) {
			echo ".";
		}

		try {
			$v = $future->value();
			printf("Task value is: %s", $v);
		} catch (Throwable $throwable) {
			printf("Exception «%s» happened", $throwable->getMessage());
		}

	}
}