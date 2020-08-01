<?php

namespace Nyrados\Translator\Processor;

use InvalidArgumentException;
use Nyrados\Translator\Processor\ProcessorInterface;
use Psr\Container\ContainerInterface;

class ProcessorContainer implements ContainerInterface
{
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException('Invalid Processor');
        }

        return new $name;
    }

    public function has($name)
    {
        return class_exists($name) && is_subclass_of($name, ProcessorInterface::class);
    }
}