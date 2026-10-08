<?php

namespace Tobuli\Helpers\RemoteFileManager;

use Tobuli\Helpers\RemoteFileManager\Client\ClientInterface;
use Tobuli\Helpers\RemoteFileManager\Client\FtpClient;
use Tobuli\Helpers\RemoteFileManager\Client\SftpClient;
use Tobuli\Helpers\RemoteFileManager\Exception\UnsupportedProtocolException;

class ClientProvider
{
    public function fromUrl(string $url): ClientInterface
    {
        $params = parse_url($url);

        if (!isset($params['scheme'])) {
            throw new UnsupportedProtocolException();
        }

        $scheme = strtolower($params['scheme']);

        switch ($scheme) {
            case 'ftp':
                return new FtpClient(
                    $params['host'],
                    $params['user'],
                    $params['pass'],
                    $params['port'] ?? 21,
                    $params['path'] ?? '',
                );
            case 'sftp':
                return new SftpClient(
                    $params['host'],
                    $params['user'],
                    $params['pass'],
                    $params['port'] ?? 22,
                    $params['path'] ?? '',
                );
            default:
                throw new UnsupportedProtocolException();
        }
    }
}