<?php
namespace Nyrados\Translator\Processor;

use Nyrados\Translator\Translation\Context\TranslationContext;

class ReplaceProcessor implements ProcessorInterface
{

    private $end; 
    private $start;

    public function __construct (string $start = '{', string $end = '}')
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function process(string $translation, TranslationContext $context): string
    {
        $replace = [];
        $context = $context->getContextVars();

        foreach (array_keys($context) as $contextVar) {
            $replace[] = $this->start . $contextVar . $this->end;
        }

        return str_replace($replace, array_values($context), $translation);
    }
}