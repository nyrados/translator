<?php

namespace Nyrados\Translator\Translation;

use InvalidArgumentException;
use Nyrados\Translator\Language\Language;
use Serializable;

class Translation
{
    private $string;
    
    /** @var Language  */
    private $language;
    private $processor = [];
    
    public function __construct(string $string, iterable $processor = [])
    {
        $this->string = $string;
        $this->processor = $processor;
        $this->language = new Language('xx-xx');
    }

    public function withProcessor(string $name): self
    {
        $new = clone $this;
        $new->processor[] = $name;
        return $new;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getProcessor(): iterable
    {
        return $this->processor;
    }

    public function __toString()
    {
        return $this->string;
    }

    public function __serialize()
    {
        $data = [
            't' => (string) $this,
        ];
        if ($this->language instanceof Language) {
            $data['l'] = $this->language->getId();
        }

        if (!empty($this->getProcessor())) {
            $data['p'] = $this->getProcessor();
        }

        return $data;
    }

    public function __unserialize(array $data): void
    {
        $this->string = $data['t'];
        $this->language = (new Language($data['l']));
        if (isset($data['p'])) {
            $this->processor = $data['p'];
        }
    }
    
    public function toValue()
    {
        $data = [
            't' => (string) $this,
        ];
        if ($this->language instanceof Language) {
            $data['l'] = $this->language->getId();
        }

        if (!empty($this->getProcessor())) {
            $data['p'] = $this->getProcessor();
        }

        return $data;
    }

    public static function fromValue($value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_array($value) && isset($value['t']) && isset($value['l'])) {
            $new  = new static($value['t'], isset($value['p']) && is_iterable($value['p'])
                    ? $value['p']
                    : []);
            $new->setLanguage(new Language($value['l']));
            return $new;
        }

        throw new InvalidArgumentException();
    }
}
