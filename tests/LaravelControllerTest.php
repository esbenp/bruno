<?php

use Mockery as m;
use Illuminate\Http\Request;

require_once __DIR__.'/Controller.php';

class LaravelControllerTest extends Orchestra\Testbench\TestCase
{

    public function testDefaultsWorks()
    {
        $request = $this->createRequest([], null);
        $controller = $this->createControllerMock($request);

        $options = $controller->getResourceOptions();

        $this->assertCount(1, $options['sort']);
        $this->assertArrayHasKey('key', $options['sort'][0]);
        $this->assertArrayHasKey('direction', $options['sort'][0]);

        $this->assertEquals('name', $options['sort'][0]['key']);
        $this->assertEquals('DESC', $options['sort'][0]['direction']);
    }

    public function testResponseIsGenerated()
    {
        $controller = new Controller;
        $response = $controller->getResponseWithResourceCollection();
        $data = $response->getData();

        $this->assertTrue($response instanceof \Illuminate\Http\JsonResponse);
        $this->assertTrue(is_array($data));
    }

    public function testParametersAreAppliedCorrectly()
    {
        $sort = [[ 'key' => 'name', 'direction' => 'DESC' ]];
        $request = $this->createRequest(['children', 'children2'], $sort, 100, 2, [
            [
                'filters' => [
                    ['name', 'eq', 'foo'],
                    ['name', 'ct', 'bar']
                ]
            ]
        ]);

        $controller = $this->createControllerMock($request);
        $options = $controller->getResourceOptions();

        $this->assertEquals($options['includes'], [
            'children', 'children2'
        ]);
        $this->assertEquals($options['limit'], 100);
        $this->assertEquals($options['page'], 2);
        $this->assertTrue(count($options['filter_groups'][0]) === 2);
        $this->assertTrue(count($options['filter_groups'][0]['filters']) === 2);

        $this->assertCount(1, $options['sort']);
        $this->assertArrayHasKey('key', $options['sort'][0]);
        $this->assertArrayHasKey('direction', $options['sort'][0]);

        $this->assertEquals('name', $options['sort'][0]['key']);
        $this->assertEquals('DESC', $options['sort'][0]['direction']);
    }

    public function testArchitectIsFired()
    {
        $request = $this->createRequest(['children:ids']);
        $controller = $this->createControllerMock($request);

        $options = $controller->getResourceOptions();
        $resources = $controller->getParsedResourceCollection($options);

        $this->assertEquals($resources['resources']->get(0)['children']->toArray(), [
            1, 2, 3
        ]);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testThatExceptionIsThrownWhenSettingPageButNotLimit()
    {
        $sort = [[ 'key' => 'name', 'direction' => 'DESC' ]];
        $request = $this->createRequest(['children', 'children2'], $sort, null, 2);
        $controller = $this->createControllerMock($request);

        $controller->getResourceOptions();
    }

    private function createControllerMock(Request $request)
    {
        $routerMock = m::mock('Illuminate\Routing\Router');
        $routerMock->shouldReceive('getCurrentRequest')->andReturn($request);

        $controller = m::mock('Controller[getRouter]');
        $controller->shouldReceive('getRouter')->once()->andReturn($routerMock);

        return $controller;
    }

    private function createRequest(array $includes = [], $sort = false, $limit = null, $page = null, array $filter_groups = [])
    {
        $vars = [];
        if (!empty($includes)) {
            $vars['includes'] = $includes;
        }

        if (!is_null($sort)) {
            if ($sort === false) {
                $sort = [
                    [
                        'key' => 'name',
                        'direction' => 'DESC'
                    ]
                ];
            }
            $vars['sort'] = $sort;
        }

        if (!is_null($limit)) {
            $vars['limit'] = $limit;
        }

        if (!is_null($page)) {
            $vars['page'] = $page;
        }

        if (!empty($filter_groups)) {
            $vars['filter_groups'] = $filter_groups;
        }

        return new Request($vars);
    }
}
