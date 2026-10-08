<?php


namespace Tobuli\Sensors\Extractions;


use Tobuli\Sensors\Contracts\Extraction;

class Formula implements Extraction
{
    const PLACEHOLDER = "[value]";

    /**
     * @var string
     */
    protected $formula;

    public function __construct($formula)
    {
        $this->setFormula($formula);
    }

    public function setFormula($formula)
    {
        $this->formula = $formula;
    }

    public function parse($value)
    {
        $equation = str_replace(
            self::PLACEHOLDER,
            parseNumber($value),
            $this->formula
        );

        return $this->solve($equation);
    }

    protected function solve($equation)
    {
        $eos = new \eqEOS();
        try {
            $result = $eos->solveIF($equation);
        }
        catch(\Exception $e) {
            $result = null;
        }

        return $result;
    }
}