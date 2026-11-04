<?php

namespace App\Http\Controllers;

use App\Services\ComponentManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * What can be chosen in the «Pro Seite» field of the lists.
     *
     * @var array<int, int>
     */
    public const array PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * Holds the choice of the «Pro Seite» field, per device.
     */
    public const string PER_PAGE_COOKIE = 'matschlacht_per_page';

    /**
     * Holds the screen size, set by the layout: the server cannot know it otherwise.
     */
    public const string SCREEN_COOKIE = 'matschlacht_screen';

    /**
     * How many entries a list shows per page: the choice made on this device, otherwise
     * the default of its screen size from Einstellungen › Erscheinungsbild.
     */
    protected function perPage(): int
    {
        $chosen = (int) request()->cookie(self::PER_PAGE_COOKIE);

        if (in_array($chosen, self::PER_PAGE_OPTIONS, true)) {
            return $chosen;
        }

        $screen = request()->cookie(self::SCREEN_COOKIE);
        $screen = in_array($screen, ['phone', 'tablet', 'desktop'], true) ? $screen : 'desktop';

        return (int) app(ComponentManager::class)->setting('appearance', "per_page_{$screen}") ?: 25;
    }
}
