<?php

namespace Tobuli\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tobuli\Entities\Device;

class DeviceImageService
{
    const IMAGE_PATH = 'images/device_images/';

    protected Device $device;

    public function __construct(Device $device)
    {
        $this->device = $device;
    }

    public function get(): ?string
    {
        $path = Str::finish(self::IMAGE_PATH, '/') . "{$this->device->id}.*";

        return File::glob($path)[0] ?? null;
    }

    public function save(UploadedFile $image): void
    {
        $path = Str::finish(self::IMAGE_PATH, '/');

        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $this->deleteExisting($path);

        $filename = $this->device->id . '.' . Str::random() . '.' . $image->getClientOriginalExtension();

        if (!$image->move($path, $filename)) {
            throw new \Exception(trans('global.failed_file_save'));
        }
    }

    public function delete(): void
    {
        $path = Str::finish(self::IMAGE_PATH, '/');

        if (!File::exists($path)) {
            return;
        }

        $this->deleteExisting($path);
    }

    private function deleteExisting(string $path): void
    {
        $existingFiles = File::glob("{$path}{$this->device->id}.*");

        if (!empty($existingFiles)) {
            File::delete($existingFiles);
        }
    }
}
