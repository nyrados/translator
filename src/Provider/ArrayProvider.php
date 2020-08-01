<?php
namespace Nyrados\Translator\Provider;

use Generator;
use Nyrados\Translator\Language\Language;
use Nyrados\Translator\Processor\ReplaceProcessor;
use Nyrados\Translator\Translation\Translation;
use RecursiveTreeIterator;

class ArrayProvider implements ProviderInterface
{
    private $data = [];

    public function getTranslations(Language $language, array $strings): array
    {
        if(!isset($this->data[$language->getId()])) {
            return [];
        }

        foreach ($strings as $string) {
            if (!isset($this->data[$language->getId()][$string])) {
                return [];
            }
        }
        
        $rs = [];
        foreach ($strings as $string) {
            $rs[] = ReplaceProcessor::addReplaceProcessor(new Translation($this->data[$language->getId()][$string]));
        }

        return $rs;
    }

    public function set(string $language, array $data)
    {
        foreach ($data as $string => $translation) {
            $this->data[(new Language($language))->getId()][$string] = $translation;
        }
    }
}