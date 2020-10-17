<?php

namespace Nyrados\Translator\Processor;

use Nyrados\Translator\Translation\Context\TranslationContext;
use Nyrados\Translator\Translation\Translation;

class ReplaceProcessor implements ProcessorInterface
{
    /**
     * Replaces all values from context.
     * 
     * Example:
     *  
     *  process("Hello {name}!", ["name" => "John"]);
     *  
     *  will produce: Hello John!
     *
     * @param string $translation
     * @param array $context
     * @return string
     */
    public function process(string $translation, array $context): string
    {
        $replace = [];
        foreach (array_keys($context) as $contextVar) {
            $replace[] = '{' . $contextVar . '}';
        }

        return str_replace($replace, array_values($context), $translation);
    }
}
