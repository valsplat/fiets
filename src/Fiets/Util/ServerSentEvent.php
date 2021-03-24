<?php

namespace Fiets\Util;

    class ServerSentEvent
    {
        /**
         * Constructs the SSE data format and flushes that data to the client.
         *
         * @param string $msg line of text that should be transmitted
         * @param string $id  timestamp/id of this connection
         *
         * @author http://www.html5rocks.com/en/tutorials/eventsource/basics/
         */
        public static function send($msg, $id = null)
        {
            if (empty($id)) {
                $id = time();
            }

            echo "id: $id".PHP_EOL;
            echo "data: $msg".PHP_EOL;
            echo PHP_EOL;

            if (ob_get_level() > 0) {
                ob_flush();
            }

            flush();
        }
    }
