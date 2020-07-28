<?php
namespace Nyrados\Translator;

use InvalidArgumentException;
use Nyrados\Translator\Language\Language;
use Nyrados\Translator\Provider\ProviderInterface;
use Nyrados\Translator\Language\LanguageDecorator;
use Nyrados\Translator\Language\LanguageInterface;
use Nyrados\Translator\Language\SimpleLanguage;
use Nyrados\Translator\Processor\ProcessorContainer;
use Nyrados\Translator\Provider\CollectorProvider;
use Nyrados\Translator\Translation\Context\TranslationContext;
use Nyrados\Translator\Translation\Translation;
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

    /** @var ContainerInterface */
    private $processorContainer;

    /** @var CollectorProvider */
    private $collector;

    public function __construct()
    {
        $this->processorContainer = new ProcessorContainer();
        $this->addProvider($this->collector = new CollectorProvider());
    }   

    public function addProvider(ProviderInterface $provider, int $priority = 100)
    {
        while (isset($this->provider[$priority])) {
            $priority++;
        }

        $this->provider[$priority] = $provider;
    }

    public function setPreferences(array $preferences, bool $strict = false)
    {
        if (empty($preferences)) {
            throw new InvalidArgumentException('Preferences cannot be empty');
        }

        $this->preferences = [];

        foreach (array_values(array_unique($preferences)) as $language) {
            
            $language = new Language($language);

            $this->preferences[] = $language;

            if (
                !$strict &&
                !in_array($language->getCountry(), $preferences) &&
                !in_array($language->withRegion($language->getCountry()) , $preferences) 
            ) { 
                $this->preferences[] = $language->withRegion($language->getCountry());
            }
        }
    }

    public function translate(string $string, array $context = [], string $language = ''): ?string
    {
        $preferences = $this->preferences;
        if (!empty($language)) {
            array_unshift($preferences, new Language($language));
        }

        krsort($this->provider);

        foreach ($preferences as $preference) {
            foreach ($this->provider as $provider) {
                $translation = $provider->getTranslation($preference, $string);
                if ($translation instanceof Translation) {
                    $context = new TranslationContext($context, $preference);

                    $this->collector->addEntry($preference, $string, $translation);

                    return $this->processTranslation($translation, $context);
                }
            }
        }

        return null;
    }

    private function processTranslation(Translation $translation, TranslationContext $context): string
    {
        $result = (string) $translation;

        foreach ($translation->getProcessor() as $processorName) {
            if (!$this->processorContainer->has($processorName)) {
                throw new RuntimeException(sprintf("Invalid Translation Processor '%s' ", $processorName));
            }

            /** @var ProcessorInterface */
            $processor = $this->processorContainer->get($processorName);
            $result = $processor->process($result, $context);
        }

        return $result;
    }

    public function saveCache()
    {
        var_dump ($this->collector->getPool());
    }
}