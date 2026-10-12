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
        Schema::create('asset_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->enum('action', ['create', 'update', 'location', 'condition', 'status', 'delete', 'restore']);
            $table->string('field_name', 50)->nullable();
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->string('notes')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index(['asset_id', 'created_at'], 'asset_histories_asset_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_histories');
    }
};
