<?php
namespace Nyrados\Translator\Tests;

use Nyrados\Translator\Language\Language;
use PHPUnit\Framework\TestCase;

class LanguageTest extends TestCase
{
    /**
     * @dataProvider languageData
     *
     * @return void
     */
    public function testCanParseLanguage($language, string $code, string $region, bool $same)
    {
        $lang = new Language($language);
        $this->assertSame($code, $lang->getCode());
        $this->assertSame($region, $lang->getRegion());
        $this->assertSame($same, $lang->isRegionSame());
    }

    public function languageData()
    {
        return [
            ['en-en', 'en', 'en', true],
            ['en', 'en', 'en', true],
            ['en-US', 'en', 'us', false],
            [new Language('fr'), 'fr', 'fr', true]
        ];
    }
}