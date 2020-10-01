<?php

use Nyrados\Translator\Helper;
use Nyrados\Translator\Provider\ArrayProvider;
use Nyrados\Translator\TranslatorApi;

require "./vendor/autoload.php";

$data = new ArrayProvider();
$data->set('en', [
    'greet' => 'Hello {name}!',
    'article_header' => 'A meaningful title',
    'article' => 'A very interesting article',
]);

$data->set('de', [
    'greet' => 'Hello {name}!',
    'article_header' => 'Eine aussagekräftige Überschrift',
    'article' => 'Ein interessanter Artikel'
]);

$t = new TranslatorApi();
$t->addProvider($data);
$t->setPreferences(Helper::preferencesFromAcceptLanguage());

$t->translate('greet', ['name' => 'World']);