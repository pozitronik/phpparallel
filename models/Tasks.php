<?php
declare(strict_types = 1);

namespace app\models;

use Yii;
use yii\base\Exception;

/**
 * Parallel tasks
 */
class Tasks {

	/**
	 * @param int $pause Time to wait (seconds)
	 * @param string $message Optional return message
	 * @return string
	 */
	public static function waiter(int $pause = 5, string $message = ""):string {
		echo "Wait for {$pause} s.\n";
		sleep($pause);
		return $message;
	}

	/**
	 * @param float $result
	 * @return float
	 */
	public static function simulateDBRequest(float $result):float {
		sleep(5);
		return $result;
	}

	/**
	 * @param float $result
	 * @return float
	 * @throws Exception
	 */
	public static function simulateCalculation(float $result):float {
		$startTime = microtime(true);
		$rnd = Yii::$app->security->generateRandomString();
		while (5 > microtime(true) - $startTime) {
			$rnd = sha1($rnd);
		}
		return $result;
	}

}