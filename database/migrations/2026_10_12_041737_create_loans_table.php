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
        Schema::create('loans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('asset_id');
            $table->string('borrower_name', 100)->index();
            $table->string('borrower_contact', 50)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->date('loaned_at');
            $table->date('due_at')->nullable();
            $table->date('returned_at')->nullable();
            $table->string('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('loans_created_by_fk');
            $table->timestamps();

            $table->index(['asset_id', 'returned_at'], 'loans_asset_returned_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
