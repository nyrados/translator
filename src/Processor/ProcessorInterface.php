<?php
namespace Nyrados\Translator\Processor;

use Nyrados\Translator\Language\LanguageInterface;
use Nyrados\Translator\Translation\Context\TranslationContext;

interface ProcessorInterface
{
    public function process(string $translation, array $context): string;
}