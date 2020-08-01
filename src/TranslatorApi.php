<?php
namespace Nyrados\Translator;

use DateInterval;
use InvalidArgumentException;
use Nyrados\Translator\Cache\ArrayCache;
use Nyrados\Translator\Cache\CacheItem;
use Nyrados\Translator\Cache\IterableCache;
use Nyrados\Translator\Cache\RequestCacheInterface;
use Nyrados\Translator\Cache\MemoryCache;
use Nyrados\Translator\Cache\RequestCache;
use Nyrados\Translator\Cache\RequestCacheSaver;
use Nyrados\Translator\Language\Language;
use Nyrados\Translator\Provider\ProviderInterface;
use Nyrados\Translator\Translation\Translation;
use Nyrados\Translator\Translation\TranslationSection;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Traversable;

class TranslatorApi
{
    public const 
        PARSER = '/^(?<country>[a-z]{2,})(-(?<region>[a-z]{2,}))?$/',
        TRANSLATION_STRING_SEPARATOR = '_',
        TRANSLATION_STRING_SEPARATE_DEPTH = 1
    ;

    /** @var ProviderInterface[] */
    private $provider = [];

    /** @var Language[] */
    private $preferences;

    /** @var Language */
    private $fallback;

    /** @var Config */
    public $config = [];

    /**
     * Construct a new TranslatorApi
     * 
     * @param array $config Assoc array with mixed config values
     * 
     * Aviable Options:
     * 
     * Caching:
     * --------------
     * The cache saves the translation results from the translation providers as file.
     * So the translator has not to look up each time which translation provider is required
     * and which is the suitable language 
     * 
     * * cache (bool):  
     *      Activate or deactivate caching.
     *      Its not recommended for an dev environment.
     *      Default: false
     * 
     * * cache_dir (string):     
     *      An absolute path to a cache directory.
     *      If the directory does not exists it will be created.
     *      Default: subfolder with name translator-md5(__DIR__) in sys_get_temp_dir()
     * 
     * * cache_interval (DateInterval):
     *      A Dateinterval that describes how long the cache is stored.
     *      Default: 1 hour
     * 
     * Other:
     * --------------
     * * processor_container (Psr\Container\ContainerInterface)
     *      A container that provides values of Nyrados\Translator\Processor\ProcessorInterface
     *      Default: Nyrados\Translator\Processor\ProcessorContainer
     */
    public function __construct(array $config = [])
    {
        $this->config = new Config($config);
        $this->fallback = new Language('en');
        $this->preferences = [$this->fallback];
        
    }   

    /**
     * Adds Translation Provider
     *
     * @param ProviderInterface $provider
     * @param integer $priority
     * @return void
     */
    public function addProvider(ProviderInterface $provider, int $priority = 100): void
    {
        while (isset($this->provider[$priority])) {
            $priority++;
        }

        $this->provider[$priority] = $provider;
    }

    /**
     * Sets Cache Name if cache is enabled
     * 
     * For details look at the options.
     *
     * @param string $name
     * @return void
     */
    public function setCacheName(string $name): void
    {
        if($this->config->isCacheActive()) {
            $this->config->getRequestCache()->load($name, $this->preferences);
        }
    }

    /**
     * Sets Language Preferences
     *
     * @param array $preferences
     * @param boolean $strict
     * @return void
     */
    public function setPreferences(array $preferences): void
    {
        if (empty($preferences)) {
            throw new InvalidArgumentException('Preferences cannot be empty');
        }

        $this->preferences = [];

        foreach (array_values(array_unique($preferences)) as $language) {
            
            $language = new Language($language);

            $this->preferences[] = $language;

            if (
                !in_array($language->getCountry(), $preferences) &&
                !in_array($language->withRegion($language->getCountry()) , $preferences) 
            ) { 
                $this->preferences[] = $language->withRegion($language->getCountry());
            }
        }

        $this->preferences[] = $this->fallback;
    }

    /**
     * Set Fallback Language
     *
     * @param string|Language $language
     * @return void
     */
    public function setFallback($language): void
    {
        $this->fallback = new Language($language);
    }

    /**
     * Translates by checking if $value is needed to be translated multiple or single
     *
     * @param array|string $value
     * @param array $context
     * @param string $language
     * 
     * @return TranslationSection|string|null|
     */
    public function translate($value, array $context = [], string $language = '')
    {
        if (is_array($value)) {
            return $this->multiple($value, $language);
        }
        
        if (is_string($value) || ( is_object($value) && method_exists($value, '__toString'))) {
            return $this->single((string) $value, $context, $language);
        }

        throw new InvalidArgumentException();
    }

    /**
     * Translates a single translation string
     *
     * If the translation string is unaviable then null will be returned.
     * 
     * @param string $value The translation string name 
     * @param array $context Optional context
     * @param string $language Optional preffered language
     * @return string|null
     */
    public function single(string $value, array $context = [], string $language = ''): ?string
    {
        $translations = $this->fetchTranslations(is_string($value) ? [$value] : $value, $language);
        if (empty($translations)) {
            return null;
        }

        return $this->processTranslation($translations[$value], $context);
    }

    /**
     * Translates multiple translation strings
     *
     * @param string[] $value
     * @param string $language
     * @return TranslationSection|null
     */
    public function multiple(array $value, string $language = ''): ?TranslationSection
    {
        return new TranslationSection($this, $value, $language);
    }

    /**
     * Fetches unprocessed Translations Instances
     *
     * @param array $strings
     * @param string $language
     * @return Translation[]
     */
    public function fetchTranslations(array $strings, string $language = ''): array
    {
        $preferences = $this->preferences;
        $requestCache = $this->config->getRequestCache();
        if (!empty($language)) {
            array_unshift($preferences, new Language($language));
        }

        $name = implode('.', $strings);

        if ($requestCache->has($name)) {
            $data = $requestCache->get($name);

            if ($data instanceof Translation) {
                return [$strings[0] => $data];
            }

            return $data;
        }

        foreach ($preferences as $preference) {
            foreach ($this->provider as $provider) {
                $translations = $provider->getTranslations($preference, $strings);

                if (!empty($translations)) {

                    $i = 0;
                    $rs = [];
                    foreach ($translations as $translation) {
                        $translation->setLanguage($preference);
                        $rs[$strings[$i]] = $translation; 
                        $i++;
                    }

                    $this->config->getRequestCache()->set($name, 
                        $i === 1 ? $rs[$strings[0]] : $rs
                    );

                    return $rs;

                }
            }
        }

        return [];
    }

    /**
     * Processes Translation
     *
     * @param Translation $translation
     * @param array $context
     * @return string
     */
    public function processTranslation(Translation $translation, array $context = []): string
    {
        $result = (string) $translation;
        $container = $this->config->getProcessorContainer();

        foreach ($translation->getProcessor() as $processorName) {
            if (!$container->has($processorName)) {
                throw new RuntimeException(sprintf("Invalid Translation Processor '%s' ", $processorName));
            }

            /** @var ProcessorInterface */
            $processor = $container->get($processorName);
            $result = $processor->process($result, $context);
        }

        return $result;
    }
}