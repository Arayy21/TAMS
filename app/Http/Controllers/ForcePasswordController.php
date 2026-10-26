<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForcePasswordController extends Controller
{
    public function show(Request $request)
    {
        if (! $request->user()->must_change_password) {
            return redirect('/');
        }

        return view('auth.force-password');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required'  => 'Kata sandi baru wajib diisi.',
            'password.min'       => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        if (Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Kata sandi baru harus berbeda dari kata sandi sementara.',
            ]);
        }

        $user->forceFill([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
            'session_version'      => (int) $user->session_version + 1,
            'remember_token'       => Str::random(60),
        ])->save();

        // Sesi ini tetap berlaku, sesi lain akan keluar
        $request->session()->put('session_version', (int) $user->session_version);

        return redirect('/')->with('success', 'Kata sandi baru berhasil disimpan. Selamat datang kembali.');
    }
}