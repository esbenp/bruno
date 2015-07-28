<?php

namespace Optimus\Api\Controller\ModeResolver;

use Optimus\Api\Controller\ModeResolver\IdsModeResolver;
use Optimus\Api\Controller\ModeResolver\ModeResolverInterface;

class SideloadModeResolver implements ModeResolverInterface {

    private $idsResolver;

    public function __construct(){
        $this->idsResolver = new IdsModeResolver;
    }

    public function resolve($property, &$object, &$root)
    {
        $root[$property] = $object;

        return $this->idsResolver->resolve($property, $object, $root);
    }

}
