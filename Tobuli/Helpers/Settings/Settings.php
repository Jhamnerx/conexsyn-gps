<?php

namespace Tobuli\Helpers\Settings;

use Cache;

abstract class Settings {

    protected $prefix;

    protected $cache;

    protected $parent;

    abstract protected function _has($key);
    abstract protected function _get($key);
    abstract protected function _set($key, $value);
    abstract protected function _forget($key);

    public function __construct()
    {
        $this->cache = Cache::store('array');
    }

    public function get($key, $withParent = true)
    {
        return $this->cache->rememberForever(
            $this->getCahceKey($key),
            function() use($key, $withParent) {
                return $withParent ? $this->withParent($key) : $this->_get($key);
            }
        );
    }

    public function set($key, $value, $merge = false)
    {
        if ($merge) {
            $value = $this->merge($key, $value);
        }

        $this->cache->flush();

        return $this->_set($key, $value);
    }

    public function has($key)
    {
        return $this->_has($key);
    }

    public function forget($key)
    {
        $this->cache->flush();

        return $this->_forget($key);
    }

    public function merge($key, $value)
    {
        if (!$this->isMergeable($value))
            return $value;

        $previous = $this->_get($key);

        if (!$this->isMergeable($previous))
            return $value;

        return array_merge_recursive_distinct($previous, $value);
    }

    public function setParent($parentSettings)
    {
        $this->parent = $parentSettings;
    }

    public function getCahceKey($key)
    {
        return $this->getPrefix() . $key;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    protected function withParent($key)
    {
        if (!$this->_has($key))
            return $this->parent ? $this->parent->get($key) : null;

        $value = $this->_get($key);

        if (!$this->isMergeable($value))
            return $value;

        $parent_value = $this->parent ? $this->parent->get($key) : null;

        if (!$this->isMergeable($parent_value))
            return $value;

        return array_merge_recursive_distinct($parent_value, $value);
    }

    protected function isMergeable($value)
    {
        if ( ! is_array($value))
            return false;

        return true;
    }
}