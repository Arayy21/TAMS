<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assets')) {
            return;
        }

        // Hanya kode berbentuk AWALAN-ANGKA yang angkanya kurang dari 6 digit
        // (termasuk aset yang sudah dihapus/diarsipkan). Kode yang sudah 6 digit tidak disentuh.
        DB::statement("
            UPDATE assets
            SET asset_code = CONCAT(SUBSTRING_INDEX(asset_code, '-', 1), '-', LPAD(SUBSTRING_INDEX(asset_code, '-', -1), 6, '0'))
            WHERE asset_code REGEXP '^[A-Z0-9]+-[0-9]+$'
              AND CHAR_LENGTH(SUBSTRING_INDEX(asset_code, '-', -1)) < 6
        ");
    }

    public function down(): void
    {
        if (! Schema::hasTable('assets')) {
            return;
        }

        // Kembali ke 4 digit hanya untuk nomor yang masih muat (sampai 9999)
        DB::statement("
            UPDATE assets
            SET asset_code = CONCAT(SUBSTRING_INDEX(asset_code, '-', 1), '-', LPAD(CAST(SUBSTRING_INDEX(asset_code, '-', -1) AS UNSIGNED), 4, '0'))
            WHERE asset_code REGEXP '^[A-Z0-9]+-[0-9]{6}$'
              AND CAST(SUBSTRING_INDEX(asset_code, '-', -1) AS UNSIGNED) <= 9999
        ");
    }
};