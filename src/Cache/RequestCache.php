<?php

namespace Nyrados\Translator\Cache;

use Nyrados\Translator\Helper;
use Nyrados\Translator\Translation\Translation;
use Psr\SimpleCache\CacheInterface;

class RequestCache
{
    /** @var Translation[] */
    protected $storage = [];

    /** @var CacheInterface */
    private $source;

    /** @var CacheInterface */
    private $single;

    /** @var CacheInterface[] */
    private $groups = [];

    /** @var string[] */
    private $singleKeys = [];
    
    /** @var array<string, array> */
    private $groupKeys = [];


    public function __construct()
    {
        $this->source = new ArrayCache();
        $this->single = new ArrayCache();
    }

    /**
     * Check depending on the length of the given array if the RequestCache contains a
     * single Translation or a Group
     *
     * @param array $keys
     * @return boolean
     */
    public function has(array $keys)
    {
        return (count($keys) === 1 && $this->single->has($keys[0])) || isset($this->groups[Helper::getChecksum($keys)]);
    }

    /**
     * Sets Translations into Cache
     *
     * @param array $translations
     * @return void
     */
    public function set(array $translations)
    {
        if (count($translations) === 1) {
            foreach ($translations as $key => $translation) {
                if (!in_array($key, $this->singleKeys)) {
                    $this->singleKeys[] = $key;
                }
                
                $this->single->set($key, $translation);
            }

            return;
        }

        $group = clone $this->source;
        $name = Helper::getChecksum(array_keys($translations));
        foreach ($translations as $key => $translation) {
            $this->groupKeys[$name][] = $key;
            $group->set($key, $translation);
        }


        $this->groups[$name] = $group;
    }

    /**
     * Returns Translations
     *
     * @param array $keys
     * @return array<Translation>
     */
    public function get(array $keys): ?array
    {
        if (count($keys) === 1) {
            return [
                $keys[0] => $this->single->get($keys[0])
            ];
        }

        $name = Helper::getChecksum($keys);
        if (!isset($this->groups[$name])) {
            return null;
        }

        return $this->groups[$name]->getMultiple($keys);
    }

    /**
     * Return Depended Groups
     *
     * @return array<CacheInterface>
     */
    public function getDependedGroups(): array
    {
        return $this->groups;
    }

    /**
     * Returns Single Cache
     *
     * @return CacheInterface
     */
    public function getSingleCache(): CacheInterface
    {
        return $this->single;
    }

    public function getKeys(string $group = null): iterable
    {
        if ($group === null) {
            return $this->singleKeys;
        }

        if (isset($this->groupKeys[$group])) {
            return $this->groupKeys[$group];
        }

        return [];
    }

    /**
     * Returns Checksum to represent the used translations.
     *
     * @return void
     */
    public function getChecksum(): string
    {
        return Helper::getChecksum([
            Helper::getChecksum($this->singleKeys),
            Helper::getChecksum(array_keys($this->groupKeys))
        ]);
    }
}
