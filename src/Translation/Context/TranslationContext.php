<?php
namespace Nyrados\Translator\Translation\Context;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Iterator;
use IteratorAggregate;
use Nyrados\Translator\Language\Language;
use Nyrados\Translator\Language\LanguageDecorator;
use Nyrados\Translator\Language\LanguageInterface;

class TranslationContext implements ArrayAccess, IteratorAggregate
{
    private $array = [];

    /** @var Language */
    private $language;

    public function __construct(array $context, Language $language)
    {
        $this->array = $context;
        $this->language = $language;
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->array);
    }

    public function getContextVars()
    {
        return $this->array;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function offsetExists($offset)
    {
        return isset($this->array[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->array[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        if($offset === null) {
            $this->array[] = $value;
        } else {
            $this->array[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        if($this->offsetExists($offset)) {
            unset($this->array[$offset]);
        }
    }
}