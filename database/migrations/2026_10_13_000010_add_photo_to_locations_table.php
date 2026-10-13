<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('locations', 'photo')) {
            return;
        }

        Schema::table('locations', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('locations', 'photo')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn('photo');
            });
        }
    }
};