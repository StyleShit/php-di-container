<?php

namespace StyleShit\DIContainer\Tests\Mocks;

class C
{
    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
    }
}
