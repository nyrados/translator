<?php
namespace Nyrados\Translator\Cache;

use ArrayIterator;
use DateInterval;
use IteratorAggregate;
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

    public function has(array $keys) 
    {
        return (count($keys) === 1 && $this->single->has($keys[0])) || isset($this->groups[Helper::getChecksum($keys)]);
    }

    public function set(array $translations)
    {
        if(count($translations) === 1) {
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

    public function get(array $keys)
    {
        if (count($keys) === 1) {
            return [
                $keys[0] => $this->single->get($keys[0])
            ];
        }

        $name = Helper::getChecksum($keys);

        if (!isset($this->groups[$name])) {
            return;
        }

        return $this->groups[$name]->getMultiple($keys);
    }

    /**
     * Return Depended Groups
     *
     * @return CacheInterface[]
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
    public function getCache(): CacheInterface
    {
        return $this->single;
    }

    public function getKeys(string $name = null): iterable
    {
        if($name === null) {
            return $this->singleKeys;
        }

        if(isset($this->groupKeys[$name])) {
            return $this->groupKeys[$name];
        }

        return [];
    }

    public function getName()
    {
        return 'default';
    }

    public function getChecksum()
    {
        return Helper::getChecksum([Helper::getChecksum($this->singleKeys), Helper::getChecksum(array_keys($this->groupKeys))]);
    }

}