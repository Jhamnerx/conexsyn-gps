<?php

namespace Tobuli\InputFields;

class Field
{
    /**
     * @param string $name
     * @param string $title
     * @param null $default
     * @return NumberField
     */
    public function number(string $name, string $title, $default = null)
    {
        return new NumberField($name, $title, $default);
    }

    /**
     * @param string $name
     * @param string $title
     * @param null $default
     * @return StringField
     */
    public function string(string $name, string $title, $default = null)
    {
        return new StringField($name, $title, $default);
    }

    /**
     * @param string $name
     * @param string $title
     * @param null $default
     * @return TextField
     */
    public function text(string $name, string $title, $default = null)
    {
        return new TextField($name, $title, $default);
    }

    public function checkbox(string $name, string $title, bool $checked = false, $default = 1): CheckboxField
    {
        return new CheckboxField($name, $title, $checked, $default);
    }

    /**
     * @param string $name
     * @param string $title
     * @param null $default
     * @return DatetimeField
     */
    public function datetime(string $name, string $title, $default = null)
    {
        return new DatetimeField($name, $title, $default);
    }

    /**
     * @param string $name
     * @param string $title
     * @param null $default
     * @return SelectField
     */
    public function select(string $name, string $title, $default = null)
    {
        return new SelectField($name, $title, $default);
    }

    /**
     * @param string $name
     * @param string $title
     * @param null $default
     * @return MultiSelectField
     */
    public function multiSelect(string $name, string $title, $default = null)
    {
        return new MultiSelectField($name, $title, $default);
    }

    /**
     * @param string $name
     * @param string $title
     * @param null $default
     * @return MultiGroupSelectField
     */
    public function multiGroupSelect(string $name, string $title, $default = null)
    {
        return new MultiGroupSelectField($name, $title, $default);
    }

    public function selectLoader(string $name, string $title, string $url, mixed $default = null): SelectLoaderField
    {
        return new SelectLoaderField($name, $title, $url, $default);
    }

    public function multiSelectLoader(string $name, string $title, string $url, mixed $default = null): MultiSelectLoaderField
    {
        return new MultiSelectLoaderField($name, $title, $url, $default);
    }
}