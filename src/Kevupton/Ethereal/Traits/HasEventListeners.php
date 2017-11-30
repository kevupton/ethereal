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
    protected static $eventMethodPattern = '/([a-z]+)_event_handler/i';

    /**
     * Registers a boot method which listens for the creating event.
     */
    protected static function boot ()
    {
        if (is_callable(['parent', 'boot'])) {
            parent::boot();
        }

        $methods = get_class_methods(static::class);

        foreach ($methods as $method) {
            if ($event = self::getEventNameFromMethod($method)) {

                // Register an event so that on create, we fill the model
                // with all of the request inputs
                if (is_callable(['static', $event])) {
                    static::$event(function (Model $model) use ($method) {
                        return $model->$method($model);
                    });
                }
            }
        }
    }

    /**
     * @param $method
     * @return bool|string
     */
    protected static function getEventNameFromMethod ($method)
    {
        if (preg_match(self::$eventMethodPattern, snake_case($method), $matches)) {
            return $matches[1];
        }

        return false;
    }
}