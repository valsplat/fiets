<?php
	namespace Fiets;
	use \Fiets\Configure as Configure;

	class Fiets {
		public $view;
		public $log;
		public $mode;
		public $environment;
		private $error;

		/**
		 * Registered middleware, will be executed on each request.
		 *
		 * @var string
		 */
		public $middleware = array();

		/**
		 * Constructor
		 *
		 * @author Bjorn Post
		 */
		public function __construct() {
			$this->mode = Configure::read('mode');

			error_reporting(E_ALL);
			if($this->isProduction()) {
				ini_set('display_errors', false);
			} else {
				ini_set('display_errors', true);
			}

			set_error_handler(array('\Fiets\Fiets', 'handleErrors'));
			set_exception_handler(array($this, 'error'));

			$this->view = new \Fiets\View;
			$this->view->setApplication($this);

			$this->log = new \Fiets\Logger;
		}

		/**
		 * Are we on production environment? (live data + stable codebase)
		 * @author Joris Leker
		 */
		public function isProduction() {
			return ($this->mode === 'production');
		}

		/**
		* Are we on staging environment? (live data + testing codebase)
		* @author Joris Leker
		*/
		public function isStaging() {
			return ($this->mode === 'staging');
		}

		/**
		 * Are we on development server? (sandbox data + dev codebase)
		 */
		public function isDevelopment() {
			return ($this->mode === 'development');
		}

		/**
		 * Register middleware.
		 *
		 * Once registered, this middleware will be run on each
		 * request the server handles.
		 *
		 * @param Middleware $newMiddleware
		 * @return void
		 * @author Bjorn Post
		 */
		public function addMiddleware(\Fiets\Middleware $newMiddleware) {
			$newMiddleware->setApplication($this);
			$this->middleware[] = $newMiddleware;
		}

		/**
		 * Error handler
		 *
		 * This method defines or invokes the application-wide error handler.
		 * There are 2 contexts in which this method may be invoked:
		 *
		 * 1. When declaring the handler: if $argument is callable, the
		 *    callable will be registered to be invoked when an uncaught
		 *    exception is detected.
		 *
		 * 2. When invoking the handler: if $argument is not callable, we
		 *    assume you want to invoke an already registered handler. We
		 *    will call it and create a HTTP-500 response.
		 *
		 * @param mixed $argument Callable|\Excption
		 * @author Bjorn Post
		 */
		public function error($argument = null) {
			if(is_callable($argument)) {
				$this->error = $argument;
			} else {
				if(!headers_sent()) header('HTTP/1.1 500 Internal Server Error');
				echo $this->callErrorHandler($argument);
			}
		}

		/**
		 * Call error handler
		 *
		 * This will invoke the error handler and return it's output.
		 *
		 * @param string $argument \Exception|null $argument
		 * @return string
		 * @author Bjorn Post
		 */
		protected function callErrorHandler($argument = null) {
			ob_start();
			if(is_callable($this->error)) {
				call_user_func_array($this->error, array($argument));
			} else {
				call_user_func_array(array($this, 'defaultError'), array($argument));
			}

			return ob_get_clean();
		}

		/**
		 * Default error message
		 *
		 * @see \Fiets\Fiets::error()
		 * @see \Fiets\Fiets::callErrorHandler()
		 * @param string $argument
		 * @return void
		 * @author Bjorn Post
		 */
		protected function defaultError($argument = null) {
			if(!headers_sent()) header('HTTP/1.1 500 Internal Server Error');

			$style = "html * { padding:0; margin:0; }
			body * { padding:10px 20px; max-width: 800px; }
			body * * { padding:0; }
			body { font:small sans-serif; }
			body>div { border-bottom:1px solid #ddd; }
			h1 { font-weight:normal; }
			h2 { margin-bottom:.8em; }
			h2 span { font-size:80%; color:#666; font-weight:normal; }
			h3 { margin:1em 0 .5em 0; }
			h4 { margin:0 0 .5em 0; font-weight: normal; }
			code, pre { font-size: 100%; white-space: pre-wrap; }
			table { border:1px solid #ccc; border-collapse: collapse; width:100%; background:white; margin-top:1.5em; }
			tbody td, tbody th { vertical-align:top; padding:2px 3px; }
			table th { width: 10em; text-align: left; }

			#summary { background: #ffc; }
			#summary h2 { font-weight: normal; color: #666; }
			#traceback { background:#eee; line-height:1.5em; }
			#summary table { border:none; background:transparent; }
			pre.exception_value { font-family: sans-serif; color: #666; font-size: 1.5em; margin: 10px 0 10px 0; }";

			$title = get_class($argument);
			$code = $argument->getCode();
			$message = $argument->getMessage();
			$file = $argument->getFile();
			$line = $argument->getLine();
			$trace = $argument->getTraceAsString();

			if(Configure::read('mode') === 'development') {
				$html  = '<div id="summary">';
				$html .= sprintf('<h1>%s @ %s</h1>', $title, $_SERVER['REQUEST_URI']);
				$html .= sprintf('<pre class="exception_value">%s (%s)</pre>', $message, $code);

				$html .= '<table class="meta">';

				// Request
				if(PHP_SAPI !== 'cli') {
					$html .= sprintf('<tr><th>Request Method:</th><td>%s</td></tr>', $_SERVER['REQUEST_METHOD']);
					$html .= sprintf('<tr><th>Request URI:</th><td>%s</td></tr>', $_SERVER['REQUEST_URI']);
				}

				$html .= sprintf('<tr><th>File:</th><td>%s</td></tr>', $file);
				$html .= sprintf('<tr><th>Line:</th><td>%s</td></tr>', $line);

				$html .= '</table>';
				$html .= '</div>';

				if($title == 'Pheasant\Database\Mysqli\Exception') {
					$html .= '<div id="traceback">';
					$html .= '<h2>Query details</h2>';
					$html .= @sprintf('<pre>%s</pre>', $argument->getTrace()[0]['args'][0]);
					$html .= '</div>';
				}

				if($trace) {
					$html .= '<div id="traceback">';
					$html .= '<h2>Traceback</h2>';
					$html .= sprintf('<pre>%s</pre>', str_replace(ROOT,'',$trace) );
					$html .= '</div>';
				}

				echo sprintf("<html><head><title>%s</title><style>%s</style></head><body>%s</body></html>", $title, $style, $html);

			} else {
				echo $this->render('500.html',compact('html','title','code','message','file','line','trace');
			}

			if($title !== 'Pheasant\Database\Mysqli\Exception') {
				// write errors to log
				if(PHP_SAPI === 'cli') {
					\Analog::log(sprintf('%s - %s (%s) @ %s:%s', $file, $title, $code, $file, $line), $code);
				} else {
					\Analog::log(sprintf('%s %s - %s (%s) @ %s:%s', $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $title, $code, $file, $line), $code);
				}
			} else {
				// write errors to log
				if(PHP_SAPI === 'cli') {
					\Analog::log(sprintf('%s - %s (%s) @ %s:%s (%s)', $file, $title, $code, $file, $line, $argument->getTrace()[0]['args'][0]), $code);
				} else {
					\Analog::log(sprintf('%s %s - %s (%s) @ %s:%s (%s)', $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $title, $code, $file, $line, $argument->getTrace()[0]['args'][0]), $code);
				}
			}
		}

		/**
		 * Convert errors into ErrorExceptions
		 *
		 * This makes them easier to catch in your code.
		 *
		 * @param int    $errno     Error code
		 * @param string $errstr    Error message
		 * @param string $errfile   Absolute path to the affected file
		 * @param int    $errline   Line number of the error in the affected file
		 * @return true
		 * @throws \ErrorException
		 * @author Bjorn Post
		 */
		public static function handleErrors($errno, $errstr = '', $errfile = '', $errline = '') {
			if($errno === 0) {
				// errno 0 means it was suppressed -> http://php.net/manual/en/function.set-error-handler.php
				return;
			}

			if(error_reporting() & $errno) {
				throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
			}

			// write errors to log
			if(PHP_SAPI === 'cli') {
				\Analog::log(sprintf('%s - %s - %s @ %s:%s', $file, $title, $message, $file, $line), $code);
			} else {
				\Analog::log(sprintf('%s %s - %s - %s @ %s:%s', $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $title, $message, $file, $line), $code);
			}

			return true;
		}

		public function render($name, $context = array()) {
			return $this->view->render($name, $context);
		}
	}
