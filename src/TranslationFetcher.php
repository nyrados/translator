<?php

namespace Nyrados\Translator;

use InvalidArgumentException;
use Nyrados\Translator\Cache\FileCache;
use Nyrados\Translator\Cache\Util\RequestCache;
use Nyrados\Translator\Processor\ProcessorInterface;
use Nyrados\Translator\Language\Language;
use Nyrados\Translator\Processor\ProcessorContainer;
use Nyrados\Translator\Provider\ProviderInterface;
use Nyrados\Translator\Translation\Translation;
use Nyrados\Translator\Translation\TranslationSection;
use Nyrados\Translator\Translation\UndefinedStringCollector;
use RuntimeException;

class TranslationFetcher
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

    /** @var RequestCache */
    private $requestCache;
    
    public function __construct(RequestCache $requestCache = null, Language $fallback = null)
    {
        $this->requestCache = $requestCache ?? new RequestCache();
        $this->fallback = $fallback ?? new Language('en');
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
     * Sets Language Preferences
     *
     * @param array $preferences
     * @param boolean $strict
     * @return array
     */
    public function setPreferences(array $preferences): array
    {
        if (empty($preferences)) {
            throw new InvalidArgumentException('Preferences cannot be empty');
        }

        $this->preferences = [];

        $preferences[] = $this->fallback->getId();

        foreach (array_values(array_unique($preferences)) as $language) {
            $this->preferences[] = $language = new Language($language);
            if (
                !in_array($language->getCode(), $preferences) &&
                !in_array($language->withRegion($language->getCode()), $preferences)
            ) {
                $this->preferences[] = $language->withRegion($language->getCode());
            }
        }

        return $this->preferences;
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
        $translations = $this->fetchTranslations([$value], $language);

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
        if (!empty($language)) {
            array_unshift($preferences, new Language($language));
        }

        if ($this->requestCache->has($strings)) {
            return $this->requestCache->get($strings);
        }

        foreach ($preferences as $preference) {
            $result = $this->fetchLanguageTranslations($strings, $preference);
            if (!empty($result)) {
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
        
        if (count($strings) === 1) {
            foreach ($this->provider as $provider) {
                $translations = array_values($provider->getTranslations($language, $strings));

                if (!empty($translations)) {
                    $translations[0]->setLanguage($language);
                    $this->requestCache->setSingle($strings[0], $translations[0]);

                    return [$strings[0] => $translations[0]];
                }
            }

            return [];
        }

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

                $this->requestCache->setGroup($rs);

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
        $container = new ProcessorContainer();

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
