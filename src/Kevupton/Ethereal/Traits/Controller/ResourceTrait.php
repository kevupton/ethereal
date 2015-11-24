<?php namespace Kevupton\Ethereal\Traits\Controller;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Kevupton\Ethereal\Models\Ethereal;

trait ResourceTrait {

    protected $required_id = true;

    protected $from_key = 'from';
    protected $take_key = 'take';
    protected $take_default = 10;
    protected $from_default = 0;
    protected $paginate = true;

    private $trans_errors = array();
    private $default_errors = array(
        'not_found' => [404, 'Error :val not found in :class data.']
    );
    private $error_messages = array();


    /**
     * Gets the current objects class
     *
     * @return string the Ethereal class name
     */
    abstract function getClass();

    /**
     * Validate that the id is not null. If there is a getId method then run that.
     *
     * @param Request $request
     * @param $id
     * @throws \ErrorException
     */
    private function validateId(Request $request, &$id) {
        if (is_null($id) && $this->required_id ||
            !method_exists($this, 'getId') && !$this->required_id) {
            throw new \ErrorException("ID is null");
        } else if (is_null($id)) $id = $this->getId($request);
    }

    /**
     * Executes the main functionality providing before and after situations.
     *
     * @param Request $request the request data
     * @param string $type the type of request it is
     * @param callable $callable the calling function to do in between.
     * @return string
     */
    private function execute(Request $request, $type, array $data = array()) {
        if (!$this->hasErrors()) {
            $type = ucfirst(strtolower($type));

            if (method_exists($this, "before$type")) $this->{"before$type"}($request);
            if ($this->isSuccess()) {
                $callable = $type . 'Main';
                call_user_func_array([$this, $callable], array_merge([$request], $data));
                if ($this->isSuccess() &&
                    method_exists($this, "after$type")) $this->{"after$type"}($request);
            }
        }

        return $this->json()->out();
    }

    /**
     * Applies the pagination rules to the given query
     *
     * @param Request $request the request
     * @param mixed $query the query of the object
     * @return mixed
     */
    protected function paginate(Request $request, $query) {
        $from = $request->get($this->from_key);
        $take = $request->get($this->take_key);

        if (!is_null($from) || !is_null($take)) {
            $from = ($from)?: $this->from_default;
            $take = ($take)?: $this->take_default;

            $query->take($take)->skip($from);
        }

        return $query;
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
        return $this->execute($request, 'index');
    }

    /**
     * Function which handles the main results for the index logic.
     * Override to create custom logic.
     *
     * @param Request $request
     */
    protected function indexMain(Request $request) {

        $class = $this->getClass();
        $query = $class::query();

        if ($this->paginate) {
            $this->paginate($request, $query);
        }

        $results = $this->indexLogic($request, $query);

        $name = ($results instanceof Ethereal || is_null($results))? 'class': 'results';

        $this->json()->addData($name, $results, true);

    }

    /**
     * The index logic of the program. Returns the results to be displayed.
     * Override to change the default logic query.
     *
     * @param Request $request
     * @param mixed $query
     * @return mixed
     */
    protected function indexLogic(Request $request, $query) {
        return $query->get()->all();
    }

    /**
     * Creates the objects class, through get.
     *
     * @param Request $request
     * @return string
     */
    public function create(Request $request) {
        return $this->execute($request,'create');
    }

    /**
     * The create logic of the program. Returns the results to be displayed.
     * Override to change the default logic query.
     *
     * @param Request $request
     */
    protected function createMain(Request $request) {
        $class = $this->getClass();

        /** @var Ethereal $object */
        $object = new $class();
        $object->fill($request->all());

        $object->save();

        if ($object->hasErrors()) {
            $this->json()->addError($object->errors()->all());
        } else {
            $this->json()->addData('class', $object->getAttributes());
        }
    }

    /**
     * Post method for storing data
     *
     * @param Request $request
     * @return string the json response
     */
    public function store(Request $request) {
        return $this->execute($request,'store');
    }

    /**
     * The store logic of the program. Returns the results to be displayed.
     * Override to change the default logic query.
     *
     * @param Request $request
     */
    protected function storeMain(Request $request) {
        $class = $this->getClass();

        /** @var Ethereal $object */
        $object = new $class();
        $object->fill($request->all());

        $object->save();

        if ($object->hasErrors()) {
            $this->json()->addError($object->errors()->all());
        } else {
            $this->json()->addData('class', $object->getAttributes());
        }
    }

    /**
     * The get method for retrieving a specific identity
     *
     * @param Request $request
     * @param $id
     * @return string
     */
    public function show(Request $request, $id = null) {
        $this->validateId($request, $id);
        return $this->execute($request, 'show', [$id]);
    }

    /**
     * The show logic of the program. Returns the results to be displayed.
     * Override to change the default logic query.
     *
     * @param Request $request
     */
    protected function showMain(Request $request, $id) {
        $class = $this->getClass();

        /** @var Ethereal $object */
        $object = $class::find($id);

        if (is_null($object)) {
            $this->json()->addError($this->getErrorMessage('not_found', [
                'class' => $this->getClass(),
                'val' => $id
            ]));
        } else {
            $this->json()->addData('class', $object->getAttributes());
        }
    }


    /**
     * Get method for editting data
     *
     * @param Request $request
     * @param $id
     * @return string
     */
    public function edit(Request $request, $id = null) {
        $this->validateId($request, $id);
        return $this->execute($request,'edit', [$id]);
    }

    /**
     * The edit logic of the program. Returns the results to be displayed.
     * Override to change the default logic query.
     *
     * @param Request $request
     * @param $id
     */
    protected function editMain(Request $request, $id) {
        $class = $this->getClass();

        /** @var Ethereal $object */
        $object = $class::find($id);

        if (is_null($object)) { //if the object was not found then add an error
            $this->json()->addError($this->getErrorMessage('not_found', [
                'class' => $this->getClass(),
                'val' => $id
            ]));
        } else {
            $object->fill($request->all());
            $object->save();

            if ($object->hasErrors()) { //if the edit function failed
                $this->json()->addError($object->errors()->all());
            }
        }
    }

    /**
     * @param Request $request
     * @param null $id
     * @return string
     * @throws \ErrorException
     */
    public function update(Request $request, $id = null) {
        $this->validateId($request, $id);
        return $this->execute($request,'update', [$id]);
    }

    /**
     * The update logic of the program. Returns the results to be displayed.
     * Override to change the default logic query.
     *
     * @param Request $request
     * @param $id
     */
    protected function updateMain(Request $request, $id) {
        $class = $this->getClass();

        /** @var Ethereal $object */
        $object = $class::find($id);

        if (is_null($object)) { //if the object was not found then add an error
            $this->json()->addError($this->getErrorMessage('not_found', [
                'class' => $this->getClass(),
                'val' => $id
            ]));
        } else {
            $object->fill($request->all());
            $object->save();

            if ($object->hasErrors()) { //if the edit function failed
                $this->json()->addError($object->errors()->all());
            }
        }
    }

    /**
     * @param Request $request
     * @param null $id
     * @return string
     * @throws \ErrorException
     */
    public function delete(Request $request, $id = null) {
        $this->validateId($request, $id);
        return $this->execute($request,'delete', [$id]);
    }

    /**
     * The delete logic of the program. Returns the results to be displayed.
     * Override to change the default logic query.
     *
     * @param Request $request
     * @param $id
     * @throws \Exception
     */
    protected function deleteMain(Request $request, $id) {
        $class = $this->getClass();

        /** @var Ethereal $object */
        $object = $class::find($id);

        if (is_null($object)) { //if the object was not found then add an error
            $this->json()->addError($this->getErrorMessage('not_found', [
                'class' => $this->getClass(),
                'val' => $id
            ]));
        } else {
            $object->fill($request->all());
            $object->delete();

            if ($object->hasErrors()) { //if the edit function failed
                $this->json()->addError($object->errors()->all());
            }
        }
    }
}