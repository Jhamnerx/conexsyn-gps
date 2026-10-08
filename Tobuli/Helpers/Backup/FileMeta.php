<?php

namespace Tobuli\Helpers\Backup;

use DateTime;

class FileMeta
{
    public const TYPE_DIR = 'd';
    public const TYPE_LINK = 'l';
    public const TYPE_FILE = '-';
    public const TYPE_UNKNOWN = '?';

    private ?string $name;
    private ?string $dir;
    private ?string $type;
    private ?int $size;
    private ?string $owner;
    private ?string $group;
    private ?int $mask;
    private ?DateTime $modified;

    public static function fromFtpRaw(string $row, string $dir): self
    {
        preg_match('/^(.)(.{9})\s+\S+\s+(\S+)\s+(\S+)\s+(\S+)\s+(.{12})\s(.+)$/', $row, $matches);

        return new static(
            basename($matches[7]),
            $dir,
            $matches[1],
            $matches[5],
            $matches[3],
            $matches[4],
            base_convert(strtr($matches[2], 'rwx-', '1110'), 2, 8),
            self::resolveFtpDate($matches[6]),
        );
    }

    private static function resolveFtpDate(string $input): DateTime
    {
        $date = new DateTime($input);

        if (preg_match('/\d\d:\d\d$/', $input) && $date > new DateTime) {
            $date->modify('-1 year');
        }

        return $date;
    }

    public function __construct(
        string $name,
        string $dir,
        string $type,
        int $size,
        string $owner,
        string $group,
        int $mask,
        DateTime $modified
    ) {
        $this->name = $name;
        $this->dir = $dir;
        $this->type = $this->setType($type);
        $this->size = $size;
        $this->owner = $owner;
        $this->group = $group;
        $this->mask = $mask;
        $this->modified = $modified;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDir(): string
    {
        return $this->dir;
    }

    public function getPath(): string
    {
        return $this->dir . '/' . $this->name;
    }

    private function setType(string $type): string
    {
        return $type === self::TYPE_FILE || $type === self::TYPE_DIR || $type === self::TYPE_LINK
            ? $type
            : self::TYPE_UNKNOWN;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getMask(): int
    {
        return $this->mask;
    }

    public function getModified(): DateTime
    {
        return $this->modified;
    }
}