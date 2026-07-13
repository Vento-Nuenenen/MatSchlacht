<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('stock')->default(1);
            $table->integer('available')->default(0);
            $table->integer('repair')->default(0);
            $table->integer('lot')->default(1);
            $table->boolean('consumable')->default(false);
            $table->integer('item_price_cents')->default(0);

            $table->foreignId('item_group_id')->constrained();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
