<?php namespace Kevupton\Ethereal\Models;

use Illuminate\Database\Eloquent\Model;
use Kevupton\Ethereal\Traits\HasAutoHydration;
use Kevupton\Ethereal\Traits\HasDynamicRelationships;
use Kevupton\Ethereal\Traits\HasEventListeners;
use Kevupton\Ethereal\Traits\HasSingletonMethods;
use Kevupton\Ethereal\Traits\HasTableColumns;
use Kevupton\Ethereal\Traits\HasValidation;

class Ethereal extends Model implements RelationshipConstants
{
    use HasEventListeners,
        HasSingletonMethods,
        HasValidation,
        HasDynamicRelationships,
        HasTableColumns,
        HasAutoHydration
    {
        HasEventListeners::boot insteadof HasValidation;
        HasEventListeners::getEventNameFromMethod insteadof HasValidation;
        HasEventListeners::boot insteadof HasAutoHydration;
        HasEventListeners::getEventNameFromMethod insteadof HasAutoHydration;
    }
}
