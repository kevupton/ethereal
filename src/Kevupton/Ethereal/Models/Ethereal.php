<?php namespace Kevupton\Ethereal\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Kevupton\Ethereal\Relations\HasManyThroughCustom;
use Symfony\Component\Debug\Exception\NullMorphException;
use \Auth;

class Ethereal extends Model {
    const HAS_MANY = "hasMany";
    const HAS_ONE = "hasOne";
    const BELONGS_TO_MANY = "belongsToMany";
    const BELONGS_TO = "belongsTo";
    const MORPH_TO_MANY = "morphToMany";
    const MORPH_BY_MANY = "morphByMany";
    const MORPH_TO = "morphTo";
    const MORPH_ONE = "morphOne";
    const MORPH_MANY = "morphMany";
    const HAS_MANY_THROUGH = 'hasManyThrough';
    const HAS_MANY_THROUGH_CUSTOM = 'hasManyThroughCustom';

    public static $loaded_columns = array();

    public $autoHydrateEntityFromInput = true;
    // purge redundant form data
    public $autoPurgeRedundantAttributes = true;
    // hydrates on new entries validation
    protected $tableColumns = array();
    protected static $unguarded = false;

    private $callingMethod = '';

    public function __construct(array $attributes = array()) {
        $this->tableColumns = $this->getColumns();
        parent::__construct($attributes);
    }

    public function getColumns($table = null, $class = null) {
        $class = ($class)?: get_class($this);
        $table = ($table)?: $this->table;
        if (!isset(self::$loaded_columns[$class])) {
            self::$loaded_columns[$class] = Schema::getColumnListing($table);
        }
        return self::$loaded_columns[$class];
    }

    public function disableHydration() {
        $this->autoHydrateEntityFromInput = false;
        $this->autoPurgeRedundantAttributes = false;
    }

    public function __get($key) {
        try {
            $class = get_class($this);
            if (isset($class::$relationsData) && array_key_exists($key, $class::$relationsData)) {
                if (!isset($this->relations[$key])) {
                    $relations = $this->$key();
                    $this->relations[$key] = $relations->getResults();
                }
                return $this->relations[$key];
            }
            return parent::__get($key);
        } catch(NullMorphException $e) {
            return null;
        }
    }

    private function getRelational($method) {
        $class = get_class($this);
        if (isset($class::$relationsData) && array_key_exists($method, $class::$relationsData)) {
            $values = array_values($class::$relationsData[$method]);
            $m = array_shift($values);
            if (empty($values)) {
                return $this->$m();
            } else {
                return call_user_func_array(array($this, $m), $values);
            }
        }
        return false;
    }

    public function __call($method, $parameters) {
        $this->callingMethod = $method;
        $d = $this->getRelational($method);
        if ($d !== false) {
            return $d;
        }
        return parent::__call($method, $parameters);
    }

    public function morphTo($name = null, $type = null, $id = null) {
        if (is_null($name))
        {
            $name = $this->callingMethod;
        }
        return parent::morphTo($name, $type, $id);
    }

    public function belongsTo($string = null, $foreign_key = null, $other_key = null, $relation = null) {
        if (is_null($relation))
        {
            $relation = $this->callingMethod;
        }
        return parent::belongsTo($string, $foreign_key, $other_key, $relation);
    }

    public static function getMaxLength($attr) {
        $class = get_called_class();
        if (isset($class::$rules)) {
            if (isset($class::$rules[$attr])) {
                $rule = $class::$rules[$attr];
                $rules = array();
                foreach (explode('|', $rule) as $r) {
                    $r = explode(':', $r);
                    $rules[$r[0]] = (isset($r[1]))? explode(',', $r[1]): null;
                }
                if (array_key_exists('foreign_int', $rules)) return 10;
                else if (array_key_exists('max_digits', $rules)) return $rules['max_digits'][0];
                else if (array_key_exists('max', $rules)) return $rules['max'][0];
            }
        }
        return null;
    }

    /**
     * Define a has-many-through relationship.
     *
     * @param  string $related
     * @param  string $through
     * @param  string|null $firstKey
     * @param  string|null $secondKey
     * @param null $localKey
     * @param null $pivotKey
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function hasManyThroughCustom($related, $through, $firstKey = null, $secondKey = null, $localKey = null, $pivotKey = null)
    {
        $through = new $through;

        $firstKey = $firstKey ?: $this->getForeignKey();

        $secondKey = $secondKey ?: $through->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new HasManyThroughCustom((new $related)->newQuery(), $this, $through, $firstKey, $secondKey, $localKey, $pivotKey);
    }
}
