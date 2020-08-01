<?php
namespace Nyrados\Translator;

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
        $segments = explode(',', $header);
        $preferences = array_map(function($element) {
            return explode(';q=', $element);
        }, $segments);

        usort($preferences, function ($a, $b) {
            return (isset($b[1]) ? $b[1] : 1) <=> (isset($a[1]) ? $a[1] : 1);    
        });

        

        return array_map(function(array $data) { 
            return $data[0]; 
        }, $preferences);
    }
}