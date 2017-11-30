<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 29/11/2017
 * Time: 12:59 PM
 */

namespace Kevupton\Ethereal\Traits;

use Schema;

trait HasTableColumns
{
    /**
     * Where the table columns will be stored
     *
     * @var array
     */
    public static $tableColumns = null;

    /**
     * The table name
     *
     * @var
     */
    protected $table;

    /**
     * Remembers the columns for this period of time
     * Make false if you don't want to remember
     *
     * @var int
     */
    protected $rememberColumnsDuration = 60 * 24;

    /**
     * Gets the column listing for this table
     *
     * @return array
     */
    public static function getColumns ()
    {
        if (isset(static::$tableColumns)) {
            return self::$tableColumns;
        }

        $class = new static();

        return static::$tableColumns = \Cache::remember(
            static::class . '::columns',
            $class->rememberColumnsDuration,
            function () use ($class) {
                $table = is_callable([$class, 'getTable']) ? $class->getTable() : $class->table;
                return Schema::getColumnListing($table);
            }
        );
    }
}