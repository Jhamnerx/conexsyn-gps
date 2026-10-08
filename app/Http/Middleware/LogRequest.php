<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class LogRequest
{
    private array $config;
    private Request $request;
    private array $parameters;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $this->config = config('tobuli.model_change_log.request');
        $this->request = $request;

        $config = $this->config;

        if (empty($config['enabled'])) {
            return $next($request);
        }

        if ($this->isIncluded() === false || $this->isExcluded()) {
            return $next($request);
        }

        $attributes = $this->getParameters();
        $attributes['path'] = $request->path();

        activity()
            ->causedBy($request->user())
            ->withProperties(['attributes' => $attributes])
            ->useLog($request->route()->getName())
            ->log('request');

        return $next($request);
    }

    private function isIncluded(): ?bool
    {
        $paths = $this->config['paths']['included'] ?? [];

        return $this->hasPathMatch($paths);
    }

    private function isExcluded(): ?bool
    {
        $paths = $this->config['paths']['excluded'] ?? [];

        return $this->hasPathMatch($paths);
    }

    private function hasPathMatch(array $patterns): ?bool
    {
        foreach ($patterns as $pattern) {
            if (is_string($pattern)) {
                if ($this->request->is($pattern)) {
                    return true;
                }

                continue;
            }

            if (!$this->request->is($pattern['path'])) {
                continue;
            }

            if (!empty($pattern['only_with_parameters']) && !$this->getParameters()) {
                return false;
            }

            return true;
        }

        return null;
    }

    private function getParameters(): array
    {
        if (isset($this->parameters)) {
            return $this->parameters;
        }

        $query = array_filter($this->request->query());

        if (empty($query)) {
            return $this->parameters = [];
        }

        $config = $this->config['global']['parameters'];

        if (!empty($config['except'])) {
            $this->parameters = Arr::except($query, $config['except']);
        }

        return $this->parameters;
    }
}
