<?php

namespace App\Providers;

use App\Services\Pushwoosh\Pushwoosh;
use App\Services\Pushwoosh\PushwooshApiServiceInterface;
use GuzzleHttp\Client;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class PushwooshServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(PushwooshApiServiceInterface::class, function ($app) {
            return new Pushwoosh(
                new Client(),
                $app['config']['services.pushwoosh.api_token']
            );
        });
    }

    public function provides(): array
    {
        return [
            PushwooshApiServiceInterface::class,
        ];
    }
}
