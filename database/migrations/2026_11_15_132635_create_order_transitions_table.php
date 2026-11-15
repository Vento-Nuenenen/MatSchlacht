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
        Schema::create('order_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('from_state');
            $table->string('to_state');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('sends_mail')->default(false);
            $table->timestamps();

            $table->unique(['from_state', 'to_state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_transitions');
    }
};
