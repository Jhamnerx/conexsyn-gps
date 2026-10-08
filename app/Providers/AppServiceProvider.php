<?php namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Route::singularResourceParameters(false);

        try {
            $this->app['config']->set('app.name', settings('main_settings.server_name'));
        } catch (\Exception $exception) {}

        if (config('app.debug')) {
            error_reporting(E_ALL & ~E_USER_DEPRECATED);
        } else {
            error_reporting(0);
        }

        Response::macro('downloadStreamFile', function ($file, $name = null, array $headers = []) {

            $headers = array_merge([
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . (is_null($name) ? basename($file) : $name) . '"',
            ], $headers);

            return Response::streamDownload(function() use ($file) {
                if ($f = fopen($file, 'rb')) {
                    while(!feof($f) and (connection_status()==0)) {
                        print(fread($f, 1024*8));
                        flush();
                    }
                    fclose($f);
                }
            }, $name, $headers);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $pathCustom = storage_path('views');
        $pathMain = base_path('Tobuli/Views');

        $this->registerPath($pathCustom);
        $this->registerPath($pathMain);

        Blade::withoutDoubleEncoding();

        Paginator::useBootstrap();
    }

    protected function registerPath(string $path): void
    {
        View::addLocation($path);
        View::addNamespace('admin', $path . '/Admin');
        View::addNamespace('front', $path . '/Frontend');
    }
}