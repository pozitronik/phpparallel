<?php
declare(strict_types = 1);

namespace app\models;

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
		echo "Wait for {$pause} s.";
		sleep($pause);
		return $message;
	}

}