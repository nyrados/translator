<?php
namespace Nyrados\Translator\Translation;

use InvalidArgumentException;

class Translation
{

    private $string;

    private $processor;

    public function __construct(string $string, iterable $processor = [])
    {
        $this->string = $string;
        $this->processor = $processor;
    }

    public function getProcessor(): iterable
    {
        return $this->processor;
    }

    public function __toString()
    {
        return $this->string;
    }
    
    public function toValue()
    {
        return empty($this->getProcessor()) 
            ? (string) $this
            : [
                't' => (string) $this,
                'p' => $this->getProcessor()
            ];
    }

    public static function fromValue($value): self
    {
        if($value instanceof self) {
            return $value;
        }

        if(is_string($value)) {
            
            return new static ($value);
        }

        if(is_array($value) && isset($value['t'])) {
            
            return new static (
                $value['t'], 
                isset($value['p']) && is_iterable($value['p']) 
                    ? $value['p'] 
                    : []
            );
        }

        throw new InvalidArgumentException();
    }
}