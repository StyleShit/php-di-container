<?php

namespace StyleShit\DIContainer\Tests\Mocks;

class MultipleTypedDependencies
{
    public function __construct(Contract $contract, D $d, $name = 'mock')
    {
        $this->contract = $contract;
        $this->d = $d;
        $this->name = $name;
    }
}
