<?php namespace Kevupton\Ethereal\Traits\Controller;

use App\Http\Requests\Request;
use Kevupton\Ethereal\Models\Ethereal;
use Kevupton\Ethereal\Utils\Json;

trait ResourceTrait {
    /** @var  Json */
    private $response;

    private $trans_errors = array();
    private $default_errors = array(
        'not_found' => [404, 'Error :val not found in :class not found.']
    );
    private $error_messages = array();

    /**
     * Gets the current objects class
     *
     * @return string the Ethereal class name
     */
    abstract function getClass();

    /**
     * Executes the main functionality providing before and after situations.
     *
     * @param Request $request the request data
     * @param string $type the type of request it is
     * @param callable $callable the calling function to do in between.
     * @return string
     */
    private function execute(Request $request, $type, callable $callable) {
        $this->response = new Json();
        $type = ucfirst(strtolower($type));

        if (method_exists($this, "before$type")) $this->{"before$type"}($request);
        if ($this->response->isSuccess()) {
            $callable($this->response, $request);
            if ($this->response->isSuccess() &&
                method_exists($this, "after$type")) $this->{"after$type"}($request);
        }

        return $this->response->out();
    }

    /**
     * Gets the appropriate error message response.
     *
     * @param $key string the key to search for the message
     * @param $data array the data to replace the message with
     * @return array an array containing one error message
     */
    private function getErrorMessage($key, array $data = array()) {
        $result = [];
        $use = null;
        if (array_key_exists($key, $this->trans_errors)) {
            $use = $this->trans_errors;
        } else if (array_key_exists($key, $this->error_messages)) {
            $use = $this->error_messages;
        } else if (array_key_exists($key, $this->default_errors)) {
            $use = $this->default_errors;
        } else {
            $result = ['Error message not found.'];
        }

        if (!is_null($use)) {
            if (is_array($use[$key])) {
                $result = [
                    $use[$key][0] => $use[$key][1]
                ];
                $msg = &$result[$use[$key][0]];
            } else {
                $result = [$use[$key]];
                $msg = &$result[0];
            }

            foreach ($data as $item_key => $item_val) {
                $msg = str_replace(":$item_key", $item_val, $msg);
            }
        }

        return $result;
    }

    /**
     * The default route to get all data of object
     *
     * @param Request $request
     * @return string
     */
    public function index(Request $request) {
        return $this->execute($request, 'index', function() {
            $class = $this->getClass();
            $this->response->addData('results', $class::all());
        });
    }

    /**
     * Creates the objects class, through get.
     *
     * @param Request $request
     * @return string
     */
    public function create(Request $request) {
        return $this->execute($request,'create', function() use ($request) {
            $class = $this->getClass();

            /** @var Ethereal $object */
            $object = new $class();
            $object->fill($request->all());

            $object->save();

            if ($object->hasErrors()) {
                $this->response->addError($object->errors()->all());
            }
        });
    }

    /**
     * Post method for storing data
     *
     * @param Request $request
     * @return string the json response
     */
    public function store(Request $request) {
        return $this->execute($request,'store', function() use ($request) {
            $class = $this->getClass();

            /** @var Ethereal $object */
            $object = new $class();
            $object->fill($request->all());

            $object->save();

            if ($object->hasErrors()) {
                $this->response->addError($object->errors()->all());
            }
        });
    }

    /**
     * The get method for retrieving a specific identity
     *
     * @param Request $request
     * @param $id
     * @return string
     */
    public function show(Request $request, $id) {
        return $this->execute($request,'show', function() use ($request, $id) {
            $class = $this->getClass();

            /** @var Ethereal $object */
            $object = $class::find($id);

            if (is_null($object)) {
                $this->response->addError($this->getErrorMessage('not_found', [
                    'class' => $this->getClass(),
                    'val' => $id
                ]));
            }
        });
    }

    /**
     * Get method for editting data
     *
     * @param Request $request
     * @param $id
     * @return string
     */
    public function edit(Request $request, $id) {
        return $this->execute($request,'edit', function() use ($request, $id) {
            $class = $this->getClass();

            /** @var Ethereal $object */
            $object = $class::find($id);

            if (is_null($object)) { //if the object was not found then add an error
                $this->response->addError($this->getErrorMessage('not_found', [
                    'class' => $this->getClass(),
                    'val' => $id
                ]));
            } else {
                $object->fill($request->all());
                $object->save();

                if ($object->hasErrors()) { //if the edit function failed
                    $this->response->addError($object->errors()->all());
                }
            }
        });
    }

    public function update(Request $request, $id) {
        return $this->execute($request,'update', function() use ($request, $id) {
            $class = $this->getClass();

            /** @var Ethereal $object */
            $object = $class::find($id);

            if (is_null($object)) { //if the object was not found then add an error
                $this->response->addError($this->getErrorMessage('not_found', [
                    'class' => $this->getClass(),
                    'val' => $id
                ]));
            } else {
                $object->fill($request->all());
                $object->save();

                if ($object->hasErrors()) { //if the edit function failed
                    $this->response->addError($object->errors()->all());
                }
            }
        });
    }

    public function delete(Request $request, $id) {
        return $this->execute($request,'delete', function() use ($request, $id) {
            $class = $this->getClass();

            /** @var Ethereal $object */
            $object = $class::find($id);

            if (is_null($object)) { //if the object was not found then add an error
                $this->response->addError($this->getErrorMessage('not_found', [
                    'class' => $this->getClass(),
                    'val' => $id
                ]));
            } else {
                $object->fill($request->all());
                $object->delete();

                if ($object->hasErrors()) { //if the edit function failed
                    $this->response->addError($object->errors()->all());
                }
            }
        });
    }
}