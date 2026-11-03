<?php

namespace App\Providers;

use App\Mail\Transport\MicrosoftGraphTransport;
use App\Models\Attachment;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\ItemCollectionItem;
use App\Models\ItemGroup;
use App\Models\ItemStock;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\StorageUnit;
use App\Models\User;
use App\Observers\ItemStockObserver;
use App\Observers\OrderObserver;
use App\Services\ComponentManager;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
        $this->app->singleton(ComponentManager::class);
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

        Blade::if('component_enabled', fn (string $component) => app(ComponentManager::class)->isEnabled($component));

        Order::observe(OrderObserver::class);
        ItemStock::observe(ItemStockObserver::class);

        Relation::morphMap([
            'item' => Item::class,
            'attachment' => Attachment::class,
            'user' => User::class,
            'order' => Order::class,
            'item_group' => ItemGroup::class,
            'ordered_item' => OrderedItem::class,
            'item_collection' => ItemCollection::class,
            'item_collection_item' => ItemCollectionItem::class,
            'storage_unit' => StorageUnit::class,
            'item_stock' => ItemStock::class,
        ]);

        Event::listen(SocialiteWasCalled::class, MicrosoftExtendSocialite::class);

        Mail::extend('microsoftgraph', function (array $config) {
            $tenantId = (string) config('services.microsoft.tenant');
            $clientId = (string) config('services.microsoft.client_id');
            $clientSecret = (string) config('services.microsoft.client_secret');

            if ($tenantId === '' || $clientId === '' || $clientSecret === '') {
                Log::warning('Microsoft Graph mailer is not configured (AZURE_TENANT_ID, AZURE_CLIENT_ID, AZURE_CLIENT_SECRET). Mails are written to the log instead.');

                return Mail::createSymfonyTransport(['transport' => 'log']);
            }

            return new MicrosoftGraphTransport(
                new GraphServiceClient(new ClientCredentialContext($tenantId, $clientId, $clientSecret)),
                (string) config('mail.microsoft_graph.user_id', config('mail.from.address'))
            );
        });
    }
}
