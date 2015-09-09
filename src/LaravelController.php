<?php

namespace Optimus\Api\Controller;

use InvalidArgumentException;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Optimus\Architect\Architect;

abstract class LaravelController extends Controller
{
    protected $defaults = [];

    /**
     * Create a json response
     * @param  mixed  $data
     * @param  integer $statusCode
     * @param  array  $headers
     * @return Illuminate\Http\JsonResponse
     */
    protected function response($data, $statusCode = 200, array $headers = [])
    {
        if ($data instanceof Arrayable && !$data instanceof JsonSerializable) {
            $data = $data->toArray();
        }

        return new JsonResponse($data, $statusCode, $headers);
    }

    /**
     * Parse data using architect
     * @param  mixed $data
     * @param  array  $options
     * @param  string $key
     * @return mixed
     */
    protected function parseData($data, array $options, $key = null)
    {
        $architect = new Architect;

        return $architect->parseData($data, $options['modes'], $key);
    }

    /**
     * Parse include strings into resource and modes
     * @param  array  $includes
     * @return array The parsed resources and their respective modes
     */
    protected function parseIncludes(array $includes)
    {
        $return = [
            'includes' => [],
            'modes' => []
        ];

        foreach ($includes as $include) {
            $explode = explode(':', $include);

            if (!isset($explode[1])) {
                $explode[1] = $this->defaults['mode'];
            }

            $return['includes'][] = $explode[0];
            $return['modes'][$explode[0]] = $explode[1];
        }

        return $return;
    }

    /**
     * Parse GET parameters into resource options
     * @return array
     */
    protected function parseResourceOptions()
    {
        $request = $this->getRouter()->getCurrentRequest();

        $this->defaults = array_merge([
            'includes' => [],
            'sort' => null,
            'limit' => null,
            'page' => null,
            'mode' => 'embed'
        ], $this->defaults);

        $includes = $this->parseIncludes($request->get('includes', $this->defaults['includes']));
        $sort = $request->get('sort', $this->defaults['sort']);
        $limit = $request->get('limit', $this->defaults['limit']);
        $page = $request->get('page', $this->defaults['page']);

        if ($page !== null && $limit === null) {
            throw new InvalidArgumentException('Cannot use page option without limit option');
        }

        return [
            'includes' => $includes['includes'],
            'modes' => $includes['modes'],
            'sort' => $sort,
            'limit' => $limit,
            'page' => $page
        ];
    }
}
