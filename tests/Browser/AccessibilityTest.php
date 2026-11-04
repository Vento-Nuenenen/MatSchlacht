<?php

test('the main pages have no serious accessibility issues', function (string $path) {
    e2eLogin();
    e2eItem('Zelt');

    visit($path)->assertNoAccessibilityIssues();
})->with(['/overwatch', '/items', '/orders/create', '/manage/orders', '/settings/components', '/profile']);

test('the login page has no serious accessibility issues', function () {
    visit('/login')->assertNoAccessibilityIssues();
});
