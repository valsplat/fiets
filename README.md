#Fiets, a simple Web Framework
Fiets is a PHP 5.3+ micro-framework we use at Valsplat to develop our internal applications.

	<?php
		define('ROOT', dirname(__DIR__));
		require ROOT.'/vendor/autoload.php';

		use \Gum\Route as Route;

		$app = new \Fiets\Fiets;

		Route::get('/hello/:what', function($what) use ($app) {
			echo "Hello, ".$what;
		});

Fiets works with PHP 5.3.3 or later.
