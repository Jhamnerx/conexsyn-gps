<?php namespace Tobuli\Services;

use DirectoryIterator;
use FilesystemIterator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tobuli\Entities\DeviceIcon;

/**
 * Recuperacion de iconos de dispositivos.
 *
 * La tabla device_icons guarda rutas relativas a public/ (images/device_icons/...).
 * Las subidas desde Admin no se versionan, asi que al mover una instalacion la
 * base puede referenciar archivos que ya no existen. Este servicio:
 *  - copia esos archivos desde una carpeta origen (otra instalacion, backup);
 *  - registra los iconos de v2/ y rotating/ sin fila, como DeviceIconsTableSeeder;
 *  - garantiza la flecha por defecto (id 0) sin fijar el nombre de la base.
 *
 * La ruta publica es inyectable para probar la parte de archivos sin Laravel.
 */
class DeviceIconRestoreService
{
    const ICONS_DIR = 'images/device_icons';

    /** Subcarpeta => tipo de icono con el que se registra (igual que el seeder). */
    const FOLDERS = ['v2' => 'icon', 'rotating' => 'rotating'];

    const EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif'];

    /** Sufijos de los iconos "por estado": solo el _online tiene fila propia. */
    const STATUS_COMPANIONS = ['offline', 'ack', 'engine'];

    const ARROW_PATH = 'assets/images/arrow-ack.png';

    /** Orden que el seeder asigna a los iconos del sistema. */
    const DEFAULT_ORDER = 3;

    /** @var string */
    private $publicPath;

    public function __construct(?string $publicPath = null)
    {
        $this->publicPath = rtrim($publicPath ?? public_path(), '/\\');
    }

    /**
     * Todas las filas de iconos (sin la flecha id 0).
     */
    public function allRows(): Collection
    {
        return DeviceIcon::where('id', '>', 0)->orderBy('id')->get();
    }

    /**
     * Filas cuyo archivo no existe bajo public/.
     */
    public function missingRows(): Collection
    {
        return $this->allRows()
            ->filter(function (DeviceIcon $row) {
                return !file_exists($this->absolute($row->path));
            })
            ->values();
    }

    /**
     * Dispositivos cuyo icon_id apunta a alguna de las filas dadas.
     */
    public function devicesUsing(Collection $rows): int
    {
        $ids = $rows->pluck('id')->all();

        return $ids ? DB::table('devices')->whereIn('icon_id', $ids)->count() : 0;
    }

    /**
     * Copia a public/ el archivo de cada fila buscandolo en $sourceDir
     * (recursivo): primero por la misma ruta relativa, despues por nombre.
     * Nunca sobrescribe un archivo existente.
     *
     * @param iterable<object> $rows  objetos con propiedad ->path
     * @return array{copied: string[], notFound: string[], skipped: string[]}
     */
    public function restore(string $sourceDir, iterable $rows, bool $dryRun = false): array
    {
        $index = $this->indexSource($sourceDir);
        $result = ['copied' => [], 'notFound' => [], 'skipped' => []];

        foreach ($rows as $row) {
            $path = $row->path;
            $destination = $this->absolute($path);

            if (file_exists($destination)) {
                $result['skipped'][] = $path;
                continue;
            }

            $source = $this->locate($index, $path);

            if (!$source) {
                $result['notFound'][] = $path;
                continue;
            }

            if (!$dryRun) {
                $directory = dirname($destination);

                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                copy($source, $destination);
            }

            $result['copied'][] = $path;
        }

        return $result;
    }

    /**
     * Imagenes de v2/ y rotating/ con dimensiones validas y sin fila en la base.
     *
     * @param string[]|null $existingPaths  rutas ya registradas (por defecto, las de la base)
     * @return array<int, array{path: string, type: string, width: int, height: int}>
     */
    public function scanRegistrable(?array $existingPaths = null): array
    {
        $existing = array_flip($existingPaths ?? $this->existingPaths());
        $found = [];

        foreach (self::FOLDERS as $folder => $type) {
            foreach ($this->imageFiles($this->absolute(self::ICONS_DIR . '/' . $folder)) as $file) {
                $path = self::ICONS_DIR . '/' . $folder . '/' . basename($file);

                if (isset($existing[$path])) {
                    continue;
                }

                $size = @getimagesize($file);

                if (!$size || !$size[0] || !$size[1]) {
                    continue;
                }

                $found[] = ['path' => $path, 'type' => $type, 'width' => $size[0], 'height' => $size[1]];
            }
        }

        usort($found, function ($a, $b) {
            return strcmp($a['path'], $b['path']);
        });

        return $found;
    }

    /**
     * Crea las filas de scanRegistrable() con order = 3, como el seeder.
     * (order no es asignable en masa en DeviceIcon, se fija aparte.)
     *
     * @return string[] rutas registradas
     */
    public function register(bool $dryRun = false): array
    {
        $created = [];

        foreach ($this->scanRegistrable() as $data) {
            if (!$dryRun) {
                $icon = new DeviceIcon($data);
                $icon->order = self::DEFAULT_ORDER;
                $icon->save();
            }

            $created[] = $data['path'];
        }

        return $created;
    }

    /**
     * Garantiza la flecha por defecto con id 0. Devuelve true si hubo que crearla.
     */
    public function ensureArrowIcon(bool $dryRun = false): bool
    {
        if (DB::table('device_icons')->where('id', 0)->exists()) {
            return false;
        }

        if ($dryRun) {
            return true;
        }

        DB::table('device_icons')
            ->whereIn('path', ['images/arrow-ack.png', self::ARROW_PATH])
            ->delete();

        DB::table('device_icons')->insert([
            'order'  => 1,
            'width'  => 25,
            'height' => 33,
            'path'   => self::ARROW_PATH,
            'type'   => 'arrow',
        ]);

        // MySQL trata id = 0 como autoincremento en el INSERT: se fija despues,
        // igual que hace DeviceIconsTableSeeder.
        DB::table('device_icons')->where('path', self::ARROW_PATH)->update(['id' => 0]);
        DB::table('devices')->whereNull('icon_id')->update(['icon_id' => 0]);

        return true;
    }

    /**
     * Archivos de la raiz de images/device_icons sin fila, ignorando los
     * companeros _offline/_ack/_engine de los iconos por estado.
     *
     * @param string[]|null $existingPaths
     * @return string[]
     */
    public function orphanFiles(?array $existingPaths = null): array
    {
        $existing = array_flip($existingPaths ?? $this->existingPaths());
        $orphans = [];

        foreach ($this->imageFiles($this->absolute(self::ICONS_DIR)) as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $underscore = strrpos($name, '_');

            if ($underscore !== false && in_array(substr($name, $underscore + 1), self::STATUS_COMPANIONS)) {
                continue;
            }

            $path = self::ICONS_DIR . '/' . basename($file);

            if (!isset($existing[$path])) {
                $orphans[] = $path;
            }
        }

        return $orphans;
    }

    /**
     * @return string[]
     */
    private function existingPaths(): array
    {
        return DB::table('device_icons')->pluck('path')->all();
    }

    private function absolute(string $relative): string
    {
        return $this->publicPath . '/' . ltrim($relative, '/');
    }

    /**
     * Imagenes de una carpeta (no recursivo), ordenadas.
     *
     * @return string[] rutas absolutas
     */
    private function imageFiles(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];

        foreach (new DirectoryIterator($directory) as $item) {
            if ($item->isDot() || !$item->isFile()) {
                continue;
            }

            if (!in_array(strtolower($item->getExtension()), self::EXTENSIONS)) {
                continue;
            }

            $files[] = $item->getPathname();
        }

        sort($files);

        return $files;
    }

    /**
     * Indexa el origen por ruta relativa y por nombre de archivo.
     *
     * @return array{relative: array<string, string>, name: array<string, string[]>}
     */
    private function indexSource(string $directory): array
    {
        $directory = rtrim($directory, '/\\');
        $byRelative = [];
        $byName = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));

            $byRelative[$relative] = $file->getPathname();
            $byName[$file->getFilename()][] = $file->getPathname();
        }

        foreach ($byName as &$candidates) {
            sort($candidates);
        }

        return ['relative' => $byRelative, 'name' => $byName];
    }

    /**
     * El origen puede ser public/ (rutas images/device_icons/...) o la propia
     * carpeta images/device_icons (rutas v2/...); si no coincide ninguna ruta
     * relativa se acepta el mismo nombre de archivo en cualquier subcarpeta.
     */
    private function locate(array $index, string $path): ?string
    {
        $candidates = [$path];

        if (strpos($path, self::ICONS_DIR . '/') === 0) {
            $candidates[] = substr($path, strlen(self::ICONS_DIR) + 1);
        }

        foreach ($candidates as $relative) {
            if (isset($index['relative'][$relative])) {
                return $index['relative'][$relative];
            }
        }

        $name = basename($path);

        return empty($index['name'][$name]) ? null : $index['name'][$name][0];
    }
}
