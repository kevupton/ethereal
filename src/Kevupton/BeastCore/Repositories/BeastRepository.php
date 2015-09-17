<?php namespace Kevupton\BeastCore\Repositories;

use Illuminate\Database\Eloquent\Model;

abstract class BeastRepository {
    private $cached;
    protected $exceptions = [];

    /**
     * Retrieves the class instance of the specified repository.
     *
     * @return string the string instance of the defining class
     */
    abstract function getClass();

    /**
     * Retrieves the which exception class to use.
     *
     * @param string $exception the exception to throw.
     * @return string the string instance of the exception class
     */
    public function getException($exception = 'main') {
        if (isset($this->exceptions[$exception]) && !empty($this->exceptions[$exception])) {
            return $this->exceptions[$exception];
        } else {
            if ($exception == 'main') {
                return \Exception::class;
            } else {
                return $this->getException();
            }
        }
    }

    /**
     * Attempts to retrieve the Ticket by the given ticket ID.
     *
     * @param int $id the id of the ticket
     * @return Model an instance of the Repository class.
     * @throws \Exception of specified type if it is not found.
     */
    public final function retrieveByID($id) {
        $class = $this->getClass();
        try {
            return $class::findOrFail($id);
        } catch(\Exception $e) {
            $this->throwException("$class id: $id not found");
        }
    }

    /**
     * Throws a specified exception under a specific category with a specified message
     *
     * @param string $message the message of the exception
     * @param string $exception the exception category to throw ex. main
     */
    public function throwException($message = '', $exception = 'main') {
        $class = $this->getException($exception);
        throw new $class($message);
    }

    /**
     * Get method for retrieving the data.
     *
     * @param $key
     * @return null
     */
    public function __get($key) {
        if (isset($this->$key)) {
            return $this->$key;
        } else {
            return $this->cache($key);
        }
    }

    /**
     * Sets the param
     *
     * @param $key
     * @param $val
     * @return null
     */
    public function __set($key, $val) {
        if (isset($this->$key)) {
            return $this->$key;
        } else {
            return $this->cache($key, $val);
        }
    }

    /**
     * Gets or sets a cached value
     *
     * @param $key
     * @param null $val
     * @param bool $clear
     * @return null
     */
    protected function cache($key, $val = null, $clear = false) {
        if (isset($this->cached[$key]) && !$clear) {
            return $this->cached[$key];
        } else {
            if (!is_null($val)) {
                if (is_callable($val)) {
                    $this->cached[$key] = $val();
                } else $this->cached[$key] = $val;
                return $this->cached[$key];
            }
        }
        return null;
    }

    /**
     * Clears the cached data
     *
     * @param null|string $key the specific key to clear
     */
    protected function cacheClear($key = null) {
        if (!is_null($key) && isset($this->cached[$key])) {
            unset($this->cached[$key]);
        } else $this->cached = [];
    }

    /**
     * Loads the element into the dom
     *
     * @param $id
     * @return null
     */
    public function load($id) {
        $val = null;
        if (is_null($id)) return null;
        elseif (is_a($id, $this->getClass())) {
            $val = $id;
        } elseif (is_numeric($id)) {
            $val = $this->retrieveByID($id);
        }
        return $this->cache(strtolower($this->getClassShortName()), $val, true);
    }

    /**
     * Returns the short name of the specified class attached to the repository.
     *
     * @return string the short name
     */
    public function getClassShortName() {
        return last(explode("\\",$this->getClass()));
    }
}