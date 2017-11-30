<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 28/11/2017
 * Time: 6:46 PM
 */

namespace Kevupton\Ethereal\Traits;

use Illuminate\Database\Eloquent\Model;
use Validator;

trait HasValidation
{
    use HasEventListeners;

    public $rules = [];
    public $validationMessages = null;
    public $customAttributes = null;
    public $validateModel = true;

    protected $validator = Validator::class;

    /**
     * Gets the max value for an attributes rules.
     *
     * @param $attributeName
     * @return int|null
     */
    public static function getAttributeMaxValue ($attributeName)
    {
        $rules = self::getRulesArray($attributeName);

        if (array_key_exists('max', $rules)) {
            return $rules['max'][0];
        } else {
            if (array_key_exists('size', $rules)) {
                return $rules['size'][0];
            }
        }

        return null;
    }

    /**
     * Converts an attributes rules string into a readable array
     *
     * @param $attributeName
     * @return array|null
     */
    private static function getRulesArray ($attributeName)
    {
        $model = new static();

        if (!is_array($model->rules) || !isset($model->rules[$attributeName])) {
            return null;
        }

        $rules = [];

        foreach (explode('|', $model->rules[$attributeName]) as $rule) {
            list($key, $value) = array_merge(explode(':', $rule), [null]);
            $rules[$key] = $value ? explode(',', $value) : null;
        }

        return $rules;
    }

    /**
     * Event handler for handling on save events
     *
     * @param Model $model
     */
    public static function validationSavingEventHandler (Model $model)
    {
        $validator = $model->validate();

        if ($validator && $validator !== true) {
            var_dump($validator->passes());
        }
    }

    /**
     * Registers the validating event
     *
     * @param $callback
     */
    public static function validating ($callback)
    {
        static::registerModelEvent('validating', $callback);
    }

    /**
     * Validates the model attributes against the set rules.
     *
     * @param array|null $rules
     * @param array|null $attributes
     * @param array|null $messages
     * @param null $customAttributes
     * @return bool
     */
    public function validate (array $rules = null, array $attributes = null, array $messages = null, $customAttributes = null)
    {
        if ($this->fireModelEvent('validating') === false) {
            return false;
        }

        if (!is_array($this->rules) || !count($this->rules)) {
            return true;
        }

        /** @var \Illuminate\Validation\Validator $validate */
        ($this->validator)::validate(
            $attributes ?: $this->attributes ?: [],
            $rules ?: $this->rules ?: [],
            $messages ?: $this->validationMessages ?: [],
            $customAttributes ?: $this->customAttributes ?: []
        );
    }
}