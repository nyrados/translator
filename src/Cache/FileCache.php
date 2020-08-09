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
        $this->name = $name;
        $this->cache = $config->getRequestCache();
        $this->expires = (new DateTime())->add($expires);
        Helper::createDirIfNotExists($dir);
    }


    public function load(array $preferences)
    {
        if(!file_exists($this->dir . '/' . $this->name . '/meta.php')) {
            return;
        }

        $this->meta = include $this->dir . '/' . $this->name . '/meta.php';
        if ($this->meta['e'] < (new DateTime())->getTimestamp()) {
            $this->meta = [];
            return;
        }
        
        $this->loadSingle($preferences);

        if(!isset($this->meta['g'])) {
            return;
        }

        foreach($this->meta['g'] as $group) {
            $this->loadGroup($group, $preferences);
        }

    }

    public function loadSingle(array $preferences) 
    {
        $neededKeys = $this->meta['k'];
        $name = $this->cache->getName();

        foreach ($preferences as $preference) {
            if(!file_exists($this->dir . '/' . $name . '/'  . $preference->getId() . '.php')) {
                continue;
            }

            $data = require $this->dir . '/' . $name . '/' . $preference->getId() . '.php';
            foreach ($data as $key => $translation) {

                //Check if key is needed
                if ($this->cache->has([$key]) && !in_array($key, $neededKeys)) {
                    continue;
                }

                //Remove Key from List
                if (($search = array_search($key, $neededKeys)) !== false) {
                    unset($neededKeys[$search]);
                }

                //Set Translation
                $this->cache->set([$key => unserialize($translation)]);

                if(empty($neededKeys)) {
                    break 2;
                }
            }
        }
    }

    public function loadGroup(string $group, array $preferences)
    {
        if(!is_dir($this->dir . '/g/' . $group)) {
            return;
        }

        foreach ($preferences as $preference) {

            $file = $this->dir . '/g/' . $group . '/' . $preference->getId() . '.php';

            if (!file_exists($file)) {
                continue;
            }
            
            $this->cache->set(array_map(function(string $translation): Translation {

                return unserialize($translation);

            }, require $file));

            break;
        } 

    }

    public function __destruct()
    {
        $this->save($this->cache);
    }




    /** SAVE */

    public function save(RequestCache $cache)
    {
        Helper::createDirIfNotExists($this->dir . '/' . $this->name);

        $checksum = $cache->getChecksum();

        if(isset($this->meta['c']) && $this->meta['c'] == $checksum) {
            return;
        }

        $this->saveArray([
            'e' => $this->expires,
            'c' => $cache->getChecksum(),
            'g' => array_keys($cache->getDependedGroups()),
            'k' => $cache->getKeys(),
        ], $this->dir . '/' . $this->name . '/meta');

        $data = $this->sortTranslationsByLanguageId($cache->getCache()->getMultiple($cache->getKeys()));

        foreach ($data as $language => $translations) {

            $rs = [];
            foreach($translations as $key => $translation) {
                $rs[$key] = serialize($translation);
            }

            $this->saveArray($rs, $this->dir . '/' . $this->name . '/' . $language);
        }


        foreach ($cache->getDependedGroups() as $this->name => $groupCache) {
            $keys = $cache->getKeys($this->name);

            $this->saveGroup($this->name, $groupCache->getMultiple($keys));
        }

    }

    private function sortTranslationsByLanguageId(iterable $translations)
    {
        $sort = [];
        foreach($translations as $key => $translation) {
            $sort[$translation->getLanguage()->getId()][$key] = $translation; 
        }

        return $sort;
    }

    private function saveGroup(string $name, iterable $translations)
    {
        if(empty($translations)) {
            return;
        }

        $rs = [];
        foreach($translations as $key => $translation) {
            $rs[$key] = serialize($translation);
        }

        Helper::createDirIfNotExists($this->dir . '/g/' . $name);

        $this->saveArray($rs,  $this->dir . '/g/' . $name . '/' . $translation->getLanguage()->getId());
    }

    private function saveArray(array $array, string $file)
    {
        $fp = fopen($file . '.php', 'w+');
        fwrite($fp, "<?php\nreturn " . var_export($array, true) . ';');
        fclose($fp);
    }



}