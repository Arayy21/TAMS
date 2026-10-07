<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    private const ROLES = ['admin', 'pengelola', 'staff'];

    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->q, fn ($q, $v) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")
            ))
            ->when($request->role, fn ($q, $v) => $q->where('role', $v))
            ->when($request->status === 'aktif', fn ($q) => $q->where('is_active', 1))
            ->when($request->status === 'nonaktif', fn ($q) => $q->where('is_active', 0))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total'    => User::count(),
            'aktif'    => User::where('is_active', 1)->count(),
            'nonaktif' => User::where('is_active', 0)->count(),
        ];

        return view('users.index', compact('users', 'summary'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:users,email',
            'role'     => ['required', Rule::in(self::ROLES)],
            'password' => 'required|string|min:8|confirmed',
        ], $this->messages());

        $user = new User();
        $user->forceFill([
            'name'      => $data['name'],
            'email'     => strtolower($data['email']),
            'role'      => $data['role'],
            'password'  => Hash::make($data['password']),
            'is_active' => $request->boolean('is_active'),
        ])->save();

        return redirect()->route('users.index')
            ->with('success', "Akun {$user->name} berhasil dibuat.");
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role'     => ['required', Rule::in(self::ROLES)],
            'password' => 'nullable|string|min:8|confirmed',
        ], $this->messages());

        $active = $request->boolean('is_active');

        // Tidak boleh mengubah peran atau status akun sendiri
        if ($user->id === auth()->id() && ($data['role'] !== $user->role || ! $active)) {
            throw ValidationException::withMessages([
                'role' => 'Anda tidak dapat mengubah peran atau menonaktifkan akun Anda sendiri.',
            ]);
        }

        // Harus selalu ada minimal satu Admin aktif
        $losesAdmin = $user->role === 'admin' && $user->is_active
            && ($data['role'] !== 'admin' || ! $active);

        if ($losesAdmin && ! User::where('role', 'admin')->where('is_active', 1)->where('id', '!=', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'role' => 'Harus ada minimal satu Admin yang aktif. Jadikan akun lain sebagai Admin terlebih dahulu.',
            ]);
        }

        $attributes = [
            'name'      => $data['name'],
            'email'     => strtolower($data['email']),
            'role'      => $data['role'],
            'is_active' => $active,
        ];

        if (! empty($data['password'])) {
            $attributes['password'] = Hash::make($data['password']);
        }

        $user->forceFill($attributes)->save();

        return redirect()->route('users.index')
            ->with('success', "Akun {$user->name} berhasil diperbarui.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->hasActivity()) {
            return back()->with('error', 'Akun ini sudah memiliki riwayat aktivitas sehingga tidak dapat dihapus. Nonaktifkan saja agar riwayatnya tetap utuh.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')->with('success', "Akun {$name} berhasil dihapus.");
    }

    private function messages(): array
    {
        return [
            'name.required'      => 'Nama wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email sudah terdaftar.',
            'role.required'      => 'Peran wajib dipilih.',
            'password.required'  => 'Kata sandi wajib diisi.',
            'password.min'       => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ];
    }
}