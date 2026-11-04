<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fill the handed out quantities, remove columns nothing uses and index the foreign keys,
     * which PostgreSQL does not do on its own.
     */
    public function up(): void
    {
        DB::table('ordered_items')
            ->whereIn('order_id', DB::table('orders')->whereIn('state', ['delivered', 'returned'])->select('id'))
            ->update(['delivered' => DB::raw('quantity')]);

        Schema::table('ordered_items', function (Blueprint $table) {
            $table->dropColumn('confirmed');
            $table->index('order_id');
            $table->index('item_id');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['available', 'item_price_cents']);
            $table->index('item_group_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['role_id']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['item_group_id']);
            $table->integer('available')->default(0);
            $table->integer('item_price_cents')->default(0);
        });

        Schema::table('ordered_items', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['item_id']);
            $table->integer('confirmed')->default(0);
        });
    }
};
