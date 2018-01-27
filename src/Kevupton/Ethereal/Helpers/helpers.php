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
     * @param null   $filter
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

if (!function_exists('lumen_resource')) {
    /**
     * Creates a bunch of resource routes which link to the specified controller.
     *
     * @param       $app        Laravel\Lumen\Application application instance to run the methods on.
     * @param       $prefix     string the prefix of the application URL.
     * @param       $group      string the id of the base route
     * @param       $controller string the controller class location to use.
     * @param array $list       the list of resources to use
     * @param array $except     the list of resources not to use
     * @param bool  $require_id
     * @deprecated
     */
    function lumen_resource ($app, $prefix, $group, $controller, array $list = [], array $except = [], $require_id = true)
    {
        $id        = $require_id ? '{id}' : '';
        $available = [
            'index'   => ['get', ''],
            'create'  => ['get', 'create'],
            'store'   => ['post', ''],
            'show'    => ['get', $id],
            'edit'    => ['get', $id . (!$require_id ? "" : "/") . "edit"],
            'update'  => ['put', $id],
            'destroy' => ['delete', '{id}'],
        ];
        if (empty($list)) {
            $list = array_keys($available);
        }
        foreach ($except as &$val) {
            $val = strtolower($val);
        }
        $keys = array_keys($available);
        foreach ($list as $item) {
            $func = null;
            if (is_array($item)) {
                $val  = $item[0];
                $func = $item[1];
                $uri  = $item[2];
            } else {
                $val = strtolower($item);
                if (in_array($val, $keys) && !in_array($val, $except)) {
                    $func = $available[$val][0];
                    $uri  = $available[$val][1];
                }
            }
            if (!is_null($func)) {
                $app->$func("$prefix/$uri", ['as' => "$group.$val", 'uses' => "$controller@$val"]);
            }
        }
    }
}

if (!function_exists('is_laravel')) {
    /**
     * Returns whether or not the application is laravel
     *
     * @return bool
     */
    function is_laravel ()
    {
        return is_a(app(), 'Illuminate\Foundation\Application');
    }
}

if (!function_exists('is_lumen')) {
    /**
     * Returns whether or not the application is lumen
     *
     * @return bool
     */
    function is_lumen ()
    {
        return is_a(app(), 'Laravel\Lumen\Application');
    }
}

if (!function_exists('router')) {
    /**
     * Gets the router instance based on the application
     *
     * @return \Laravel\Lumen\Routing\Router|\Illuminate\Routing\Router
     */
    function router ()
    {
        if (is_lumen()) {
            return app()->router;
        } elseif (is_laravel()) {
            return app('router');
        }
        return null;
    }
}