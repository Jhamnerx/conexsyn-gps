<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Helpers\RemoteFileManager\ClientProvider;

class FtpController extends Controller
{
    private ClientProvider $clientProvider;

    public function __construct(ClientProvider $clientProvider)
    {
        parent::__construct();

        $this->clientProvider = $clientProvider;
    }

    public function test()
    {
        $this->data['path'] = $this->getTempFile();

        return $this->store();
    }

    public function store()
    {
        Validator::validate($this->data, [
            'url' => 'required|url|starts_with:ftp,sftp',
            'path' => 'required',
        ]);

        try {
            $this->clientProvider
                ->fromUrl($this->data['url'])
                ->upload($this->data['path']);
        } catch (\Exception $e) {
            throw new ValidationException($e->getMessage());
        }

        return ['status' => 1, 'message' => trans('front.successfully_uploaded')];
    }

    private function getTempFile(): string
    {
        $path = storage_path('cache/test.txt');

        if (!File::exists($path)) {
            $res = File::put($path, 'test');

            if ($res === false) {
                throw new ValidationException(trans('global.error_occurred'));
            }
        }

        return $path;
    }
}
