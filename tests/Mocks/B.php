<?php

namespace StyleShit\DIContainer\Tests\Mocks;

class B
{
    public function __construct(C $c)
    {
        $this->c = $c;
    }
}
