<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Databases that ran an earlier Hitobito branch have a table with other
     * columns: it is replaced while it is empty, otherwise this fails instead of losing data.
     */
    public function up(): void
    {
        if (Schema::hasTable('hitobito_group_role_mappings') && DB::table('hitobito_group_role_mappings')->doesntExist()) {
            Schema::drop('hitobito_group_role_mappings');
        }

        Schema::create('hitobito_group_role_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('hitobito_group_id');
            $table->string('hitobito_role_class')->nullable();
            $table->string('role_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hitobito_group_role_mappings');
    }
};
