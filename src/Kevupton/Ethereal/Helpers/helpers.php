<?php

if (!function_exists('current_datetime')) {
    /**
     * Gets the current datetime as a string in mysql datetime format
     *
     * @return string
     */
    function current_datetime ()
    {
        return date(mysql_datetime_format());
    }
}


if (!function_exists('mysql_datetime_format')) {
    /**
     * Returns the mysql datetime format as a string, for use in the php Date() method.
     *
     * @return string
     */
    function mysql_datetime_format ()
    {
        return 'Y-m-d H:i:s';
    }
}

if (!function_exists('json_response')) {
    /**
     * Gets the apps JsonResponse
     *
     * @return \Kevupton\Ethereal\Utils\JsonResponse
     */
    function json_response ()
    {
        return app('eth.json');
    }
}