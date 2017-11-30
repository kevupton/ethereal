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
            // get the event name from the method, and then check to see if it is callable
            // from the current scope.
            if (!($event = self::getEventNameFromMethod($method)) ||
                !is_callable(['static', $event])) {
                continue;
            }

            // Register an event so that on create, we fill the model
            // with all of the request inputs
            static::$event(function (Model $model) use ($method) {
                return $model->$method($model);
            });
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