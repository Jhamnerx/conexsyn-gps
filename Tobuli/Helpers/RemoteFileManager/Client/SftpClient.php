<?php

namespace Tobuli\Helpers\RemoteFileManager\Client;

use Tobuli\Helpers\RemoteFileManager\Exception\FailedLoginException;
use Tobuli\Helpers\RemoteFileManager\Exception\NoConnectionException;

class SftpClient implements ClientInterface
{
    private $conn;
    private $sftp;
    private string $defaultPath = '/';

    public function __construct(string $host, string $user, string $pass, int $port = 22, ?string $path = null)
    {
        $this->conn = ssh2_connect($host, $port);

        if ($this->conn === false) {
            throw new NoConnectionException();
        }

        if (ssh2_auth_password($this->conn, $user, $pass) === false) {
            throw new FailedLoginException();
        }

        $this->sftp = ssh2_sftp($this->conn);

        if ($this->sftp === false) {
            throw new NoConnectionException();
        }

        if ($path) {
            $this->defaultPath = $path;
        }
    }

    public function __destruct()
    {
        if ($this->conn) {
            ssh2_disconnect($this->conn);
        }
    }

    public function upload(string $localPath, ?string $remotePath = null): bool
    {
        if (!$remotePath) {
            $remotePath = $this->defaultPath;
        }

        $remotePath .= '/' . basename($localPath);

        $contents = file_get_contents($localPath);

        if ($contents === false) {
            return false;
        }

        $stream = fopen("ssh2.sftp://$this->sftp$remotePath", 'w');

        if ($stream === false) {
            return false;
        }

        if (fwrite($stream, $contents) === false) {
            return false;
        }

        fclose($stream);

        return true;
    }
}