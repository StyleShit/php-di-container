<?php

namespace StyleShit\DIContainer\Exceptions;

class UnresolvableDependencyException extends \Exception
{
    public static function make($name)
    {
        return new static("Dependency `$$name` cannot be resolved");
    }
}
