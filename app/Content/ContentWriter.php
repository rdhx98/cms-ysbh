<?php

namespace App\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Mengisi record dari state editor. Satu tempat, supaya "apa yang disimpan untuk jenis apa" bisa diuji tanpa Livewire.
 * Tidak menyimpan (save() dipanggil pemanggil) dan tidak memvalidasi (lihat ContentRules).
 */
final class ContentWriter
{
    /**
     * @param array{titles:array,slug:array,meta_title:array,meta_description:array,status:?string,blocks:array,order:array,settings:array,
     *              key:string,description:string,is_closing:bool,sort_order:int,category_id:?int,user_id:int|string|null} $state
     */
    public static function fill(Model $record, ContentType $type, array $state): Model
    {
        $record->title = $state['titles'];
        $record->status = $state['status'];
        // fromRaw() membersihkan: ID hantu di urutan dibuang, pengaturan bawaan ditambahkan, 'settings' yang terselip dipindah.
        // (Konstruktor ContentDocument TIDAK membersihkan.) Blok yatim sengaja tidak dipangkas: lihat panel debug.
        $record->content = ContentDocument::fromRaw([
            'blocks' => $state['blocks'], 'order' => $state['order'], 'settings' => $state['settings'],
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
        }

        // Waktu terbit diisi SEKALI, saat pertama kali berstatus terbit; menyimpan ulang tidak menggesernya
        if ($type->hasPublishedAt() && $state['status'] === $type->publishedStatus() && $record->published_at === null) {
            $record->published_at = Carbon::now();
        }

        return $record;
    }
}