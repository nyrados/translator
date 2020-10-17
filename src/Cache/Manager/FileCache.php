<?php

namespace Nyrados\Translator\Cache\Manager;

use Nyrados\Translator\Helper;
use Nyrados\Translator\Cache\Util\Meta;
use Nyrados\Translator\Cache\TranslationCacheInterface;
use Nyrados\Translator\Language\Language;

class FileCache implements CacheManagerInterface
{

    /** @var string */
    private $dir;

    public function __construct(string $dir)
    {
        $this->dir = $dir;
    }

    public function loadMeta(string $cacheName): ?Meta
    {   
        $file = $this->dir . '/' . $cacheName . '/meta.php';
        if (file_exists($file)) {
            return Meta::fromArray(require $file);
        }

        return null;
    }

    public function loadSingle(string $cacheName, Language $preference): iterable
    {
        $file = $this->dir . '/' . $cacheName . '/' . $preference->getId() . '.php';
        if (!file_exists($file)) {
            return [];
        }

        return array_map(fn(string $translation) => unserialize($translation), require $file);
    }

    public function loadGroup(string $groupName, Language $preference): iterable
    {
        $file = $this->dir . '/g/' . $groupName . '/' . $preference->getId() . '.php';
        if (!file_exists($file)) {
            return [];
        }

        return array_map(fn(string $translation) => unserialize($translation), require $file);
    }


    public function saveMeta(string $cacheName, Meta $meta)
    {
        Helper::savePHPArrayToFile($this->dir . '/' . $cacheName . '/meta', $meta->toArray());
    }

    /**
     * Saves a Translation Group
     *
     * @param string $name
     * @param Translation[] $translations
     * @return void
     */
    public function saveGroup(string $groupName, iterable $translations)
    {
        if (empty($translations)) {
            return;
        }

        $translations = Helper::iterableToArray($translations);
        $language = array_values($translations)[0]->getLanguage()->getId();

        Helper::savePHPArrayToFile(
            $this->dir . '/g/' . $groupName . '/' . $language, 
            array_map(fn($translation) => serialize($translation), $translations)
        );
    }

    public function saveSingle(string $cacheName, iterable $translations)
    {
        $translations = $this->sortTranslationsByLanguage($translations);

        foreach ($translations as $langId => $translations) {
            Helper::savePHPArrayToFile(
                $this->dir . '/' . $cacheName . '/' . $langId,
                array_map(fn($translation) => serialize($translation), $translations)
            );
        }

    }

    /**
     * Sorts Translations by Language
     *
     * Result Structure
     *  [
     *      // Translations with Language fr-fr
     *      "fr-fr" => [],
     *
     *      // Translations with Language en-en
     *      "en-en" => []
     *  ]
     *
     * @param iterable $translations
     * @return array[]
     */
    private function sortTranslationsByLanguage(iterable $translations): array
    {
        $sort = [];
        foreach ($translations as $key => $translation) {
            $sort[$translation->getLanguage()->getId()][$key] = $translation;
        }

        return $sort;
    }
}