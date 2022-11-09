<?php

namespace StyleShit\DIContainer\Exceptions;

class ConcreteNotInstantiableException extends \InvalidArgumentException
{
    public static function make($concrete)
    {
        return new static("Concrete `$concrete` is not instantiable");
    }
}
