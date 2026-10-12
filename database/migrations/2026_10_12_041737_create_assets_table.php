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
        Schema::create('assets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('asset_code', 20)->unique();
            $table->string('name', 150)->index();
            $table->unsignedBigInteger('category_id')->index();
            $table->unsignedBigInteger('location_id')->index();
            $table->unsignedInteger('quantity')->default(1);
            $table->enum('asset_condition', ['baik', 'rusak', 'perbaikan'])->default('baik')->index('assets_condition_index');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif')->index();
            $table->text('description')->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('photo')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('assets_created_by_fk');
            $table->unsignedBigInteger('updated_by')->nullable()->index('assets_updated_by_fk');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
