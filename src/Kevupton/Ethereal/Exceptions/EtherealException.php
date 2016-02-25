<?php namespace Kevupton\Ethereal\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;
use Kevupton\Ethereal\Models\Ethereal;

class EtherealException extends Exception {
    private $data = array();

    public function __construct($message = "", $code = 0, Exception $previous = null, array $data = array())
    {
        $this->data = $data;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Gets the model associated with the exception
     *
     * @return Ethereal|Model|null
     */
    public function getModel() {
        return $this->_get('model');
    }

    /**
     * Gets the validation errors associated with the Ethereal model
     *
     * @return MessageBag|null
     */
    public function getValidationErrors() {
        if (($model = $this->getModel()) instanceof Ethereal) {
            return $model->errors();
        } else return null;
    }

    /**
     * Returns variable from the data container
     *
     * @param $name
     * @return mixed|null
     */
    protected function _get($name) {
        if ($this->_has($name)) {
            return $this->data[$name];
        } else return null;
    }

    /**
     * Checks to see if variable exists
     *
     * @param $name
     * @return bool
     */
    protected function _has($name) {
        return isset($this->data[$name]);
    }
}