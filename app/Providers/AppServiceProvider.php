<?php

namespace App\Providers;

use App\Mail\Transport\MicrosoftGraphTransport;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Azure\AzureExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            if ($user->isPrivileged()) {
                return true;
            }
        });

        Event::listen(SocialiteWasCalled::class, AzureExtendSocialite::class);

        Mail::extend('microsoftgraph', function (array $config) {
            return new MicrosoftGraphTransport(
                $config['tenant_id'],
                $config['client_id'],
                $config['client_secret'],
                $config['user_id']
            );
        });
    }
}
