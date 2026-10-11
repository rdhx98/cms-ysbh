<?php

namespace App\Content;

use App\Content\Blocks\BlockSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Mengisi record dari state editor. Satu tempat, supaya "apa yang disimpan untuk jenis apa" bisa diuji tanpa Livewire.
 * Tidak menyimpan (save() dipanggil pemanggil) dan tidak memvalidasi (lihat ContentRules).
 */
final class ContentWriter
{
    /**
     * @param array{locales?:string[],titles:array,slug:array,meta_title:array,meta_description:array,status:?string,blocks:array,order:array,settings:array,
     *              key:string,description:string,is_closing:bool,sort_order:int,category_id:?int,user_id:int|string|null} $state
     */
    public static function fill(Model $record, ContentType $type, array $state): Model
    {
        $record->title = $state['titles'];
        $record->status = $state['status'];
        // fromRaw() membersihkan: ID hantu di urutan dibuang, pengaturan bawaan ditambahkan, 'settings' yang terselip dipindah.
        // (Konstruktor ContentDocument TIDAK membersihkan.) Blok yatim sengaja tidak dipangkas: lihat panel debug.
        $record->content = ContentDocument::fromRaw([
            // Data blok dibersihkan di SERVER (kelas, tautan, panjang): permintaan Livewire bisa dibuat tangan.
            'blocks' => BlockSanitizer::clean($state['blocks'], $state['locales'] ?? ['id', 'en']), 'order' => $state['order'], 'settings' => $state['settings'],
        ])->toArray();

        if ($type->usesSlug()) {
            $record->slug = $state['slug'];
        }
        if ($type->usesMeta()) {
            $record->meta_title = $state['meta_title'];
            $record->meta_description = $state['meta_description'];
        }

        if ($type->usesKey()) {
            // Model Snippet menormalkan key dan menolak isi yang memuat snippet lain
            $record->key = $state['key'];
            $record->description = $state['description'] !== '' ? $state['description'] : null;
            $record->is_closing = $state['is_closing'];
            $record->sort_order = $state['sort_order'];
            $record->created_by ??= $state['user_id'];
            $record->updated_by = $state['user_id'];
        }

        if ($type === ContentType::Article) {
            $record->category_id = $state['category_id'];
            $record->user_id ??= $state['user_id'];
            // Kolom gambar sampul wajib terisi; editor lama memakai berkas bawaan bila belum ada pilihan
            if (empty($record->featured_image)) {
                $record->featured_image = 'default.webp';
            }
        }

        // Waktu terbit diisi SEKALI, saat pertama kali berstatus terbit; menyimpan ulang tidak menggesernya
        if ($type->hasPublishedAt() && $state['status'] === $type->publishedStatus() && $record->published_at === null) {
            $record->published_at = Carbon::now();
        }

        return $record;
    }

    /**
     * Relasi yang baru bisa disimpan SETELAH record punya id (panggil sesudah save()).
     * Artikel: tag. ID tag terpilih dikembalikan supaya state editor bisa memakai ID (bukan nama) untuk tag yang baru dibuat.
     *
     * @return int[]|null ID tag setelah sinkron, atau null bila jenis ini tidak punya tag
     */
    public static function syncRelations(Model $record, ContentType $type, array $state, array $locales = ['id', 'en']): ?array
    {
        if ($type !== ContentType::Article) {
            return null;
        }

        $ids = TagResolver::resolve($state['tags'] ?? [], $locales);
        $record->tags()->sync($ids);

        return $ids;
    }
}
