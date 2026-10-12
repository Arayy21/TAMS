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
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('stock_opname_id');
            $table->unsignedBigInteger('asset_id')->index('opname_items_asset_index');
            $table->unsignedInteger('system_qty');
            $table->unsignedInteger('borrowed_qty')->default(0);
            $table->enum('system_condition', ['baik', 'rusak', 'perbaikan']);
            $table->unsignedInteger('physical_qty')->nullable();
            $table->enum('physical_condition', ['baik', 'rusak', 'perbaikan'])->nullable();
            $table->string('notes')->nullable();
            $table->unsignedBigInteger('checked_by')->nullable()->index('opname_items_checked_by_fk');
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['stock_opname_id', 'asset_id'], 'opname_items_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
    }
};
