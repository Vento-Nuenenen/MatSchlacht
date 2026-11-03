<?php

namespace App\Providers;

use App\Enums\FileStorageProvider;
use App\Mail\Transport\MicrosoftGraphTransport;
use App\Models\Attachment;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\ItemCollectionItem;
use App\Models\ItemGroup;
use App\Models\ItemMaintenance;
use App\Models\ItemProcurement;
use App\Models\ItemStock;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\StorageUnit;
use App\Models\User;
use App\Observers\ItemStockObserver;
use App\Observers\OrderObserver;
use App\Services\ComponentManager;
use App\Services\FileStorage\DropboxAdapter;
use App\Services\FileStorage\FileStorageManager;
use App\Services\FileStorage\GoogleDriveAdapter;
use App\Services\FileStorage\SftpAdapter;
use App\Services\FileStorage\SharePointAdapter;
use App\Services\FileStorage\WebDavAdapter;
use App\Services\ThemeService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
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
        $this->app->singleton(FileStorageManager::class);
        $this->app->singleton(ThemeService::class);
    }

    /**
     * Disks for uploaded files, configured through the "Dateispeicher" settings.
     */
    protected function registerFileStorageDrivers(): void
    {
        Storage::extend('attachments', fn () => app(FileStorageManager::class)->disk(app(FileStorageManager::class)->activeProvider()));

        Storage::extend('attachment_provider', fn ($app, array $config) => Storage::build(
            app(FileStorageManager::class)->diskConfig(FileStorageProvider::from($config['provider']))
        ));

        Storage::extend('matschlacht-sftp', function ($app, array $config): FilesystemAdapter {
            $adapter = new SftpAdapter(
                (string) $config['host'],
                (int) $config['port'],
                (string) $config['username'],
                $config['password'] ?? null,
                $config['private_key'] ?? null,
                (string) ($config['root'] ?? '/'),
            );

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });

        Storage::extend('webdav', function ($app, array $config): FilesystemAdapter {
            $adapter = new WebDavAdapter(
                (string) $config['base_url'],
                (string) $config['username'],
                (string) $config['password'],
                (string) ($config['folder'] ?? ''),
            );

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });

        Storage::extend('dropbox', function ($app, array $config): FilesystemAdapter {
            $adapter = new DropboxAdapter(
                (string) $config['app_key'],
                (string) $config['app_secret'],
                (string) $config['refresh_token'],
                (string) ($config['folder'] ?? ''),
            );

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });

        Storage::extend('google_drive', function ($app, array $config): FilesystemAdapter {
            $adapter = new GoogleDriveAdapter(
                (string) $config['client_id'],
                (string) $config['client_secret'],
                (string) $config['refresh_token'],
                (string) ($config['folder'] ?? 'MatSchlacht'),
            );

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });

        Storage::extend('sharepoint', function ($app, array $config): FilesystemAdapter {
            $adapter = new SharePointAdapter(
                (string) $config['tenant_id'],
                (string) $config['client_id'],
                (string) $config['client_secret'],
                (string) $config['drive_id'],
                (string) ($config['folder'] ?? ''),
            );

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });
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
            'item_procurement' => ItemProcurement::class,
            'item_maintenance' => ItemMaintenance::class,
        ]);

        $this->registerFileStorageDrivers();

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
