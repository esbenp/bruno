<?php

use Illuminate\Support\Collection;
use Optimus\Api\Controller\LaravelController;

class Controller extends LaravelController
{

    // Test that setting defaults does not override
    protected $defaults = [
        'sort' => 'name'
    ];

    public function getResponseWithResourceCollection()
    {
        $resources = $this->getResourceCollection();

        return $this->response($resources);
    }

    public function getParsedResourceCollection(array $options)
    {
        $resources = $this->getResourceCollection();

        return $this->parseData($resources, $options, 'resources');
    }

    public function getResourceCollection()
    {
        $children = new Collection([
            ['id' => 1, 'name' => 'Child 1'],
            ['id' => 2, 'name' => 'Child 2'],
            ['id' => 3, 'name' => 'Child 3']
        ]);

        $children2 = new Collection([
            ['id' => 4, 'name' => 'Child 1'],
            ['id' => 5, 'name' => 'Child 2'],
            ['id' => 6, 'name' => 'Child 3']
        ]);

        $resources = new Collection([
            [
                'id' => 1,
                'name' => 'Resource 1',
                'children' => $children,
                'children2' => $children2,
            ],
            [
                'id' => 2,
                'name' => 'Resource 2',
                'children' => $children,
                'children2' => $children2,
            ]
        ]);

        return $resources;
    }

    public function getResourceOptions()
    {
        return $this->parseResourceOptions();
    }
}
