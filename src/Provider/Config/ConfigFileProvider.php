<?php

namespace Nyrados\Translator\Provider\Config;

use InvalidArgumentException;
use LogicException;
use Nyrados\Translator\Helper;
use Nyrados\Translator\Language\Language;
use Nyrados\Translator\Provider\ArrayProvider;
use Nyrados\Translator\TranslatorApi;
use Symfony\Component\Yaml\Yaml;

class ConfigFileProvider extends ArrayProvider
{
    /** @var ConfigFileConverterInterface[] */
    private $converter = [];
    private $dir = '';
    public function __construct(string $dir)
    {
        Helper::createDirIfNotExists($dir);
        $this->dir = $dir;
        $this->setConfigFileConverter('json', new JsonConverter());
        if (class_exists(Yaml::class)) {
            $this->setConfigFileConverter('yml', new YamlConverter());
        }
    }

    public function getTranslations(Language $language, array $strings): array
    {
        $translations = parent::getTranslations($language, $strings);
        if (!empty($translations)) {
            return $translations;
        }

        foreach (scandir($this->dir) as $file) {
            $file = $this->dir . '/' . $file;
            if (is_dir($file)) {
                continue;
            }

            // format: [prefix].[lang].[extension]
            $split = explode('.', $file);
            $extension = array_pop($split);
            if (!isset($this->converter[$extension])) {
                continue;
            }


            $this->set(array_pop($split), $this->converter[$extension]->convert($file));
        }

        return parent::getTranslations($language, $strings);
    }

    public function setConfigFileConverter(string $extension, ConfigFileConverterInterface $converter)
    {
        $this->converter[$extension] = $converter;
    }

    public function saveMissing(TranslatorApi $translator, string $format, string $name = '')
    {
        if (!isset($this->converter[$format])) {
            throw new InvalidArgumentException('Invalid Format "' . $format . "'");
        }

        $file = (empty($name) ? $translator->getName() : $name) . '.'
                . $translator->getFallbackLanguage()->getId() . '.' . $format
        ;
        $this->converter[$format]->saveMissing($this->dir . '/' . $file, $translator->getUndefinedStrings());
    }

    public static function generateContextComment(array $context)
    {
        $context = array_map(function (string $name) {

            return '{' . $name . '}';
        }, array_keys($context));
        return 'Defined context vars: ' . implode(', ', $context);
    }
}
