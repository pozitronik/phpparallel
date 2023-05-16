<?php
declare(strict_types = 1);

use parallel\Runtime;

$runtime = new Runtime();

$future = $runtime->run(function(){
	for ($i = 0; $i < 500; $i++)
		echo "!";

	return "работает";
});

for ($i = 0; $i < 500; $i++) {
	echo ".";
}

printf("\nПараллелизм %s\n", $future->value());