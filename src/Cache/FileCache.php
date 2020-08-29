<?php

namespace Nyrados\Translator\Cache;

use DateInterval;
use DateTime;
use Nyrados\Translator\Config;
use Nyrados\Translator\Helper;
use Nyrados\Translator\Translation\Translation;

class FileCache
{
    /** @var string */
    private $dir;

    /** @var RequestCache */
    private $cache;

    private $expires;
    private $name;
    private $meta = [];
    
    public function __construct(string $name, Config $config)
    {
        $this->dir = $config->getCacheDir();
        $this->name = md5($name);
        $this->cache = $config->getRequestCache();
        $this->expires = (new DateTime())->add($config->getCacheExpireInterval());
        Helper::createDirIfNotExists($this->dir);
    }

    /**
     * Loads Cache into RequestCache
     *
     * @param array $preferences
     * @return void
     */
    public function load(array $preferences): void
    {
        if (!file_exists($this->dir . '/' . $this->name . '/meta.php')) {
            return;
        }

        $this->meta = include $this->dir . '/' . $this->name . '/meta.php';
        if ($this->meta['e'] < (new DateTime())->getTimestamp()) {
            $this->meta = [];
            return;
        }
        
        $this->loadSingle($preferences);
        if (!isset($this->meta['g'])) {
            return;
        }

        foreach ($this->meta['g'] as $group) {
            $this->loadGroup($group, $preferences);
        }
    }

    /**
     * Loads Single Translations into RequestCache
     *
     * @param array $preferences
     * @return void
     */
    public function loadSingle(array $preferences): void
    {
        $neededKeys = $this->meta['k'];

        foreach ($preferences as $preference) {
            $file = $this->dir . '/' . $this->name . '/'  . $preference->getId() . '.php';
            if (!file_exists($file)) {
                continue;
            }

            $data = require $file;
            foreach ($data as $key => $translation) {
                //Skip if key is not needed
                if ($this->cache->has([$key]) && !in_array($key, $neededKeys)) {
                    continue;
                }

                //Remove key from needed key list
                if (($search = array_search($key, $neededKeys)) !== false) {
                    unset($neededKeys[$search]);
                }

                //Set the translation
                $this->cache->set([$key => unserialize($translation)]);

                //Interupt if no other key is needed
                if (empty($neededKeys)) {
                    break 2;
                }
            }
        }
    }

    /**
     * Loads Group into Cache
     *
     * @param string $group
     * @param array $preferences
     * @return void
     */
    public function loadGroup(string $group, array $preferences)
    {
        if (!is_dir($this->dir . '/g/' . $group)) {
            return;
        }

        foreach ($preferences as $preference) {
            $file = $this->dir . '/g/' . $group . '/' . $preference->getId() . '.php';
            if (!file_exists($file)) {
                continue;
            }
            
            $this->cache->set(array_map(function (string $translation): Translation {
                return unserialize($translation);
            }, require $file));
            
            break;
        }
    }

    /**
     * Saves Whole Requestcache
     *
     * @return void
     */
    public function save(): void
    {
        Helper::createDirIfNotExists($this->dir . '/' . $this->name);

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
     * Saves Request Cache on destruct
     */
    public function __destruct()
    {
        $this->save();
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

    /**
     * Saves a Translation Group
     *
     * @param string $name
     * @param Translation[] $translations
     * @return void
     */
    private function saveGroup(string $name, iterable $translations)
    {
        if (empty($translations)) {
            return;
        }

        $rs = [];
        foreach ($translations as $key => $translation) {
            $rs[$key] = serialize($translation);
        }

        Helper::createDirIfNotExists($this->dir . '/g/' . $name);
        $this->saveArray($this->dir . '/g/' . $name . '/' . $translation->getLanguage()->getId(), $rs);
    }

    /**
     * Saves PHP Array as File
     *
     * @param string $file
     * @param array $array
     * @return void
     */
    private function saveArray(string $file, array $array): void
    {
        $fp = fopen($file . '.php', 'w+');
        fwrite($fp, "<?php\nreturn " . var_export($array, true) . ';');
        fclose($fp);
    }
}
