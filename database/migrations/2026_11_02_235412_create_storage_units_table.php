<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    protected array $permissions = ['view storage', 'create storage', 'edit storage', 'delete storage'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('storage_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('storage_units')->restrictOnDelete();
            $table->string('level', 20)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_units');

        Permission::whereIn('name', $this->permissions)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
