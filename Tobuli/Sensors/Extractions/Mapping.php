<?php


namespace Tobuli\Sensors\Extractions;


use Illuminate\Support\Collection;
use Tobuli\Sensors\Contracts\Extraction;

class Mapping implements Extraction
{
    const VALUE_TYPE = 1;
    const RANGE_TYPE = 2;
    const EMPTY_TYPE = 3;
    const TEXT_MAP = 1;
    const FORMULA_MAP = 2;
    const VALUE_MAP = 3;

    /**
     * @var array
     */
    protected $mappings;

    public function __construct($mappings)
    {
        $this->setMappings($mappings);
    }

    public function setMappings($mappings)
    {
        foreach ($mappings as $mapping) {
            switch ($mapping['vt']) {
                case self::VALUE_TYPE:
                    $in = function($value) use ($mapping) {
                        return $value == $mapping['v'];
                    };
                    break;
                case self::RANGE_TYPE:
                    $in = function($value) use ($mapping) {
                        return $value >= $mapping['v'] && $value <= $mapping['t'];
                    };
                    break;
                case self::EMPTY_TYPE:
                    $in = function($value) use ($mapping) {
                        return is_null($value);
                    };
                    break;
            }

            switch ($mapping['mt']) {
                case self::TEXT_MAP:
                    $out = function($value) use ($mapping) {
                        return trans($mapping['mv']);
                    };
                    break;
                case self::FORMULA_MAP:
                    $out = function($value) use ($mapping) {
                        $formula = new Formula($mapping['mv']);
                        return $formula->parse($value);
                    };
                    break;
                case self::VALUE_MAP:
                    $out = function($value) use ($mapping) {
                        return $value;
                    };
                    break;
            }

            $this->mappings[] = [
                'in' => $in,
                'out' => $out,
                'icon_id' => $mapping['icon'] ?? null
            ];

        }
    }

    public function parse($value)
    {
        foreach ($this->mappings as $mapping) {
            if ($mapping['in']($value))
                return $mapping['out']($value);
        }

        return null;
    }

    public function getIcon($value)
    {
        foreach ($this->mappings as $mapping) {
            if ($mapping['in']($value))
                return $mapping['icon_id'];
        }

        return null;
    }

    public static function getValueTypes() : Collection
    {
        return collect([
            self::VALUE_TYPE => trans('front.value'),
            self::RANGE_TYPE => trans('front.range'),
            self::EMPTY_TYPE => trans('front.empty'),
        ]);
    }

    public static function getMapTypes() : Collection
    {
        return collect([
            self::TEXT_MAP    => trans('front.text'),
            self::FORMULA_MAP => trans('front.formula'),
            self::VALUE_MAP   => trans('front.value'),
        ]);
    }
}