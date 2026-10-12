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
        Schema::table('asset_histories', function (Blueprint $table) {
            $table->foreign(['asset_id'], 'asset_histories_asset_fk')->references(['id'])->on('assets')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['user_id'], 'asset_histories_user_fk')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_histories', function (Blueprint $table) {
            $table->dropForeign('asset_histories_asset_fk');
            $table->dropForeign('asset_histories_user_fk');
        });
    }
};
