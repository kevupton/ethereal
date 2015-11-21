<?php namespace Kevupton\Ethereal\Validation;

use Illuminate\Validation\Validator;
use Symfony\Component\Translation\TranslatorInterface;

class CustomValidator extends Validator {
    private $msgs = array(
        'max_decimal' => 'May not have more than :whole whole number digits',
        'min_decimal' => 'Must have more than :whole whole number digits',
        'max_digits' => 'Number cannot be greater than :digits',
        'min_digits' => 'Number cannot be less than :digits',
        'foreign_int' => 'Invalid foreign integer value',
        'foreign_str' => 'Invalid foreign string'
    );
    public function __construct(TranslatorInterface $translator, array $data, array $rules, array $messages = array(), array $customAttributes = array()) {
        parent::__construct($translator, $data, $rules, $messages, $customAttributes);

        $this->setCustomMessages($this->msgs);
        $this->numericRules[] = 'ForeignInt';
    }

    protected function validateMaxDecimal($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'max_decimal');
        return (bool) preg_match('/^[0-9]{0,' . $parameters[0] . '}(\.[0-9]+)?$/',(string) $value);
    }

    protected function replaceMaxDecimal($message, $attribute, $rule, $parameters) {
        return str_replace(':whole', $parameters[0], $message);
    }

    protected function validateMinDecimal($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'min_decimal');
        return (bool) preg_match('/^[0-9]{' . $parameters[0] . ',}(\.[0-9]+)?$/',(string) $value);
    }

    protected function replaceMinDecimal($message, $attribute, $rule, $parameters) {
        return str_replace(':whole', $parameters[0], $message);
    }

    protected function validateMaxDigits($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'max_digits');
        return $this->validateNumeric($attribute, $value) 
                && (strlen($value)) <= $parameters[0];
    }

    protected function replaceMaxDigits($message, $attribute, $rule, $parameters) {
        return str_replace(':digits', $parameters[0], $message);
    }

    protected function validateMinDigits($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'min_digits');
        return $this->validateNumeric($attribute, $value) 
                && (strlen($value)) >= $parameters[0];
    }
    
    protected function replaceMinDigits($message, $attribute, $rule, $parameters) {
        return str_replace(':digits', $parameters[0], $message);
    }

    protected function validateForeignInt($attribute, $value, $parameters) {
        $this->requireParameterCount(2, $parameters, 'foreign_int');
        foreach (['Integer' => [], 'Min' => [1], 'MaxDigits' => [10], 'Exists' => $parameters] as $rule => $params) {
            if ( ! $this->{'validate'.$rule}($attribute, $value, $params)) {
                $this->addError($attribute, $rule, $params);
                return false;
            }
        }
        return true;
    }

    protected function validateForeignStr($attribute, $value, $parameters) {
        $this->requireParameterCount(3, $parameters, 'foreign_str');
        foreach (['Between' => [1, $parameters[0]], 'Exists' => [$parameters[1], $parameters[2]]] as $rule => $params) {
            if ( ! $this->{'validate'.$rule}($attribute, $value, $params)) {
                $this->addError($attribute, $rule, $params);
                return false;
            }
        }
        return true;
    }
}