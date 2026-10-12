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
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->foreign(['created_by'], 'opnames_created_by_fk')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['finished_by'], 'opnames_finished_by_fk')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['location_id'], 'opnames_location_fk')->references(['id'])->on('locations')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropForeign('opnames_created_by_fk');
            $table->dropForeign('opnames_finished_by_fk');
            $table->dropForeign('opnames_location_fk');
        });
    }
};
