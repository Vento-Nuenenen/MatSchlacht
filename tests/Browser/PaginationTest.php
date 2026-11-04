<?php

use App\Models\ItemGroup;

beforeEach(function () {
    e2eLogin();
    $group = ItemGroup::factory()->create();

    foreach (range(1, 60) as $number) {
        e2eItem("Material {$number}", 5, ['item_group_id' => $group->id]);
    }
});

test('the page size and the page change without a page reload', function () {
    $rows = 'document.querySelectorAll("tbody tr").length';

    $page = visit('/items');
    $page->script('window.sameDocument = true');

    $page->assertScript($rows, 50)
        ->select('[data-per-page]', '10')
        ->assertSee('1–10 von 60')
        ->assertScript($rows, 10)
        ->assertScript('window.sameDocument', true)
        ->assertSelected('[data-per-page]', '10')
        ->click('.app-pagination a[rel="next"]')
        ->assertSee('11–20 von 60')
        ->assertQueryStringHas('page', '2')
        ->assertScript('window.sameDocument', true);

    $page->script('history.back()');

    $page->assertSee('1–10 von 60')
        ->select('[data-per-page]', '')
        ->assertSee('1–50 von 60')
        ->assertScript('window.sameDocument', true)
        ->assertNoJavaScriptErrors();
});

test('the choice is kept for the other lists and after a reload', function () {
    visit('/items')
        ->select('[data-per-page]', '25')
        ->assertSee('1–25 von 60')
        ->navigate('/items')
        ->assertSee('1–25 von 60')
        ->assertSelected('[data-per-page]', '25');
});

test('the default depends on the size of the screen', function (int $width, int $height, string $expected) {
    visit('/overwatch')->resize($width, $height)
        ->navigate('/overwatch')
        ->navigate('/items')
        ->assertSee($expected);
})->with([
    'smartphone' => [390, 844, '1–10 von 60'],
    'tablet' => [820, 1180, '1–25 von 60'],
    'desktop' => [1440, 900, '1–50 von 60'],
]);

test('entries stay stacked after a page change on a smartphone', function () {
    visit('/overwatch')->on()->mobile()
        ->navigate('/items')
        ->assertSee('Seite 1 von 6')
        ->click('.app-pagination a[rel="next"]')
        ->assertSee('Seite 2 von 6')
        ->assertPresent('table.table-stack td[data-label="Bestand"]');
});

test('the content can still be used after a page change', function () {
    $page = visit('/items');

    $page->click('.app-pagination a[rel="next"]')
        ->assertSee('51–60 von 60')
        ->click('thead th:nth-child(3)')
        ->click('tbody tr:first-child a.btn-warning')
        ->assertPathContains('/edit');
});
