<?php

namespace Nyrados\Translator\Processor;

use InvalidArgumentException;
use Nyrados\Translator\Processor\ProcessorInterface;

class ProcessorContainer
{
    public function get(string $name)
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException('Invalid Processor');
        }

        return new $name;
    }

    public function has(string $name)
    {
        return class_exists($name) && is_subclass_of($name, ProcessorInterface::class);
    }
}