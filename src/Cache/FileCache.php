<?php

namespace Nyrados\Translator\Cache;

use DateInterval;
use DateTime;
use Nyrados\Translator\Config;
use Nyrados\Translator\Helper;
use Nyrados\Translator\Translation\Translation;

class FileCache
{

    public function __construct(string $dir = __DIR__ . '/../../cache')
    {
        $this->dir = $dir;
    }

    public function save(string $name, RequestCache $cache)
    {
        Helper::createDirIfNotExists($this->dir . '/' . $name);

        if (!$this->saveMetaFile()) {
            return;
        }

        $data = $this->sortTranslationsByLanguageId($this->cache->getCache()->getMultiple($this->cache->getKeys()));
        foreach ($data as $language => $translations) {
            $rs = [];
            foreach ($translations as $key => $translation) {
                $rs[$key] = serialize($translation);
            }

            $this->saveArray($this->dir . '/' . $this->name . '/' . $language, $rs);
        }


        foreach ($this->cache->getDependedGroups() as $this->name => $groupCache) {
            $keys = $this->cache->getKeys($this->name);
            $this->saveGroup($this->name, $groupCache->getMultiple($keys));
        }
    }

    /**
     * Saves Meta File if needed
     *
     * @return bool true if cache needs to be updated
     */
    private function saveMetaFile(): bool
    {
        $checksum = $this->cache->getChecksum();
        if (isset($this->meta['c']) && $this->meta['c'] == $checksum) {
            return false;
        }

        $this->saveArray($this->dir . '/' . $this->name . '/meta', [
            'e' => $this->expires->getTimestamp(),
            'c' => $this->cache->getChecksum(),
            'g' => array_keys($this->cache->getDependedGroups()),
            'k' => $this->cache->getKeys(),
        ]);

        return true;
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
    private function sortTranslationsByLanguageId(iterable $translations): array
    {
        $sort = [];
        foreach ($translations as $key => $translation) {
            $sort[$translation->getLanguage()->getId()][$key] = $translation;
        }

        return $sort;
    }
}
