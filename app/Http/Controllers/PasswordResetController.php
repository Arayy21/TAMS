<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Support\TempPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function index(Request $request)
    {
        PasswordResetRequest::expireOld();

        $tab = in_array($request->tab, ['menunggu', 'riwayat', 'semua'], true) ? $request->tab : 'menunggu';

        $requests = PasswordResetRequest::with(['user', 'handler'])
            ->when($tab === 'menunggu', fn ($q) => $q->where('status', 'menunggu'))
            ->when($tab === 'riwayat', fn ($q) => $q->where('status', '!=', 'menunggu'))
            ->orderByRaw("status = 'menunggu' DESC")
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $counts = [
            'menunggu' => PasswordResetRequest::where('status', 'menunggu')->count(),
            'riwayat'  => PasswordResetRequest::where('status', '!=', 'menunggu')->count(),
        ];

        return view('reset-requests.index', compact('requests', 'tab', 'counts'));
    }

    public function reset(Request $request, PasswordResetRequest $resetRequest)
    {
        $request->validate([
            'verified' => 'accepted',
        ], [
            'verified.accepted' => 'Centang pernyataan bahwa identitas pengguna sudah Anda periksa.',
        ]);

        $plain = null;
        $error = null;
        $name  = null;
        $email = null;

        DB::transaction(function () use ($resetRequest, &$plain, &$error, &$name, &$email) {
            // Kunci agar tidak diproses dua kali bersamaan
            $req = PasswordResetRequest::whereKey($resetRequest->id)->lockForUpdate()->first();

            if ($req->status !== 'menunggu') {
                $error = 'Permintaan ini sudah diproses.';
                return;
            }

            if ($req->created_at->lt(now()->subDays(PasswordResetRequest::EXPIRE_DAYS))) {
                $req->update(['status' => 'kedaluwarsa']);
                $error = 'Permintaan sudah kedaluwarsa. Minta pengguna mengirim permintaan baru.';
                return;
            }

            $user = User::whereKey($req->user_id)->lockForUpdate()->first();

            if (! $user || ! $user->is_active) {
                $req->update(['status' => 'ditolak', 'handled_by' => auth()->id(), 'handled_at' => now()]);
                $error = 'Akun tidak ditemukan atau sudah dinonaktifkan, sehingga permintaan ditolak.';
                return;
            }

            if ($user->id === auth()->id()) {
                $error = 'Untuk mengganti kata sandi Anda sendiri, gunakan menu Profil Saya.';
                return;
            }

            $plain = TempPassword::make();

            $user->forceFill([
                'password'             => Hash::make($plain),
                'must_change_password' => true,
                'session_version'      => (int) $user->session_version + 1,
                'remember_token'       => Str::random(60),
            ])->save();

            $req->update(['status' => 'selesai', 'handled_by' => auth()->id(), 'handled_at' => now()]);

            $name  = $user->name;
            $email = $user->email;
        });

        if ($error) {
            return back()->with('error', $error);
        }

        // Kata sandi sementara hanya dititipkan untuk satu kali tampil
        return redirect()->route('reset-requests.index', ['tab' => 'riwayat'])
            ->with('temp_password', ['name' => $name, 'email' => $email, 'password' => $plain]);
    }

    public function reject(PasswordResetRequest $resetRequest)
    {
        if ($resetRequest->status !== 'menunggu') {
            return back()->with('info', 'Permintaan ini sudah diproses.');
        }

        $resetRequest->update(['status' => 'ditolak', 'handled_by' => auth()->id(), 'handled_at' => now()]);

        return back()->with('success', 'Permintaan ditolak.');
    }
}