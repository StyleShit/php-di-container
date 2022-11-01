<?php

namespace StyleShit\DIContainer\Exceptions;

class AbstractNotFoundException extends \InvalidArgumentException
{
    public static function make($abstract)
    {
        return new static("Abstract `$abstract` not found");
    }
}
