<?php


namespace Tobuli\Sensors\Extractions;


use Illuminate\Support\Collection;
use Tobuli\Sensors\Contracts\Extraction;

class BitCut implements Extraction
{
    /**
     * @var int
     */
    protected $start;

    /**
     * @var int
     */
    protected $mask;

    protected $base;

    public function __construct($start, $count, $base)
    {
        $this->start = intval($start);
        $this->mask = (1 << (intval($count))) - 1;
        $this->base = $base == 16;
    }

    public function parse($value)
    {
        try {
            if ($this->base) {
                $value = intval(base_convert($value, 16, 10));
            } else {
                $value = intval($value);
            }

            $value = ($value >> $this->start) & $this->mask;
        } catch (\Exception $e) {
            $value = null;
        }

        return $value === false ? null : $value;
    }

    public static function bases(): Collection
    {
        return collect([
            [
                'key' => '10',
                'title' => 'DEC',
            ],
            [
                'key' => '16',
                'title' => 'HEX',
            ],
        ]);
    }
}