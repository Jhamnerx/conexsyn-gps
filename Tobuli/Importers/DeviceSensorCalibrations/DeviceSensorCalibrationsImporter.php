<?php

namespace Tobuli\Importers\DeviceSensorCalibrations;

use Tobuli\Exceptions\ValidationException;
use Tobuli\Importers\Importer;

class DeviceSensorCalibrationsImporter extends Importer
{
    public function import($file, $additionals = [])
    {
        $items = $this->reader->read($file);

        $items = array_unique($items, SORT_REGULAR);

        $x = array_map(fn ($item) => $item['x'], $items);

        if (count($x) !== count(array_unique($x, SORT_NUMERIC))) {
            throw new ValidationException([
                'x' => trans('front.duplicates') . ' x (' . trans('validation.attributes.parameter_value') . ')'
            ]);
        }

        $y = array_map(fn ($item) => $item['y'], $items);

        if (count($y) !== count(array_unique($y, SORT_NUMERIC))) {
            throw new ValidationException([
                'y' => trans('front.duplicates') . ' y (' . trans('front.calibrated_value') . ')'
            ]);
        }

        foreach ($items as $item) {
            $this->validate($item);
        }

        return $items;
    }

    protected function getDefaults(): array
    {
        return [];
    }

    protected function importItem($data, $attributes = [])
    {
    }

    public function getValidationBaseRules(): array
    {
        return [
            'x' => 'required|numeric',
            'y' => 'required|numeric',
        ];
    }
}
