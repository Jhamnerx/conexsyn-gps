<?php


namespace Tobuli\Sensors\Extractions;


use Tobuli\Sensors\Contracts\Extraction;
use Tobuli\Services\ConditionService;

class Logic implements Extraction
{
    /**
     * @var string
     */
    protected $value;

    /**
     * @var int
     */
    protected $type;

    /**
     * @var string
     */
    protected $text;

    /**
     * @var SetFlag|null
     */
    protected $setFlag;

    public function __construct($type, $value, $text, $setFlag)
    {
        $this->type = $type;
        $this->value = $value;
        $this->text = $text;
        $this->setFlag = $setFlag;
    }


    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param $value
     * @return bool
     */
    public function parse($value)
    {
        if ($this->setFlag) {
            $value = $this->setFlag->parse($value);
        }

        return ConditionService::check($this->type, $value, $this->value);
    }
}