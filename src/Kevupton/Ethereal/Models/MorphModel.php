<?php namespace Kevupton\Ethereal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class MorphModel extends Ethereal
{
    /** @var string the class associated with the morphing */
    protected $morphClass;

    /** @var string The name of the morph foreign key */
    protected $morphBy;
    /** @var string The explicit type of the morph foreign key */
    protected $morphType;
    /** @var string The explicit id of the morph foreign key */
    protected $morphId;
    /** @var string the name to bind the morphing to */
    protected $relationshipName;


    public function __construct (array $attributes = array())
    {
        if ((new $this->morphClass)->timestamps) {
            $this->touches[] = $this->getRelationshipName();
        }
        $this->relationships[$this->getRelationshipName()] = [self::MORPH_ONE, $this->morphClass, $this->morphBy, $this->morphType, $this->morphId];
        parent::__construct($attributes);
    }

    /**
     * Gets the Morph name which is what the relationship will be called
     * @return string
     */
    public function getRelationshipName ()
    {
        return $this->relationshipName ?: ($this->relationshipName = camel_case(short_name($this->morphClass)));
    }

    /**
     * On delete we also want to delete the morphed model
     *
     * @param MorphModel $model
     */
    public static function deletedEventHandle (MorphModel $model)
    {
        if ($morphModel = $model->getMorphModel()) {
            $morphModel->delete();
        }
    }

    /**
     * @return Model|null
     */
    public function getMorphModel ()
    {
        return $this->{$this->getRelationshipName()};
    }

    /**
     * Getter to return the morphed model with priority
     * over the parent model
     *
     * @param $key
     * @return mixed|null
     */
    public function __get ($key)
    {
        $relationshipName = $this->getRelationshipName();

        if ($key !== $relationshipName) {
            $morphModel = $this->__get($relationshipName);

            if (!is_null($result = $morphModel->$key)) {
                return $result;
            }
        }

        return parent::__get($key);
    }

    /**
     * Setter to also trigger the setter for the morph model
     * only if there is a fillable attribute
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set ($key, $value)
    {
        $morphModel = $this->getOrCreateMorphModel();

        if (in_array($key, $morphModel->getFillable() ?: [])) {
            $morphModel->$key = $value;
        } else {
            parent::__set($key, $value);
        }
    }

    /**
     * We need to assign the key to the model on create
     *
     * @param MorphModel $model
     */
    public static function createdEventHandler (MorphModel $model)
    {
        $relation = $model->getMorphRelation();

        $type = $relation->getMorphType();
        $id = $relation->getForeignKeyName();

        $morphedModel = $model->getOrCreateMorphModel();

        $morphedModel->$type = get_class($model);
        $morphedModel->$id = $model->getKey();
    }

    /**
     * @return MorphOne
     */
    public function getMorphRelation ()
    {
        return $this->{$this->getRelationshipName()}();
    }

    /**
     * @return Model|null
     */
    public function getOrCreateMorphModel ()
    {
        if ($morphModel = $this->getMorphModel()) {
            return $morphModel;
        }

        return $this->relations[$this->getRelationshipName()] = $morphModel = new $this->morphClass();
    }

    /**
     * Once saved we want to also call save on the morphed model.
     * The child should save first for validation purposes
     *
     * @param MorphModel $model
     */
    public static function savedEventHandler (MorphModel $model)
    {
        $model->getOrCreateMorphModel()->save();
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
        $morphModel = $this->getOrCreateMorphModel();
        $attributes = $attributes ?: array_merge($this->attributes, $morphModel->getAttributes());

        if (is_null($rules)) {
            $rules = $this->rules;

            if ($morphModel->validateModel) {
                $rules = array_merge($this->rules, $morphModel->rules ?: []);
            }
        }

        return parent::validate($rules, $attributes, $messages, $customAttributes);
    }

    /**
     * We also want to fill the morphed model with data
     *
     * @param array $attributes
     * @return $this
     */
    public function fill (array $attributes)
    {
        /** @var MorphModel $return */
        $return = parent::fill($attributes);

        $this->getOrCreateMorphModel()->fill($attributes);

        return $return;
    }
}
