<?php

use App\Models\OrderTransition;
use App\Models\Setting;
use App\Services\ComponentManager;
use App\Services\OrderWorkflow;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The switch for all status mails, replaced by the switch of every transition.
     */
    protected const string LEGACY_MAIL_SETTING = 'order_notifications.notify_on_status_change';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_transition_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_transition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('scope');
            $table->timestamps();

            $table->unique(['order_transition_id', 'role_id']);
        });

        // Existing installations keep who may move orders and which mails are sent.
        app(OrderWorkflow::class)->applyDefaults();

        $legacyMailSetting = Setting::firstWhere('key', self::LEGACY_MAIL_SETTING);

        if ($legacyMailSetting instanceof Setting) {
            if ($legacyMailSetting->value === false) {
                OrderTransition::query()->update(['sends_mail' => false]);
            }

            $legacyMailSetting->delete();
            app(ComponentManager::class)->flush();
            app(OrderWorkflow::class)->flush();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_transition_role');
    }
};
