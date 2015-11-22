<?php namespace Kevupton\Ethereal\Utils;

class Json {
    private $data = array();
    private $errors = array();

    public function addError($key, $val = null) {
        if (is_array($key)) {
            $this->errors = array_merge($this->errors, $key);
        } else {
            if (!is_null($val)) {
                $this->errors[$key] = $val;
            } else {
                $this->errors[] = $val;
            }
        }
    }

    public function addData($key, $val = null, $use_null = false) {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            if (!is_null($val) || $use_null) {
                $this->data[$key] = $val;
            } else {
                $this->data[] = $key;
            }
        }
    }

    public function removeError($key) {
        unset($this->errors[$key]);
    }

    public function removeData($key) {
        unset($this->data[$key]);
    }

    public function out() {
        return json_encode($this->toArray());
    }

    public function toArray() {
        return [
            'data' => $this->data,
            'errors' => $this->errors,
            'success' => $this->isSuccess()
        ];
    }

    public function __toString() {
        return $this->out();
    }

    public function isSuccess() {
        return count($this->errors) == 0;
    }

    public function hasErrors() {
        return count($this->errors) > 0;
    }
}