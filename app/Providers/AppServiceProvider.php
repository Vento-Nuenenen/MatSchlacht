<?php

namespace App\Providers;

use App\Mail\Transport\MicrosoftGraphTransport;
use App\Models\Attachment;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\ItemCollectionItem;
use App\Models\ItemGroup;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\User;
use App\Observers\OrderObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Psr\Log\NullLogger;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\Transport\LogTransport;

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
            $tenantId = (string) config('services.microsoft.tenant');
            $clientId = (string) config('services.microsoft.client_id');
            $clientSecret = (string) config('services.microsoft.client_secret');

            if (empty($tenantId) || empty($clientId) || empty($clientSecret)) {
                // Return a dummy transport or handle the error gracefully
                // For now, we'll use a log transport if credentials are missing
            }

            $tokenContext = new ClientCredentialContext(
                $tenantId,
                $clientId,
                $clientSecret
            );

            $graphServiceClient = new GraphServiceClient($tokenContext);

            return new MicrosoftGraphTransport(
                $graphServiceClient,
                (string) config('mail.microsoft_graph.user_id', config('mail.from.address'))
            );
        });
    }
}
