<?php

use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\ItemStock;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\StorageUnit;
use App\Models\User;
use App\States\Order\Committed;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    e2eEnable('storage', 'procurement', 'maintenance');
    $this->admin = e2eLogin();
    $this->item = e2eItem('Spatz-Zelt');
});

test('an input error appears at its field and nowhere else', function () {
    User::factory()->create(['email' => 'lea@example.com']);

    visit('/users/create')
        ->fill('firstname', 'Lea')
        ->fill('lastname', 'Muster')
        ->fill('email', 'lea@example.com')
        ->press('form[action$="/users"] button[type=submit]')
        ->assertPathIs('/users/create')
        ->assertPresent('input#email.is-invalid')
        ->assertAttribute('input#email', 'aria-invalid', 'true')
        ->assertScript("document.querySelector('input#email').nextElementSibling.classList.contains('invalid-feedback')", true)
        ->assertCount('.invalid-feedback', 1)
        ->assertNotPresent('.alert-danger')
        ->assertNotPresent('.app-toast')
        ->assertScript('document.activeElement.id', 'email');
});

test('the error disappears as soon as the field is corrected', function () {
    User::factory()->create(['email' => 'lea@example.com']);

    visit('/users/create')
        ->fill('firstname', 'Lea')
        ->fill('lastname', 'Muster')
        ->fill('email', 'lea@example.com')
        ->press('form[action$="/users"] button[type=submit]')
        ->assertPresent('input#email.is-invalid')
        ->fill('email', 'neu@example.com')
        ->assertNotPresent('input#email.is-invalid')
        ->assertNotPresent('.invalid-feedback');
});

test('an error appears in the form that was sent, even with equal field names', function () {
    $page = visit("/items/{$this->item->id}/edit");
    $page->script("document.querySelectorAll('form').forEach(form => form.noValidate = true)");

    $page->fill('procurement_quantity', '0')
        ->press('form[action$="/procurements"] button[type=submit]')
        ->assertPresent('#procurement_quantity.is-invalid')
        ->assertNotPresent('#maintenance_quantity.is-invalid');
});

test('errors of a later form do not end up in the first one', function () {
    $page = visit("/items/{$this->item->id}/edit");
    $page->script("document.querySelectorAll('form').forEach(form => form.noValidate = true)");

    $page->fill('maintenance_quantity', '0')
        ->press('form[action$="/maintenances"] button[type=submit]')
        ->assertPresent('#maintenance_quantity.is-invalid')
        ->assertNotPresent('#procurement_quantity.is-invalid');
});

test('a rule on several fields appears at the checkbox it belongs to', function () {
    ItemStock::factory()->create(['item_id' => $this->item->id]);

    $page = visit("/items/{$this->item->id}/edit");
    $page->script("const box = document.querySelector('#add_to_stock'); box.disabled = false; box.checked = true;");

    $page->fill('procurement_quantity', '2')
        ->press('form[action$="/procurements"] button[type=submit]')
        ->assertPresent('#add_to_stock.is-invalid')
        ->assertSee('Dieses Material liegt auf Lagerplätzen');
});

test('a missing stock is shown at the material list of the order', function () {
    $role = Role::findByName('Wolfsstufe');
    $other = Order::factory()->create(['user_id' => $this->admin->id, 'role_id' => $role->id, 'state' => Committed::class, 'event_start' => '2030-07-10', 'event_end' => '2030-07-20']);
    OrderedItem::factory()->create(['order_id' => $other->id, 'item_id' => $this->item->id, 'quantity' => 9]);

    visit('/orders/create')
        ->fill('event', 'Sommerlager')
        ->select('role_id', (string) $role->id)
        ->select('user_id', (string) $this->admin->id)
        ->fill('event_start', '2030-07-10')
        ->fill('event_end', '2030-07-20')
        ->click("tr:has(input[name=\"items[{$this->item->id}]\"]) [data-qty-step=\"1\"]")
        ->click("tr:has(input[name=\"items[{$this->item->id}]\"]) [data-qty-step=\"1\"]")
        ->press('button[name=action][value=order]')
        ->assertSeeIn('[data-error-for="items"]', 'nur noch 1 Stück verfügbar')
        ->assertNotPresent('.app-toast');
});

test('the error of a single material appears in its row', function () {
    $item = e2eItem('Beil', 2);
    $role = Role::findByName('Wolfsstufe');

    $page = visit('/orders/create')
        ->fill('event', 'Sommerlager')
        ->select('role_id', (string) $role->id)
        ->select('user_id', (string) $this->admin->id)
        ->fill('event_start', '2030-07-10')
        ->fill('event_end', '2030-07-20');

    $page->script("const input = document.querySelector('input[name=\"items[{$item->id}]\"]'); input.max = 999; input.dataset.maxQty = 999; input.value = 5;");

    $page->press('button[name=action][value=save]')
        ->assertSeeIn("tr:has(input[name=\"items[{$item->id}]\"])", 'nur noch 2 Stück verfügbar');
});

test('the errors of the settings appear at their fields', function () {
    visit('/settings/components')
        ->select('setting-file_storage-provider', 'nextcloud')
        ->press('form[action$="/settings/components"] .justify-content-end button[type=submit]')
        ->assertPresent('#setting-file_storage-nextcloud_url.is-invalid')
        ->assertNotPresent('.alert-danger');
});

test('success and other errors appear as a toast', function () {
    $group = ItemGroup::factory()->create(['name' => 'Zelte']);
    Item::factory()->create(['item_group_id' => $group->id]);

    $page = visit('/item-groups');
    $page->script('window.confirm = () => true');

    $page->click("form[action$=\"/item-groups/{$group->id}\"] button[type=submit]")
        ->assertSeeIn('.app-toast-danger', 'enthält noch Material')
        ->assertVisible('.app-toast-danger')
        ->assertNotPresent('.alert-danger')
        ->assertScript("bootstrap.Toast.getInstance(document.querySelector('.app-toast-danger'))._config.autohide", false)
        ->click('.app-toast-danger .btn-close')
        ->assertMissing('.app-toast-danger');

    visit('/item-groups/create')
        ->fill('name', 'Küche')
        ->press('form[action$="/item-groups"] button[type=submit]')
        ->assertVisible('.app-toast-success');
});

test('an error without a visible field is not lost', function () {
    $location = StorageUnit::factory()->create();

    // The field is part of a closed dialog when the page is shown again.
    $page = visit("/storage-units/{$location->id}");
    $page->script("window.location.href = '/storage-units/{$location->id}/labels?skip=99'");

    $page->assertVisible('.app-toast-danger')
        ->assertSeeIn('.app-toast-danger', 'Bereits verwendete Etiketten');
});

test('toasts stay inside the screen of a smartphone', function () {
    $group = ItemGroup::factory()->create();
    Item::factory()->create(['item_group_id' => $group->id]);

    $page = visit('/item-groups')->on()->mobile();
    $page->script('window.confirm = () => true');

    $page->click("form[action$=\"/item-groups/{$group->id}\"] button[type=submit]")
        ->assertVisible('.app-toast-danger')
        ->assertScript("(() => { const box = document.querySelector('.app-toast-danger').getBoundingClientRect(); return box.left >= 0 && box.right <= window.innerWidth; })()", true);
});

test('a guest sees the login error at the field', function () {
    auth()->logout();

    visit('/login')
        ->fill('email', 'niemand@example.com')
        ->fill('password', 'falsch')
        ->press('button[type=submit]')
        ->assertPresent('input#email.is-invalid')
        ->assertNotPresent('.alert-danger');
});
