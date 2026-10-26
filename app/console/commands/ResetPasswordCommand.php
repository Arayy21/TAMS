<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\TempPassword;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPasswordCommand extends Command
{
    protected $signature = 'tams:reset-password {email : Email akun yang akan disetel ulang}';

    protected $description = 'Setel ulang kata sandi akun dengan kata sandi sementara (untuk keadaan darurat)';

    public function handle(): int
    {
        $user = User::whereRaw('LOWER(email) = ?', [Str::lower($this->argument('email'))])->first();

        if (! $user) {
            $this->error('Akun dengan email itu tidak ditemukan.');

            return self::FAILURE;
        }

        $plain = TempPassword::make();

        $user->forceFill([
            'password'             => Hash::make($plain),
            'must_change_password' => true,
            'session_version'      => (int) $user->session_version + 1,
            'remember_token'       => Str::random(60),
        ])->save();

        DB::table('password_reset_requests')
            ->where('user_id', $user->id)->where('status', 'menunggu')
            ->update(['status' => 'selesai', 'handled_at' => now(), 'updated_at' => now()]);

        $this->info("Kata sandi akun {$user->name} ({$user->email}) berhasil disetel ulang.");
        $this->line("Kata sandi sementara: {$plain}");
        $this->warn('Catat sekarang. Pengguna wajib menggantinya saat masuk.');

        if (! $user->is_active) {
            $this->warn('Akun ini NONAKTIF dan belum bisa masuk. Aktifkan lewat Kelola Pengguna.');
        }

        return self::SUCCESS;
    }
}