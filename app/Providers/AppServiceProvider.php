<?php

namespace App\Providers;

use App\Mail\Transport\MicrosoftGraphTransport;
use App\Models\Attachment;
use App\Models\Item;
use App\Models\Order;
use App\Observers\OrderObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        // Implicitly grant "Materialwart" role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Materialwart') ? true : null;
        });

        Order::observe(OrderObserver::class);

        Relation::morphMap([
            'item' => Item::class,
            'attachment' => Attachment::class,
            'user' => User::class,
            'order' => Order::class,
            'item_group' => ItemGroup::class,
            'ordered_item' => OrderedItem::class,
            'item_collection' => ItemCollection::class,
            'item_collection_item' => ItemCollectionItem::class,
        ]);

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
