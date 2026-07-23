<?php
use Illuminate\Support\Str;
use App\Models\User;

public static function generateUsername($fullName)
{
    // 1. Bersihkan spasi dan ubah huruf kecil
    $cleanName = strtolower(trim(preg_replace('/\s+/', ' ', $fullName)));
    $words = explode(' ', $cleanName);
    $wordCount = count($words);
    
    $firstWord = $words[0];
    $lastWord = $words[$wordCount - 1];
    
    // --- SKENARIO UTAMA ---
    if ($wordCount === 1) {
        // Jika 1 kata: j + jordano = jjordano
        $baseUsername = substr($firstWord, 0, 1) . $firstWord;
    } else {
        // Jika 2 kata atau lebih: j + ambatukam = jambatukam
        $baseUsername = substr($firstWord, 0, 1) . $lastWord;
    }
    
    // Hapus karakter unik (tanda petik, dll)
    $username = preg_replace('/[^a-z0-9]/', '', $baseUsername);
    
    // Jika belum ada di database, langsung gunakan ini!
    if (!User::where('username', $username)->exists()) {
        return $username;
    }


    // --- SKENARIO ALTERNATIF (Pencegahan Duplikat Tanpa Angka) ---
    if ($wordCount > 2) {
        // Logika > 2 kata: Karakter 1 kata pertama + Karakter 1 kata kedua + Kata terakhir
        // j + m + ambatukam = jmambatukam
        $fallbackBase = substr($firstWord, 0, 1) . substr($words[1], 0, 1) . $lastWord;
    } else {
        // Logika <= 2 kata: Karakter 1 & 2 kata pertama + Kata terakhir
        // jo + ambatukam = joambatukam
        // (Atau 'jo' + 'jordano' = jojordano jika hanya 1 kata)
        $fallbackBase = substr($firstWord, 0, 2) . ($wordCount > 1 ? $lastWord : $firstWord);
    }

    $username = preg_replace('/[^a-z0-9]/', '', $fallbackBase);

    // Cek lagi apakah yang alternatif ini masih kosong?
    if (!User::where('username', $username)->exists()) {
        return $username;
    }


    // --- PERTAHANAN TERAKHIR (The Absolute Last Resort) ---
    // Berlaku JIKA ada 3 orang bernama persis sama:
    // Jordano Ambatukam -> jambatukam (User 1)
    // Jordano Ambatukam -> joambatukam (User 2)
    // Jordano Ambatukam -> joambatukam1 (User 3)
    $counter = 1;
    while (User::where('username', $username)->exists()) {
        $username = preg_replace('/[^a-z0-9]/', '', $fallbackBase) . $counter;
        $counter++;
    }
    
    return $username;
}
?>
