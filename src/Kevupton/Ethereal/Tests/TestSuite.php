<?php

namespace Kevupton\Ethereal\Tests;

/**
 * @deprecated
 * Class TestSuite
 * @package Kevupton\Ethereal\Tests
 */
abstract class TestSuite {

    /**
     * Validates the given class with the specified relationship and value
     *
     * @param $classname the classname of the class
     * @param $rel the relationship
     * @param $val the value of the relationship response
     */
    protected function validate($classname, $rel, $val) {
        if (is_null($val)) {
            echo "\nErrors testing relation '$rel' on '$classname'\n";
            $this->assertNotNull($val);
        }
    }

    /**
     * Runs a test on all relationships for the given model
     *
     * @param $name the name of the class to be testing (for error purposes)
     * @param Ethereal $model the model to be testing.
     */
    protected function runRelationshipTests($name, Ethereal $model) {
        $class = get_class($model);
        $key = "";
        try {
            if (is_null($model)) {
                echo "$name is null\n";
            } else
                foreach ($class::$relationsData as $key => $data) {
                    $this->validate($class, $key, $model->$key);
                }
        } catch(\Exception $e) {
            echo "Thrown on class: $class, '$key'\n";
            echo $e;
        }
    }
}