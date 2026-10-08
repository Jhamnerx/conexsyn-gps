<?php

namespace Tobuli\Reports;

trait RfidFormatter
{
    protected function formatRfid(?string $rfid): ?string
    {
        if (!$rfid) {
            return $rfid;
        }

        $last = strrpos($rfid, '01');

        if ($last < 5) {
            return $rfid;
        }

        return substr($rfid, $last - 5, 5);
    }
}
