<?php

namespace App\Providers;

use App\Mail\Transport\MicrosoftGraphTransport;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;

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
        Event::listen(SocialiteWasCalled::class, MicrosoftExtendSocialite::class);

        Mail::extend('microsoftgraph', function (array $config) {
            $tokenContext = new ClientCredentialContext(
                (string) env('AZURE_TENANT_ID'),
                (string) env('AZURE_CLIENT_ID'),
                (string) env('AZURE_CLIENT_SECRET')
            );

            $graphServiceClient = new GraphServiceClient($tokenContext);

            return new MicrosoftGraphTransport(
                $graphServiceClient,
                env('MAIL_OFFICE365_USER_ID', config('mail.from.address'))
            );
        });
    }
}
