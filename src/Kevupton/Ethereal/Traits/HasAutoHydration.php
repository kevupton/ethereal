<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 29/11/2017
 * Time: 1:40 PM
 */

namespace Kevupton\Ethereal\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasAutoHydration
 * @package Kevupton\Ethereal\Traits
 */
trait HasAutoHydration
{
    use HasEventListeners;

    public $autoHydrateModel = true;

    /**
     * Event handler to auto hydrate all of the request inputs on the
     * model.
     *
     * @param Model $model
     */
    public static function hydrationCreatingEventHandler (Model $model)
    {
        if (!$model->autoHydrateModel) {
            return;
        }

        $original = $model->getAttributes();
        $model->fill(array_merge(request()->all(), $original));
    }
}