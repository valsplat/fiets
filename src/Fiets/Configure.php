<?php
	namespace Fiets;

	class Configure {
		use \Fiets\Singleton;

		private $config = array();

		public static function read($key = null) {
			$instance = self::get_instance();

			if(is_null($key)) {
				return $instance->config;
			} else {
				return $instance->config[$key];
			}
		}

		public static function write($key, $value) {
			$instance = self::get_instance();
			$instance->config[$key] = $value;
		}
	}