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
        Schema::table('assets', function (Blueprint $table) {
            $table->foreign(['category_id'], 'assets_category_fk')->references(['id'])->on('categories')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['created_by'], 'assets_created_by_fk')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['location_id'], 'assets_location_fk')->references(['id'])->on('locations')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['updated_by'], 'assets_updated_by_fk')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign('assets_category_fk');
            $table->dropForeign('assets_created_by_fk');
            $table->dropForeign('assets_location_fk');
            $table->dropForeign('assets_updated_by_fk');
        });
    }
};
