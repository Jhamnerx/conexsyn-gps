<?php

namespace Tobuli\Helpers\Alerts\Notification\Input;

class SelectMeta extends InputMeta
{
    protected ?string $type = self::TYPE_SELECT;
    protected array $options;

    public function toArray(): array
    {
        $data = parent::toArray();

        $data['options'] = $this->options;

        return $data;
    }

    public function setType(?string $type): self
    {
        throw new \RuntimeException('Cannot change input type to ' . __CLASS__);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }
}