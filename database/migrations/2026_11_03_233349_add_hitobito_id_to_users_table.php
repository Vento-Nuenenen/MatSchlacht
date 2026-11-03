<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Databases that ran an earlier Hitobito branch already have the column.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'hitobito_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('hitobito_id')->nullable()->unique()->after('azure_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['hitobito_id']);
            $table->dropColumn('hitobito_id');
        });
    }
};
