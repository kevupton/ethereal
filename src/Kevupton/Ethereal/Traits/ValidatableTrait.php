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

trait ValidatableTrait
{
    use HasAttributes,
        HasEvents;

    protected $rules = [];
    protected $validationMessages = null;
    protected $customAttributes = null;

    protected $validator = Validator::class;

    /**
     * Before each save we need to validate the attributes
     *
     * @param array $array
     * @return mixed
     */
    public function save (array $array = array())
    {
        $this->validate();
        return self::save($array);
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