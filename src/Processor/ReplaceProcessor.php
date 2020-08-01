<?php
namespace Nyrados\Translator\Processor;

use Nyrados\Translator\Translation\Context\TranslationContext;
use Nyrados\Translator\Translation\Translation;

class ReplaceProcessor implements ProcessorInterface
{
    public function process(string $translation, array $context): string
    {
        $replace = [];

        foreach (array_keys($context) as $contextVar) {
            $replace[] = '{' . $contextVar . '}';
        }

        return str_replace($replace, array_values($context), $translation);
    }

    public static function addReplaceProcessor(Translation $translation): Translation
    {
        if (preg_match('/{[a-z]+}/', (string) $translation)) {
            
            return $translation->withProcessor(self::class);
        }

        return $translation;
    }
}