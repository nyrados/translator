<?php
namespace Nyrados\Translator\Processor;

/**
 * Describes a Translation Processor
 */
interface ProcessorInterface
{
    /**
     * Process the translation string with a given context
     *
     * See ReplaceProcessor for a simple exsample
     *
     * @param string $translation
     * @param array $context
     * @return string
     */
    public function process(string $translation, array $context): string;
}