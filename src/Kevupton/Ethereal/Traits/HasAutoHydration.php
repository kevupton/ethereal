<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 29/11/2017
 * Time: 1:40 PM
 */

namespace Kevupton\Ethereal\Traits;

/**
 * Trait HasAutoHydration
 * @package Kevupton\Ethereal\Traits
 */
trait HasAutoHydration
{
    public $autoHydrateModel = true;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        if (is_callable(['parent', '__construct'])) {
            parent::__construct($attributes);
        }

        if ($this->autoHydrateModel) {
            $this->hydrateModel();
        }
    }

    /**
     * Auto hydrate all of the request inputs on the model.
     */
    public function hydrateModel ()
    {
        $original = $this->getAttributes();
        $this->fill(array_merge(request()->all(), $original));
    }
}