<?php

namespace Kevupton\Ethereal\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait HasResourceInterface
{
    /** @var string the class instance for the resource */
    public static $class;

    /** @var int how many to paginate the paginatable pages. Use false for no pagination */
    protected $paginate = 10;

    /**
     * Gets the model from the id given
     *
     * @param $id
     * @return Model
     */
    private static function model (&$id)
    {
        if ($id instanceof Model) {
            return $id;
        }

        $id = (static::$class)::findOrFail($id);

        return $id;
    }

    /**
     * Instantiates the routes based on the controller name or path specified
     *
     * @param array  $routes
     * @param null   $path
     * @param array  $actionOverrides
     * @param string $namespace
     */
    public static function routes ($routes = ['index', 'store', 'show', 'update', 'destroy'], $path = null, $actionOverrides = [], $namespace = '')
    {
        $path        = $path ?: preg_replace('/_?controller/i', '', snake_case(short_name(static::class)));
        $mainActions = $actionOverrides;
        $shortName   = short_name(static::class);

        if (is_array($actionOverrides)) {
            $mainActions = array_merge([
                'uses' => make_namespace($namespace, $shortName),
                'as'   => make_path('.', dot_namespace($namespace), snake_case($shortName)),
            ], $mainActions);
        }

        $modelName = snake_case(short_name(static::$class));
        $router    = router();

        if (method_exists($router, 'bind')) {
            $router->bind($modelName, function ($id) {
                return self::model($id);
            });
        }

        $result = [];

        foreach ($routes as $key) {
            $actions = $mainActions;

            // append each of the routes to the controller method and the name.
            if (is_array($actions)) {
                $actions['uses'] = $actions['uses'] . "@$key";
                $actions['as']   = $actions['uses'] . ".$key";
            }

            /** @var \Illuminate\Routing\Route $route */
            $route = null;

            switch ($key) {
                case 'index':
                    $route = $router->get($path, $actions);
                    break;
                case 'store':
                    $route = $router->post($path, $actions);
                    break;
                case 'show':
                    $route = $router->get("$path/{{$modelName}}", $actions);
                    break;
                case 'update':
                    $route = $router->patch("$path/{{$modelName}}", $actions);
                    break;
                case 'destroy':
                    $route = $router->delete("$path/{{$modelName}}", $actions);
                    break;
                default:
                    continue;
            }

            $result[$key] = $route;
        }
    }

    /**
     * Function which handles the main results for the index logic.
     * Override to create custom logic.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index (Request $request)
    {
        return (static::$class)::paginate($this->paginate);
    }

    /**
     * The store logic of the program. Returns the results to be displayed.
     * Override to change the default logic query.
     *
     * @param Request $request
     * @return Model
     */
    public function store (Request $request)
    {
        return (static::$class)::create($request->all());
    }

    /**
     * The show logic of the program. Returns the results to be displayed.
     * Override to change the default logic query.
     *
     * @param Model $model
     * @return mixed
     * @internal param Request $request
     */
    public function show ($model)
    {
        return self::model($model);
    }

    /**
     * The update logic of the program. Returns the results to be displayed.
     * Override to change the default logic query.
     *
     * @param Request $request
     * @param Model   $model
     * @return Model
     */
    public function update (Request $request, $model)
    {
        self::model($model)
            ->fill($request->all())
            ->save();

        return $model;
    }

    /**
     * The delete logic of the program. Returns the results to be displayed.
     * Override to change the default logic query.
     *
     * @param Request $request
     * @param Model   $model
     * @return Model
     * @throws \Exception
     */
    public function destroy (Request $request, $model)
    {
        self::model($model)->delete();
        return $model;
    }
}