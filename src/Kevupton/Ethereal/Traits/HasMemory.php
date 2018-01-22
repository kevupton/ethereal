<?php

namespace Kevupton\Ethereal\Traits;

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

    /**
     * Checks whether a specific key has been remembered
     *
     * @param $key
     * @return bool
     */
    protected function hasMemory ($key)
    {
        return array_key_exists($key, $this->_memory);
    }

    /**
     * Wipes all items from the memory
     */
    protected function clearMemory ()
    {
        $this->_memory = [];
    }

    /**
     * Forgets a specific key in the memory
     *
     * @param $key
     */
    public function forget ($key)
    {
        if (array_key_exists($key, $this->_memory)) {
            unset($this->_memory[$key]);
        }
    }
}