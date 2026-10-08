<?php

namespace Tobuli\Helpers\Alerts\Notification\Input;

use Tobuli\InputFields\AbstractField;

class InputMeta extends AbstractField
{
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_SELECT = 'select';
    public const TYPE_COLOR = 'color';

    protected ?bool $active = null;
    protected ?string $type = null;
    protected $input;
    protected $name = null;
    protected $title = null;
    protected $description = null;

    public function toArray(): array
    {
        return array_filter([
            'input' => $this->input,
            'active' => $this->active,
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'input_type' => $this->type,
        ], fn ($value) => $value !== null);
    }

    public function render()
    {
        return $this->toArray();
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setInput($input): self
    {
        $this->input = $input;

        return $this;
    }
}