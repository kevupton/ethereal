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

if (!function_exists('short_name')) {
    /**
     * Returns the mysql datetime format as a string, for use in the php Date() method.
     *
     * @param string $className
     * @return string
     */
    function short_name ($className)
    {
        return last(explode("\\", $className));
    }
}

if (!function_exists('get_public_methods')) {
    /**
     * Gets the public methods on a class
     *
     * @param string $className
     * @param null $filter
     * @return array
     */
    function get_public_methods ($className, $filter = null)
    {
        $class = new ReflectionClass($className);
        return $class->getMethods(ReflectionMethod::IS_PUBLIC & $filter);
    }
}

if (!function_exists('dot_namespace')) {
    /**
     * Converts a name to be in snake case and dot separator.
     * Example: \App\Models\TestModel = app.models.test_model
     *
     * @param string $namespace
     * @return string
     */
    function dot_namespace ($namespace = '')
    {
        return implode('.', array_map(function ($string) {
            return snake_case($string);
        }, explode('/', str_replace('\\', '/', clean_path($namespace)))));
    }
}

if (!function_exists('clean_path')) {
    /**
     * Converts a name to be in snake case and dot separator.
     * Example: \App\Models\TestModel = app.models.test_model
     *
     * @param string $namespace
     * @param string $separator
     * @return string
     */
    function clean_path ($namespace = '', $separator = '\\')
    {
        return preg_replace('/\\+/', '\\', rtrim($namespace, '\\'));
    }
}

if (!function_exists('make_path')) {
    /**
     * Converts a name to be in snake case and dot separator.
     * Example: \App\Models\TestModel = app.models.test_model
     *
     * @param param string[] $pieces
     * @return string
     */
    function make_path (...$pieces)
    {
        $separator = array_pop($pieces);
        return implode($separator, array_map(function ($string) use ($separator) {
            return clean_path($string, $separator);
        }, array_filter($pieces)));
    }
}

if (!function_exists('make_namespace')) {
    /**
     * Converts a name to be in snake case and dot separator.
     * Example: \App\Models\TestModel = app.models.test_model
     *
     * @param string[] $paths
     * @return string
     */
    function make_namespace (...$paths)
    {
        return implode('\\', array_map('clean_path', array_filter($paths)));
    }
}