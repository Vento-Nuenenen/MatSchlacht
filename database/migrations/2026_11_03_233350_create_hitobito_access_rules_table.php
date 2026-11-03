<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    protected string $permission = 'manage hitobito access';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hitobito_access_rules', function (Blueprint $table) {
            $table->id();
            $table->string('hitobito_person_id')->nullable();
            $table->string('hitobito_group_id')->nullable();
            $table->string('hitobito_role_class')->nullable();
            $table->string('hitobito_permission')->nullable();
            $table->string('role_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Permission::findOrCreate($this->permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hitobito_access_rules');

        Permission::where('name', $this->permission)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
