<?php

namespace Fiets;

abstract class Middleware
{
    protected $app;

    final public function setApplication(&$application)
    {
        $this->app = $application;
    }

    final public function getApplication()
    {
        return $this->app;
    }

    abstract public function call();
}
