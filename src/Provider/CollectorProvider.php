<?php
namespace Nyrados\Translator\Provider;

use Nyrados\Translator\Language\Language;
use Nyrados\Translator\Translation\Translation;

class CollectorProvider implements ProviderInterface
{

    private $pool = [];

    public function getTranslation(Language $language, string $string): ?Translation
    {
        return isset($pool[$string]) ? $pool[$string] : null;
    }

    public function addEntry(Language $language, string $string, Translation $translation)
    {
        $this->pool[$string] = $translation;
    }

    public function getPool(): array
    {
        return $this->pool;
    }
}