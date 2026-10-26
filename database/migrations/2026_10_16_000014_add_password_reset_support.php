<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('users', 'session_version')) {
                $table->unsignedInteger('session_version')->default(0)->after('must_change_password');
            }
        });

        if (! Schema::hasTable('password_reset_requests')) {
            Schema::create('password_reset_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('email', 150);
                $table->string('message')->nullable();
                $table->enum('status', ['menunggu', 'selesai', 'ditolak', 'kedaluwarsa'])->default('menunggu')->index();
                $table->string('requested_ip', 45)->nullable();
                $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->timestamp('handled_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_requests');

        Schema::table('users', function (Blueprint $table) {
            foreach (['session_version', 'must_change_password'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};