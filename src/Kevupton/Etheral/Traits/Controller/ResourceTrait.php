<?php namespace Kevupton\Ethereal\Traits\Controller;

use App\Http\Requests\Request;

trait ResourceTrait {
    /**
     * Gets the current objects class
     *
     * @return string the Ethereal class name
     */
    abstract function getClass();

    public function index(Request $request) {
        if (method_exists($this, 'beforeIndex')) $this->beforeIndex($request);
        $class = $this->getClass();
        $results = $class::all();
        if (method_exists($this, 'afterIndex')) $this->afterIndex($request);
        echo $results;
    }

    public function create(Request $request) {

    }

    public function store(Request $request) {

    }

    public function show(Request $request, $id) {

    }

    public function edit(Request $request, $id) {

    }

    public function update(Request $request, $id) {

    }

    public function delete(Request $request, $id) {

    }
}