<?php

namespace Nyrados\Translator\Cache;

use DateInterval;
use Nyrados\Translator\Cache\Util\Meta;
use Nyrados\Translator\Cache\Manager\CacheManagerInterface;
use Nyrados\Translator\Cache\Manager\FileCache;
use Nyrados\Translator\Cache\Util\RequestCache;
use Nyrados\Translator\Helper;

class CacheManagerAdapter
{

    public const DEFAULT_INTERVAL = 'PT1H';

    /** @var RequestCache */
    private $requestCache;

    /** @var CacheManagerInterface */
    private $cache;

    /** @var Meta|null */
    private $loadedMeta;

    /** @var string */
    private $name;

    /** @var DateInterval */
    private $expires;

    private $preferences;

    public function __construct(RequestCache $cache, array $preferences)
    {
        $this->cache = new FileCache();
        $this->requestCache = $cache;
        $this->preferences = $preferences;
        $this->expires = new DateInterval(self::DEFAULT_INTERVAL);
    }

    public function setManager(CacheManagerInterface $manager)
    {
        $this->cache = $manager;
    }

    public function setExpires(DateInterval $expires)
    {
        $this->expires = $expires;
    }

    public function load(string $cacheName): void
    {
        $this->name = $cacheName;
        $cache = $this->cache;
        $meta = $cache->loadMeta($cacheName);

        //Not load if no meta is aviable or is expired
        if ($meta === null || $meta->isExpired()) {
            return;
        }

        $this->loadedMeta = $meta;

        $this->loadGroups();
        $this->loadSingleTranslations($cacheName);
    }

    private function loadGroups(): void
    {
        foreach ($this->loadedMeta->getGroups() as $groupName) {
            foreach ($this->preferences as $preference) {
                $loaded = $this->cache->loadGroup($groupName, $preference);

                if (!empty($loaded)) {
                    $this->requestCache->set(Helper::iterableToArray($loaded));
                    break;
                }
            }
        }
    }

    private function loadSingleTranslations(string $cacheName): void
    {
        $neededKeys = $this->loadedMeta->getSingleKeys();

        if (empty($neededKeys)) {
            return;
        }
        
        foreach ($this->preferences as $preference) {
            foreach ($this->cache->loadSingle($cacheName, $preference) as $key => $translation) {
                //Skip if key is already in requestCache
                if ($this->requestCache->has([$key])) {
                    continue;
                }

                //Remove key from needed key list
                if (($search = array_search($key, $neededKeys)) !== false) {
                    unset($neededKeys[$search]);
                }

                //Set Translation
                $this->requestCache->set([$key => $translation]);

                if (empty($neededKeys)) {
                    return;
                }
            }
        }
    }

    public function save(string $name = null)
    {
        $name = $name ?? $this->name;

        //Save new meta, if no meta is loaded or if meta checksum is different
        if (
            !$this->loadedMeta instanceof Meta ||
            !$this->loadedMeta->containsSame($this->requestCache->getChecksum())
        ) {
            $this->cache->saveMeta($name, Meta::fromRequestCache(
                $this->requestCache,
                $this->expires
            ));

        //If meta checksum equals stop saving
        } elseif ($this->loadedMeta->containsSame($this->requestCache->getChecksum())) {
            return;
        }

        $this->cache->saveSingle(
            $name,
            $this->requestCache->getSingleCache()->getMultiple(
                $this->requestCache->getKeys()
            )
        );

        foreach ($this->requestCache->getDependedGroups() as $groupName => $groupCache) {
            $this->cache->saveGroup($groupName, $groupCache->getMultiple(
                $this->requestCache->getKeys($groupName)
            ));
        }
    }
}
