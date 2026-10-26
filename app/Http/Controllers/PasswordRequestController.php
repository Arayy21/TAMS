<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PasswordRequestController extends Controller
{
    public function create()
    {
        return view('auth.forgot');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email'   => 'required|email|max:150',
            'message' => 'nullable|string|max:255',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
        ]);

        $email = Str::lower(trim($data['email']));

        // Batasi permintaan: per alamat IP dan per email
        foreach ([['forgot-ip:' . $request->ip(), 5, 600], ['forgot-mail:' . $email, 3, 3600]] as [$key, $max, $decay]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));

                return back()->withInput()->withErrors([
                    'email' => "Terlalu banyak permintaan. Coba lagi dalam {$minutes} menit.",
                ]);
            }
        }

        RateLimiter::hit('forgot-ip:' . $request->ip(), 600);
        RateLimiter::hit('forgot-mail:' . $email, 3600);

        $user = User::whereRaw('LOWER(email) = ?', [$email])->where('is_active', 1)->first();

        // Hanya akun aktif yang dicatat, dan satu permintaan menunggu per akun.
        // Jawaban untuk pengguna selalu sama agar email terdaftar tidak bisa ditebak.
        if ($user && ! PasswordResetRequest::pending()->where('user_id', $user->id)->exists()) {
            PasswordResetRequest::create([
                'user_id'      => $user->id,
                'email'        => $user->email,
                'message'      => $data['message'] ?? null,
                'status'       => 'menunggu',
                'requested_ip' => $request->ip(),
            ]);
        }

        return redirect()->route('password.request')->with('status', 'terkirim');
    }
}