<?php

namespace App\Content;

/**
 * Jenis konten yang diedit oleh komponen `content.builder`.
 * Satu-satunya tempat yang tahu: model apa, parameter rute apa, status apa, dan kunci terjemahan judulnya.
 *
 * Tipe ditentukan dari NAMA RUTE (page.create, article.edit, ...) hanya saat mount();
 * setelah itu disimpan di properti #[Locked], karena request update Livewire tidak lagi memakai rute asli.
 */
enum ContentType: string
{
    case Page = 'page';
    case Article = 'article';
    case Snippet = 'snippet';

    /** "page.create" -> Page, "article.write" -> Article, "v2.snippet.edit" -> Snippet (awalan lain diabaikan). */
    public static function fromRouteName(?string $name): self
    {
        foreach (explode('.', (string) $name) as $segment) {
            if ($type = self::tryFrom($segment)) {
                return $type;
            }
        }

        throw new \InvalidArgumentException("Rute '{$name}' bukan rute content.builder.");
    }

    /** @return class-string<\Illuminate\Database\Eloquent\Model> */
    public function modelClass(): string
    {
        return match ($this) {
            self::Page => \App\Models\Page::class,
            self::Article => \App\Models\Post::class,
            self::Snippet => \App\Models\Snippet::class,
        };
    }

    /** Nama parameter rute yang berisi record (rute edit). */
    public function routeParam(): string
    {
        return match ($this) {
            self::Page => 'page',
            self::Article => 'post',
            self::Snippet => 'snippet',
        };
    }

    public function editRoute(): string
    {
        return $this->value . '.edit';
    }

    /**
     * Nama rute tujuan yang MEMPERTAHANKAN awalan rute saat ini:
     *   page + "v2.page.create" + "edit"  ->  "v2.page.edit"
     * Tanpa ini, redirect setelah simpan pertama memakai "page.edit" = rute editor LAMA.
     */
    public function routeNameFor(string $currentRouteName, string $action): string
    {
        $segments = explode('.', $currentRouteName);
        $at = array_search($this->value, $segments, true);
        $prefix = $at === false ? [] : array_slice($segments, 0, $at);

        return implode('.', [...$prefix, $this->value, $action]);
    }

    /** Status yang berarti "sudah terbit" (mengisi published_at sekali). */
    public function publishedStatus(): string
    {
        return $this === self::Article ? 'published' : 'online';
    }

    /** Snippet tidak punya published_at. */
    public function hasPublishedAt(): bool
    {
        return $this !== self::Snippet;
    }

    /** Nama tabel (untuk aturan unik). */
    public function table(): string
    {
        return match ($this) {
            self::Page => 'pages',
            self::Article => 'posts',
            self::Snippet => 'snippets',
        };
    }

    public function indexRoute(): string
    {
        return match ($this) {
            self::Page => 'page.index',
            self::Article => 'article.index',
            self::Snippet => 'snippet.index',
        };
    }

    /** @return string[] status yang sah untuk jenis ini */
    public function statuses(): array
    {
        return match ($this) {
            self::Page, self::Snippet => ['offline', 'online'],
            self::Article => ['draft', 'review', 'published', 'scheduled', 'archived', 'rejected'],
        };
    }

    /** Status awal record baru. (Editor lama memakai 'draft' untuk halaman, padahal aturannya hanya offline/online.) */
    public function defaultStatus(): string
    {
        return match ($this) {
            self::Page, self::Snippet => 'offline',
            self::Article => 'draft',
        };
    }

    /** Label status untuk UI. */
    public function statusLabels(): array
    {
        return match ($this) {
            self::Page, self::Snippet => ['offline' => 'Offline (draf)', 'online' => 'Online (terbit)'],
            self::Article => ['draft' => 'Draf', 'review' => 'Ditinjau', 'published' => 'Terbit', 'scheduled' => 'Terjadwal', 'archived' => 'Arsip', 'rejected' => 'Ditolak'],
        };
    }

    public function statusRule(): string
    {
        return 'required|in:' . implode(',', $this->statuses());
    }

    /** Snippet adalah potongan konten: tidak punya alamat sendiri, jadi tanpa slug dan metadata SEO. */
    public function usesSlug(): bool
    {
        return $this !== self::Snippet;
    }

    public function usesMeta(): bool
    {
        return $this !== self::Snippet;
    }

    /** Snippet dikenali lewat `key` yang stabil (mis. "donasi"), bukan slug. */
    public function usesKey(): bool
    {
        return $this === self::Snippet;
    }

    /** Kunci terjemahan untuk <title>, mis. ui.title.page.create / ui.title.article.edit. */
    public function titleKey(bool $editing): string
    {
        return 'ui.title.' . $this->value . '.' . ($editing ? 'edit' : 'create');
    }

    /** Teks cadangan (Indonesia) bila kunci terjemahan belum ditambahkan ke lang/{id,en}/ui.php. */
    public function headerFallback(bool $editing): string
    {
        return match ($this) {
            self::Page => $editing ? 'Edit Halaman' : 'Halaman Baru',
            self::Article => $editing ? 'Edit Artikel' : 'Artikel Baru',
            self::Snippet => $editing ? 'Edit Snippet' : 'Snippet Baru',
        };
    }

    public function titleFallback(bool $editing, string $title = '…'): string
    {
        return match ($this) {
            self::Page => $editing ? "Ubah Halaman: {$title}" : 'Buat Halaman',
            self::Article => $editing ? "Ubah Artikel: {$title}" : 'Tulis Artikel',
            self::Snippet => $editing ? "Ubah Snippet: {$title}" : 'Buat Snippet',
        };
    }

    /** Kunci terjemahan untuk judul di header editor (yang sekarang tampil mentah sebagai ui.header.write_page). */
    public function headerKey(bool $editing): string
    {
        return 'ui.header.' . $this->value . '.' . ($editing ? 'edit' : 'create');
    }
}