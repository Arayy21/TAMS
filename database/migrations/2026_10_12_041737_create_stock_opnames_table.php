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
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->date('opname_date');
            $table->unsignedBigInteger('location_id')->nullable()->index('opnames_location_fk');
            $table->enum('status', ['berjalan', 'selesai'])->default('berjalan')->index();
            $table->boolean('adjustments_applied')->default(false);
            $table->string('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('opnames_created_by_fk');
            $table->unsignedBigInteger('finished_by')->nullable()->index('opnames_finished_by_fk');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};
