<?php

namespace Nyrados\Translator\Cache;

use DateInterval;
use InvalidArgumentException;
use Nyrados\Translator\Cache\Manager\CacheManagerInterface;
use Nyrados\Translator\Cache\Manager\FileCache;
use Nyrados\Translator\Cache\Util\RequestCache;

class CacheFactory
{

    public const MAP_TYPE = [
        'file' => [self::class, 'createFileTypeManager']
    ];

    public static function create(RequestCache $cache, array $preferences, array $options = [])
    {
        $default = [
            'type' => 'file',
            'expires' => new DateInterval('PT1H'),
        ];

        $options = array_merge($default, $options);
        $manager = self::getManager($options);

        $cache = new Cache($cache, $preferences);
        $cache->setExpires($default['expires']);
        $cache->setManager($manager);

        return $cache;
    }

    private static function getManager(array $options): CacheManagerInterface
    {
        if ($options['type'] instanceof CacheManagerInterface) {
            return $options['type'];
        }

        if (is_string($options['type']) && isset(self::MAP_TYPE[$options['type']])) {
            return self::MAP_TYPE[$options['type']]($options);
        }

        throw new InvalidArgumentException('Invalid type');
    }


    private static function createFileTypeManager(array $options): CacheManagerInterface
    {
        if (isset($options['dir'])) {
            return new FileCache($options['dir']);
        }

        return new FileCache();
    }
}