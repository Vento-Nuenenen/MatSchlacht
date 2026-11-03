<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_procurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->date('procured_on');
            $table->unsignedInteger('quantity');
            $table->string('supplier')->nullable();
            $table->unsignedInteger('unit_price_cents')->nullable();
            $table->string('invoice_number')->nullable();
            $table->date('warranty_until')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('added_to_stock')->default(false);
            $table->timestamps();
        });

        Permission::findOrCreate('manage procurements');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_procurements');

        Permission::where('name', 'manage procurements')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
