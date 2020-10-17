<?php

namespace Nyrados\Translator\Cache\Util;

use Psr\SimpleCache\CacheInterface;

class ArrayCache implements CacheInterface
{
    protected $storage = [];

    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->storage[$key] : $default;
    }

    public function has($key)
    {
        return isset($this->storage[$key]);
    }

    public function getKeys(): iterable
    {
        return array_keys($this->storage);
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $rs = [];
        foreach ($keys as $key) {
            $rs[$key] = $this->get($key, $default);
        }

        return $rs;
    }

    public function set($key, $value, $ttl = null)
    {
        $this->storage[$key] = $value;
        return true;
    }

    public function setMultiple($values, $ttl = null)
    {
        $state = true;
        foreach ($values as $key => $value) {
            $state = $this->set($key, $value, $ttl) && $state;
        }

        return $state;
    }

    public function delete($key)
    {
        if ($this->has($key)) {
            unset($this->key);
            return true;
        }

        return false;
    }

    public function deleteMultiple($keys)
    {
        $state = true;
        foreach ($keys as $key) {
            $state = $this->delete($key) && $state;
        }

        return $state;
    }

    public function clear()
    {
        $this->storage = [];
    }
}
