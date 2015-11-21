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
     * @param $app the application instance to run the methods on.
     * @param $group
     * @param $controller
     * @param array $list
     */
    function lumen_resource($app, $group, $controller, array $list = []) {

    }
}