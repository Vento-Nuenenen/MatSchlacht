<?php

use App\Enums\FileStorageProvider;
use App\Models\Attachment;
use App\Models\Item;
use App\Models\ItemProcurement;
use App\Models\Setting;
use App\Models\User;
use App\Services\ComponentManager;
use App\Services\FileStorage\DropboxAdapter;
use App\Services\FileStorage\FileStorageManager;
use App\Services\FileStorage\GoogleDriveAdapter;
use App\Services\FileStorage\SftpAdapter;
use App\Services\FileStorage\SharePointAdapter;
use App\Services\FileStorage\WebDavAdapter;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Config;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->materialwart = User::factory()->create();
    $this->materialwart->assignRole('Materialwart');

    $this->leader = User::factory()->create();
    $this->leader->assignRole('Wolfsstufe');
});

/**
 * @param  array<string, mixed>  $settings
 */
function configureFileStorage(array $settings): void
{
    app(ComponentManager::class)->update(['file_storage' => ['settings' => $settings]]);
    app(FileStorageManager::class)->forgetDisks();
}

/**
 * The settings form payload of all components with the given file storage settings.
 *
 * @param  array<string, mixed>  $fileStorageSettings
 * @return array<string, mixed>
 */
function settingsPayloadWithFileStorage(array $fileStorageSettings): array
{
    $components = [];

    foreach (app(ComponentManager::class)->definitions() as $component => $definition) {
        $components[$component] = [
            'enabled' => '1',
            'settings' => collect($definition['settings'])
                ->map(fn (array $setting) => $setting['type'] === 'bool' ? '1' : ($setting['type'] === 'secret' ? '' : $setting['default']))
                ->all(),
        ];
    }

    $components['file_storage']['settings'] = array_replace($components['file_storage']['settings'], $fileStorageSettings);

    return ['components' => $components];
}

test('the file storage uses s3 by default', function () {
    expect(app(FileStorageManager::class)->activeProvider())->toBe(FileStorageProvider::S3);
});

test('the disk configuration is built from the settings of each provider', function (FileStorageProvider $provider, array $settings, array $expectedConfig) {
    config(['services.microsoft' => ['tenant' => 'tenant-id', 'client_id' => 'client-id', 'client_secret' => 'client-secret']]);
    configureFileStorage(['provider' => $provider->value, ...$settings]);

    expect(app(FileStorageManager::class)->diskConfig($provider))->toMatchArray($expectedConfig);
})->with([
    's3 compatible' => [FileStorageProvider::S3, ['s3_key' => 'key', 's3_secret' => 'secret', 's3_region' => 'auto', 's3_bucket' => 'mat', 's3_endpoint' => 'https://r2.example.com', 's3_path_style' => true],
        ['driver' => 's3', 'key' => 'key', 'secret' => 'secret', 'region' => 'auto', 'bucket' => 'mat', 'endpoint' => 'https://r2.example.com', 'use_path_style_endpoint' => true]],
    'local' => [FileStorageProvider::Local, [], ['driver' => 'local']],
    'sftp' => [FileStorageProvider::Sftp, ['sftp_host' => 'files.example.com', 'sftp_port' => 2222, 'sftp_username' => 'mat', 'sftp_password' => 'geheim', 'sftp_root' => '/data'],
        ['driver' => 'matschlacht-sftp', 'host' => 'files.example.com', 'port' => 2222, 'username' => 'mat', 'password' => 'geheim', 'root' => '/data']],
    'ftp' => [FileStorageProvider::Ftp, ['ftp_host' => 'ftp.example.com', 'ftp_port' => 21, 'ftp_username' => 'mat', 'ftp_password' => 'geheim', 'ftp_root' => '/mat', 'ftp_ssl' => true, 'ftp_passive' => false],
        ['driver' => 'ftp', 'host' => 'ftp.example.com', 'username' => 'mat', 'password' => 'geheim', 'ssl' => true, 'passive' => false]],
    'sharepoint' => [FileStorageProvider::SharePoint, ['sharepoint_drive_id' => 'b!drive', 'sharepoint_folder' => 'Material'],
        ['driver' => 'sharepoint', 'tenant_id' => 'tenant-id', 'client_id' => 'client-id', 'drive_id' => 'b!drive', 'folder' => 'Material']],
    'nextcloud' => [FileStorageProvider::Nextcloud, ['nextcloud_url' => 'https://cloud.example.ch/', 'nextcloud_username' => 'pfadi mat', 'nextcloud_password' => 'app-pass'],
        ['driver' => 'webdav', 'base_url' => 'https://cloud.example.ch/remote.php/dav/files/pfadi%20mat', 'username' => 'pfadi mat', 'password' => 'app-pass']],
    'dropbox' => [FileStorageProvider::Dropbox, ['dropbox_app_key' => 'key', 'dropbox_app_secret' => 'secret', 'dropbox_folder' => 'Mat'],
        ['driver' => 'dropbox', 'app_key' => 'key', 'app_secret' => 'secret', 'folder' => 'Mat']],
    'google drive' => [FileStorageProvider::GoogleDrive, ['google_client_id' => 'client.apps.googleusercontent.com', 'google_client_secret' => 'secret'],
        ['driver' => 'google_drive', 'client_id' => 'client.apps.googleusercontent.com', 'client_secret' => 'secret', 'folder' => 'MatSchlacht']],
]);

test('uploads go to the disk of the active provider', function () {
    configureFileStorage(['provider' => 'local']);
    Storage::fake('attachments_local');

    Storage::disk('attachments')->put('items/zelt.jpg', 'bild');

    Storage::disk('attachments_local')->assertExists('items/zelt.jpg');
});

test('secrets are stored encrypted, never shown and kept when left empty', function () {
    $this->actingAs($this->materialwart)
        ->put(route('settings.components.update'), settingsPayloadWithFileStorage([
            'provider' => 'sftp', 'sftp_host' => 'files.example.com', 'sftp_port' => 22, 'sftp_username' => 'mat', 'sftp_password' => 'streng-geheim',
        ]))
        ->assertSessionHasNoErrors();

    $storedPassword = Setting::where('key', 'file_storage.sftp_password')->sole()->value;
    expect($storedPassword)->not->toBe('streng-geheim')
        ->and(Crypt::decryptString($storedPassword))->toBe('streng-geheim');

    $this->actingAs($this->materialwart)
        ->get(route('settings.components.index'))
        ->assertSuccessful()
        ->assertDontSee('streng-geheim')
        ->assertSee('gespeichert – leer lassen');

    $this->actingAs($this->materialwart)
        ->put(route('settings.components.update'), settingsPayloadWithFileStorage([
            'provider' => 'sftp', 'sftp_host' => 'files.example.com', 'sftp_port' => 22, 'sftp_username' => 'mat', 'sftp_password' => '',
        ]))
        ->assertSessionHasNoErrors();

    app(ComponentManager::class)->flush();
    expect(app(ComponentManager::class)->setting('file_storage', 'sftp_password'))->toBe('streng-geheim');
});

test('the selected provider requires its connection settings', function (string $provider, string $missingSetting) {
    $this->actingAs($this->materialwart)
        ->put(route('settings.components.update'), settingsPayloadWithFileStorage(['provider' => $provider, $missingSetting => '']))
        ->assertSessionHasErrors("components.file_storage.settings.{$missingSetting}");
})->with([
    ['sftp', 'sftp_host'],
    ['ftp', 'ftp_username'],
    ['sharepoint', 'sharepoint_drive_id'],
    ['s3', 's3_bucket'],
    ['nextcloud', 'nextcloud_url'],
    ['dropbox', 'dropbox_app_key'],
    ['google_drive', 'google_client_id'],
]);

test('the connection test reports success and failure', function () {
    configureFileStorage(['provider' => 'local']);
    Storage::fake('attachments_local');

    $this->actingAs($this->materialwart)
        ->post(route('settings.file-storage.test'))
        ->assertSessionHas('success');

    configureFileStorage(['provider' => 'ftp', 'ftp_host' => '127.0.0.1', 'ftp_port' => 1, 'ftp_username' => 'mat']);

    $this->actingAs($this->materialwart)
        ->post(route('settings.file-storage.test'))
        ->assertSessionHas('error');

    $this->actingAs($this->leader)->post(route('settings.file-storage.test'))->assertForbidden();
});

test('attachments are served through the app with the permissions of their record', function () {
    Storage::fake('attachments');
    $item = Item::factory()->create();
    $itemImage = $item->attachments()->create(['path' => 'items/zelt.jpg']);
    $receipt = ItemProcurement::factory()->create(['item_id' => $item->id])->attachments()->create(['path' => 'procurements/rechnung.pdf']);
    Storage::disk('attachments')->put('items/zelt.jpg', 'bild');
    Storage::disk('attachments')->put('procurements/rechnung.pdf', 'pdf');

    $this->actingAs($this->leader)->get(route('attachments.show', $itemImage))->assertOk()->assertStreamedContent('bild');
    $this->actingAs($this->leader)->get(route('attachments.show', $receipt))->assertForbidden();
    $this->actingAs($this->materialwart)->get(route('attachments.show', $receipt))->assertOk()->assertStreamedContent('pdf');

    Storage::disk('attachments')->delete('items/zelt.jpg');
    $this->actingAs($this->leader)->get(route('attachments.show', $itemImage))->assertNotFound();

    auth()->logout();
    $this->get(route('attachments.show', $itemImage))->assertRedirect(route('login'));
});

test('files can be transferred to another provider', function () {
    configureFileStorage(['provider' => 'local']);
    Storage::fake('attachments_s3');
    Storage::fake('attachments_local');
    $item = Item::factory()->create();
    $item->attachments()->create(['path' => 'items/a.jpg']);
    $item->attachments()->create(['path' => 'items/b.pdf']);
    $item->attachments()->create(['path' => 'items/fehlt.jpg']);
    Storage::disk('attachments_s3')->put('items/a.jpg', 'a');
    Storage::disk('attachments_s3')->put('items/b.pdf', 'b');

    $this->artisan('attachments:transfer', ['from' => 's3', '--delete-source' => true])->assertSuccessful();

    Storage::disk('attachments_local')->assertExists(['items/a.jpg', 'items/b.pdf']);
    Storage::disk('attachments_s3')->assertMissing(['items/a.jpg', 'items/b.pdf']);
    expect(Storage::disk('attachments_local')->get('items/a.jpg'))->toBe('a')
        ->and(Attachment::count())->toBe(3);
});

test('the transfer refuses unknown or identical providers', function (string $from, ?string $to) {
    configureFileStorage(['provider' => 'local']);

    $this->artisan('attachments:transfer', array_filter(['from' => $from, 'to' => $to]))->assertFailed();
})->with([
    'unknown provider' => ['mega', null],
    'same provider' => ['local', null],
]);

test('the sharepoint adapter stores files through microsoft graph', function () {
    $driveUrl = SharePointAdapter::GRAPH_URL.'/drives/b%21drive/root:/MatSchlacht';
    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        $driveUrl.'/items/zelt.jpg:/content' => Http::sequence()->push(['id' => '1'], 201)->push('bild'),
        $driveUrl.'/items/zelt.jpg' => Http::sequence()->push(['id' => '1', 'file' => ['mimeType' => 'image/jpeg']])->push(null, 204),
        $driveUrl.'/items/fehlt.jpg' => Http::response(['error' => ['code' => 'itemNotFound']], 404),
    ]);
    $adapter = new SharePointAdapter('tenant', 'client', 'secret', 'b!drive', 'MatSchlacht');

    $adapter->write('items/zelt.jpg', 'bild', new Config);

    expect($adapter->read('items/zelt.jpg'))->toBe('bild')
        ->and($adapter->fileExists('items/zelt.jpg'))->toBeTrue()
        ->and($adapter->fileExists('items/fehlt.jpg'))->toBeFalse();

    $adapter->delete('items/zelt.jpg');

    Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
        && $request->url() === $driveUrl.'/items/zelt.jpg:/content'
        && $request->hasHeader('Authorization', 'Bearer token')
        && $request->body() === 'bild');
    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE' && $request->url() === $driveUrl.'/items/zelt.jpg');
    Http::assertSentCount(6);
});

test('the sftp and sharepoint disks use the own adapters', function (FileStorageProvider $provider, string $adapterClass) {
    configureFileStorage(['provider' => $provider->value, 'sftp_host' => 'files.example.com', 'sftp_username' => 'mat', 'sharepoint_drive_id' => 'b!drive']);

    expect(app(FileStorageManager::class)->disk($provider)->getAdapter())->toBeInstanceOf($adapterClass);
})->with([
    'sftp' => [FileStorageProvider::Sftp, SftpAdapter::class],
    'sharepoint' => [FileStorageProvider::SharePoint, SharePointAdapter::class],
    'nextcloud' => [FileStorageProvider::Nextcloud, WebDavAdapter::class],
    'dropbox' => [FileStorageProvider::Dropbox, DropboxAdapter::class],
    'google drive' => [FileStorageProvider::GoogleDrive, GoogleDriveAdapter::class],
]);

test('the nextcloud adapter stores files through webdav', function () {
    $baseUrl = 'https://cloud.example.ch/remote.php/dav/files/mat';
    $fileXml = '<?xml version="1.0"?><d:multistatus xmlns:d="DAV:"><d:response><d:href>/remote.php/dav/files/mat/MatSchlacht/items/zelt.jpg</d:href>'
        .'<d:propstat><d:prop><d:resourcetype/><d:getcontentlength>4</d:getcontentlength><d:getcontenttype>image/jpeg</d:getcontenttype></d:prop></d:propstat></d:response></d:multistatus>';
    Http::fake([
        $baseUrl.'/MatSchlacht/' => Http::response(null, 405),
        $baseUrl.'/MatSchlacht/items/' => Http::response(null, 201),
        $baseUrl.'/MatSchlacht/items/zelt.jpg' => fn (Request $request) => match ($request->method()) {
            'PUT' => Http::response(null, 201),
            'GET' => Http::response('bild'),
            'PROPFIND' => Http::response($fileXml, 207),
            'DELETE' => Http::response(null, 204),
        },
        $baseUrl.'/MatSchlacht/items/fehlt.jpg' => Http::response(null, 404),
    ]);
    $adapter = new WebDavAdapter($baseUrl, 'mat', 'app-pass', 'MatSchlacht');

    $adapter->write('items/zelt.jpg', 'bild', new Config);

    expect($adapter->read('items/zelt.jpg'))->toBe('bild')
        ->and($adapter->fileExists('items/zelt.jpg'))->toBeTrue()
        ->and($adapter->fileSize('items/zelt.jpg')->fileSize())->toBe(4)
        ->and($adapter->fileExists('items/fehlt.jpg'))->toBeFalse();

    $adapter->delete('items/zelt.jpg');

    Http::assertSent(fn (Request $request) => $request->method() === 'MKCOL' && $request->url() === $baseUrl.'/MatSchlacht/items/');
    Http::assertSent(fn (Request $request) => $request->method() === 'PUT' && $request->body() === 'bild'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('mat:app-pass')));
    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE' && $request->url() === $baseUrl.'/MatSchlacht/items/zelt.jpg');
});

test('the dropbox adapter stores files through the dropbox api', function () {
    Http::fake([
        DropboxAdapter::TOKEN_URL => Http::response(['access_token' => 'token', 'expires_in' => 14400]),
        DropboxAdapter::CONTENT_URL.'/files/upload' => Http::response(['.tag' => 'file']),
        DropboxAdapter::CONTENT_URL.'/files/download' => Http::response('bild'),
        DropboxAdapter::API_URL.'/files/get_metadata' => fn (Request $request) => $request['path'] === '/MatSchlacht/items/zelt.jpg'
            ? Http::response(['.tag' => 'file', 'size' => 4])
            : Http::response(['error_summary' => 'path/not_found/..'], 409),
        DropboxAdapter::API_URL.'/files/delete_v2' => Http::response(['metadata' => []]),
    ]);
    $adapter = new DropboxAdapter('key', 'secret', 'refresh', 'MatSchlacht');

    $adapter->write('items/zelt.jpg', 'bild', new Config);

    expect($adapter->read('items/zelt.jpg'))->toBe('bild')
        ->and($adapter->fileExists('items/zelt.jpg'))->toBeTrue()
        ->and($adapter->fileExists('items/fehlt.jpg'))->toBeFalse();

    $adapter->delete('items/zelt.jpg');

    Http::assertSent(fn (Request $request) => $request->url() === DropboxAdapter::TOKEN_URL
        && $request['grant_type'] === 'refresh_token' && $request['refresh_token'] === 'refresh');
    Http::assertSent(fn (Request $request) => $request->url() === DropboxAdapter::CONTENT_URL.'/files/upload'
        && json_decode($request->header('Dropbox-API-Arg')[0], true)['path'] === '/MatSchlacht/items/zelt.jpg'
        && $request->hasHeader('Authorization', 'Bearer token')
        && $request->body() === 'bild');
    Http::assertSent(fn (Request $request) => $request->url() === DropboxAdapter::API_URL.'/files/delete_v2' && $request['path'] === '/MatSchlacht/items/zelt.jpg');
});

test('the google drive adapter resolves paths to folders and stores files', function () {
    $filesUrl = GoogleDriveAdapter::API_URL.'/files';
    Http::fake([
        GoogleDriveAdapter::TOKEN_URL => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        $filesUrl.'?*' => function (Request $request) {
            $query = $request['q'];

            return match (true) {
                str_contains($query, "name = 'MatSchlacht' and 'root' in parents") => Http::response(['files' => [['id' => 'root-folder', 'name' => 'MatSchlacht', 'mimeType' => GoogleDriveAdapter::FOLDER_MIME_TYPE]]]),
                str_contains($query, "name = 'items' and 'root-folder' in parents") => Http::response(['files' => []]),
                default => Http::response(['files' => []]),
            };
        },
        $filesUrl => Http::response(['id' => 'items-folder']),
        GoogleDriveAdapter::UPLOAD_URL.'/files?uploadType=resumable' => Http::response(null, 200, ['Location' => 'https://upload.example.com/session']),
        'https://upload.example.com/session' => Http::response(['id' => 'file-1']),
    ]);
    $adapter = new GoogleDriveAdapter('client', 'secret', 'refresh');

    $adapter->write('items/zelt.jpg', 'bild', new Config);

    Http::assertSent(fn (Request $request) => $request->method() === 'POST' && $request->url() === $filesUrl
        && $request['name'] === 'items' && $request['mimeType'] === GoogleDriveAdapter::FOLDER_MIME_TYPE && $request['parents'] === ['root-folder']);
    Http::assertSent(fn (Request $request) => $request->url() === GoogleDriveAdapter::UPLOAD_URL.'/files?uploadType=resumable'
        && $request['name'] === 'zelt.jpg' && $request['parents'] === ['items-folder']);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://upload.example.com/session' && $request->body() === 'bild'
        && ! $request->hasHeader('Authorization'));
    expect($adapter->fileExists('items/fehlt.jpg'))->toBeFalse();
});

test('dropbox and google drive are connected through oauth', function (FileStorageProvider $provider, array $credentials, string $authorizeHost, string $tokenUrl, string $tokenSetting) {
    configureFileStorage(['provider' => $provider->value, ...$credentials]);
    Http::fake([$tokenUrl => Http::response(['access_token' => 'access', 'refresh_token' => 'refresh-token', 'expires_in' => 3600])]);

    $redirect = $this->actingAs($this->materialwart)->get(route('settings.file-storage.connect', $provider));

    $redirect->assertRedirect();
    $location = $redirect->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
    expect(parse_url($location, PHP_URL_HOST))->toBe($authorizeHost)
        ->and($query['redirect_uri'])->toBe(route('settings.file-storage.callback', $provider));

    $this->actingAs($this->materialwart)
        ->get(route('settings.file-storage.callback', ['provider' => $provider, 'code' => 'auth-code', 'state' => $query['state']]))
        ->assertRedirect(route('settings.components.index'))
        ->assertSessionHas('success');

    app(ComponentManager::class)->flush();
    expect(app(ComponentManager::class)->setting('file_storage', $tokenSetting))->toBe('refresh-token')
        ->and(Setting::where('key', "file_storage.{$tokenSetting}")->sole()->value)->not->toBe('refresh-token');
    Http::assertSent(fn (Request $request) => $request->url() === $tokenUrl && $request['code'] === 'auth-code');
})->with([
    'dropbox' => [FileStorageProvider::Dropbox, ['dropbox_app_key' => 'key', 'dropbox_app_secret' => 'secret'], 'www.dropbox.com', DropboxAdapter::TOKEN_URL, 'dropbox_refresh_token'],
    'google drive' => [FileStorageProvider::GoogleDrive, ['google_client_id' => 'client', 'google_client_secret' => 'secret'], 'accounts.google.com', GoogleDriveAdapter::TOKEN_URL, 'google_refresh_token'],
]);

test('the oauth callback rejects a wrong state and keeps the other settings', function () {
    configureFileStorage(['provider' => 'dropbox', 'dropbox_app_key' => 'key', 'dropbox_app_secret' => 'secret', 'dropbox_folder' => 'Mat']);
    Http::fake();

    $this->actingAs($this->materialwart)->get(route('settings.file-storage.connect', FileStorageProvider::Dropbox));
    $this->actingAs($this->materialwart)
        ->get(route('settings.file-storage.callback', ['provider' => FileStorageProvider::Dropbox, 'code' => 'auth-code', 'state' => 'falsch']))
        ->assertSessionHas('error');

    Http::assertNothingSent();
    expect(app(ComponentManager::class)->setting('file_storage', 'dropbox_folder'))->toBe('Mat');
});

test('only administrators can connect oauth providers and only oauth providers can be connected', function () {
    $this->actingAs($this->leader)->get(route('settings.file-storage.connect', FileStorageProvider::Dropbox))->assertForbidden();
    $this->actingAs($this->materialwart)->get(route('settings.file-storage.connect', FileStorageProvider::Sftp))->assertNotFound();
});
