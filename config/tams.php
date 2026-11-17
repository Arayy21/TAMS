<?php

return [
    // Awalan dan jumlah digit nomor kode aset, contoh: TCH-000001
    'asset_prefix' => env('ASSET_CODE_PREFIX', 'TCH'),
    'asset_digits' => (int) env('ASSET_CODE_DIGITS', 6),

    // Kontak admin yang tampil di halaman "Lupa kata sandi" (kosongkan bila tidak ingin ditampilkan)
    'admin_contact' => env('ADMIN_CONTACT'),
];