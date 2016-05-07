<?php namespace Kevupton\Ethereal\Traits\Controller;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QBuilder;
use Illuminate\Http\Request;
use Kevupton\Ethereal\Exceptions\EtherealException;
use Kevupton\Ethereal\Exceptions\ResourceException;
use Kevupton\Ethereal\Models\Ethereal;
use Kevupton\Ethereal\Repositories\Repository;

trait ResourceTrait {

    protected $required_id = true;

    protected $from_key = 'from';
    protected $take_key = 'take';
    protected $page_key = 'page';
    protected $take_default = 10;
    protected $from_default = 0;
    protected $paginate = true;

    private $trans_errors = array();
    private $default_errors = array(
        'not_found' => [404, 'Error :val not found in :class data.']
    );
    private $error_messages = array();
    private $_instantiated_class = null;

    /**
     * Gets the current objects class
     *
     * @return string the Ethereal class name
     */
    abstract function getClass();

    /**
     * Attempts to instantiates a new class, otherwise returns false.
     *
     * @return bool|Ethereal|Repository
     */
    private function newClass() {
        $class = $this->getClass();
        try {
            return ($this->_instantiated_class = new $class());
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * Checks to see if the given class is a repository
     *
     * @return bool
     */
    private function isRepository() {
        return ($this->getInstantiated() instanceof Repository);
    }

    /**
     * Instantiates the new class if null else returns an already instantiated class.
     *
     * @return Ethereal|Repository
     */
    private function getInstantiated() {
        return (is_null($this->_instantiated_class))? $this->newClass(): $this->_instantiated_class;
    }

    /**
     * Gets the appropriate query for the given data type.
     *
     * @return Builder|QBuilder
     */
    protected function getQuery() {
        if ($this->getInstantiated() !== false) {
            return $this->getInstantiated()->query();
        } else {
            $class = $this->getClass();
            return $class::query();
        }
    }

    /**
     * Gets the main class associated with either repository or Ethereal
     * @return string
     */
    private function getMainClass() {
        if ($this->isRepository()) return $this->getInstantiated()->getClass();
        else return $this->getClass();
    }

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
     * @param array $data the data loaded from the request.
     * @return string
     */
    private function execute(Request $request, $type, array $data = array()) {
        try {
            if (!$this->hasErrors()) {
                $type = ucfirst(strtolower($type));

                if (method_exists($this, "before$type")) $this->{"before$type"}($request);
                if ($this->isSuccess()) {
                    $callable = $type . 'Main';

                    $class = $this->getInstantiated();
                    if (!$this->isRepository() || !$class->resourceLoad($this->json(), $type, $request, $data)) {
                        call_user_func_array([$this, $callable], array_merge([$request], $data));
                    }

                    if ($this->isSuccess() &&
                        method_exists($this, "after$type")) $this->{"after$type"}($request);
                }
            }
        } catch(ResourceException $e) {
            $this->json()->addError($e->getMessage());
        } catch(EtherealException $e) {
            $errors = $e->getValidationErrors();

            if (is_null($errors)) {
                $this->json()->addError($e->getMessage());
            }
        }

        return response()->json($this->json()->toArray(),$this->json()->getStatusCode());
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
        $page = (int) $request->get($this->page_key, 1);

        if (!is_null($from) || !is_null($take) || !is_null($page)) {
            $take = ($take)?: $this->take_default;
            $from = ($from)?: $this->from_default;
            $from += $take * ($page - 1);

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
        $query = $this->getQuery();

        if ($this->paginate) {
            $this->paginate($request, $query);
        }

        $results = $this->indexLogic($request, $query);

        if ($results instanceof Collection) {
            $results = $results->all();
        }

        if ($results instanceof Ethereal) {
            $this->json()->addData('class', $results, true);
        } else {
            $this->json()->addData('results', $results, true);
            $this->json()->addData('count', count($results), true);
        }

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
        return $query->get();
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
        $object = null;
        try {
            /** @var Ethereal $object */
            $object = $this->getInstantiated()->create($request->all());
            $errors = $object->errors();
        } catch(EtherealException $e) {
            $errors = $e->getValidationErrors();

            if (is_null($errors)) {
                $this->json()->addError($e->getMessage());
                return;
            }
        }
        if ($errors->count()) {
            $this->json()->addError($errors->all());
        } else {
            $this->json()->addData('class', $object->jsonSerialize());
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
        $object = null;
        try {
            /** @var Ethereal $object */
            $object = $this->getInstantiated()->create($request->all());
            $errors = $object->errors();
        } catch(EtherealException $e) {
            $errors = $e->getValidationErrors();

            if (is_null($errors)) {
                $this->json()->addError($e->getMessage());
                return;
            }
        }
        if ($errors->count()) {
            $this->json()->addError($errors->all());
        } else {
            $this->json()->addData('class', $object->jsonSerialize());
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
        $class = $this->getMainClass();
        /** @var Ethereal $object */
        $object = new $class();

        /** @var Ethereal $object */
        $object = $this->getQuery()->where($object->getPrimaryKey(), $id)->first();

        if (is_null($object)) {
            $this->json()->addError($this->getErrorMessage('not_found', [
                'class' => $this->getClass(),
                'val' => $id
            ]));
        } else {
            $this->json()->addData('class', $object->jsonSerialize());
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
        $class = $this->getMainClass();
        /** @var Ethereal $object */
        $object = new $class();

        /** @var Ethereal $object */
        $object = $this->getQuery()->where($object->getPrimaryKey(), $id)->first();

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
            } else {
                $this->json()->addData('class', $object->jsonSerialize());
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
        $class = $this->getMainClass();
        /** @var Ethereal $object */
        $object = new $class();

        /** @var Ethereal $object */
        $object = $this->getQuery()->where($object->getPrimaryKey(), $id)->first();

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
            } else {
                $this->json()->addData('class', $object->jsonSerialize());
            }
        }
    }

    /**
     * @param Request $request
     * @param null $id
     * @return string
     * @throws \ErrorException
     */
    public function destroy(Request $request, $id = null) {
        $this->validateId($request, $id);
        return $this->execute($request,'destroy', [$id]);
    }

    /**
     * The delete logic of the program. Returns the results to be displayed.
     * Override to change the default logic query.
     *
     * @param Request $request
     * @param $id
     * @throws \Exception
     */
    protected function destroyMain(Request $request, $id) {
        $class = $this->getMainClass();
        /** @var Ethereal $object */
        $object = new $class();

        /** @var Ethereal $object */
        $object = $this->getQuery()->where($object->getPrimaryKey(), $id)->first();

        if (is_null($object)) { //if the object was not found then add an error
            $this->json()->addError($this->getErrorMessage('not_found', [
                'class' => $this->getClass(),
                'val' => $id
            ]));
        } else {
            $object->delete();

            if ($object->hasErrors()) { //if the edit function failed
                $this->json()->addError($object->errors()->all());
            }
        }
    }
}