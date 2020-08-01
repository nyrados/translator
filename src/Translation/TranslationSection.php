<?php
namespace Nyrados\Translator\Translation;

use Iterator;
use Nyrados\Translator\TranslatorApi;

class TranslationSection implements Iterator
{

    /** @var Translation[] */
    private $translations = [];

    /** @var TranslatorApi */
    private $translator;

    private $index = 0;

    private $context = [];
    private $strings = [];

    public function __construct(TranslatorApi $translator, array $data, string $language = '')
    {
        $this->translator = $translator;

        foreach ($data as $key => $value) {
            $this->context[] = is_numeric($key) ? [] : $value;
            $this->strings[] = is_numeric($key) ? $value : $key;
        }
        

        foreach ($translator->fetchTranslations($this->strings, $language) as $translation) {
            $this->translations[] = $translation;
        }
    }

    public function get(array $context = []): ?string
    {
        $value = $this->current()($context);
        $this->next();
        return $value;
    }

    public function __invoke(array $context = [])
    {
        return $this->get($context);
    }

    public function __toString()
    {
        $value = $this->get();
        return $value === null ? '' : $value;
    }


    public function rewind()
    {
        $this->index = 0;
    }

    public function key()
    {
        return $this->index;
    }

    public function valid()
    {
        return isset($this->translations[$this->index]);
    }

    public function next()
    {
        $this->index++;
    }

    public function current()
    {
        return function (array $context = []) {
            return $this->valid() 
                ? $this->translator->processTranslation(
                    $this->translations[$this->index], 
                    array_merge($this->context[$this->index], $context
                )) : null;
        };
    }

}