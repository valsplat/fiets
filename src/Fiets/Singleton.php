<?php

namespace Fiets;

trait Singleton
{
    private static $instance;

    private function __construct()
    {
    }

    public function __clone()
    {
    }

    public function __wakeup()
    {
    }

    /**
     * Get the instance.
     *
     * @return object
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get the instance.
     *
     * @deprecated use self::getInstance()
     *
     * @return void
     *
     * @author Bjorn
     */
    public static function get_instance()
    {
        return self::getInstance();
    }
}
