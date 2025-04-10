<?php

namespace Udhuong\Uploader;

use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Udhuong\Uploader\Domain\Contracts\MediaRepository;
use Udhuong\Uploader\Infrastructure\File\UploadService;
use Udhuong\Uploader\Infrastructure\Repositories\MediaRepositoryImpl;
use Udhuong\Uploader\Presentation\Consoles\UploadFileCommand;

class UploaderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            UploadFileCommand::class,
        ]);
        $this->mergeConfigFrom(__DIR__.'/../config/uploader.php', 'uploader');

        $this->app->singleton('upload', function () {
            $uploadService = new UploadService;
            $uploadService->directory(config('uploader.directory'));
            $uploadService->disk(config('uploader.disk'));

            return $uploadService;
        });
        $this->app->singleton('intervention_image', function () {
            return new ImageManager(new Driver);
        });
    }

    public function boot(): void
    {
        $this->registerRepository();
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/uploader.php' => config_path('uploader.php'),
            __DIR__.'/../../../storage/app/public' => storage_path('app/public'),
        ], 'uploader');
    }

    private function registerRepository(): void
    {
        $this->app->bind(MediaRepository::class, MediaRepositoryImpl::class);
    }
}
