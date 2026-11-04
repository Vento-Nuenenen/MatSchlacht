<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mappings gave an app role without admitting anybody. They become rules
     * that do not grant access, so that nobody gets in who was refused before.
     */
    public function up(): void
    {
        Schema::table('hitobito_access_rules', function (Blueprint $table) {
            $table->boolean('grants_access')->default(true);
        });

        foreach (DB::table('hitobito_group_role_mappings')->orderBy('id')->get() as $mapping) {
            DB::table('hitobito_access_rules')->insert([
                'hitobito_group_id' => $mapping->hitobito_group_id,
                'hitobito_role_class' => $mapping->hitobito_role_class,
                'role_name' => $mapping->role_name,
                'grants_access' => false,
                'created_at' => $mapping->created_at,
                'updated_at' => $mapping->updated_at,
            ]);
        }

        Schema::drop('hitobito_group_role_mappings');
    }

    /**
     * Rules without access that a mapping cannot express are removed:
     * without the column they would start to admit people.
     */
    public function down(): void
    {
        Schema::create('hitobito_group_role_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('hitobito_group_id');
            $table->string('hitobito_role_class')->nullable();
            $table->string('role_name');
            $table->timestamps();
        });

        $rolesOnly = DB::table('hitobito_access_rules')->where('grants_access', false);

        foreach ((clone $rolesOnly)->whereNotNull('hitobito_group_id')->whereNotNull('role_name')->whereNull('hitobito_person_id')->whereNull('hitobito_permission')->orderBy('id')->get() as $rule) {
            DB::table('hitobito_group_role_mappings')->insert([
                'hitobito_group_id' => $rule->hitobito_group_id,
                'hitobito_role_class' => $rule->hitobito_role_class,
                'role_name' => $rule->role_name,
                'created_at' => $rule->created_at,
                'updated_at' => $rule->updated_at,
            ]);
        }

        $rolesOnly->delete();

        Schema::table('hitobito_access_rules', function (Blueprint $table) {
            $table->dropColumn('grants_access');
        });
    }
};
