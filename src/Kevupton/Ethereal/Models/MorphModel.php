<?php namespace Kevupton\Ethereal\Models;

class MorphModel extends Ethereal {
    public $autoPurgeRedundantAttributes = false;
    // hydrates on new entries validation

    protected $inputs = array();
    protected $morphColumns = array();
    protected $morph_name;

    //To be set by the host
    protected $morphBy;
    protected $morphTable;
    protected $morphModel;

    public function __construct(array $attributes = array()) {
        $class = get_class($this);
        $this->morph_name = snake_case(last(explode("\\",$this->morphModel)));
        $vars = get_class_vars($this->morphModel);
        if (isset($vars['timestamps']) && $vars['timestamps']) {
            $this->touches[] = $this->morph_name;
        }
        $class::$relationsData[$this->morph_name] = array(self::MORPH_ONE, $this->morphModel, $this->morphBy);
        $this->morphColumns = $this->getColumns($this->morphTable);
        parent::__construct($attributes);
    }

    public function __get($key) {
        $x = parent::__get($key);
        if ($x == null) {
            $l = parent::__get($this->morph_name);
            if ($l != null) {
                $x = $l->$key;
            }
        }
        return $x;
    }

    public function __set($key, $value) {
        $is_morph_column = in_array($key, $this->morphColumns);
        $is_table_column = in_array($key, $this->tableColumns);
        $set = false;
        $name = $this->morph_name;
        $l = $this->$name;
        if ($l != null) {
            $x = $l->$key;
            if ($x != null || $is_morph_column) {
                $l->$key = $value;
                $set = true;
            }
        } else if ($is_table_column) {
            parent::__set($key, $value);
            $set = true;
        } else if ($is_morph_column) {
            $this->inputs[$key] = $value;
            $set = true;
        }
        if (!$set) {
            parent::__set($key, $value);
        }
        return $value;
    }

    public function afterSave() {
        $name = $this->morph_name;
        $l = $this->$name;
        if ($l != null) {
            $l->save();
        } else {
            $name = $this->morph_name;
            $test = $this->$name()->create($this->inputs);
        }
    }

    public function validate(array $rules = array(), array $customMessages = array()) {
        $return = parent::validate($rules, $customMessages);
        $name = $this->morph_name;
        $l = $this->$name;
        $data = ($l != null)? $l->getAttributes(): $this->inputs;
        $validate = $this->validateData($data);
        foreach ($validate->errors()->getMessages() as $key => $msgs) {
            foreach ($msgs as $msg) {
                $this->validationErrors->add($key, $msg);
            }
        }
        return $validate->passes() && $return;
    }

    public function fill(array $attributes) {
        $return = parent::fill($attributes);
        $name = $this->morph_name;
        $l = $this->$name;
        if ($l != null) {
            $l->fill($attributes);
        } else {
            $this->inputs = array_merge($this->inputs, $attributes);
        }
        return $return;
    }

    private function validateData($data) {
        $name = $this->morphModel;
        $rules = $name::$rules;
        unset($rules[$this->morphBy . '_id'], $rules[$this->morphBy . '_type']);
        return $this->makeValidator($data, $rules);
    }

    public function delete() {
        $name = $this->morph_name;
        $l = $this->$name;
        if ($l != null) {
            $l->delete();
        }
        parent::delete();
    }
}
