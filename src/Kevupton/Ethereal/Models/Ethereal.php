<?php namespace Kevupton\Ethereal\Models;

use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Kevupton\Ethereal\Traits\HasAutoHydration;
use Kevupton\Ethereal\Traits\HasDynamicRelationships;
use Kevupton\Ethereal\Traits\HasQueryHelpers;
use Kevupton\Ethereal\Traits\HasSingletonMethods;
use Kevupton\Ethereal\Traits\HasTableColumns;
use Kevupton\Ethereal\Traits\HasValidation;

class Ethereal extends Model implements RelationshipConstants
{
    use HasSingletonMethods,
        HasValidation,
        HasQueryHelpers,
        HasDynamicRelationships,
        HasTableColumns,
        HasAutoHydration;

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
