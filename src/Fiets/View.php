<?php
	namespace Fiets;
	use \Fiets\Configure as Configure;

	class View {
		public $twig;
		protected $app;

		public function __construct() {
			$this->twig = new \Twig_Environment(new \Twig_Loader_Filesystem(ROOT.'/templates'), array(
				'cache' => ROOT.'/cache',
				'debug' => (Configure::read('mode') === 'development'),
			));

			if(Configure::read('mode') !== 'production') {
				$this->twig->addExtension(new \Twig_Extension_Debug());
			}

			$this->twig->getExtension('core')->setNumberFormat(2, ',', '.');
			$this->twig->addGlobal('now', time());
		}

		final public function setApplication(&$application) {
			$this->app = $application;
		}

		final public function getApplication() {
			return $this->app;
		}

		public function render($name, $context = array()) {
			if(php_sapi_name() !== 'cli') {
				$this->twig->addGlobal('REQUEST', array(
					'server' => $_SERVER,
					'data' => $_REQUEST,
					'session' => $_SESSION,
					'cookie' => $_COOKIE,
					'get' => $_GET,  // add these as late as possible, scripts might have added / changed stuff
					'post' => $_POST // that we need during rendering
				));
			}

			return $this->twig->render($name, $context);
		}
	}
