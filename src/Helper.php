<?php

namespace Nyrados\Translator;

use Nyrados\Translator\Processor\ReplaceProcessor;
use Nyrados\Translator\Translation\Translation;
use Psr\Http\Message\ServerRequestInterface;

class Helper
{
    private function __construct()
    {
    }

    /**
     * Provides language prefrences from $_SERVER
     * or Psr\Http\Message\ServerRequestInterface if provided
     *
     * If Nothing is aviable english will be returned
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    public static function preferencesFromAcceptLanguage(ServerRequestInterface $request = null): array
    {
        if ($request instanceof ServerRequestInterface) {
            return self::parseAcceptLanguage($request->getHeaderLine('accept-language'));
        }

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return self::parseAcceptLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        }

        return ['en-en'];
    }

    /**
     * Parses HTTP Accept-Language header into a sorted list of languages
     *
     * @param string $header
     * @return array
     */
    public static function parseAcceptLanguage(string $header): array
    {
        $segments = explode(',', str_replace(' ', '', $header));
        $preferences = array_map(function ($element) {

            return explode(';q=', $element);
        }, $segments);
        usort($preferences, function ($a, $b) {

            return (isset($b[1]) ? $b[1] : 1) <=> (isset($a[1]) ? $a[1] : 1);
        });
        
        return array_map(fn (array $data) => $data[0], $preferences);
    }

    /**
     * Detects native Translation Processor by looking for the translation string
     *
     * @param Translation $translation
     * @return Translation
     */
    public static function detectProccessor(Translation $translation): Translation
    {
        if (preg_match('/{[a-z]+}/', (string) $translation)) {
            $translation = $translation->withProcessor(ReplaceProcessor::class);
        }

        return $translation;
    }

    public static function createDirIfNotExists(string $name)
    {
        if (!is_dir($name)) {
            mkdir($name, 0777, true);
        }
    }

    public static function getChecksum($for)
    {
        if (is_array($for)) {
            $for = implode('.', $for);
        }

        return md5($for);
    }

    public static function iterableToArray(iterable $iterable): array
    {
        return is_array($iterable) ? $iterable : iterator_to_array($iterable);
    }

    public static function savePHPArrayToFile(string $file, array $array)
    {
        if (!is_dir(dirname($file))) {
            self::createDirIfNotExists(dirname($file));
        }

        $fp = fopen($file . '.php', 'w+');
        fwrite($fp, "<?php\nreturn " . var_export($array, true) . ';');
        fclose($fp);
    }
}
