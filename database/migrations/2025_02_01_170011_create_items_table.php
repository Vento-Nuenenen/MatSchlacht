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
            $table->integer('stock')->default(1)->nullable();
            $table->integer('available')->default(0)->nullable();
            $table->integer('repair')->default(0)->nullable();
            $table->integer('lot')->default(1)->nullable();
            $table->boolean('consumable')->default(false)->nullable();
            $table->integer('purch_price_cents')->default(0)->nullable();

            $table->foreignId('item_group_id');

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
