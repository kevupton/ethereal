<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 29/11/2017
 * Time: 2:07 PM
 */

namespace Kevupton\Ethereal\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasEventListeners
{
    /**
     * Registers a boot method which listens for the creating event.
     */
    protected static function boot ()
    {
        if (is_callable(['parent', 'boot'])) {
            parent::boot();
        }

        // Register an event so that on create, we fill the model
        // with all of the request inputs
        if (is_callable(['static', 'creating'])) {
            static::creating(function (Model $model) {
                if (!$model->autoHydrateModel) return;

                $original = $model->getAttributes();
                $model->fill(array_merge(request()->all(), $original));
            });
        }
    }
}