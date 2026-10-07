<?php

namespace App\Editor\Blocks;

use App\Editor\BlockType;

/**
 * Kontrak sebuah blok sebagai MODUL: satu kelas di app/Editor/Blocks/<Nama>Block.php (+ satu komponen tampilan publik
 * resources/views/components/blocks/render/<tipe>.blade.php). Modul ditemukan OTOMATIS (App\Editor\Modules): menambah blok baru
 * tidak lagi mengubah registri, palet, atau pembersih; cukup menambah berkas.
 */
interface BlockModule
{
    /** Definisi untuk registri/inspektur: tipe, label, ikon, kontrol, dan nilai bawaan (kind = 'block'). */
    public static function definition(): BlockType;

    /**
     * Di mana blok ini boleh ditambahkan.
     *
     * @return array{group?:string,root?:bool,columns?:bool} group: judul grup di menu ("Konten"/"Layout");
     *                                                       root: boleh di tingkat atas; columns: boleh di dalam kolom
     */
    public static function placement(): array;

    /**
     * Membersihkan data blok SEBELUM disimpan dan SEBELUM dirender. Prinsip: nilai tak sah diganti bawaan (tidak ditolak);
     * data yang dicetak sebagai kelas CSS atau href tidak pernah dipercaya apa adanya. Harus idempoten.
     *
     * @param string[] $locales
     */
    public static function sanitize(array $data, array $locales): array;
}