<?php


namespace Tobuli\Sensors;


class Tag
{
    /**
     * @var string
     */
    protected $tag_name;

    /**
     * @var string
     */
    protected $regex;

    public function __construct($tag)
    {
        $this->setTagName($tag);
    }

    public function setTagName($tag)
    {
        $this->tag_name = $tag;

        $tagQoute = preg_quote($this->tag_name, '/');
        $this->regex = '/<' . $tagQoute . '>(.*?)<\/' . $tagQoute . '>/s';
    }

    public function getTagName()
    {
        return $this->tag_name;
    }

    public function parse($value)
    {
        if (is_array($value))
            return $value[$this->tag_name] ?? null;

        preg_match($this->regex, $value, $matches);
        if (isset($matches['1']))
            return $matches['1'];

        return null;
    }


}