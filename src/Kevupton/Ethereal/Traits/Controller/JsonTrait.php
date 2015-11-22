<?php namespace Kevupton\Ethereal\Traits\Controller;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Kevupton\Ethereal\Models\Ethereal;
use Kevupton\Ethereal\Utils\Json;

trait JsonTrait {

    /** @var  Json */
    protected $response;

    public function newJson() {
        $this->response = new Json();
    }

    public function hasErrors() {
        return $this->response->hasErrors();
    }

    public function isSuccess() {
        return $this->response->isSuccess();
    }

}