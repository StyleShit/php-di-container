<?php

namespace Tests\Mocks;

class A
{
    public function __construct(B $dep)
    {
        $this->b = $dep;
    }
}

class B
{
    public function __construct(C $dep)
    {
        $this->c = $dep;
    }
}

class C
{
    public function __construct(Contract $dep)
    {
        $this->contract = $dep;
    }
}

class D
{
    public function __construct($name = 'mock')
    {
        $this->name = $name;
    }
}

interface Contract
{
}

class ContractImpl implements Contract
{
}
