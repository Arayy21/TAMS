<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (! Schema::hasColumn('loans', 'last_location_id')) {
                $table->foreignId('last_location_id')->nullable()->after('notes')
                    ->constrained('locations')->nullOnDelete()->cascadeOnUpdate();
            }
            if (! Schema::hasColumn('loans', 'last_location_note')) {
                $table->string('last_location_note', 100)->nullable();
            }
            if (! Schema::hasColumn('loans', 'last_location_at')) {
                $table->timestamp('last_location_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (Schema::hasColumn('loans', 'last_location_id')) {
                $table->dropConstrainedForeignId('last_location_id');
            }
            foreach (['last_location_note', 'last_location_at'] as $col) {
                if (Schema::hasColumn('loans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};