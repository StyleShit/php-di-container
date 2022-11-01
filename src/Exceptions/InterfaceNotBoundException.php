<?php

namespace StyleShit\DIContainer\Exceptions;

class InterfaceNotBoundException extends \InvalidArgumentException
{
    public static function make($abstract)
    {
        return new static("Interface `$abstract` is not bound to a concrete");
    }
}
