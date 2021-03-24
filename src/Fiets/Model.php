<?php

namespace Fiets;

abstract class Model extends \Pheasant\DomainObject
{
    protected $app;

    public function setApplication(&$application)
    {
        $this->app = $application;
    }

    public function getApplication()
    {
        return $this->app;
    }

    /**
     * Override \Pheasant\DomainObject's tableName function
     * because it returns singular.
     *
     * @return void
     *
     * @author Bjorn Post
     */
    public function tableName()
    {
        $tokens = explode('\\', get_class($this));

        return strtolower(array_pop($tokens)).'en';
    }

    /**
     * Loads an array of values into the object
     * but only uses properties that are defined
     * and makes it impossible to overload primary key.
     *
     * @param array data to load
     * @param bool unset primary key if present in array
     *
     * @author Joris Leker
     */
    public function loadStrict($array, $unsetPrimary = true)
    {
        if ($unsetPrimary) {
            $className = get_called_class();
            $model = new $className();
            $primary = key($model->schema()->primary());
            unset($array[$primary]);
        }

        foreach ($array as $key => $value) {
            if ($this->has($key)) {
                if (is_object($value) || is_array($value)) {
                    $this->$key = $value;
                } else {
                    $this->set($key, $value);
                }
            }
        }

        return $this;
    }
}
