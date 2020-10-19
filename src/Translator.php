<?php

namespace Nyrados\Translator;

use InvalidArgumentException;
use Nyrados\Translator\Cache\Cache;
use Nyrados\Translator\Cache\CacheFactory;
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

    private $default;

    public function __construct(array $options = [])
    {
        $this->requestCache = new RequestCache();
        $this->fetcher = new TranslationFetcher($this->requestCache);
        $this->default = new Language('en');

        $this->fetcher->setPreferences($this->preferences = 
            Helper::iterableToArray($this->convertPreferences(
                !isset($options['preferences'])
                    ? Helper::preferencesFromAcceptLanguage()
                    : (is_array($options['preferences'])
                        ? $options['preferences']
                        : ['en-en']
                    )    
            )
        ));
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
    public function translate($value, array $context= [], string $language = '')
    {
        if (is_array($value)) {
            return $this->fetcher->multiple($value, $language);
        }
        
        if (is_string($value) || ( is_object($value) && method_exists($value, '__toString'))) {
            return $this->fetcher->single((string) $value, $context, $language);
        }

        throw new InvalidArgumentException();
    }

    /**
     * Sets Language Preferences
     *
     * @param array $preferences
     * @param boolean $strict
     * @return void
     */
   private function convertPreferences(array $preferences): iterable
    {
        if (empty($preferences)) {
            throw new InvalidArgumentException('Preferences cannot be empty');
        }

        $preferences[] = $this->default->getId();

        foreach (array_values(array_unique($preferences)) as $language) {
            yield $language = new Language($language);
            if (
                !in_array($language->getCode(), $preferences) &&
                !in_array($language->withRegion($language->getCode()), $preferences)
            ) {
                yield $language->withRegion($language->getCode());
            }
        }
    }


    /** CACHING */

    public function enableCache(array $options = []): Cache
    {
        return $this->cache = CacheFactory::create(
            $this->requestCache, 
            $this->preferences, 
            $options)
        ;
    }

    public function loadCache(string $name): void
    {
        
        if ($this->cache instanceof Cache) {
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