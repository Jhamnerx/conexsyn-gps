<?php

namespace Tobuli\InputFields;

class CheckboxField extends AbstractField
{
    protected bool $checked;

    public function __construct(string $name, string $title, bool $checked = false, $default = 1)
    {
        parent::__construct($name, $title, $default);

        $this->checked = $checked;
    }

    public function toArray(): array
    {
        return parent::toArray() + ['checked' => $this->checked];
    }

    public function getType(): string
    {
        return 'checkbox';
    }

    public function isChecked(): bool
    {
        return $this->checked;
    }

    public function setChecked(bool $checked): self
    {
        $this->checked = $checked;

        return $this;
    }

    public function render(array $options = [])
    {
        return \Form::checkbox(
            $this->getHtmlName(),
            $this->getDefault(),
            $this->checked,
            array_merge(['class' => 'form-control'], $options)
        );
    }
}