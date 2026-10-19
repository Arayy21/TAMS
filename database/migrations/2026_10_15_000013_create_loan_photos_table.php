<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loan_photos')) {
            return;
        }

        Schema::create('loan_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('stage', ['sebelum', 'sesudah']);
            $table->string('path');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['loan_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_photos');
    }
};