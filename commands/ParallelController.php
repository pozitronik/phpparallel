<?php
declare(strict_types = 1);

namespace app\commands;

use yii\console\Controller;
use parallel\Runtime;

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

		$future = $runtime->run(function(){
			for ($i = 0; $i < 500; $i++)
				echo "*";

			return "easy";
		});

		for ($i = 0; $i < 500; $i++) {
			echo ".";
		}

		printf("\nUsing \\parallel\\Runtime is %s\n", $future->value());
	}

}