<?php


namespace Tobuli\Sensors\Extractions;


use Tobuli\Helpers\SetFlagDO;

class FormulaSetFlag extends Formula
{
    const PLACEHOLDER = "[value]";

    /**
     * @var string
     */
    protected $formula;

    /**
     * @var SetFlagDO[]
     */
    protected $setflags;

    public function __construct(string $formula, array $setflags)
    {
        $formula = $this->replaceFormula($formula, $setflags);
        $this->setflags = $setflags;

        parent::__construct($formula);
    }

    public function parse($value)
    {
        $equation = $this->formula;

        foreach ($this->setflags as $place => $setflag) {
            $equation = str_replace(
                "[$place]",
                $setflag->crop($value),
                $equation
            );
        }

        return $this->solve($equation);
    }

    /**
     * @param string $formula
     * @param SetFlagDO[] $setflags
     * @return string
     */
    protected function replaceFormula($formula, $setflags)
    {
        foreach ($setflags as $place => $setflag) {
            $formula = str_replace($setflag->getPlace(), "[$place]", $formula);
        }

        return $formula;
    }

    /**
     * @param string $formula
     * @return array
     */
    static public function resolveSetflag($formula)
    {
        $groups = \Tobuli\Helpers\SetFlag::multiCrop($formula);

        $data = [];

        foreach ($groups as $i => $group) {
            $data["[value{$i}]"] = $group;
        }

        return $data;
    }
}