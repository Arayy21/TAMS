<?php

namespace App\Support;

class TempPassword
{
    // Kata sandi acak yang mudah dibaca (tanpa huruf yang mirip: l, I, 1, O, 0)
    public static function make(int $length = 10): string
    {
        $upper  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower  = 'abcdefghjkmnpqrstuvwxyz';
        $digits = '23456789';
        $all    = $upper . $lower . $digits;

        do {
            $out = '';
            for ($i = 0; $i < $length; $i++) {
                $out .= $all[random_int(0, strlen($all) - 1)];
            }
        } while (! preg_match('/[A-Z]/', $out) || ! preg_match('/[a-z]/', $out) || ! preg_match('/[2-9]/', $out));

        return $out;
    }
}