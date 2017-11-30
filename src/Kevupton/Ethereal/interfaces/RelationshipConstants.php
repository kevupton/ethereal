<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 29/11/2017
 * Time: 11:43 AM
 */

namespace Kevupton\Ethereal\Models;


interface RelationshipConstants
{
    const HAS_MANY = "hasMany";
    const HAS_ONE = "hasOne";
    const BELONGS_TO_MANY = "belongsToMany";
    const BELONGS_TO = "belongsTo";
    const MORPH_TO_MANY = "morphToMany";
    const MORPH_BY_MANY = "morphByMany";
    const MORPH_TO = "morphTo";
    const MORPH_ONE = "morphOne";
    const MORPH_MANY = "morphMany";
    const HAS_MANY_THROUGH = 'hasManyThrough';
    const HAS_MANY_THROUGH_CUSTOM = 'hasManyThroughCustom';
}