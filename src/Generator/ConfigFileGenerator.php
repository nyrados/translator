<?php

namespace Nyrados\Translator\Generator;

use InvalidArgumentException;
use Nyrados\Translator\TranslatorApi;

class ConfigFileGenerator
{

    /** @var TranslatorApi */
    private $translator;

    private $dir;

    public function __construct(TranslatorApi $translator, string $dir)
    {
        $this->translator = $translator;
        $this->dir = $dir;

        if(!is_dir($this->dir)) {
            mkdir($this->dir, 0777, true);
        }
    }

    public function saveJson(string $name, bool $comments = true)
    {
        $strings = $this->translator->getUndefinedStrings();
        
        $lines = []; 
        foreach ($strings as $string => $context) {

            $context = $strings->getContext($string);
            if ($comments && !empty($context)) {
                $lines[] = "\n" . '    " // ' . $this->generateContextComment($context) . '": null';
            }

            $lines[] = '    "' . $string . '": null';
        }

      
        //var_dump("{\n" . implode(",\n", $lines) . "\n}");
    }

    private function save(string $name, string $extension, string $content)
    {
        $file = fopen($this->dir . '/' . $name . '.' . $this->translator->getFallbackLanguage()->getId() . $extension, 'w+');
        fwrite($file, $content);
        fclose($file);
    }

    private function generateContextComment(array $context)
    {
        $context = array_map(function(string $name) {
            return '{' . $name . '}';
        }, array_keys($context));
        return 'Defined context vars: ' . implode(', ', $context);
    }


}