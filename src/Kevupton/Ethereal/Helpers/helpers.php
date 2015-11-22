<?php

if (!function_exists('current_datetime')) {
    /**
     * Gets the current datetime as a string in mysql datetime format
     *
     * @return string
     */
    function current_datetime() {
        return date(mysql_datetime_format());
    }
}


if (!function_exists('mysql_datetime_format')) {
    /**
     * Returns the mysql datetime format as a string, for use in the php Date() method.
     *
     * @return string
     */
    function mysql_datetime_format() {
        return 'Y-m-d H:i:s';
    }
}


if (!function_exists('lumen_resource')) {
    /**
     * Creates a bunch of resource routes which link to the specified controller.
     *
     * @param $app Laravel\Lumen\Application application instance to run the methods on.
     * @param $prefix string the prefix of the application URL.
     * @param $group string the id of the base route
     * @param $controller string the controller class location to use.
     * @param array $list the list of resources to use
     * @param array $except the list of resources not to use
     */
    function lumen_resource($app, $prefix, $group, $controller, array $list = [], array $except = []) {
        $available = array(
            'index' => ['get', '/'],
            'create' => ['get', 'create'],
            'store' => ['post', '/'],
            'show' => ['get', '{id}'],
            'edit' => ['get', '{id}/edit'],
            'update' => ['put', '{id}'],
            'destroy' => ['delete', '{id}']
        );
        if (empty($list)) $list = $available;
        foreach ($except as &$val) {
            $val = strtolower($val);
        }
        $keys = array_keys($available);
        foreach ($list as $item) {
            $val = strtolower($item);
            if (in_array($val,$keys) && !in_array($val, $except)) {
                $func = $available[$val][0];
                $uri = $available[$val][1];
                $app->$func("$prefix/$uri", ['as' => "$group.$val", 'uses' => "$controller@$val"]);
            }
        }
    }
}