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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('asset_id');
            $table->enum('type', ['masuk', 'keluar']);
            $table->enum('reason', ['pembelian', 'rusak', 'dibuang']);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('qty_before');
            $table->unsignedInteger('qty_after');
            $table->date('moved_at')->index('movements_date_index');
            $table->string('reference', 50)->nullable();
            $table->string('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('movements_created_by_fk');
            $table->timestamps();

            $table->index(['asset_id', 'moved_at'], 'movements_asset_index');
            $table->index(['type', 'reason'], 'movements_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
