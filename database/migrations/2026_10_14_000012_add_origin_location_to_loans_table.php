<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (! Schema::hasColumn('loans', 'origin_location_id')) {
                $table->foreignId('origin_location_id')->nullable()->after('quantity')
                    ->constrained('locations')->nullOnDelete()->cascadeOnUpdate();
            }
        });

        // Peminjaman lama: isi dengan lokasi aset saat ini (perkiraan terbaik)
        DB::table('loans')->whereNull('origin_location_id')->update([
            'origin_location_id' => DB::raw('(SELECT location_id FROM assets WHERE assets.id = loans.asset_id)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (Schema::hasColumn('loans', 'origin_location_id')) {
                $table->dropConstrainedForeignId('origin_location_id');
            }
        });
    }
};