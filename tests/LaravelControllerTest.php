<?php

use Mockery as m;
use Illuminate\Http\Request;

require_once __DIR__.'/Controller.php';

class LaravelControllerTest extends Orchestra\Testbench\TestCase
{
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
        $request = $this->createRequest(['children', 'children2'], 'name', 100, 2);
        $controller = $this->createControllerMock($request);

        $options = $controller->getResourceOptions();

        $this->assertEquals($options['includes'], [
            'children', 'children2'
        ]);
        $this->assertEquals($options['sort'], 'name');
        $this->assertEquals($options['limit'], 100);
        $this->assertEquals($options['page'], 2);
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
        $request = $this->createRequest(['children', 'children2'], 'name', null, 2);
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

    private function createRequest(array $includes = [], $sort = 'property', $limit = null, $page = null)
    {
        return new Request([
            'includes' => $includes,
            'sort' => $sort,
            'limit' => $limit,
            'page' => $page
        ]);
    }
}
