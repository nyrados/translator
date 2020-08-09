<?php
namespace Nyrados\Translator;

use DateInterval;
use Nyrados\Translator\Cache\RequestCache;
use Nyrados\Translator\Processor\ProcessorContainer;
use Psr\Container\ContainerInterface;

class Config
{
    private $config = [];

    /** @var RequestCache */
    private $requestCache;

    public function __construct(array $config = [])
    {
        $defaultConfig = [
            'cache_dir' => sys_get_temp_dir() . '/translator-' . md5(__DIR__),
            'cache_interval' => new DateInterval('PT1H'),
            'cache' => false,
            'processor_container' => new ProcessorContainer()
        ];

        $this->config = array_merge($defaultConfig, $config);

        $this->requestCache = new RequestCache();
    }

    public function getCacheExpireInterval(): DateInterval
    {
        return $this->config['cache_interval'];
    }

    public function getCacheDir(): string
    {
        return $this->config['cache_dir'];
    }

    public function isCacheActive(): bool
    {
        return $this->config['cache'];
    }

    public function getRequestCache(): RequestCache
    {
        return $this->requestCache;
    }

    public function getProcessorContainer(): ContainerInterface
    {
        return $this->config['processor_container'];
    }
}