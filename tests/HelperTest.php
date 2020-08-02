<?php
namespace Nyrados\Translator\Tests;

use Nyrados\Translator\Helper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    /**
     * @dataProvider acceptLanguageData
     */
    public function testCanParseAcceptLanguage(string $headerLine, array $expectedList)
    {
        $this->assertSame($expectedList, Helper::parseAcceptLanguage($headerLine));
    }

    public function acceptLanguageData()
    {
        return [
            ['en', ['en']],
            ['en-US', ['en-US']],
            ['en-US, en;q=0.9', ['en-US', 'en']],
            ['de-AT, en;q=0.7, de;q=0.9, en-US;q=0.8', ['de-AT', 'de', 'en-US', 'en']]
        ];
    }

}