<?php

namespace Tobuli\Plugins;

use Illuminate\Support\Str;
use Tobuli\Plugins\Contracts\PluginInterface;

class PluginsProvider
{
    private array $cache = [];

    public function get(string $key): ?PluginInterface
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $name = Str::studly($key);

        $className = "\\Tobuli\\Plugins\\Services\\{$name}Plugin";

        if (!class_exists($className)) {
            return $this->cache[$key] = null;
        }

        return $this->cache[$key] = new $className();
    }
}