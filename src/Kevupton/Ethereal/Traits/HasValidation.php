<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 28/11/2017
 * Time: 6:46 PM
 */

namespace Kevupton\Ethereal\Traits;

use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Validator;

trait HasValidation
{
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
        } else if (array_key_exists('size', $rules)) {
            return $rules['size'][0];
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
     * Validates the model attributes against the set rules.
     *
     * @param array|null $rules
     * @param array|null $messages
     * @param null $customAttributes
     * @return bool
     */
    public function validate (array $rules = null, array $messages = null, $customAttributes = null)
    {
        if ($this->fireModelEvent('validating') === false) {
            return false;
        }

        if (!is_array($this->rules) || !count($this->rules)) {
            return true;
        }

        /** @var \Illuminate\Validation\Validator $validate */
        return ($this->validator)::make(
            $this->attributes,
            $rules ?: $this->rules,
            $messages ?: $this->validationMessages,
            $customAttributes ?: $this->customAttributes
        );
    }
}