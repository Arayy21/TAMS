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
        Schema::table('loans', function (Blueprint $table) {
            $table->foreign(['asset_id'], 'loans_asset_fk')->references(['id'])->on('assets')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['created_by'], 'loans_created_by_fk')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign('loans_asset_fk');
            $table->dropForeign('loans_created_by_fk');
        });
    }
};
