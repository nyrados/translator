<?php
namespace Nyrados\Translator;

use InvalidArgumentException;
use Nyrados\Translator\Cache\FileCache;
use Nyrados\Translator\Processor\ProcessorInterface;
use Nyrados\Translator\Language\Language;
use Nyrados\Translator\Provider\ProviderInterface;
use Nyrados\Translator\Translation\Translation;
use Nyrados\Translator\Translation\TranslationSection;
use Nyrados\Translator\Translation\UndefinedStringCollector;
use RuntimeException;

class TranslatorApi
{
    public const 
        PARSER = '/^(?<code>[a-z]{2,})(-(?<region>[a-z]{2,}))?$/',
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

    /** @var UndefinedStringCollector */
    private $undefined;

    /** @var string */
    private $name = 'default';

    /** @var FileCache */
    private $cache;

    /**
     * Construct a new TranslatorApi
     * 
     * @param array $config Assoc array with mixed config values
     * 
     * ## Aviable Options:
     * 
     * ### Caching:
     * 
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
     * ### Other:
     * 
     * * processor_container (Psr\Container\ContainerInterface)
     *      A container that provides values of Nyrados\Translator\Processor\ProcessorInterface
     *      Default: Nyrados\Translator\Processor\ProcessorContainer
     */
    public function __construct(array $config = [])
    {
        $this->config = new Config($config);
        $this->fallback = new Language('en');
        $this->preferences = [$this->fallback];
        $this->undefined = new UndefinedStringCollector();
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
    public function setName(string $name): void
    {
        $this->name = $name;

        if($this->config->isCacheActive()) {
            $this->cache = new FileCache($name, $this->config);
            $this->cache->load($this->preferences);            
        }
    }

    public function getName(): string
    {
        return $this->name;
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
                !in_array($language->getCode(), $preferences) &&
                !in_array($language->withRegion($language->getCode()) , $preferences) 
            ) { 
                $this->preferences[] = $language->withRegion($language->getCode());
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

        if(empty($translations)) {
            $this->undefined->set($value, $context);
        }

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
    public function fetchTranslations(array $strings, string $language = '')
    {
        $preferences = $this->preferences;
        $requestCache = $this->config->getRequestCache();
        if (!empty($language)) {
            array_unshift($preferences, new Language($language));
        }

        if ($requestCache->has($strings)) {
            return $requestCache->get($strings);
        }

        foreach ($preferences as $preference) {
            $result = $this->fetchLanguageTranslations($strings, $preference);
            if(!empty($result)) {
                return $result;
            }
        }

        return [];
    }

    /**
     * Fetch Translation for Language
     *
     * @param array $strings
     * @param string|Language $language
     * @return array
     */
    public function fetchLanguageTranslations(array $strings, $language): array
    {
        $language = new Language($language);

        foreach ($this->provider as $provider) {
            $translations = $provider->getTranslations($language, $strings);

            if (!empty($translations)) {

                $i = 0;
                $rs = [];
                foreach ($translations as $translation) {
                    $translation->setLanguage($language);
                    $rs[$strings[$i]] = $translation; 
                    $i++;
                }

                $this->config->getRequestCache()->set($rs);

                return $rs;
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

    /**
     * Returns Fallback Language
     *
     * @return Language
     */
    public function getFallbackLanguage(): Language
    {
        return $this->fallback;
    }

    /**
     * Returns Undefinded String Collector
     *
     * @return UndefinedStringCollector
     */
    public function getUndefinedStrings(): UndefinedStringCollector
    {
        return $this->undefined;
    }
}