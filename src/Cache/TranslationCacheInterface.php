<?php

namespace Nyrados\Translator\Cache;

use Nyrados\Translator\Language\Language;

interface TranslationCacheInterface
{

    public function loadMeta(string $cacheName): ?Meta;
    
    public function loadSingle(string $cacheName, Language $preference): iterable;

    public function loadGroup(string $groupName, Language $preference): iterable;


    public function saveMeta(string $cacheName, Meta $meta);

    public function saveSingle(string $cacheName, iterable $translations);

    public function saveGroup(string $groupName, iterable $translations);

}