<?php
namespace Nyrados\Translator\Cache;

use ArrayIterator;
use DateInterval;
use DateTime;
use IteratorAggregate;
use Nyrados\Translator\Translation\Translation;

class RequestCache extends ArrayCache
{
    /** @var Translation[] */
    protected $storage = [];

    /** @var DateInterval */
    private $interval;

    private $name;
    private $dir;

    /** @var PHPCacheFile */
    private $cache;

    public function __construct(DateInterval $interval, string $cacheDir)
    {
        $this->dir = $cacheDir;
        $this->interval =  $interval;

        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0777, true);
        }
    }

    public function load(string $name, array $preferences = []): void
    {
        $this->name = $name;

        $this->cache = new CacheGroup($this->dir, $this->name);
        $this->cache->applyCache($this, $preferences);
    }

    public function isLoaded(): bool
    {
        return $this->cache instanceof CacheGroup;
    }

    public function __destruct()
    {
        if ($this->isLoaded()) {
            
            $this->cache->saveCache($this, array_keys($this->storage), $this->interval);
        }
    }

    public static function getKeyChecksum(array $keys)
    {
        return md5(implode('.', $keys));
    }
}