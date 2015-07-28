<?php

namespace Optimus\Api\Controller\ModeResolver;

use Optimus\Api\Controller\ModeResolver\ModeResolverInterface;

class IdsModeResolver implements ModeResolverInterface {

    public function resolve($property, &$object, &$root)
    {
        if (is_array($object)) {
            return array_map(function($entry){
                return $entry->id;
            }, $object);
        } elseif($object instanceof Collection) {
            return $object->map(function($entry){
                return $entry->id;
            });
        }
    }

}
