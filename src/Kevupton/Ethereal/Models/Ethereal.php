<?php namespace Kevupton\Ethereal\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;
use Kevupton\Ethereal\Relations\HasManyThroughCustom;
use Kevupton\Ethereal\Validation\CustomValidator;
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
    protected $validationErrors;
    protected static $unguarded = false;

    protected $validator = CustomValidator::class;

    public function validate(array $rules = array(), array $customMessages = array()) {
        $return = true;
        if (method_exists($this, 'beforeValidate')) {
            $return = $this->beforeValidate();
        }
        $class = get_class($this);

        if (!$return) {
            return false;
        } else {
            if (isset($class::$rules)) {
                $validate = $this->makeValidator($this->attributes, $class::$rules);
                foreach ($validate->errors()->getMessages() as $key => $msgs) {
                    $return = false;
                    foreach ($msgs as $msg) {
                        $this->validationErrors->add($key, $msg);
                    }
                }
            }
            return $return;
        }
    }

    public function makeValidator($data, $rules) {
        $validator = $this->validator;

        $validate = new $validator(app('translator'), $data, $rules);
        $presence = app('validation.presence');

        if (isset($presence)) {
            $validate->setPresenceVerifier($presence);
        }

        return $validate;
    }

    public function errors() {
        return $this->validationErrors;
    }

    public function __construct(array $attributes = array()) {
        $this->tableColumns = $this->getColumns();
        $this->validationErrors = new MessageBag;
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

    protected function beforeValidate() {
        if (in_array('user_id', $this->tableColumns)) {
            if (Auth::check()) {
                if (is_null($this->user_id)) $this->user_id = Auth::user()->id;
            }
        }
        return true;
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
        $d = $this->getRelational($method);
        if ($d !== false) {
            return $d;
        }
        return parent::__call($method, $parameters);
    }

    public function hasErrors() {
        return $this->validationErrors->count() > 0;
    }

    public function morphTo($name = null, $type = null, $id = null) {
        if (is_null($name))
        {
            $backtrace = debug_backtrace(false, 4);
            $caller = $backtrace[3];
            $name = snake_case($caller['function']);
        }
        return parent::morphTo($name, $type, $id);
    }

    public function belongsTo($string = null, $foreign_key = null, $other_key = null, $relation = null) {
        if (is_null($relation))
        {
            $backtrace = debug_backtrace(false, 5);
            $caller = $backtrace[4];
            $relation = snake_case($caller['function']);
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

    public function save(array $array = array()) {
        $x = false;
        if ($this->validate()) {
            $before_create = false;
            if (!$this->exists && method_exists($this, 'beforeCreate')) {
                $this->beforeCreate();
                $before_create = true;
            }
            if (method_exists($this, 'beforeSave')? ($this->beforeSave() === false)?: true: true) {
                $x = parent::save($array);
            }
            if (!$this->hasErrors()) {
                if (method_exists($this, 'afterSave')) {
                    $this->afterSave();
                }
                if ($before_create && method_exists($this, 'afterCreate')) {
                    $this->afterCreate();
                }
            }

        }
        return $x;
    }

    public static function asSelectArray($id, $value = null, Builder $query = null, $group_by = true) {
        $array = array();
        foreach (self::developArrayResults($id, $value, $query, $group_by) as $c) {
            if ( !is_null($value)) $array[$c->$id] = $c->$value;
            else $array[$c->$id] = $c->$id;
        }
        return $array;
    }

    public static function asJqueryArray($id, $value = null, Builder $query = null, $group_by = true) {
        $array = array();
        foreach (self::developArrayResults($id, $value, $query, $group_by) as $c) {
            $array[] = ['label' => (is_null($value))? $c->$id: $c->$value, 'value' => $c->$id];
        }
        return $array;
    }

    private static function developArrayResults($id, $value = null, Builder $query = null, $group_by = true)
    {
        $class = get_called_class();
        $attr = array($id);
        if (!is_null($value)) $attr[] = $value;
        $query = ($query) ?: $class::query();
        if ($group_by) {
            if (is_string($group_by)) {
                $query = $query->groupby($group_by);
            } else {
                $query = $query->groupby($id);
            }
        }
        return $query->get($attr);
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

    /**
     * Returns the models primary key
     *
     * @return string
     */
    public function getPrimaryKey() {
        return $this->primaryKey;
    }

    /**
     * Gets the value of the primary key.
     *
     * @return mixed|null
     */
    public function getPrimaryKeyValue() {
        return $this->{$this->getPrimaryKey()};
    }
}
