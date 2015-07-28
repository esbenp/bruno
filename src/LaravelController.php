<?php

namespace Optimus\Api\Controller;

use InvalidArgumentException;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

abstract class LaravelController extends Controller {

    protected $includes = [];

    protected $sort = null;

    protected $limit = null;

    protected $page = null;

    protected $mode = 'embed';

    protected $modeResolvers = [];

    protected $request;

    protected $options;

    protected function response($data, $statusCode = 200, $headers = [])
    {
        if ($data instanceof Arrayable && !$data instanceof JsonSerializable) {
            $data = $data->toArray();
        }

        return new JsonResponse($data, $status, $headers);
    }

    protected function parseData($data, $key)
    {
        $return = [];
        $modes = $this->options['includes']['modes'];

        uksort($modes, function($a, $b){
            return substr_count($b, '.')-substr_count($a, '.');
        });

        if ($this->isCollection($data)) {
            $return[$key] = $this->parseCollection($modes, $data, $return);
        } else {
            $return[$key] = $this->parseResource($modes, $data, $return);
        }

        return $return;
    }

    protected function parseCollection(array $modes, $collection, &$root)
    {
        foreach($collection as &$resource){
            $resource = $this->parseResource($modes, $resource, $root);
        }

        return $collection;
    }

    protected function parseResource(array $modes, &$resource, &$root)
    {
        foreach($modes as $relation => $mode) {
            $modeResolver = $this->resolveMode($mode);

            $steps = explode('.', $relation);
            $property = array_shift($steps);

            if (is_array($resource) || $resource instanceof \ArrayAccess) {
                $object = &$resource[$property];
            } else {
                $object = &$resource->{$property};
            }

            if (empty($steps)) {
                $object = $this->modeResolvers[$mode]->resolve($relation, $object, $root);
            } else {
                $path = implode('.', $steps);
                $modes = [
                    $path => $mode
                ];

                if ($this->isCollection($object)) {
                    $object = $this->parseCollection($modes, $object, $root);
                } else {
                    $object = $this->parseResource($modes, $object, $root);
                }
            }

            if (is_array($resource) || $resource instanceof \ArrayAccess) {
                $resource[$property] = $object;
            } else {
                $resource->{$property} = $object;
            }
        }

        return $resource;
    }

    protected function isCollection($input)
    {
        return is_array($input) || $input instanceof Collection;
    }

    protected function resolveMode($mode)
    {
        if (!isset($this->modeResolers[$mode])) {
            $this->modeResolvers[$mode] = $this->createModeResolver($mode);
        }

        return $this->modeResolvers[$mode];
    }

    protected function &resolveDotNotation(&$data, $steps)
    {
        foreach($steps as $step) {
            if (is_array($data) || $data instanceof \ArrayAccess) {
                $data =& $data[$step];
            } else {
                $data =& $data->{$step};
            }
        }
        return $data;
    }

    protected function createModeResolver($mode)
    {
        $class = 'Optimus\Api\Controller\ModeResolver\\';
        switch($mode){
            default:
            case 'embed':
                $class .= 'EmbedModeResolver';
                break;
            case 'ids':
                $class .= 'IdsModeResolver';
                break;
            case 'sideload':
                $class .= 'SideloadModeResolver';
                break;
        }

        return new $class;
    }

    protected function parseIncludes(array $includes)
    {
        $return = [
            'includes' => [],
            'modes' => []
        ];

        foreach($includes as $include) {
            $explode = explode(':', $include);

            if (!isset($explode[1])) {
                $explode[1] = $this->mode;
            }

            $return['includes'][] = $explode[0];
            $return['modes'][$explode[0]] = $explode[1];
        }

        return $return;
    }

    protected function parseResourceOptions()
    {
        $request = $this->getRouter()->getCurrentRequest();

        $includes = $this->parseIncludes($request->get('includes', $this->includes));
        $sort = $request->get('sort', $this->sort);
        $limit = (int) $request->get('limit', $this->limit);
        $page = (int) $request->get('page', $this->page);

        if ($page !== null && $limit === null) {
            throw new InvalidArgumentException('Cannot use page option without limit option');
        }

        $this->options = [
            'includes' => $includes,
            'sort' => $sort,
            'limit' => $limit,
            'page' => $page
        ];

        return $this->options;
    }

}
