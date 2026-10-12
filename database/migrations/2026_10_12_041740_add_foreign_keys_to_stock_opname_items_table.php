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
        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->foreign(['asset_id'], 'opname_items_asset_fk')->references(['id'])->on('assets')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['checked_by'], 'opname_items_checked_by_fk')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['stock_opname_id'], 'opname_items_opname_fk')->references(['id'])->on('stock_opnames')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->dropForeign('opname_items_asset_fk');
            $table->dropForeign('opname_items_checked_by_fk');
            $table->dropForeign('opname_items_opname_fk');
        });
    }
};
