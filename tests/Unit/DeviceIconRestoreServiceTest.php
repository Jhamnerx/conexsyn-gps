<?php

use PHPUnit\Framework\TestCase;
use Tobuli\Services\DeviceIconRestoreService;

/**
 * Pruebas de la parte de archivos del servicio (sin Laravel ni base de datos):
 *   vendor/bin/phpunit --bootstrap vendor/autoload.php tests/Unit/DeviceIconRestoreServiceTest.php
 */
class DeviceIconRestoreServiceTest extends TestCase
{
    // PNG de 1x1 px
    const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private $tmp;
    private $public;
    private $source;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/device-icons-' . uniqid();
        $this->public = $this->tmp . '/public';
        $this->source = $this->tmp . '/source';

        mkdir($this->public . '/images/device_icons/v2', 0777, true);
        mkdir($this->public . '/images/device_icons/rotating', 0777, true);
        mkdir($this->source . '/v2', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmp);
    }

    public function test_restore_copies_by_relative_path_and_falls_back_to_basename()
    {
        $this->png($this->source . '/v2/aaa.png');   // misma subcarpeta que la fila
        $this->png($this->source . '/bbb.png');      // solo coincide el nombre (la fila dice v2/bbb.png)

        $rows = [
            $this->row('images/device_icons/v2/aaa.png'),
            $this->row('images/device_icons/v2/bbb.png'),
            $this->row('images/device_icons/ccc.png'),
        ];

        $result = (new DeviceIconRestoreService($this->public))->restore($this->source, $rows);

        $this->assertSame(['images/device_icons/v2/aaa.png', 'images/device_icons/v2/bbb.png'], $result['copied']);
        $this->assertSame(['images/device_icons/ccc.png'], $result['notFound']);
        $this->assertSame([], $result['skipped']);
        $this->assertFileExists($this->public . '/images/device_icons/v2/aaa.png');
        $this->assertFileExists($this->public . '/images/device_icons/v2/bbb.png');
    }

    public function test_restore_creates_missing_destination_folders()
    {
        $this->png($this->source . '/nested/deep/ddd.png');

        $result = (new DeviceIconRestoreService($this->public))
            ->restore($this->source, [$this->row('images/device_icons/nested/deep/ddd.png')]);

        $this->assertSame(['images/device_icons/nested/deep/ddd.png'], $result['copied']);
        $this->assertFileExists($this->public . '/images/device_icons/nested/deep/ddd.png');
    }

    public function test_restore_dry_run_copies_nothing_but_reports()
    {
        $this->png($this->source . '/v2/aaa.png');

        $result = (new DeviceIconRestoreService($this->public))
            ->restore($this->source, [$this->row('images/device_icons/v2/aaa.png')], true);

        $this->assertSame(['images/device_icons/v2/aaa.png'], $result['copied']);
        $this->assertFileDoesNotExist($this->public . '/images/device_icons/v2/aaa.png');
    }

    public function test_restore_never_overwrites_an_existing_file()
    {
        $this->png($this->source . '/v2/aaa.png');
        file_put_contents($this->public . '/images/device_icons/v2/aaa.png', 'original');

        $result = (new DeviceIconRestoreService($this->public))
            ->restore($this->source, [$this->row('images/device_icons/v2/aaa.png')]);

        $this->assertSame([], $result['copied']);
        $this->assertSame(['images/device_icons/v2/aaa.png'], $result['skipped']);
        $this->assertSame('original', file_get_contents($this->public . '/images/device_icons/v2/aaa.png'));
    }

    public function test_scan_registrable_lists_images_without_row_with_type_and_size()
    {
        $icons = $this->public . '/images/device_icons';
        $this->png($icons . '/v2/new.png');
        $this->png($icons . '/v2/known.png');
        $this->png($icons . '/rotating/rot.png');
        $this->png($icons . '/root.png');                            // la raiz no se registra (como el seeder)
        file_put_contents($icons . '/v2/notes.txt', 'no es imagen');
        file_put_contents($icons . '/v2/broken.png', 'basura');      // sin dimensiones validas

        $found = (new DeviceIconRestoreService($this->public))
            ->scanRegistrable(['images/device_icons/v2/known.png']);

        $this->assertSame([
            ['path' => 'images/device_icons/rotating/rot.png', 'type' => 'rotating', 'width' => 1, 'height' => 1],
            ['path' => 'images/device_icons/v2/new.png', 'type' => 'icon', 'width' => 1, 'height' => 1],
        ], $found);
    }

    public function test_orphan_files_ignores_status_companions_and_registered_files()
    {
        $icons = $this->public . '/images/device_icons';
        foreach (['a.png', 'b_online.png', 'b_offline.png', 'b_ack.png', 'b_engine.png', 'c.png'] as $name) {
            $this->png($icons . '/' . $name);
        }

        $orphans = (new DeviceIconRestoreService($this->public))
            ->orphanFiles(['images/device_icons/b_online.png', 'images/device_icons/c.png']);

        $this->assertSame(['images/device_icons/a.png'], $orphans);
    }

    private function png(string $file): void
    {
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }

        file_put_contents($file, base64_decode(self::PNG_1X1));
    }

    private function row(string $path): object
    {
        return (object) ['path' => $path];
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
