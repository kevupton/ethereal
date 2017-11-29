<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 29/11/2017
 * Time: 11:40 AM
 */

namespace Kevupton\Ethereal\Traits;

use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Relations\Relation;
use Kevupton\Ethereal\Exceptions\DynamicRelationshipException;
use LogicException;

trait HasDynamicRelationships
{
    use HasAttributes,
        HasRelationships {
        getRelationValue as oldGetRelationValue;
    }

    public $relationships = [];

    /**
     * Get a relationship.
     *
     * @param  string $key
     * @return mixed
     */
    public function getRelationValue ($key)
    {
        // If the key already exists in the relationships array, it just means the
        // relationship has already been loaded, so we'll just return it out of
        // here because there is no need to query within the relations twice.
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        // If the "attribute" exists as a method on the model, we will just assume
        // it is a relationship and will load and return results from the query
        // and hydrate the relationship's value on the "relationships" array.
        if (array_key_exists($key, $this->relationships)) {
            return $this->getRelationshipFromMethod($key);
        }
    }

    /**
     * @param $method
     * @param $parameters
     * @return bool|mixed
     */
    public function __call ($method, $parameters)
    {
        // If the method exists in the relationsData then we
        // want to call that relationship instead
        if (array_key_exists($method, $this->relationships)) {
            return $this->callRelationship($method);
        }

        // Call the parent __call if the parent __call exists
        return is_callable(['parent', '__call']) ? parent::__call($method, $parameters) : null;
    }

    /**
     * Get a relationship value from a method.
     *
     * @param  string $method
     * @return mixed
     *
     * @throws \LogicException
     */
    protected function getRelationshipFromMethod ($method)
    {
        $relation = $this->callRelationship($method);

        if (!$relation instanceof Relation) {
            throw new LogicException(get_class($this) . '::' . $method . ' must return a relationship instance.');
        }

        return tap($relation->getResults(), function ($results) use ($method) {
            $this->setRelation($method, $results);
        });
    }

    /**
     * Calls a relationship based on the key in the relations data
     *
     * @param $method
     * @return bool|mixed
     * @throws DynamicRelationshipException
     */
    public function callRelationship ($method)
    {
        $values = array_values($this->relationships[$method]);
        $relationMethod = array_shift($values);

        if (!method_exists($this, $relationMethod)) {
            throw new LogicException(get_class($this) . " has no method '$relationMethod'.");
        }

        return call_user_func_array(array($this, $relationMethod), $values);
    }
}