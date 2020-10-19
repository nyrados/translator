<?php

namespace Nyrados\Translator\Cache\Manager;

use Nyrados\Translator\Language\Language;
use Nyrados\Translator\Cache\Util\Meta;

interface CacheManagerInterface
{
    public function loadMeta(string $cacheName): ?Meta;
    
    public function loadSingle(string $cacheName, Language $preference): iterable;

    public function loadGroup(string $groupName, Language $preference): iterable;


    public function saveMeta(string $cacheName, Meta $meta);

    public function saveSingle(string $cacheName, iterable $translations);

    public function saveGroup(string $groupName, iterable $translations);
}
