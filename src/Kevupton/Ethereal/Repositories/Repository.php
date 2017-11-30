<?php namespace Kevupton\Ethereal\Repositories;

use FastRoute\Route;
use Illuminate\Database\Eloquent\Builder as EBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Kevupton\Ethereal\Models\Ethereal;
use Kevupton\LaravelJsonResponse\JsonResponse;
use ReflectionClass;

/**
 * @deprecated
 * Class Repository
 * @package Kevupton\Ethereal\Repositories
 */
abstract class Repository
{
    /**
     * Exceptions for the class to use
     * go in here
     *
     * @var array
     */
    protected $exceptions = [];
    /**
     * All of the class cached variables
     *
     * @var array
     */
    private $cached = [];
    /** @var Builder|EBuilder */
    private $query = null;

    /**
     * Loads a default class for the repository
     *
     * Repository constructor.
     * @param null $id
     */
    public function __construct ($id = null)
    {
        $this->load($id);
    }

    /**
     * Loads the element into the dom
     *
     * @param mixed $id
     * @return null|Ethereal|Model
     */
    public function load ($id)
    {
        return $this->cache([
            $this->getClassSnakeName() => $this->retrieve($id)
        ]);
    }

    /**
     * Gets or sets a cached value
     *
     * @param string $key the key to search
     * @param null $default
     * @param bool $literal the literal value to get (including '.' in the name)
     * @param bool $write_default whether to write the default in the case the default value is used
     * @param bool $run_once whether or not to run the method one, if the value is a function.
     * @return mixed|null
     */
    protected function cache ($key, $default = null, $literal = false, $write_default = false, $run_once = false)
    {
        if (is_array($key)) { //if write
            $written = array();
            foreach ($key as $k => $to_write) {

                if (is_callable($to_write) && $run_once) {
                    $to_write = $to_write();
                }

                if (!$literal) {
                    $this->define_in_iterate_cache($k, $to_write);
                } else {
                    $this->cached[$k] = $to_write;
                }

                if (is_callable($to_write)) {
                    $to_write = $to_write();
                }

                $written[] = $to_write;
            }

            return (count($written) == 1) ? $written[0] : $written;
        } else {
            $found = $literal ? $this->cached[$key] : $this->iterate_cache($key);

            if (isset($found) && !is_array($key)) { //if it as a read method

                if (is_callable($found)) {  //get the function value.
                    $found = $found();
                }

                return $found;
            } else { //is read and doesn't exist
                if ($write_default) { //check to see if to write the default value.
                    if (is_callable($default) && $run_once) { //if the default value is a function and should only run once
                        $default = $default();
                    }

                    $this->cached[$key] = $default;
                }

                if (is_callable($default)) { //if is a function then get the value.
                    $default = $default();
                }

                return $default;
            }
        }
    }

    private function define_in_iterate_cache ($key, $value)
    {
        $list = explode('.', $key);
        $result = &$this->cached;

        $count = count($list);
        for ($i = 0; $i < $count; $i++) {
            if ($i == $count - 1) {
                $result[$list[$i]] = $value;
            } else {
                if (isset($result[$list[$i]])) {

                    $_a = &$result[$list[$i]];
                    if (is_array($_a)) {
                        //do nothing
                    } else {
                        $this->throwException('Error accessing iterated cached property');
                    }

                } else {
                    if ($i < $count - 1) { //create new array
                        $result[$list[$i]] = array();
                    }
                }
                $result = &$result[$list[$i]];
            }
        }

        return $value;
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
    public function throwException ($message = '', $exception = 'main', array $data = array(), $code = 0, $prev_exception = null)
    {
        $class = $this->getException($exception);
        throw new $class($message, $code, $prev_exception, $data);
    }

    /**
     * Retrieves the which exception class to use.
     *
     * @param string $exception the exception to throw.
     * @return string the string instance of the exception class
     */
    public function getException ($exception = 'main')
    {
        if (isset($this->exceptions[$exception]) && !empty($this->exceptions[$exception])) {
            return $this->exceptions[$exception];
        } else {
            if ($exception == 'main') {
                return EtherealException::class;
            } else {
                return $this->getException();
            }
        }
    }

    private function iterate_cache ($key)
    {
        $list = explode('.', $key);
        $result = $this->cached;
        foreach ($list as $sub_key) {
            if (isset($result[$sub_key])) {
                $result = $result[$sub_key];
            } else {
                return null;
            }
        }

        return $result;
    }

    /**
     * Returns the name of the class in snake case.
     *
     * @return string
     */
    public function getClassSnakeName ()
    {
        return snake_case($this->getClassShortName());
    }

    /**
     * Returns the short name of the specified class attached to the repository.
     *
     * @return string the short name
     */
    public function getClassShortName ()
    {
        return short_name($this->getClass());
    }

    /**
     * Retrieves the class instance of the specified repository.
     *
     * @return string the string instance of the defining class
     */
    abstract function getClass ();

    /**
     * Retrieves the class by a given value or set of values
     *
     * @param int|array|Ethereal $id
     * @return null|Ethereal|Model
     */
    public function retrieve ($id)
    {

        if (is_null($id)) {
            return null;
        } elseif (is_a($id, $this->getClass())) {
            return $id;
        } elseif (is_array($id)) {
            return $this->retrieveWhere($id);
        } else {
            return $this->retrieveByID($id);
        }
    }

    /**
     * Gets an array of values based around the given input data criteria
     *
     * @param array $data
     * @param bool $and
     * @return array|Collection|static[]
     */
    public function retrieveWhere (array $data = array(), $and = true)
    {
        $method = $and ? 'where' : 'orWhere';

        $query = $this->query();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (count($value) == 3) {
                    $query->$method($value[0], $value[1], $value[2]);
                } else {
                    $query->$method($value[0], $value[1]);
                }
            } else {
                $query->$method($key, $value);
            }
        }

        return $query->get();
    }

    /**
     * Returns the active query. Or a new query if none are active.
     *
     * @return EBuilder|Builder
     */
    public final function query ()
    {
        return $this->queryLogic(($this->getClass())::query());
    }

    /**
     * Adds the query logic to the query.
     *
     * @param Builder|EBuilder $query
     * @return EBuilder|Builder
     */
    protected function queryLogic ($query)
    {
        return $query;
    }

    /**
     * Attempts to retrieve the Ticket by the given ticket ID.
     *
     * @param int $id the id of the ticket
     * @return Ethereal an instance of the Repository class.
     * @throws \Exception of specified type if it is not found.
     */
    public final function retrieveByID ($id)
    {
        try {
            return $this->query()->findOrFail($id);
        } catch (\Exception $e) {
            $this->throwException($this->getClass() . " id: $id not found.");
        }
    }

    /**
     * @param null $uri
     * @return \Illuminate\Routing\Route
     */
    public static function show ($uri = null)
    {
        return Route::get('', []);
    }

    /**
     * @param $uri
     * @param array $action
     * @return \Illuminate\Routing\Route
     */
    public static function post ($uri, $action = [])
    {
        return Route::post('', []);
    }

    /**
     * Gets all of the results associated with the class.
     *
     * @return array|Collection|static[]
     */
    public final function all ()
    {
        return $this->query()->get();
    }

    /**
     * Get method for retrieving the data.
     *
     * @param $key
     * @return null
     */
    public function __get ($key)
    {
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
    public function __set ($key, $val)
    {
        if (isset($this->$key)) {
            return $this->$key;
        } else {
            return $this->cache([$key => $val]);
        }
    }

    /**
     * Gets the cached class if it exists
     *
     * @param mixed|null $default the default value to return
     * @return Model|Ethereal|null
     */
    public function getCachedClass ($default = null)
    {
        $result = $this->retrieve($default);
        return is_null($result) ? $this->cache($this->getClassSnakeName()) : $result;
    }

    /**
     * Creates or Updates data in the database.
     *
     * @param array $data the data to update
     * @param string $by the id of the column to base the update off
     * @return Ethereal
     */
    public function createOrUpdate (array $data, $by = null)
    {
        $result = null;
        $class = $this->getClass();

        /** @var Ethereal $class */
        $class = new $class;

        //if the column is not set then go by the primary key
        if (is_null($by)) {
            $by = $class->getPrimaryKey();
        }

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
     * Throws the errors as the main exception.
     *
     * @param Ethereal $model
     * @param string $joiner
     * @param string $exception_type
     * @param string $message
     * @return Ethereal
     */
    public function throwErrors (Ethereal $model, $joiner = "\n", $exception_type = "main", $message = "")
    {
        if ($model->errors()->count() > 0) {
            $this->throwException(
                !empty($message) ? $message : 'Validation Errors: ' . implode($joiner, $model->errors()->all()),
                $exception_type,
                array(
                    'model' => $model
                )
            );
        }
        return $model;
    }

    /**
     * Removes the class
     *
     * @param mixed $id the id to delete
     * @return bool|null
     * @throws Exception
     */
    public function removeByID ($id)
    {
        return $this->retrieveByID($id)->delete();
    }

    /**
     * Creates a new class with the given data and returns it, also
     * caching it.
     *
     * @param array $data
     * @param bool $load whether or not to load it into the cache
     * @return Ethereal
     */
    public function create ($data, $load = true)
    {
        if (!is_array($data)) {
            $this->throwException('data is not an array');
        }

        $class = $this->newClass([$data]);
        $class->save();
        $this->throwErrors($class);

        if ($load) {
            $this->load($class);
        } //loads into the cache

        return $load ? $this->load($class) : $class;
    }

    /**
     * Returns an instantiated instance of the class
     * @param array $params
     * @return Ethereal
     */
    public function newClass (array $params = [])
    {
        $class = new ReflectionClass($this->getClass());
        return $class->newInstanceArgs($params);
    }

    /**
     * Clears the cached data
     *
     * @param null|string $key the specific key to clear
     */
    protected function cacheClear ($key = null)
    {
        if (!is_null($key) && isset($this->cached[$key])) {
            unset($this->cached[$key]);
        } else {
            $this->cached = [];
        }
    }

}