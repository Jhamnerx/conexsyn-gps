<?php

namespace Tobuli\History;

use Illuminate\Support\Str;
use Tobuli\History\Actions\Action;

class ActionContainer
{
    protected array $actions = [];

    public function get(): array
    {
        arsort($this->actions);

        return array_keys($this->actions);
    }

    /**
     * @param string|array<string> $actionClasses
     * @throws \Exception
     */
    public function add($actionClasses, int $weight = 1): self
    {
        if ( ! is_array($actionClasses))
            $actionClasses = [$actionClasses];

        foreach ($actionClasses as $class) {
            $this->countCallers($this->resolveActionClass($class), $weight);
        }

        return $this;
    }

    public function addDefaultAfter(): self
    {
        /** @var class-string<Action> $class */
        foreach ($this->actions as $class => $weight) {
            foreach ($class::after() as $afterAction) {
                if (in_array($afterAction, $this->actions)) {
                    continue 2;
                }
            }

            foreach ($class::defaultAfter() as $defaultAction) {
                if (in_array($defaultAction, $this->actions)) {
                    continue;
                }

                $this->add($defaultAction, $weight + 1 - $class::RADIO);
            }
        }

        return $this;
    }

    /**
     * @throws \Exception
     */
    protected function countCallers(string $class, int $weight = 1, array $list = []): void
    {
        if (in_array($class, $list)) {
            throw new \Exception("Infinity loop for '$class'");
        } else {
            $list[] = $class;
        }

        if (empty($this->actions[$class]))
            $this->actions[$class] = $class::RADIO;

        $this->actions[$class] += $weight;

        foreach ($class::required() as $require) {
            $this->countCallers($require, $weight + 1, $list);
        }

        foreach ($class::after() as $after) {
            if (!array_key_exists($after, $this->actions))
                continue;

            $this->countCallers($after, $weight + 1, $list);
        }
    }

    /**
     * @throws \Exception
     */
    protected function resolveActionClass(string $class): string
    {
        if ( ! class_exists($class))
            $class = "Tobuli\History\Actions\\" . Str::studly($class);

        if ( ! class_exists($class)) {
            throw new \Exception("DeviceHistory action class '$class' not found");
        }

        if (!is_subclass_of($class, Action::class)) {
            throw new \Exception("'$class' not extend DeviceHistory action class");
        }

        return $class;
    }

    public function __destruct()
    {
        unset($this->actions);
    }
}