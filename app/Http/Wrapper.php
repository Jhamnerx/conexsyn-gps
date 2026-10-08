<?php


namespace App\Http;


use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\MiddlewareNameResolver;
use Illuminate\Routing\SortedMiddleware;

class Wrapper
{
    public function run($controller, $method, $arguments)
    {
        $middleware = $this->getMiddlewares($controller, $method);

        $middleware = $this->sortMiddlewares($middleware);

        return (new Pipeline(app()))
            ->send(request())
            ->through($middleware)
            ->then(function ($request) use ($controller, $method, $arguments) {
                return app()
                    ->call([$controller, $method], $arguments);
            });
    }

    private function getMiddlewares($controller, $method)
    {
        return collect($this->controllerMidlleware($controller, $method))
            ->map(function ($name) {
                return (array)MiddlewareNameResolver::resolve(
                    $name,
                    app('router')->getMiddleware(),
                    app('router')->getMiddlewareGroups());
            })->flatten();
    }

    private function controllerMidlleware($controller, $method)
    {
        return (new ControllerDispatcher(app()))
            ->getMiddleware($controller, $method);
    }

    private function sortMiddlewares($middleware)
    {
        return (new SortedMiddleware(app('router')->middlewarePriority, $middleware))
            ->all();
    }
}