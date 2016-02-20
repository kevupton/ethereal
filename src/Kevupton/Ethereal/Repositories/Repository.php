<?php namespace Kevupton\Ethereal\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EBuilder;
use Illuminate\Http\Request;
use Kevupton\Ethereal\Exceptions\Exception;
use Kevupton\Ethereal\Models\Ethereal;
use Kevupton\Ethereal\Utils\Json;
use ReflectionClass;

abstract class Repository {
    /**
     * All of the class cached variables
     *
     * @var array
     */
    private $cached = [];

    /**
     * Exceptions for the class to use
     * go in here
     *
     * @var array
     */
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
     * Loads a default class for the repository
     *
     * Repository constructor.
     * @param null $id
     */
    public function __construct($id = null) {
        $this->load($id);
    }

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
            return $this->query()->findOrFail($id);
        } catch(\Exception $e) {
            $this->throwException($this->getClass() . " id: $id not found.");
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
     * @param array $data any additional data to be attached to the exception
     * @param int $code (default exception param)
     * @param null $prev_exception (default exception param)
     */
    public function throwException($message = '', $exception = 'main', array $data = array(), $code = 0, $prev_exception = null) {
        $class = $this->getException($exception);
        throw new $class($message, $code, $prev_exception, $data);
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
     * @param string $key the key to search
     * @param null|mixed $val either the default value or the value to store
     * @param bool $write whether or not to write or retrieve
     * @return null|mixed
     */
    protected function cache($key, $val = null, $write = false) {
        if (isset($this->cached[$key]) && !$write) { //if it as a read method
            return $this->cached[$key];
        } else if ($write) { //if write
            if (is_callable($val)) {
                $this->cached[$key] = $val();
            } else $this->cached[$key] = $val;
            return $this->cached[$key];
        } else { //is read and doesn't exist
            return $val;
        }
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
        return $this->cache($this->getClassSnakeName(), $val, true);
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
     * Returns the name of the class in snake case.
     *
     * @return string
     */
    public function getClassSnakeName() {
        return snake_case($this->getClassShortName());
    }

    /**
     * Gets the cached class if it exists
     *
     * @param mixed|null $default the default value to return
     * @return Model|Ethereal|null
     */
    public function getCachedClass($default = null) {
        return $this->cache($this->getClassSnakeName(), $default);
    }

    /**
     * Throws the errors as the main exception.
     *
     * @param Ethereal $model
     * @param string $joiner
     * @param string $exception_type
     * @param string $message
     */
    public function throwErrors(Ethereal $model, $joiner = "\n", $exception_type = "main", $message = "") {
        if ($model->errors()->count() > 0) {
            $this->throwException(
                !empty($message)?$message: 'Validation Errors: ' . implode($joiner, $model->errors()->all()),
                $exception_type,
                array(
                    'model' => $model
                )
            );
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

    /**
     * Creates or Updates data in the database.
     *
     * @param array $data the data to update
     * @param string $by the id of the column to base the update off
     * @return Ethereal
     */
    public function createOrUpdate(array $data, $by = null) {
        $result = null;
        $class = $this->getClass();

        /** @var Ethereal $class */
        $class = new $class;

        //if the column is not set then go by the primary key
        if (is_null($by)) $by = $class->getPrimaryKey();

        if (isset($data[$by])) {

            /** @var Ethereal $result */
            $result = $this->query()->where($by, $data[$by])->first();

            if (!is_null($result)) {
                $result->fill($data);
            } else {
                $result = $class->fill($data);
            }

            //attempt to save the changes
            $result->save();

            if ($result->hasErrors()) { //if there are errors then throw them.
                $this->throwErrors($result);
            }

        } else {
            $this->throwException($by . ' field is missing');
        }

        return $result;
    }


    /**
     * Removes the class
     *
     * @param mixed $id the id to delete
     * @return bool|null
     * @throws Exception
     */
    public function removeByID($id) {
        return $this->retrieveByID($id)->delete();
    }

    /**
     * Returns an instantiated instance of the class
     * @param array $params
     * @return Ethereal
     */
    public function newClass(array $params = []) {
        $class = new ReflectionClass($this->getClass());
        return $class->newInstanceArgs($params);
    }

    /**
     * Creates a new class with the given data and returns it.
     *
     * @param array $data
     * @return Ethereal
     */
    public function create(array $data) {
        $class = $this->newClass([$data]);
        $class->save();
        $this->throwErrors($class);
        return $class;
    }

}