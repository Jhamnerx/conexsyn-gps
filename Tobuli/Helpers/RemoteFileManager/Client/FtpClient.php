<?php

namespace Tobuli\Helpers\RemoteFileManager\Client;

use Tobuli\Helpers\RemoteFileManager\Exception\FailedLoginException;
use Tobuli\Helpers\RemoteFileManager\Exception\NoConnectionException;

class FtpClient implements ClientInterface
{
    private $conn;
    private string $defaultPath = '/';

    public function __construct(string $host, string $user, string $pass, int $port = 21, ?string $path = null)
    {
        $this->conn = ftp_connect($host, $port, 30);

        if ($this->conn === false) {
            throw new NoConnectionException();
        }

        if (ftp_login($this->conn, $user, $pass) === false) {
            throw new FailedLoginException();
        }

        @ftp_pasv($this->conn, true);

        if ($path) {
            $this->defaultPath = $path;
        }
    }

    public function __destruct()
    {
        if ($this->conn) {
            ftp_close($this->conn);
        }
    }

    public function upload(string $localPath, ?string $remotePath = null): bool
    {
        if (!$remotePath) {
            $remotePath = $this->defaultPath;
        }

        $remotePath .= '/' . basename($localPath);

        return ftp_put($this->conn, $remotePath, $localPath);
    }
}