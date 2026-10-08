<?php

namespace Tobuli\InputFields;

class SelectLoaderField extends AbstractField
{
    protected string $url;

    public function __construct(string $name, string $title, string $url, mixed $default = null)
    {
        $this->url = $url;

        parent::__construct($name, $title, $default);
    }

    public function toHtml(array $options)
    {
        if ($this->template) {
            return view($this->template)->make($this);
        }

        return \Form::select();
    }

    public function getType(): string
    {
        return 'select-loader';
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function toArray(): array
    {
        $this->addAdditionalParameter('url', $this->url);

        return parent::toArray();
    }

    public function render(array $options = [])
    {
        if ($this->getDefault()) {
            $options['data-selected'] = $this->getDefault();
        }

        return \Form::select(
            $this->getHtmlName(),
            [],
            null,
            array_merge(['class' => 'form-control', 'data-live-search' => 'true', 'data-ajax' => $this->url], $options)
        );
    }
}