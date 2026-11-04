<?php

test('every page of the app opens without errors', function () {
    e2eEnable('storage', 'procurement', 'maintenance', 'hitobito_login');
    e2eLogin();
    $item = e2eItem('Zelt');

    foreach ([
        '/overwatch', '/orders', '/orders/create', '/manage/orders', '/items', '/items/create', "/items/{$item->id}", "/items/{$item->id}/edit",
        '/item-groups', '/item-collections', '/item-collections/create', '/storage-units', '/storage-units/create', '/maintenance',
        '/users', '/users/create', '/roles', '/roles/create', '/azure-group-role-mappings', '/hitobito-access-rules',
        '/hitobito-test', '/settings/components', '/profile',
    ] as $path) {
        visit($path)->assertPathIs($path)->assertPresent('.app-shell')->assertNoJavaScriptErrors();
    }
});

test('the sidebar leads to the areas and marks the active one', function () {
    e2eLogin();

    visit('/overwatch')
        ->click('.app-sidebar a[href$="/items"]')
        ->assertPathIs('/items')
        ->assertAttributeContains('.app-sidebar a[href$="/items"]', 'class', 'active')
        ->assertSeeIn('.app-topbar-title', 'Material');
});

test('leaders only see the areas they may use', function () {
    e2eLogin('Wolfsstufe');

    visit('/overwatch')
        ->assertPresent('.app-sidebar a[href$="/orders"]')
        ->assertNotPresent('.app-sidebar a[href$="/manage/orders"]')
        ->assertNotPresent('.app-sidebar a[href$="/users"]')
        ->assertNotPresent('.app-sidebar a[href$="/settings/components"]');
});

test('smartphones use the tab bar and the more sheet', function () {
    e2eLogin();

    visit('/overwatch')->on()->mobile()
        ->assertVisible('.app-bottom-nav')
        ->assertMissing('.app-sidebar')
        ->assertCount('.app-bottom-nav .app-bottom-link', 5)
        ->click('button.app-bottom-link')
        ->assertVisible('#moreSheet')
        ->click('#moreSheet .app-tile[href$="/users"]')
        ->assertPathIs('/users')
        ->assertNoJavaScriptErrors();
});

test('tablets in portrait use the icon rail', function () {
    e2eLogin();

    $page = visit('/overwatch')->resize(820, 1180);

    $page->assertVisible('.app-sidebar')
        ->assertMissing('.app-bottom-nav')
        ->assertMissing('.app-sidebar .app-brand-name')
        ->assertScript("document.querySelector('.app-sidebar').offsetWidth < 120", true);
});

test('no page scrolls sideways on a smartphone', function (string $path) {
    e2eLogin();
    e2eItem('Zelt mit einem wirklich sehr langen Namen für schmale Bildschirme');

    visit($path)->on()->mobile()
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
})->with(['/overwatch', '/items', '/orders/create', '/manage/orders', '/users', '/settings/components']);

test('the colour mode can be switched and is remembered', function () {
    e2eLogin();

    $page = visit('/overwatch')->inLightMode();

    $page->click('.app-user-button')
        ->click('.app-user-menu [data-theme-value="dark"]')
        ->assertScript("document.documentElement.getAttribute('data-bs-theme')", 'dark')
        ->navigate('/items')
        ->assertScript("document.documentElement.getAttribute('data-bs-theme')", 'dark');
});

test('the automatic colour mode follows the device', function () {
    e2eLogin();

    visit('/overwatch')->inDarkMode()
        ->assertScript("document.documentElement.getAttribute('data-bs-theme')", 'dark');
});
