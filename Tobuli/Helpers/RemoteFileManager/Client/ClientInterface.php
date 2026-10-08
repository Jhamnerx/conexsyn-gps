<?php

namespace Tobuli\Helpers\RemoteFileManager\Client;

interface ClientInterface
{
    public function upload(string $localPath, ?string $remotePath = null): bool;
}