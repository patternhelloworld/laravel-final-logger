<?php

namespace Arwg\FinalLogger;


use Arwg\FinalLogger\Exceptions\CommonExceptionModel;
use Illuminate\Support\ServiceProvider;

class FinalLoggerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/final-logger.php' => config_path('final-logger.php'),
            ], 'config');
        }

        $this->app->singleton(ErrorLogHandlerInterface::class, config('final-logger.error_logger'));
        $this->app->singleton(GeneralLogHandlerInterface::class, config('final-logger.general_logger'));

        $this->app->singleton(CommonExceptionModel::class, function ($app) {
            return new CommonExceptionModel();
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/final-logger.php', 'final-logger');
    }
}
