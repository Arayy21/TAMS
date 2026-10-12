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
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign(['asset_id'], 'movements_asset_fk')->references(['id'])->on('assets')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['created_by'], 'movements_created_by_fk')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign('movements_asset_fk');
            $table->dropForeign('movements_created_by_fk');
        });
    }
};
