<?php

namespace Tobuli\Clients;

use Tobuli\Clients\Exception\ConnectFailedException;
use Tobuli\Clients\Exception\LoginFailedException;

class FtpHandler
{
    private $host;
    private $user;
    private $pass;
    private $port;
    private string $path;

    /** @var resource|false */
    private $conn;

    public function __construct($host, $user, $pass, $port, $path)
    {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->port = $port;
        $this->path = rtrim($path, '/') . '/';
    }

    public function get(string $localFile, string $remoteFile, int $mode = FTP_BINARY, int $offset = 0): bool
    {
        return $this->execute(fn () => ftp_get($this->getConn(), $localFile, $this->path . $remoteFile, $mode, $offset));
    }

    /**
     * @return array|false|string[]
     */
    public function rawlist(string $directory, bool $recursive = false)
    {
        return $this->execute(fn () => ftp_rawlist($this->getConn(), $this->path . $directory, $recursive));
    }

    private function execute(\Closure $callback)
    {
        $result = $callback();

        if ($result === false && $this->isConnectionDead()) {
            $this->setConnection();

            return $callback();
        }

        return $result;
    }

    private function isConnectionDead(): bool
    {
        return ftp_nlist($this->conn, '.') === false;
    }

    /**
     * @return resource
     */
    private function setConnection()
    {
        $connection = ftp_connect($this->host, $this->port, 30);

        if ($connection === false) {
            throw new ConnectFailedException();
        }

        $this->conn = @ftp_login($connection, $this->user, $this->pass) ? $connection : false;

        if ($this->conn === false) {
            throw new LoginFailedException();
        }

        @ftp_pasv($this->conn, true);

        return $this->conn;
    }

    /**
     * @return resource
     */
    public function getConn()
    {
        return $this->conn ?? $this->setConnection();
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getPass()
    {
        return $this->pass;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}