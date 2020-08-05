<?php
namespace Nyrados\Translator\Cache;

use DateInterval;
use DateTime;
use Nyrados\Translator\Translation\Translation;
use Psr\SimpleCache\CacheInterface;

class CacheGroup
{
    private $dir;
    private $meta = [];
    private $langKeys = [];

    public function __construct(string $baseDir, string $name)
    {
        $this->dir = $baseDir . '/' . $name;
        if(!is_dir($this->dir)) {
            mkdir($this->dir, 0777, true);
        }
    }

    public function isMetaDefined()
    {
        return file_exists($this->dir . '/meta.php');
    }

    public function isExpired()
    {
        return isset($this->meta['e']) && $this->meta['e'] < (new DateTime())->getTimestamp();
    }

    public function applyCache(CacheInterface $cache, array $preferences): void
    {
        if (!$this->isMetaDefined()) {
            return;
        }

        $this->meta = require $this->dir . '/meta.php';
        if($this->isExpired()) {
            return;
        }

        $needed = $this->meta['s'];
        foreach ($preferences as $lang) {
            $lang = $lang->getId();

            if (file_exists($this->dir . '/' . $lang . '.php')) {
                $data = require $this->dir . '/' . $lang . '.php';

                $merged = isset($data['s']) ? $data['s'] : [];
                if(isset($data['m'])) {
                    $merged = array_merge($data, $data['m']);
                }

                foreach ($merged as $key => $value) {

                    if (!in_array($key, $needed)) {
                        continue;
                    }

                    if (($search = array_search($key, $needed)) !== false) {
                        unset($needed[$search]);
                    }

                    $this->langKeys[$lang][] = $key;

                    $cache->set($key, 
                        is_array($value) 
                            ? array_map(function($data) {
                                return unserialize($data);
                            }, $value)
                            : unserialize($value)
                    );
                }

            }
            
            if (empty($needed)) {
                break;
            }
        }
    }

    public function saveCache(CacheInterface $cache, array $keys, DateInterval $interval)
    {
        $check = $this->buildChecksum($keys);

        if (!$this->isMetaDefined() || $this->isExpired() || $this->meta['c'] != $check) {

            // Store meta file
            $this->savePhpArray('meta', [
                'c' => $check,
                'e' => (new DateTime())->add($interval)->getTimestamp(),
                's' => $keys
            ]);
        }

        // Store each lang file
        foreach ($this->sortKeys($cache, $keys) as $lang => $langKeys) {

            $data = [];
            $data['c'] = $this->buildChecksum($langKeys);

            // Dont refresh file if it defines the same keys
            if (isset($this->langKeys[$lang]) && $data['c'] == $this->buildChecksum($this->langKeys[$lang]) || empty($langKeys)) {
                continue; 
            }
            
            // Build Translation Array
            foreach ($langKeys as $key) {
                $value = $cache->get($key);
                if ($value instanceof Translation) {
                    $data['s'][$key] = serialize($value);
                } else {
                    $data['m'][$key] = array_map(function (Translation $translation) {
                        return serialize($translation);
                    }, $value);
                }
            }

            $this->savePhpArray($lang, $data);
        }
    }

    private function sortKeys(CacheInterface $cache, array $keys): array
    {
        $rs = [];

        foreach ($cache->getMultiple($keys) as $key => $value) {

            $lang = $value instanceof Translation 
                ? $value->getLanguage()->getId()
                : array_values($value)[0]->getLanguage()->getId()
            ;
            $rs[$lang][] = $key;
        }

        return $rs;
    }

    private function buildChecksum(array $keys): string
    {
        return md5(implode('.', $keys));
    }

    private function savePhpArray(string $name, array $data)
    {
        $file = fopen($this->dir . '/' . $name . '.php', 'w+');
        fwrite($file, "<?php\nreturn " . var_export($data, true) . ';');
        fclose($file);
    }
}