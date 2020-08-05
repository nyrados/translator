<?php
namespace Nyrados\Translator\Provider;

use Nyrados\Translator\Helper;
use Nyrados\Translator\Language\Language;
use Nyrados\Translator\Translation\Translation;

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
            $rs[] = Helper::detectProccessor(new Translation($this->data[$language->getId()][$string]));
        }

        return $rs;
    }

    public function set(string $language, array $data)
    {
        foreach ($data as $string => $translation) {
            if ($translation != null) {
                $this->data[(new Language($language))->getId()][$string] = $translation;
            }
        }
    }
}