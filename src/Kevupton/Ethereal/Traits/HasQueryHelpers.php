<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 28/11/2017
 * Time: 9:19 PM
 */

namespace Kevupton\Ethereal\Traits;

/**
 * @deprecated
 * Trait HasQueryHelpers
 * @package Kevupton\Ethereal\Traits
 */
trait HasQueryHelpers
{
    public static function arraySelect ($id, $value = null, Builder $query = null, $group_by = true)
    {
        $array = array();
        foreach (self::developArrayResults($id, $value, $query, $group_by) as $c) {
            if (!is_null($value)) {
                $array[$c->$id] = $c->$value;
            } else {
                $array[$c->$id] = $c->$id;
            }
        }
        return $array;
    }

    private static function developArrayResults ($id, $value = null, Builder $query = null, $group_by = true)
    {
        $class = get_called_class();
        $attr = array($id);
        if (!is_null($value)) {
            $attr[] = $value;
        }
        $query = ($query) ?: $class::query();
        if ($group_by) {
            if (is_string($group_by)) {
                $query = $query->groupby($group_by);
            } else {
                $query = $query->groupby($id);
            }
        }
        return $query->get($attr);
    }

    public static function arrayJquery ($id, $value = null, Builder $query = null, $group_by = true)
    {
        $array = array();
        foreach (self::developArrayResults($id, $value, $query, $group_by) as $c) {
            $array[] = ['label' => (is_null($value)) ? $c->$id : $c->$value, 'value' => $c->$id];
        }
        return $array;
    }
}