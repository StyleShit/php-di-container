<?php

namespace StyleShit\DIContainer\Exceptions;

class InvalidAbstractException extends \InvalidArgumentException
{
    public static function make($abstract)
    {
        return new static('Abstract must be a string, `'.gettype($abstract).'` given');
    }
}
