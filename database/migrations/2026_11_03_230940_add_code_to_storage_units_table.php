<?php

use App\Enums\StorageLevel;
use App\Models\StorageUnit;
use App\Services\StorageCodeGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Existing bins are numbered and renamed, their old name moves into the description.
     */
    public function up(): void
    {
        Schema::table('storage_units', function (Blueprint $table) {
            $table->string('code', StorageCodeGenerator::MAX_LENGTH)->default('')->after('name');
        });

        $generator = app(StorageCodeGenerator::class);

        foreach (StorageLevel::cases() as $level) {
            $units = StorageUnit::ofLevel($level)->orderBy('id')->get();

            if ($level->holdsStock()) {
                $this->convertBins($units->groupBy('parent_id')->all(), $generator);

                continue;
            }

            foreach ($units as $unit) {
                $unit->code = $generator->uniqueCode($generator->fromName($unit->name), $unit->parent_id);
                $unit->saveQuietly();
            }
        }

        Schema::table('storage_units', function (Blueprint $table) {
            $table->unique(['parent_id', 'code']);
        });
    }

    /**
     * Reverse the migrations. The old names of the bins are not restored, they stay in the description.
     */
    public function down(): void
    {
        Schema::table('storage_units', function (Blueprint $table) {
            $table->dropUnique(['parent_id', 'code']);
            $table->dropColumn('code');
        });
    }

    /**
     * @param  array<int|string, Collection<int, StorageUnit>>  $binsByTier
     */
    protected function convertBins(array $binsByTier, StorageCodeGenerator $generator): void
    {
        foreach ($binsByTier as $bins) {
            $sortedBins = $bins->sort(fn (StorageUnit $first, StorageUnit $second) => strnatcasecmp($first->name, $second->name));

            foreach ($sortedBins as $bin) {
                $bin->description = filled($bin->description) ? "{$bin->name} – {$bin->description}" : $bin->name;
                $bin->code = $generator->nextBinNumber($bin->parent_id);
                $bin->name = $generator->binName($bin);
                $bin->saveQuietly();
            }
        }
    }
};
