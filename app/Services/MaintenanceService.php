<?php

namespace App\Services;

use App\Models\Item;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Works out which items need maintenance based on their interval and their last maintenance.
 */
class MaintenanceService
{
    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_DUE = 'due';

    public const STATUS_OK = 'ok';

    public function __construct(protected ComponentManager $components) {}

    /**
     * How many days ahead a maintenance already counts as due.
     */
    public function daysAhead(): int
    {
        return max(0, (int) $this->components->setting('maintenance', 'reminder_days_ahead'));
    }

    /**
     * All items with a maintenance interval, ordered by their next due date.
     *
     * @return Collection<int, Item>
     */
    public function scheduledItems(): Collection
    {
        return Item::with(['itemGroup', 'latestMaintenance'])
            ->whereNotNull('maintenance_interval_months')
            ->where('maintenance_interval_months', '>', 0)
            ->get()
            ->sortBy(fn (Item $item) => $item->nextMaintenanceDue()?->getTimestamp())
            ->values();
    }

    /**
     * The items whose maintenance is overdue or due within the configured days.
     *
     * @return Collection<int, Item>
     */
    public function dueItems(): Collection
    {
        return $this->scheduledItems()
            ->filter(fn (Item $item) => $this->status($item) !== self::STATUS_OK)
            ->values();
    }

    /**
     * The maintenance status of an item, null if it has no interval.
     */
    public function status(Item $item): ?string
    {
        $nextDue = $item->nextMaintenanceDue();

        if ($nextDue === null) {
            return null;
        }

        $today = CarbonImmutable::today();

        return match (true) {
            $nextDue->lessThan($today) => self::STATUS_OVERDUE,
            $nextDue->lessThanOrEqualTo($today->addDays($this->daysAhead())) => self::STATUS_DUE,
            default => self::STATUS_OK,
        };
    }

    /**
     * The recipients of the reminder mail: the configured ones, otherwise the material responsibles.
     *
     * @return array<int, string>
     */
    public function reminderRecipients(): array
    {
        $recipients = $this->components->settingList('maintenance', 'reminder_recipients');

        if ($recipients === []) {
            $recipients = $this->components->settingList('order_notifications', 'material_responsibles');
        }

        $configuredResponsibles = config('mail.material_responsibles');

        if ($recipients === [] && is_string($configuredResponsibles)) {
            $recipients = array_values(array_filter(array_map('trim', explode(',', $configuredResponsibles))));
        }

        if ($recipients === [] && is_string(config('mail.from.address'))) {
            $recipients = [config('mail.from.address')];
        }

        return $recipients;
    }
}
