<?php namespace Kevupton\Ethereal\Models;

use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Kevupton\Ethereal\Traits\HasAutoHydration;
use Kevupton\Ethereal\Traits\HasDynamicRelationships;
use Kevupton\Ethereal\Traits\HasEventListeners;
use Kevupton\Ethereal\Traits\HasQueryHelpers;
use Kevupton\Ethereal\Traits\HasSingletonMethods;
use Kevupton\Ethereal\Traits\HasTableColumns;
use Kevupton\Ethereal\Traits\HasValidation;

class Ethereal extends Model implements RelationshipConstants
{
    use HasEventListeners,
        HasSingletonMethods,
        HasValidation,
        HasQueryHelpers,
        HasDynamicRelationships,
        HasTableColumns,
        HasAutoHydration
    {
        HasEventListeners::boot insteadof HasValidation;
        HasEventListeners::getEventNameFromMethod insteadof HasValidation;
        HasEventListeners::boot insteadof HasAutoHydration;
        HasEventListeners::getEventNameFromMethod insteadof HasAutoHydration;
    }

    /**
     * Creates the class from the request
     *
     * @param Request $request
     */
    public static function fromRequest (Request $request)
    {
        $class = new static();
        $class->fill($request->all());
        $class->save();
    }
}
