# Translator
Translation API to localize web applications

## General Setup
In this example we will create an instance of `Nyrados\Translator\TranslatorApi` and set example language preferences. The TranslatorApi will look for translations in these order, including the parent language (`en-en` is the parent language of `en-us`). 
In this context it will first look for austrian german, then for regular german, then for american english and finally for british english.

A provider is an instance of `Nyrados\Translator\Provider\ProviderInterface` it provides translations for a language strings and a given language. 

```php
<?php

use Nyrados\Translator\TranslatorApi;
use Nyrados\Translator\Provider\ArrayProvider;

require "./vendor/autoload.php";

$provider = new ArrayProvider();
$provider->set('en', [
    'greet' => 'Hello {name}!',
    'article_header' => 'A meaningful title',
    'article' => 'A very interesting article',
]);
$provider->set('de', [
    'greet' => 'Hallo {name}!',
    'article_header' => 'Eine aussagekräftige Überschrift',
]);

$t = new TranslatorApi();
$t->addProvider($provider);
$t->setPreferences(['de-at', 'en-us']);
```

## Translating single Translation
Usage:
```php
<?php

//Output: Hallo John!
$t->translate('greet', ['name' => 'John!']);
```

## Translating multiple translations
Some times it is recommended to leave a specific area of a page in one language even if a translation is possible (e.g. formulars, navbar, footer).
In this example a german translation for article_header is aviable, but its not used to stay on the same language.

Example:
```php
<?php

$trans = $t->translate([
    'article_header', 
    'article'
    //with context: 'article' => ["context" => "value"]
]);

//Output: <h1>A meaningful title</h1><hr>
echo "<h1>" . $trans . "</h1><hr>";

//Output: <p>A very interesting article</p>
echo "<p>" . $trans . "</p>";
```

If you pass an array to `translate()` it will return an instance of `Nyrados\Translator\Translation\TranslationSection` which is an Iterator of callables that you can call with a specific context.

Usage:
```php
<?php
$trans->rewind();
echo $trans->current()($context);

$trans->next();
echo $trans->current()($context);
```

It is easier to use the following recommended methods as a shortcut which will return the current and calls next().

Usage:
```php
<?php

echo $trans->get($context);
echo $trans($context) // via __invoke()
echo (string) $trans; // via __toString()
```

## Setting preferences via Accept-Language header
Usage:
```php
<?php
// Set from $_SERVER['HTTP_ACCEPT_LANGUAGE']
$t->setPreferences(Helper::preferencesFromAcceptLanguage());

// Set from a Psr\Http\Message\ServerRequestInterface instance
$t->setPreferences(Helper::preferencesFromAcceptLanguage($request)); 
```