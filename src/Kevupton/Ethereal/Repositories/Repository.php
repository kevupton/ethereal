<?php namespace Kevupton\Ethereal\Repositories;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EBuilder;
use Illuminate\Http\Request;
use Kevupton\Ethereal\Models\Ethereal;
use Kevupton\Ethereal\Utils\Json;

abstract class Repository {
    private $cached;
    protected $exceptions = [];

    /** @var Builder|EBuilder */
    private $query = null;

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
                return Exception::class;
            } else {
                return $this->getException();
            }
        }
    }

    /**
     * Returns the active query. Or a new query if none are active.
     *
     * @return EBuilder|Builder
     */
    public final function query() {
        $class = $this->getClass();
        return $this->queryLogic($this->query = $class::query());
    }

    /**
     * Adds the query logic to the query.
     *
     * @param Builder|EBuilder $query
     * @return EBuilder|Builder
     */
    protected function queryLogic($query) {
        return $query;
    }

    /**
     * Attempts to retrieve the Ticket by the given ticket ID.
     *
     * @param int $id the id of the ticket
     * @return Ethereal an instance of the Repository class.
     * @throws \Exception of specified type if it is not found.
     */
    public final function retrieveByID($id) {
        try {
            if (is_null($val = $this->query()->find($id))) throw new Exception();
            else return $val;
        } catch(\Exception $e) {
            $this->throwException($this->getClass() . " id: $id not found");
        }
    }

    /**
     * Gets all of the results associated with the class.
     *
     * @return array|Collection|static[]
     */
    public final function all() {
        return $this->query()->get();
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

    /**
     * Throws the errors as the main exception.
     *
     * @param Ethereal $model
     * @param string $joiner
     * @param string $exception_type
     */
    public function throwErrors(Ethereal $model, $joiner = "\n", $exception_type = "main") {
        if ($model->errors()->count() > 0) {
            $this->throwException(implode($joiner, $model->errors()->all()), $exception_type);
        }
    }


    /**
     * Handler for the resource trait to run ethereal repositories.
     *
     * @param Json $json
     * @param string $method the action to run on the repository
     * @param Request $request the http request
     * @param array $data any additional data associated with the request
     * @return bool true if the method was called, else false.
     */
    public function resourceLoad(Json $json, $method, Request $request, $data = []) {

        $callable = '_' . strtolower($method);

        if (method_exists($this, $callable)) {
            call_user_func([$this, $callable],[$json, $request, $data]);
            return true;
        }

        return false;

    }
}