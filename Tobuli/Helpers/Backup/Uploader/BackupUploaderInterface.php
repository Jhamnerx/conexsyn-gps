<?php

namespace Tobuli\Helpers\Backup\Uploader;

use Tobuli\Entities\BackupProcess;

interface BackupUploaderInterface
{
    public function check(): bool;

    public function process($commands, BackupProcess $process, $item);
}