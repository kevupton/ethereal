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
        $this->touches[] = $this->getRelationshipName();
        $this->relationships[$this->getRelationshipName()] = array(self::MORPH_ONE, $this->morphClass, $this->morphBy, $this->morphType, $this->morphId);
        parent::__construct($attributes);
    }

    /**
     * Gets the Morph name which is what the relationship will be called
     * @return string
     */
    public function getRelationshipName ()
    {
        return $this->relationshipName ?: ($this->relationshipName = camel_case(last(explode("\\", $this->morphClass))));
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
        $morphModel = $this->getOrCreateMorphModel();

        if ($result = $morphModel->$key) {
            return $result;
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

        if (in_array($key, $morphModel->fillable)) {
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
    public function createdEventHandler (MorphModel $model)
    {
        $relation = $this->getMorphRelation();

        $type = $relation->getMorphType();
        $id = $relation->getForeignKeyName();

        $morphedModel = $this->getOrCreateMorphModel();

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

        $this->relations[$this->getRelationshipName()] = $morphModel = new ($this->morphClass)();
    }

    /**
     * Once saved we want to also call save on the morphed model
     */
    public function savedEventHandler ()
    {
        $this->getOrCreateMorphModel()->save();
    }

    /**
     * On validate we want to also validate the child,
     * only if there is a method for that.
     *
     * @param MorphModel $model
     */
    public function validatingEventHandler (MorphModel $model)
    {
        $morphModel = $model->getMorphModel();

        if ($morphModel->validateModel) {
            $morphModel->validate();
        }
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
