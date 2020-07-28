<?php
namespace Nyrados\Translator\Provider;

use Nyrados\Translator\Helper;
use Nyrados\Translator\Language\Language;
use Nyrados\Translator\Language\LanguageInterface;
use Nyrados\Translator\Processor\ReplaceProcessor;
use Nyrados\Translator\Translation\Translation;


class TranslationProvider implements ProviderInterface
{
    private $data = [
        'en-en' => [
            'start_hello_world' => [
                't' => 'Hello {name}!',
                'p' => [
                    ReplaceProcessor::class
                ]
            ]
        ]
    ];

    public function getTranslation(Language $language, string $translationString): ?Translation
    {
        $id = $language->getId();

        if (!isset($this->data[$id])) {
            return null;
        }

        $data = $this->data[$id];

        if (isset($data[$translationString])) {
            return Translation::fromValue($data[$translationString]);
        } 

        return null;
    }   

    public function add(string $lang, string $string, Translation $translation)
    {
        $this->data[$lang][$string] = $translation;
    }
}