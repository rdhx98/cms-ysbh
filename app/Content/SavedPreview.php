<?php

namespace App\Content;

use App\Content\Blocks\BlockSanitizer;

/**
 * Bahan pratinjau untuk record yang SUDAH TERSIMPAN (halaman / artikel / snippet), dibaca dari NILAI MENTAH kolom di database,
 * apa pun bentuknya: dokumen blok dari builder, atau HTML lama dari editor artikel lama (diimpor sebagai satu blok Paragraf).
 * Data dibersihkan sebelum dirender (sama dengan jalur simpan dan pratinjau kanvas). Murni: $record cukup memiliki getRawOriginal(),
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
        $doc = ContentDocument::fromRaw($record->getRawOriginal('content'), $locales);
        $status = (string) ($record->status ?? '');

        return [
            'type' => $type->value,
            'title' => Names::of($record->getRawOriginal('title'), $lang) ?: '#' . $record->getKey(),
            'status' => $status,
            'published' => $type->hasPublishedAt() ? $status === $type->publishedStatus() : $status === 'online',
            'blocks' => BlockSanitizer::clean($doc->blocks, $locales),
            'order' => $doc->order,
            'settings' => $doc->settings,
            'imported' => $doc->imported,
        ];
    }
}
