<?php

namespace App\Content;

/**
 * Bahan pratinjau untuk record yang SUDAH TERSIMPAN (halaman / artikel / snippet), dibaca dari NILAI MENTAH kolom di database,
 * apa pun bentuknya: dokumen blok dari builder, atau HTML lama dari editor artikel lama (diimpor sebagai satu blok Paragraf).
 * Data dibersihkan dan dirakit lewat PublicLookup::document (sama dengan situs publik, termasuk snippet penutup). Murni: $record cukup memiliki getRawOriginal(),
 * getKey(), dan atribut status.
 */
final class SavedPreview
{
    /**
     * @param string[] $locales
     * @return array{type:string,title:string,status:string,published:bool,blocks:array,order:array,settings:array,imported:bool}
     */
    public static function from(ContentType $type, object $record, array $locales, string $lang): array
    {
        // Dirakit seperti di situs publik: snippet sisipan dan snippet penutup ikut (bukan untuk isi sebuah snippet itu sendiri),
        // sehingga pratinjau tersimpan = yang dilihat pengunjung.
        $doc = PublicLookup::document($record->getRawOriginal('content'), $locales, $type !== ContentType::Snippet);
        $status = (string) ($record->status ?? '');

        return [
            'type' => $type->value,
            'title' => Names::of($record->getRawOriginal('title'), $lang) ?: '#' . $record->getKey(),
            'status' => $status,
            'published' => $type->hasPublishedAt() ? $status === $type->publishedStatus() : $status === 'online',
            'blocks' => $doc['blocks'],
            'order' => $doc['order'],
            'settings' => $doc['settings'],
            'imported' => $doc['imported'],
        ];
    }
}
