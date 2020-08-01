<?php
namespace Nyrados\Translation;

use Nyrados\Translator\Helper;
use Nyrados\Translator\Provider\ArrayProvider;
use Nyrados\Translator\Provider\Config\ConfigFileProvider;
use Nyrados\Translator\TranslatorApi;

require 'vendor/autoload.php';

$source = new ArrayProvider();
$config = new ConfigFileProvider(__DIR__ . '/config');

$source->set('de', [
    'greet' => 'Hallo {name}!',
]);

$translator = new TranslatorApi([
    'cache' =>  true,
    'cache_dir' => __DIR__ . '/cache',
]);

$translator->setCacheName('abs');
$translator->setPreferences(Helper::preferencesFromAcceptLanguage());

$translator->addProvider($source);
$translator->addProvider($config);

echo $translator->translate('greet');

$section = $translator->translate(['greet', 'goodbye']);

echo $section->get(['name' => 'Foo']) . ' ' . $section->get();
