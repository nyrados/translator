<?php

namespace Nyrados\Translator;

use InvalidArgumentException;
use Nyrados\Translator\Cache\Cache;
use Nyrados\Translator\Cache\CacheFactory;
use Nyrados\Translator\Cache\CacheManagerAdapter;
use Nyrados\Translator\Cache\Manager\FileCache;
use Nyrados\Translator\Cache\Util\RequestCache;
use Nyrados\Translator\Language\Language;
use Nyrados\Translator\Processor\ProcessorContainer;
use Nyrados\Translator\Provider\ProviderInterface;
use PHPUnit\TextUI\Help;

class Translator
{
    public const
        PARSER = '/^(?<code>[a-z]{2,})(-(?<region>[a-z]{2,}))?$/',
        TRANSLATION_STRING_SEPARATOR = '_',
        TRANSLATION_STRING_SEPARATE_DEPTH = 1,
        CACHE_MAP = [
            'file' => FileCache::class
        ];

    /** @var TranslationFetcher */
    private $fetcher;

    /** @var Cache */
    private $cache;

    /** @var RequestCache */
    private $requestCache;
    
    /** @var string[] */
    private $preferences;

    private $cacheName = null;

    /**
     * Construct a new TranslatorApi
     *
     * @param array $config Assoc array with mixed config values
     *
     */
    public function __construct(array $options = [])
    {
        $this->requestCache = new RequestCache();
        $this->fetcher = new TranslationFetcher($this->requestCache);

        $this->preferences = $this->fetcher->setPreferences(
            !isset($options['preferences'])
                ? Helper::preferencesFromAcceptLanguage()
                : (is_array($options['preferences'])
                    ? $options['preferences']
                    : ['en-en']
                )
        );
    }

    /**
     * Adds Translation Provider
     *
     * @param ProviderInterface $provider
     * @param integer $priority
     * @return void
     */
    public function addProvider(ProviderInterface $provider): void
    {
        $this->fetcher->addProvider($provider);
    }

    /**
     * Translates by checking if $value is needed to be translated multiple or single
     *
     * @param array|string $value
     * @param array $context
     * @param string $language
     *
     * @return TranslationSection|string|null
     */
    public function translate($value, array $context = [], string $language = '')
    {
        if (is_array($value)) {
            return $this->fetcher->multiple($value, $language);
        }
        
        if (is_string($value) || ( is_object($value) && method_exists($value, '__toString'))) {
            return $this->fetcher->single((string) $value, $context, $language);
        }

        throw new InvalidArgumentException();
    }


    /** CACHING */

    public function enableCache(array $options = []): CacheManagerAdapter
    {
        return $this->cache = CacheFactory::create(
            $this->requestCache,
            $this->preferences,
            $options
        )
        ;
    }

    public function loadCache(string $name): void
    {   
        if ($this->cache instanceof CacheManagerAdapter) {
            $this->cacheName = $name;
            $this->cache->load($name);
        }
    }

    public function __destruct()
    {
        if ($this->cacheName !== null) {
            $this->cache->save($this->cacheName);
        }
    }
}
