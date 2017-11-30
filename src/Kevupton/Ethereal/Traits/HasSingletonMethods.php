<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 29/11/2017
 * Time: 1:00 PM
 */

namespace Kevupton\Ethereal\Traits;

use Kevupton\Ethereal\Exceptions\UndefinedMethodException;

trait HasSingletonMethods
{
    protected $methodResults = [];
    protected $methodPrefix = 's';

    /**
     * Magic method for creating singletons
     *
     * @param $name
     * @return mixed|null
     */
    public function __get ($name)
    {
        try {
            return $this->callAndStoreMethod($name);
        } catch (UndefinedMethodException $e) {

        }

        return is_callable(['parent', '__get']) ? parent::__get($name) : null;
    }

    /**
     * Calls a method and stores it in the
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function callAndStoreMethod ($name)
    {
        $method = camel_case($this->methodPrefix . '_' . $name);

        if (array_key_exists($name, $this->methodResults)) {
            return $this->methodResults[$name];
        }

        if (!method_exists($this, $method)) {
            throw new UndefinedMethodException('Call to undefined method ' . get_class($this) . '::' . $method . '()');
        }

        return $this->methodResults[$name] = $this->$method();
    }
}