<?php
	namespace Fiets\Util;

	class ServerSentEvent {

		/**
		 * Constructs the SSE data format and flushes that data to the client.
		 *
		 * @param string $msg Line of text that should be transmitted.
		 * @param string $id Timestamp/id of this connection.
		 * @author http://www.html5rocks.com/en/tutorials/eventsource/basics/
		 */
		public static function send($msg, $id = null) {
			if(empty($id)) {
				$id = time();
			}

			echo "id: $id" . PHP_EOL;
			echo "data: $msg" . PHP_EOL;
			echo PHP_EOL;

			ob_flush();
			flush();
		}
	}