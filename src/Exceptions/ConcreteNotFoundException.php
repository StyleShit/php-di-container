<?php

namespace StyleShit\DIContainer\Exceptions;

class ConcreteNotFoundException extends \InvalidArgumentException
{
    public static function make($concrete)
    {
        return new static("Concrete `$concrete` not found");
    }
}
