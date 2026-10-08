<?php

namespace Tobuli\InputFields;

class MultiSelectLoaderField extends SelectLoaderField
{
    public function getType(): string
    {
        return 'multiselect-loader';
    }

    public function getHtmlName(): string
    {
        return parent::getHtmlName() . '[]';
    }

    public function render(array $options = [])
    {
        if ($this->getDefault()) {
            $options['data-selected'] = $this->getDefault();
        }

        return \Form::select($this->getHtmlName(), [], null, array_merge([
            'class' => 'form-control multiexpand',
            'multiple' => 'multiple',
            'data-live-search' => 'true',
            'data-actions-box' => 'true',
            'data-ajax' => $this->url,
        ], $options));
    }
}