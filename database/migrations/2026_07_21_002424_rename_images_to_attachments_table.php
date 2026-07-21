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
        Schema::rename('images', 'attachments');
        Schema::table('attachments', function (Blueprint $table) {
            $table->renameColumn('imageable_type', 'attachable_type');
            $table->renameColumn('imageable_id', 'attachable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->renameColumn('attachable_type', 'imageable_type');
            $table->renameColumn('attachable_id', 'imageable_id');
        });
        Schema::rename('attachments', 'images');
    }
};
