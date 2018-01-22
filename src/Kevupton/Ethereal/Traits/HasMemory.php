<?php

namespace App\Traits;

trait HasMemory
{
    /** @var array */
    protected $_memory = [];

    /**
     * Remembers a method in memory. Useful for single running methods
     *
     * @param      $key
     * @param null $callable
     * @return null
     */
    protected function memory ($key, $callable = null)
    {
        if (array_key_exists($key, $this->_memory)) {
            return $this->_memory[$key];
        }

        if (is_callable($callable)) {
            return $this->_memory[$key] = $callable();
        } else {
            return $this->_memory[$key] = $callable;
        }
    }
}