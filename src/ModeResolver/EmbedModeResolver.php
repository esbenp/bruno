<?php

namespace Optimus\Api\Controller\ModeResolver;

use Optimus\Api\Controller\ModeResolver\ModeResolverInterface;

class EmbedModeResolver implements ModeResolverInterface {

    public function resolve($property, &$object, &$root)
    {
        return $object;
    }

}
