<?php

namespace Nyrados\Translator\Translation;

use ArrayIterator;
use IteratorAggregate;

final class UndefinedStringCollector implements IteratorAggregate
{
    private $storage = [];
    
    public function set(string $name, array $context = [])
    {
        $this->storage[$name] = $context;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->storage);
    }

    public function has(string $name): bool
    {
        return isset($this->storage[$name]);
    }

    public function getContext(string $name): array
    {
        return $this->has($name) ? $this->storage[$name] : [];
    }
}
