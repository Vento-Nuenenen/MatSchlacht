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
        Schema::create('item_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('performed_on');
            $table->string('type', 20);
            $table->unsignedInteger('quantity')->nullable();
            $table->string('performed_by')->nullable();
            $table->unsignedInteger('cost_cents')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['item_id', 'performed_on']);
        });

        Permission::findOrCreate('manage maintenance');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_maintenances');

        Permission::where('name', 'manage maintenance')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
