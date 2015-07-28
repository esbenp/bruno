<?php

namespace Optimus\Api\Controller\ModeResolver;

interface ModeResolverInterface {

    public function resolve($property, &$object, &$root);

}
