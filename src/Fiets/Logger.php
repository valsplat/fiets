<?php
	namespace Fiets;
	use \Fiets\Configure as Configure;

	class Logger {
		public function __construct() {
			$class = Configure::read('log.class');
			$options = Configure::read('log.options');
			\Analog::handler(call_user_func_array(array($class, 'init'), $options));
		}
	}