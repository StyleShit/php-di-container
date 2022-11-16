<?php

namespace StyleShit\DIContainer\Tests\Mocks;

class NeedsContract
{
    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
    }
}
