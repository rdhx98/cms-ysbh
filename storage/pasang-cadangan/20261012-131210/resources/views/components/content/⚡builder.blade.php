<?php

use App\Content\ArticleCover;
use App\Content\AuditTrail;
use App\Content\ArticleTransitions;
use App\Content\ArticleWorkflow;
use App\Content\ContentDocument;
use App\Content\ContentRules;
use App\Content\ContentType;
use App\Content\Blocks\MediaUrls;
use App\Content\ContentWriter;
use App\Content\JsonSql;
use App\Content\LocaleMap;
use App\Content\Names;
use App\Content\PreviewStore;
use App\Content\BlockExcerpt;
use App\Content\ChatAvatar;
use App\Content\EditorPresence;
use App\Content\ReviewPins;
use App\Editor\BlockRegistry;
use App\Livewire\Traits\HasContentBlocks;
use App\Livewire\Traits\ManagesBlockStructure;
use App\Livewire\Traits\SearchesInternalPages;
use App\Livewire\Traits\SearchesLinkTargets;
use App\Livewire\Traits\WithNotifications;
use App\Models\ArticleReview;
use App\Models\Media;
use App\Models\PostAuthor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * content.builder — satu editor untuk Page, Article (Post), dan Snippet.
 *
 * Memuat record, judul halaman per rute, pengaturan halaman (judul/slug/status/SEO; snippet: kunci/penutup/urutan), simpan.
 * Logika yang bisa diuji dipisah dari komponen: ContentType, ContentDocument, ContentRules (validasi), ContentWriter (isi record),
 * Slug. Komponen ini sendiri belum dijalankan di Livewire oleh saya (sandbox tanpa Livewire).
 */
new class extends Component {
  use WithFileUploads;
  use WithNotifications;
  use HasContentBlocks;
  use ManagesBlockStructure;
  use SearchesInternalPages; // searchInternalPages(): tautan di dalam teks berformat (Tiptap)
  use SearchesLinkTargets; // searchLinkTargets(): pemilih tautan blok Tombol

  /** Jenis konten. Ditentukan SEKALI di mount() dari nama rute; request update Livewire tidak punya rute aslinya. */
  #[Locked]
  public string $type = "page";

  /** Nama rute saat mount, mis. "v2.page.create". Dipakai agar redirect mempertahankan awalan ("v2.page.edit"). */
  #[Locked]
  public string $routeName = "";

  public ?Model $record = null;

  public array $activeLocales = [];

  // Dulu `page_title` (kemungkinan karena `$title` bentrok dengan slot `title` milik layout). Bentuk jamak = netral.
  public array $titles = [];
  public array $slug = [];
  public array $meta_title = [];
  public array $meta_description = [];

  public array $content = [];
  public array $blockOrder = [];
  public array $settings = [];
  public ?string $status = null;

  // Khusus artikel. tags = larik campuran: angka = ID tag lama, teks = nama tag baru (lihat TagResolver)
  public ?int $category_id = null;
  public array $tags = [];
  /** Thumbnail artikel (rilis 40): "" = belum ada | ID media (angka) | nama berkas lama. Dinormalkan server sebelum disimpan (ArticleCover). */
  public string $featured_image = "";

  /**
   * Sidik jari isi record di database saat dimuat / terakhir disimpan oleh sesi ini (rilis 49 rev 3). Dibandingkan dengan keadaan database
   * SAAT menyimpan: beda = orang lain menyimpan lebih dulu, jadi simpan ditolak (tidak diam-diam menimpa). Harus #[Locked]: $record
   * dimuat ulang dari database di setiap permintaan, jadi hanya properti yang bisa menyimpan keadaan "saat dibuka".
   */
  #[Locked]
  public string $baseHash = "";

  /** '' | 'changed' — simpan terakhir ditolak karena record sudah diubah orang lain (menampilkan spanduk Muat ulang / Timpa). */
  #[Locked]
  public string $conflict = "";

  /** true bila isi dokumen BARU dibentuk dari HTML lama (artikel editor lama) dan belum pernah disimpan sebagai blok. */
  #[Locked]
  public bool $importedLegacy = false;

  // Khusus snippet (ContentType::usesKey()). Tak dipakai untuk halaman/artikel.
  public string $key = "";
  public string $description = "";
  public bool $is_closing = false;
  public int $sort_order = 0;

  // ------------------------------------------------------------------ memuat

  public function mount(): void
  {
    // Hapus & duplikat di outline memakai removeBlock()/duplicateBlock() dari trait. Versi asli menyisakan anak
    // yatim (hapus) dan berbagi anak dengan salinannya (duplikat): hentikan dengan pesan jelas, bukan merusak data.
    if (
      !method_exists($this, "deleteBlockTree") ||
      !method_exists($this, "cloneBlockTree")
    ) {
      throw new \LogicException(
        "HasContentBlocks belum di-patch (deleteBlockTree/cloneBlockTree tidak ada). Pasang patch trait dulu.",
      );
    }

    $this->routeName = (string) request()->route()?->getName();
    $type = ContentType::fromRouteName($this->routeName);
    $this->type = $type->value;

    $this->activeLocales = config("app.supported_locales", ["id", "en"]);

    $this->record = $this->resolveRecord($type);

    // Artikel: penulis hanya membuka artikelnya sendiri (admin/editor: semua). Pengecekan SERVER; menyembunyikan tombol di daftar saja tidak cukup.
    if (
      $type === ContentType::Article &&
      $this->record->exists &&
      !ArticleWorkflow::canView(
        $this->workflowActor(),
        ArticleTransitions::authorsOf($this->record),
      )
    ) {
      abort(403, "Artikel ini milik penulis lain.");
    }
    // TODO: $this->authorize($this->record->exists ? 'update' : 'create', $this->record) untuk halaman dan snippet

    $this->fillFromRecord($type);
    $this->baseHash = $this->fingerprint();
  }

  /** Computed, bukan method publik (aksi yang bisa dipanggil browser) dan bukan private (tak terjangkau dari view). */
  #[Computed]
  public function contentType(): ContentType
  {
    return ContentType::from($this->type);
  }

  /**
   * Record dari parameter rute. Binding model untuk komponen halaman Livewire HANYA terjadi lewat tipe parameter mount()
   * (Livewire menunjuk ulang aksi rute ke mount() lalu menjalankan substituteImplicitBindings). mount() ini tanpa
   * parameter, jadi parameter rute tiba sebagai string mentah ("5"), bukan model: tanpa penanganan ini, rute edit
   * diam-diam masuk mode BUAT. Karena itu: model -> pakai; kosong -> record baru; selain itu -> cari lewat id.
   */
  private function resolveRecord(ContentType $type): Model
  {
    $class = $type->modelClass();
    $param = request()->route($type->routeParam());

    if ($param instanceof Model) {
      return $param;
    }
    if ($param === null || $param === "") {
      return new $class();
    }

    return $class::query()->findOrFail($param);
  }

  /**
   * Nilai MENTAH kolom dari database. Jangan memakai $record->toArray()/atribut ber-cast: pada kolom lama yang berisi
   * string (editor artikel lama: HTML polos) cast array menghasilkan null, dan menyimpan dokumen kosong itu MENGHAPUS isinya.
   */
  private function raw(string $column): mixed
  {
    return $this->record->exists
      ? $this->record->getRawOriginal($column)
      : null;
  }

  private function fillFromRecord(ContentType $type): void
  {
    $this->titles = LocaleMap::from($this->raw("title"), $this->activeLocales);
    if ($type->usesSlug()) {
      $this->slug = LocaleMap::from($this->raw("slug"), $this->activeLocales);
    }
    if ($type->usesMeta()) {
      $this->meta_title = LocaleMap::from(
        $this->raw("meta_title"),
        $this->activeLocales,
      );
      $this->meta_description = LocaleMap::from(
        $this->raw("meta_description"),
        $this->activeLocales,
      );
    }
    if ($type->usesKey()) {
      $this->key = (string) ($this->record->key ?? "");
      $this->description = (string) ($this->record->description ?? "");
      $this->is_closing = (bool) ($this->record->is_closing ?? false);
      $this->sort_order = (int) ($this->record->sort_order ?? 0);
    }
    $this->status = $this->record->status ?: $type->defaultStatus();

    if ($type === ContentType::Article) {
      $this->category_id = $this->record->category_id
        ? (int) $this->record->category_id
        : null;
      $this->tags = $this->record->exists
        ? $this->record
          ->tags()
          ->pluck("tags.id")
          ->map(fn($id) => (int) $id)
          ->all()
        : [];
      $this->featured_image = ArticleCover::stateFromColumn(
        $this->record->featured_image ?? null,
      );
    }

    // Satu-satunya pembaca `content`. HTML lama (artikel) diimpor sebagai satu blok Paragraf, bukan dibuang.
    $doc = ContentDocument::fromRaw(
      $this->raw("content"),
      $this->activeLocales,
    );
    $this->content = MediaUrls::resolve($doc->blocks); // alamat gambar disegarkan dari media_id (rilis 45)
    $this->blockOrder = $doc->order;
    $this->settings = $doc->settings;
    $this->importedLegacy = $doc->imported;

    // Konten baru: mulai dengan satu Judul, memakai satu-satunya sumber nilai bawaan
    if (!$this->record->exists && empty($this->content)) {
      $id = "blk_" . uniqid();
      $this->content[$id] = [
        "id" => $id,
        "type" => "heading",
        "data" => $this->getDefaultDataForType("heading"),
      ];
      $this->blockOrder = [$id];
    }
  }

  /** Admin/editor boleh menerbitkan; penulis biasa hanya draf dan mengajukan tinjauan (sama seperti editor artikel lama). */
  private function canPublish(): bool
  {
    $user = auth()->user();

    return $user &&
      method_exists($user, "hasRole") &&
      $user->hasRole(["admin", "editor"]);
  }

  /** Pengguna saat ini untuk ArticleWorkflow. */
  private function workflowActor(): array
  {
    return ["id" => auth()->id(), "reviewer" => $this->canPublish()];
  }

  /** Boleh admin/editor menyetujui artikel yang ia tulis sendiri? Bawaan ya; matikan lewat config('cms.article_self_review'). */
  private function workflowSelfReview(): bool
  {
    return (bool) config("cms.article_self_review", true);
  }

  /** Peninjau boleh menyunting isi di semua status (perilaku sebelum rilis 49)? Bawaan tidak; nyalakan lewat config('cms.article_reviewer_edits'). */
  private function workflowReviewerEdits(): bool
  {
    return (bool) config("cms.article_reviewer_edits", false);
  }

  /** Artikel: boleh menyimpan perubahan isi sekarang? (draf: penulisnya; ditinjau/ditolak: tidak seorang pun; terbit dst.: peninjau). Halaman/snippet: ya. */
  private function workflowCanEdit(): bool
  {
    if ($this->contentType !== ContentType::Article) {
      return true;
    }

    return ArticleWorkflow::canEdit(
      $this->record->exists ? $this->record->status : ArticleWorkflow::DRAFT,
      $this->workflowActor(),
      ArticleTransitions::authorsOf($this->record),
      $this->workflowReviewerEdits(),
    );
  }

  /** Kolom isi yang menentukan sidik jari (bukan updated_at/penghitung, supaya aktivitas yang tidak mengubah isi tidak jadi "konflik"). Tag tidak ikut. */
  private const FINGERPRINT_COLUMNS = [
    "title",
    "slug",
    "content",
    "meta_title",
    "meta_description",
    "status",
    "category_id",
    "featured_image",
    "published_at",
    "key",
    "description",
    "is_closing",
    "sort_order",
  ];

  /**
   * Sidik jari isi record menurut DATABASE saat ini ('' bila record belum tersimpan atau sudah tidak ada). Selalu dibaca ulang dari database
   * (bukan dari atribut di memori): MySQL menormalkan kolom JSON saat dibaca, sehingga nilai di memori sesudah simpan tidak sama dengan
   * hasil bacaan berikutnya dan akan menimbulkan "konflik" palsu.
   */
  private function fingerprint(): string
  {
    if (!$this->record?->exists) {
      return "";
    }
    $row = $this->record
      ->newQuery()
      ->withoutGlobalScopes()
      ->whereKey($this->record->getKey())
      ->first();
    if ($row === null) {
      return "";
    }
    $parts = [];
    foreach (self::FINGERPRINT_COLUMNS as $column) {
      $value = $row->getRawOriginal($column);
      $parts[$column] = $value === null ? null : (string) $value;
    }

    return sha1(
      (string) json_encode(
        $parts,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
      ),
    );
  }

  /**
   * true (dan spanduk konflik menyala) bila database sudah berubah sejak record ini dibuka / terakhir disimpan. Record baru dan sesi lama
   * tanpa sidik jari tidak diperiksa. Celah yang diketahui: pemeriksaan dan penyimpanan bukan satu transaksi, jadi dua simpanan dalam
   * selisih beberapa milidetik masih bisa lolos keduanya.
   */
  private function conflictDetected(): bool
  {
    if (!$this->record->exists || $this->baseHash === "") {
      return false;
    }
    if (hash_equals($this->baseHash, $this->fingerprint())) {
      $this->conflict = "";

      return false;
    }
    $this->conflict = "changed";
    $this->notify(
      "Konten ini sudah diubah orang lain sejak Anda membukanya. Simpan dibatalkan agar pekerjaan mereka tidak tertimpa.",
      "error",
    );

    return true;
  }

  /** "Timpa dengan versi saya": terima keadaan database sekarang sebagai dasar, lalu simpan seperti biasa (semua pemeriksaan wewenang/validasi tetap berlaku). */
  public function overwrite(): void
  {
    if (!$this->record->exists || $this->conflict === "") {
      return;
    }
    $this->baseHash = $this->fingerprint();
    $this->conflict = "";
    $this->save();
  }

  /**
   * Bahan tab Review artikel (kosong untuk halaman/snippet): status, penulis, aksi yang tersedia bagi pengguna ini, dan percakapan
   * (`chat`: pesan dan peristiwa status dari `article_reviews`, terlama dulu, maks. 100 terakhir). Semua keputusan diambil ArticleWorkflow;
   * tampilan hanya menampilkan. Peran pada gelembung ("Penulis utama" / "Penulis" / "Peninjau") menurut keanggotaan SEKARANG.
   */
  #[Computed]
  public function workflow(): array
  {
    if ($this->contentType !== ContentType::Article) {
      return [];
    }

    $exists = (bool) $this->record->exists;
    $status = $exists
      ? ArticleWorkflow::normalize($this->record->status)
      : ArticleWorkflow::DRAFT;
    $mainId = $exists ? ($this->record->user_id ?: null) : null;
    $owner = ArticleTransitions::authorsOf($this->record); // penulis utama + penulis lain; null = artikel baru
    $ownerIds = $owner ?? [];
    $actor = $this->workflowActor();
    // Aturan empat mata (rilis 41): pengaju terakhir dibaca hanya bila aturannya menyala (tanpa kueri tambahan bila tidak)
    $submitter = $this->workflowSelfReview()
      ? null
      : ArticleTransitions::submitterOf($this->record);

    $chat = [];
    $authors = [];
    $ready = true;

    // Pesan berpenanda blok (rilis 50): dihitung dari SELURUH riwayat (bukan hanya 100 baris terakhir), terhadap blok yang ada di isi sekarang.
    // Terpisah dari percakapan: bila migrasi rilis 50 belum dijalankan, percakapan tetap jalan dan hanya penanda yang belum aktif.
    $liveIds = $exists ? $this->liveBlockIds() : [];
    $pinData = ["pins" => [], "pending" => 0, "blocks" => []];
    $pinsReady = true;
    if ($exists) {
      try {
        $pinRows = ArticleReview::query()
          ->where("post_id", $this->record->getKey())
          ->where(
            fn($q) => $q
              ->where(
                fn($q2) => $q2
                  ->where("action", "note")
                  ->whereNotNull("block_id"),
              )
              ->orWhereIn("action", ["resolve", "reopen"]),
          )
          ->orderBy("id")
          ->get([
            "id",
            "action",
            "user_id",
            "note",
            "block_id",
            "block_type",
            "block_excerpt",
            "ref_id",
            "created_at",
          ])
          ->map(
            fn($r) => array_merge($r->getAttributes(), [
              "created_at" => $r->created_at?->format("d M Y H:i"),
            ]),
          )
          ->all();
        $pinData = ReviewPins::summarize($pinRows, $liveIds);
      } catch (\Throwable) {
        $pinsReady = false; // kolom penanda belum ada: php artisan migrate
      }
    }
    $typeLabel = fn(string $t) => BlockRegistry::block($t)?->label ??
      ($t !== "" ? $t : "Blok");

    if ($exists) {
      try {
        $rows = ArticleReview::query()
          ->where("post_id", $this->record->getKey())
          ->orderByDesc("id")
          ->limit(100)
          ->get()
          ->reverse()
          ->values();
        $users = \App\Models\User::query()
          ->whereIn(
            "id",
            $rows
              ->pluck("user_id")
              ->filter()
              ->merge($ownerIds)
              ->merge(
                array_filter(array_column($pinData["pins"], "resolvedBy")),
              )
              ->unique()
              ->all(),
          )
          ->get()
          ->keyBy("id");
        $names = $users->map(fn($u) => $u->name);
        $nameOf = fn($id) => $id === null
          ? "Sistem"
          : (string) ($names[$id] ?? "#" . $id);
        $roleOf = function ($id) use ($mainId, $ownerIds): string {
          if ($id === null) {
            return "Sistem";
          }
          if ($mainId !== null && (string) $id === (string) $mainId) {
            return "Penulis utama";
          }

          return in_array((string) $id, $ownerIds, true)
            ? "Penulis"
            : "Peninjau";
        };
        $avatarOf = fn($id) => $id === null
          ? null
          : ChatAvatar::make($nameOf($id), $users[$id] ?? null); // foto profil / inisial (rilis 51)
        foreach ($rows as $row) {
          $uid = $row->user_id ?: null;
          $isAuthorEvent = in_array(
            $row->action,
            ["author-add", "author-remove"],
            true,
          );
          $pin = $pinData["pins"][(int) $row->id] ?? null;
          $refPin = in_array($row->action, ["resolve", "reopen"], true)
            ? $pinData["pins"][(int) ($row->ref_id ?? 0)] ?? null
            : null;
          $chat[] = [
            "id" => (int) $row->id,
            "kind" => $row->action === "note" ? "message" : "event",
            "who" => $nameOf($uid),
            "whoId" => $uid === null ? null : (int) $uid,
            "avatar" => $row->action === "note" ? $avatarOf($uid) : null,
            "mine" =>
              $uid !== null &&
              $actor["id"] !== null &&
              (string) $uid === (string) $actor["id"],
            "role" => $roleOf($uid),
            "action" => (string) $row->action,
            "verb" => ArticleWorkflow::verb((string) $row->action),
            "to" => ArticleWorkflow::normalize($row->to_status),
            "tone" => ArticleWorkflow::tone($row->to_status),
            "note" => $isAuthorEvent ? "" : (string) ($row->note ?? ""),
            "target" => $isAuthorEvent
              ? (string) ($row->note ?? "")
              : ($refPin
                ? "“" . mb_strimwidth($refPin["note"], 0, 60, "…") . "”"
                : ""),
            "pin" => $pin
              ? [
                "blockId" => $pin["blockId"],
                "label" => $typeLabel($pin["type"]),
                "excerpt" => $pin["excerpt"],
                "gone" => $pin["gone"],
                "resolved" => $pin["resolved"],
                "resolvedBy" =>
                  $pin["resolvedBy"] === null
                    ? null
                    : $nameOf($pin["resolvedBy"]),
                "resolvedAt" => $pin["resolvedAt"],
              ]
              : null,
            "at" => $row->created_at?->format("d M Y H:i") ?? "",
            "for" => $row->scheduled_for?->format("d M Y H:i") ?? "",
          ];
        }
        // Pengelompokan (rilis 51): pesan beruntun dari pengirim yang sama membentuk satu kelompok; nama di pesan pertama, foto di pesan terakhir.
        $last = count($chat) - 1;
        foreach ($chat as $i => $item) {
          $same = fn(?array $o) => $o !== null &&
            $o["kind"] === "message" &&
            $item["kind"] === "message" &&
            $o["whoId"] !== null &&
            $o["whoId"] === $item["whoId"];
          $chat[$i]["groupStart"] = !$same($chat[$i - 1] ?? null);
          $chat[$i]["groupEnd"] = !$same($i < $last ? $chat[$i + 1] : null);
        }
        foreach (
          array_values(
            array_unique(
              array_filter(
                array_merge(
                  [$mainId === null ? null : (string) $mainId],
                  $ownerIds,
                ),
              ),
            ),
          )
          as $id
        ) {
          $authors[] = [
            "id" => (int) $id,
            "name" => $nameOf($id),
            "main" => $mainId !== null && (string) $id === (string) $mainId,
          ];
        }
      } catch (\Throwable) {
        $ready = false; // tabel article_reviews belum ada: php artisan migrate
      }
    }

    $canManage =
      $exists &&
      !ArticleWorkflow::isFinal($status) &&
      ArticleWorkflow::canManageAuthors($actor, $mainId);
    foreach ($authors as &$a) {
      $a["canRemove"] = $canManage && !$a["main"];
    }
    unset($a);

    $actions = array_values(
      array_filter(
        ArticleWorkflow::actionsFor(
          $status,
          $actor,
          $owner,
          $this->workflowSelfReview(),
          $submitter,
          $this->workflowReviewerEdits(),
        ),
        fn($a) => !in_array($a["action"], ArticleWorkflow::CHAT_ACTIONS, true), // pesan dan tanda selesai lewat percakapan, bukan tombol
      ),
    );
    $publishedAt = $exists
      ? ArticleTransitions::publishedAt($this->record, Carbon::now())
      : null;

    $blockLabels = [];
    foreach ($liveIds as $bid) {
      $block = $this->content[$bid] ?? [];
      $ex = BlockExcerpt::of(
        is_array($block) ? $block : [],
        $this->activeLocales,
        40,
      );
      $blockLabels[$bid] =
        $typeLabel((string) ($block["type"] ?? "")) .
        ($ex !== "" ? " · “" . $ex . "”" : "");
    }
    $canMessage =
      $exists &&
      $ready &&
      ArticleWorkflow::allows(
        $status,
        "note",
        $actor,
        $owner,
        $this->workflowSelfReview(),
        $submitter,
        $this->workflowReviewerEdits(),
      );

    return [
      "exists" => $exists,
      "postId" => $exists ? (int) $this->record->getKey() : null,
      "userId" => $actor["id"] === null ? null : (int) $actor["id"],
      "status" => $status,
      "label" => ContentType::Article->statusLabels()[$status] ?? $status,
      "tone" => ArticleWorkflow::tone($status),
      "hint" => ArticleWorkflow::hint(
        $status,
        $actor,
        $owner,
        $this->workflowSelfReview(),
        $submitter,
        $this->workflowReviewerEdits(),
      ),
      "canEdit" => $this->workflowCanEdit(),
      "final" => ArticleWorkflow::isFinal($status),
      "actions" => $actions,
      "needsDecision" =>
        $status === ArticleWorkflow::REVIEW &&
        array_intersect(
          ArticleWorkflow::DECISIONS,
          array_column($actions, "action"),
        ) !== [],
      "canMessage" => $canMessage,
      "pinsReady" => $pinsReady,
      "canPin" => $canMessage && $pinsReady && $blockLabels !== [],
      "blockLabels" => $blockLabels,
      "pending" => $pinData["pending"],
      "pinWarning" => ReviewPins::warning($pinData["pending"]),
      "pinBlocks" => $pinData["blocks"],
      "authors" => $authors,
      "canManageAuthors" => $canManage,
      "maxAuthors" => ArticleWorkflow::MAX_AUTHORS,
      "chat" => $chat,
      "historyReady" => $ready,
      "scheduledFor" =>
        $status === ArticleWorkflow::SCHEDULED
          ? $publishedAt?->format("Y-m-d\TH:i")
          : null,
      "publishedAt" => $publishedAt?->format("d M Y H:i"),
      "timezone" => (string) config("app.timezone"),
      "noteMax" => ArticleWorkflow::NOTE_MAX,
    ];
  }

  /** Status yang BOLEH dipilih pengguna ini (label), dipakai panel Halaman. Artikel tidak memakai ini: statusnya lewat workflowAction(). */
  #[Computed]
  public function statusOptions(): array
  {
    $type = $this->contentType;
    $allowed = $type->statusesFor($this->canPublish(), $this->record?->status);

    return array_intersect_key($type->statusLabels(), array_flip($allowed));
  }

  /** id => nama. Category.name ber-cast array ({"id": "…", "en": "…"}), jadi diubah ke teks sesuai bahasa aktif. */
  #[Computed]
  public function categoryOptions(): array
  {
    if ($this->contentType !== ContentType::Article) {
      return [];
    }

    $locale = app()->getLocale();
    $out = [];
    foreach (\App\Models\Category::query()->get() as $category) {
      $out[(int) $category->id] =
        Names::of($category->name, $locale) ?: "#" . $category->id;
    }
    asort($out, SORT_NATURAL | SORT_FLAG_CASE);

    return $out;
  }

  /** [{id, name}] untuk saran tag; nama dalam bahasa aktif (Tag.name juga ber-cast array). */
  #[Computed]
  public function tagOptions(): array
  {
    if ($this->contentType !== ContentType::Article) {
      return [];
    }

    $locale = app()->getLocale();

    return \App\Models\Tag::query()
      ->get()
      ->map(
        fn($t) => [
          "id" => (int) $t->id,
          "name" => Names::of($t->name, $locale) ?: "#" . $t->id,
        ],
      )
      ->sortBy("name", SORT_NATURAL | SORT_FLAG_CASE)
      ->values()
      ->all();
  }

  // ------------------------------------------------------------------ judul halaman (<title>)

  /** Teks untuk <x-slot:title>. Dipanggil saat halaman dirender penuh (muat awal & wire:navigate). */
  #[Computed]
  public function pageTitle(): string
  {
    $editing = (bool) $this->record?->exists;
    $key = $this->contentType->titleKey($editing);

    if (!trans()->has($key)) {
      return $this->contentType->titleFallback($editing, $this->displayTitle());
    }

    return $editing ? __($key, ["title" => $this->displayTitle()]) : __($key);
  }

  /** Judul di header editor; memakai teks cadangan bila kunci terjemahan belum ditambahkan. */
  #[Computed]
  public function headerLabel(): string
  {
    $editing = (bool) $this->record?->exists;
    $key = $this->contentType->headerKey($editing);

    return trans()->has($key)
      ? __($key)
      : $this->contentType->headerFallback($editing);
  }

  private function displayTitle(): string
  {
    foreach ([app()->getLocale(), ...$this->activeLocales] as $locale) {
      if (filled($this->titles[$locale] ?? null)) {
        return $this->titles[$locale];
      }
    }
    return "…";
  }

  // ------------------------------------------------------------------ menyimpan

  /** Aturan & nama atribut ada di ContentRules (diuji terhadap validator Laravel + SQLite). */
  protected function rules(): array
  {
    return ContentRules::for(
      $this->contentType,
      $this->activeLocales,
      $this->record?->getKey(),
      $this->canPublish(),
      $this->record?->status,
    );
  }

  protected function validationAttributes(): array
  {
    return ContentRules::attributes($this->contentType, $this->activeLocales);
  }

  public function save(): void
  {
    $wasNew = !$this->record->exists;
    if (!$this->persistRecord()) {
      return;
    }

    // Editor lama memakai ui.notification.page_saved; jenis lain memakai pola yang sama bila kuncinya ada.
    $notice = "ui.notification." . $this->type . "_saved";
    $this->notify(
      trans()->has($notice) ? __($notice) : "Berhasil disimpan",
      "success",
    );

    $this->redirectToEditIfNew($wasNew);
  }

  /**
   * Validasi + isi record + simpan. false bila ditolak (galat sudah ditampilkan). Artikel: STATUS TIDAK PERNAH diubah di sini
   * (nilai kiriman klien diabaikan); status hanya berubah lewat workflowAction(). Penulis tidak bisa menyimpan artikel yang terkunci.
   */
  private function persistRecord(): bool
  {
    $type = $this->contentType;

    if ($type === ContentType::Article) {
      if (!$this->workflowCanEdit()) {
        $this->notify("Artikel ini terkunci dan tidak bisa diubah.", "error");

        return false;
      }
      $this->status = $this->record->exists
        ? ArticleWorkflow::normalize($this->record->status)
        : ArticleWorkflow::DRAFT;
    } elseif (!$this->canPublish()) {
      // Halaman dan snippet (rilis 49 rev 4): online/offline hanya diubah admin/editor. Nilai dari browser diabaikan; yang lain menyimpan isi
      // dengan status yang sedang berlaku (record baru: offline). Pengubahan oleh admin/editor tercatat di riwayat (log `status`).
      $this->status =
        $this->record->exists &&
        in_array($this->record->status, $type->statuses(), true)
          ? $this->record->status
          : $type->defaultStatus();
    }

    // Rilis 49 rev 3: orang lain menyimpan record ini sejak Anda membukanya? Tolak, jangan menimpa diam-diam.
    if ($this->conflictDetected()) {
      return false;
    }

    try {
      $this->validate();
    } catch (ValidationException $e) {
      // Kolom yang salah ada di tab Halaman: tampilkan, jangan biarkan pengguna menebak
      $this->js("Alpine.store('editor').tab = 'page'");
      throw $e;
    }

    if ($type === ContentType::Article) {
      // Nilai dari browser tidak dipercaya: hanya "", ID media gambar yang ADA, atau nama berkas lama yang tidak diubah
      $before = $this->featured_image;
      $this->featured_image = ArticleCover::normalize(
        $this->featured_image,
        $this->record->getOriginal("featured_image"),
        fn(int $id) => $this->imageExists($id),
      );
      if ($before !== "" && $this->featured_image === "") {
        $this->notify(
          "Thumbnail yang dipilih tidak ditemukan atau bukan gambar; dikosongkan.",
          "error",
        );
      }
    }

    ContentWriter::fill($this->record, $type, [
      "locales" => $this->activeLocales,
      "titles" => $this->titles,
      "slug" => $this->slug,
      "meta_title" => $this->meta_title,
      "meta_description" => $this->meta_description,
      "status" => $this->status,
      "blocks" => $this->content,
      "order" => $this->blockOrder,
      "settings" => $this->settings,
      "key" => $this->key,
      "description" => $this->description,
      "is_closing" => $this->is_closing,
      "sort_order" => $this->sort_order,
      "category_id" => $this->category_id,
      "tags" => $this->tags,
      "featured_image" => $this->featured_image,
      "user_id" => auth()->id(),
    ]);

    try {
      $this->record->save();
    } catch (UniqueConstraintViolationException $e) {
      // Jaring pengaman untuk indeks unik yang tidak kita periksa sendiri (mis. unik pada seluruh kolom JSON judul/slug)
      $this->addError(
        "titles." . ($this->activeLocales[0] ?? "id"),
        "Judul atau slug ini sudah dipakai konten lain.",
      );
      $this->js("Alpine.store('editor').tab = 'page'");

      return false;
    }

    // Tag baru dibuat dan relasi disinkron SETELAH record punya id; state memakai ID-nya sejak sekarang
    $tagIds = ContentWriter::syncRelations(
      $this->record,
      $type,
      ["tags" => $this->tags],
      $this->activeLocales,
    );
    if ($tagIds !== null) {
      $this->tags = $tagIds;
    }
    $this->importedLegacy = false;
    $this->baseHash = $this->fingerprint(); // dasar baru = apa yang baru saja kita simpan
    $this->conflict = "";

    return true;
  }

  private function redirectToEditIfNew(bool $wasNew): void
  {
    if ($wasNew) {
      // Pindah ke rute edit PADA AWALAN YANG SAMA ("v2.page.edit", bukan "page.edit" = editor lama). Kirim ID eksplisit
      // (getRouteKey() model ini = slug, bukan id).
      $this->redirect(
        route($this->contentType->routeNameFor($this->routeName, "edit"), [
          $this->contentType->routeParam() => $this->record->getKey(),
        ]),
        navigate: true,
      );
    }
  }

  /**
   * Alur persetujuan artikel (rilis 38): ajukan / tarik / terbitkan / jadwalkan / kembalikan / tolak / dst. Satu-satunya jalan mengubah
   * status artikel. Wewenang, catatan wajib, dan waktu jadwal diperiksa di SERVER oleh ArticleWorkflow::plan(); tombol di tampilan hanya
   * kemudahan. Mengembalikan true bila berhasil (dipakai tampilan untuk menutup formulir).
   */
  public function workflowAction(
    string $action,
    ?string $note = null,
    ?string $at = null,
  ): bool {
    if ($this->contentType !== ContentType::Article) {
      return false;
    }

    $exists = (bool) $this->record->exists;
    $owner = ArticleTransitions::authorsOf($this->record); // semua penulis (utama + tambahan); null = artikel baru
    $status = $exists ? $this->record->status : ArticleWorkflow::DRAFT;
    $now = Carbon::now();

    // Periksa DULU, sebelum menyimpan apa pun: aksi yang ditolak tidak boleh meninggalkan simpanan setengah jalan
    $check = ArticleWorkflow::plan(
      $status,
      $action,
      $this->workflowActor(),
      $owner,
      $note,
      $at,
      $now,
      $exists ? ArticleTransitions::publishedAt($this->record, $now) : null,
      $this->workflowSelfReview(),
      $this->workflowSelfReview()
        ? null
        : ArticleTransitions::submitterOf($this->record),
      $this->workflowReviewerEdits(),
    );
    if (!$check["ok"]) {
      $this->notify((string) $check["error"], "error");

      return false;
    }

    // Diajukan / diterbitkan / dijadwalkan = isi yang dilihat peninjau HARUS isi yang tersimpan: simpan dulu (dan wajib lolos validasi)
    $wasNew = !$exists;
    if (
      in_array($action, ["submit", "publish", "schedule"], true) &&
      $this->workflowCanEdit() &&
      !$this->persistRecord()
    ) {
      return false;
    }
    // Peninjau tidak menyimpan (isi dibekukan), tetapi menyetujui isi: bila penulis menarik, mengubah, dan mengajukan ulang sejak halaman ini
    // dibuka, yang akan terbit berbeda dari yang pernah dilihat peninjau. Tolak; muat ulang dulu.
    if (
      in_array($action, ["publish", "schedule"], true) &&
      !$this->workflowCanEdit() &&
      $this->conflictDetected()
    ) {
      return false;
    }
    if (!$this->record->exists) {
      $this->notify("Simpan artikel dulu.", "error");

      return false;
    }

    $clean =
      $this->baseHash === "" ||
      hash_equals($this->baseHash, $this->fingerprint()); // sebelum transisi: ada perubahan orang lain yang belum kita lihat?
    try {
      $result = ArticleTransitions::apply(
        $this->record,
        $action,
        $this->workflowActor(),
        $note,
        $at,
        $this->workflowSelfReview(),
        $now,
        $this->workflowReviewerEdits(),
      );
    } catch (\Illuminate\Database\QueryException $e) {
      report($e);
      $this->notify(
        "Riwayat tinjauan belum bisa dicatat. Jalankan: php artisan migrate",
        "error",
      );

      return false;
    }
    if (!$result["ok"]) {
      $this->notify((string) $result["error"], "error");

      return false;
    }

    $this->status = $this->record->status;
    if ($clean) {
      $this->baseHash = $this->fingerprint(); // perubahan status oleh kita sendiri bukan "konflik"; perubahan orang lain tetap terdeteksi
    }
    unset($this->workflow); // cache Computed: panel dirender ulang dengan status/percakapan terbaru
    $this->notify(
      ArticleWorkflow::done(
        $action,
        $result["publishedAt"] instanceof \DateTimeInterface
          ? $result["publishedAt"]
          : null,
      ),
      "success",
    );
    $this->redirectToEditIfNew($wasNew);

    return true;
  }

  // ------------------------------------------------------------------ pesan berpenanda blok, selesai, presence (rilis 50)

  /** Id blok yang ada di dokumen SEKARANG (urutan dokumen). Blok yatim di $content (tak terjangkau dari blockOrder) tidak dihitung. @return list<string> */
  private function liveBlockIds(): array
  {
    return $this->structureWalk();
  }

  /**
   * Kirim pesan di percakapan, boleh menempel pada SATU blok. Blok diperiksa di server terhadap isi artikel (id harus ada) dan jenis + cuplikannya
   * dipotret ke baris riwayat, sehingga pesan tetap terbaca bila blok itu dihapus. Tidak mengubah isi atau status artikel.
   */
  public function sendMessage(string $note, ?string $blockId = null): bool
  {
    if ($this->contentType !== ContentType::Article || !$this->record->exists) {
      $this->notify("Simpan artikel dulu.", "error");

      return false;
    }
    $live = array_intersect_key(
      $this->content,
      array_flip($this->liveBlockIds()),
    );
    $anchor = ReviewPins::anchor($blockId, $live, $this->activeLocales);
    if (!$anchor["ok"]) {
      $this->notify((string) $anchor["error"], "error");

      return false;
    }

    return $this->recordChat("note", $note, [
      "block_id" => $anchor["block_id"],
      "block_type" => $anchor["block_type"],
      "block_excerpt" => $anchor["block_excerpt"],
    ]);
  }

  /**
   * Tandai pesan berpenanda selesai ($resolved = true) atau buka lagi. Dicatat sebagai baris riwayat tersendiri (tidak mengubah pesan aslinya).
   * Siapa pun yang boleh berbalas di percakapan ini boleh menandai.
   */
  public function resolvePin(int $messageId, bool $resolved = true): bool
  {
    if ($this->contentType !== ContentType::Article || !$this->record->exists) {
      return false;
    }
    try {
      $msg = ArticleReview::query()
        ->where("post_id", $this->record->getKey())
        ->where("id", $messageId)
        ->where("action", "note")
        ->whereNotNull("block_id")
        ->first();
      if (!$msg) {
        $this->notify("Pesan berpenanda tidak ditemukan.", "error");

        return false;
      }
      $last = ArticleReview::query()
        ->where("post_id", $this->record->getKey())
        ->where("ref_id", $messageId)
        ->whereIn("action", ["resolve", "reopen"])
        ->orderByDesc("id")
        ->value("action");
    } catch (\Illuminate\Database\QueryException $e) {
      report($e);
      $this->notify(
        "Penanda blok belum aktif. Jalankan: php artisan migrate",
        "error",
      );

      return false;
    }
    if (($last === "resolve") === $resolved) {
      $this->notify(
        $resolved
          ? "Pesan ini sudah ditandai selesai."
          : "Pesan ini masih terbuka.",
        "error",
      );

      return false;
    }

    return $this->recordChat($resolved ? "resolve" : "reopen", null, [
      "block_id" => $msg->block_id,
      "block_type" => $msg->block_type,
      "block_excerpt" => $msg->block_excerpt,
      "ref_id" => (int) $msg->getKey(),
    ]);
  }

  /** Jalur bersama aksi percakapan (note/resolve/reopen): wewenang + catatan diperiksa ArticleWorkflow::plan(), baris riwayat ditambah, tanpa menyentuh artikel. */
  private function recordChat(string $action, ?string $note, array $extra): bool
  {
    try {
      $result = ArticleTransitions::apply(
        $this->record,
        $action,
        $this->workflowActor(),
        $note,
        null,
        $this->workflowSelfReview(),
        Carbon::now(),
        $this->workflowReviewerEdits(),
        $extra,
      );
    } catch (\Illuminate\Database\QueryException $e) {
      report($e);
      $this->notify(
        $extra !== []
          ? "Penanda blok belum bisa dicatat. Jalankan: php artisan migrate"
          : "Riwayat tinjauan belum bisa dicatat. Jalankan: php artisan migrate",
        "error",
      );

      return false;
    }
    if (!$result["ok"]) {
      $this->notify((string) $result["error"], "error");

      return false;
    }
    unset($this->workflow); // percakapan dan hitungan pesan terbuka dirender ulang
    $this->dispatch("review-pins"); // kanvas memperbarui lencana penanda
    $this->notify(ArticleWorkflow::done($action), "success");

    return true;
  }

  /** Penanda untuk kanvas: id blok => {open, total}, hanya blok yang masih ada. Tanpa render ulang; kosong untuk halaman/snippet dan artikel baru. */
  #[Renderless]
  public function pinsNow(): array
  {
    if ($this->contentType !== ContentType::Article || !$this->record->exists) {
      return [];
    }
    unset($this->workflow);

    return $this->workflow["pinBlocks"] ?? [];
  }

  /**
   * Denyut "sedang dibuka" (tiap 30 dtk dari tab yang terlihat): mencatat pengguna ini dan mengembalikan nama pengguna LAIN yang aktif pada record
   * ini. Hanya untuk record tersimpan yang boleh dibuka pengguna ini (mount() sudah memeriksa). Tidak merender ulang komponen.
   *
   * @return list<array{id:int,name:string}>
   */
  #[Renderless]
  public function heartbeat(): array
  {
    if (!auth()->check() || !$this->record?->exists) {
      return [];
    }
    $ids = EditorPresence::make()->beat(
      $this->type,
      $this->record->getKey(),
      auth()->id(),
    );
    if ($ids === []) {
      return [];
    }
    $names = \App\Models\User::query()
      ->whereIn("id", $ids)
      ->pluck("name", "id");

    return collect($ids)
      ->map(
        fn($id) => [
          "id" => (int) $id,
          "name" => (string) ($names[$id] ?? "#" . $id),
        ],
      )
      ->values()
      ->all();
  }

  // ------------------------------------------------------------------ penulis artikel (rilis 49 rev 3)

  /** Cari akun untuk ditambahkan sebagai penulis. Hanya yang boleh mengelola penulis; nama saja yang keluar (maks. 8). */
  #[Renderless]
  public function searchAuthors(string $q): array
  {
    abort_unless(
      auth()->check() &&
        $this->contentType === ContentType::Article &&
        $this->record->exists &&
        ArticleWorkflow::canManageAuthors(
          $this->workflowActor(),
          $this->record->user_id ?: null,
        ),
      403,
    );

    $q = trim(mb_substr($q, 0, 80));
    if (mb_strlen($q) < 2) {
      return [];
    }
    $taken = array_map(
      "strval",
      ArticleTransitions::authorsOf($this->record) ?? [],
    );

    return \App\Models\User::query()
      ->whereRaw("name LIKE ? ESCAPE '!'", [JsonSql::like($q)])
      ->orderBy("name")
      ->limit(8)
      ->get(["id", "name"])
      ->reject(fn($u) => in_array((string) $u->getKey(), $taken, true))
      ->map(
        fn($u) => ["id" => (int) $u->getKey(), "label" => (string) $u->name],
      )
      ->values()
      ->all();
  }

  /** Tambah penulis (setara: menulis, mengajukan, menarik kembali, berbalas pesan). Dicatat di riwayat tinjauan sebagai `author-add`. */
  public function addAuthor(int $userId): bool
  {
    if (!$this->authorsEditable()) {
      return false;
    }
    $user = \App\Models\User::query()->find($userId);
    if ($user === null) {
      $this->notify("Akun tidak ditemukan.", "error");

      return false;
    }
    $current = array_map(
      "strval",
      ArticleTransitions::authorsOf($this->record) ?? [],
    );
    if (in_array((string) $userId, $current, true)) {
      $this->notify(
        $user->name . " sudah menjadi penulis artikel ini.",
        "error",
      );

      return false;
    }
    if (count($current) >= ArticleWorkflow::MAX_AUTHORS) {
      $this->notify(
        "Penulis maksimal " . ArticleWorkflow::MAX_AUTHORS . " orang.",
        "error",
      );

      return false;
    }

    return $this->recordAuthorChange(
      "author-add",
      $user,
      function () use ($userId) {
        PostAuthor::query()->create([
          "post_id" => $this->record->getKey(),
          "user_id" => $userId,
        ]); // siapa menambahkan: ada di baris riwayat author-add
      },
      "Penulis ditambahkan.",
    );
  }

  /** Lepas penulis tambahan. Penulis utama (pembuat artikel) tidak bisa dilepas. Dicatat sebagai `author-remove`. */
  public function removeAuthor(int $userId): bool
  {
    if (!$this->authorsEditable()) {
      return false;
    }
    if (
      $this->record->user_id &&
      (string) $this->record->user_id === (string) $userId
    ) {
      $this->notify("Penulis utama tidak bisa dilepas.", "error");

      return false;
    }
    $user = \App\Models\User::query()->find($userId);
    try {
      $isCo = PostAuthor::query()
        ->where("post_id", $this->record->getKey())
        ->where("user_id", $userId)
        ->exists();
    } catch (\Illuminate\Database\QueryException) {
      $isCo = false;
    }
    if (!$isCo) {
      $this->notify("Akun itu bukan penulis tambahan artikel ini.", "error");

      return false;
    }

    return $this->recordAuthorChange(
      "author-remove",
      $user,
      function () use ($userId) {
        PostAuthor::query()
          ->where("post_id", $this->record->getKey())
          ->where("user_id", $userId)
          ->delete();
      },
      "Penulis dilepas.",
      $userId,
    );
  }

  /** Wewenang bersama addAuthor/removeAuthor: artikel tersimpan, belum ditolak, dan pemakai = penulis utama / admin / editor. */
  private function authorsEditable(): bool
  {
    if ($this->contentType !== ContentType::Article || !$this->record->exists) {
      $this->notify("Simpan artikel dulu.", "error");

      return false;
    }
    if (ArticleWorkflow::isFinal($this->record->status)) {
      $this->notify("Artikel yang ditolak tidak bisa dilanjutkan.", "error");

      return false;
    }
    if (
      !ArticleWorkflow::canManageAuthors(
        $this->workflowActor(),
        $this->record->user_id ?: null,
      )
    ) {
      $this->notify(
        "Hanya penulis utama, admin, atau editor yang dapat mengelola penulis.",
        "error",
      );

      return false;
    }

    return true;
  }

  /** Ubah daftar penulis + tulis baris riwayat (nama sasaran di kolom catatan) dalam satu transaksi. */
  private function recordAuthorChange(
    string $action,
    ?object $user,
    \Closure $change,
    string $done,
    ?int $fallbackId = null,
  ): bool {
    $status = ArticleWorkflow::normalize($this->record->status);
    try {
      DB::transaction(function () use (
        $action,
        $user,
        $change,
        $status,
        $fallbackId,
      ) {
        $change();
        ArticleReview::query()->create([
          "post_id" => $this->record->getKey(),
          "user_id" => auth()->id(),
          "action" => $action,
          "from_status" => $status,
          "to_status" => $status,
          "note" => $user ? (string) $user->name : "#" . $fallbackId,
          "scheduled_for" => null,
          "created_at" => Carbon::now(),
        ]);
      });
    } catch (\Illuminate\Database\QueryException $e) {
      report($e);
      $this->notify(
        "Daftar penulis belum bisa disimpan. Jalankan: php artisan migrate",
        "error",
      );

      return false;
    }
    unset($this->workflow);
    $this->notify($done, "success");

    return true;
  }

  /** Tautan kembali ke daftar: rute "<awalan>.<jenis>.index" bila ada, jika tidak "<jenis>.index" (editor lama). */
  #[Computed]
  public function indexUrl(): string
  {
    foreach (
      [
        $this->contentType->routeNameFor($this->routeName, "index"),
        $this->contentType->indexRoute(),
      ]
      as $name
    ) {
      if (Route::has($name)) {
        return route($name);
      }
    }

    return url("/");
  }

  // ------------------------------------------------------------------ media dari File Manager

  #[On("mediaSelected")]
  public function handleMediaSelection(array $data): void
  {
    $basePath = (string) ($data["componentId"] ?? "");

    // Thumbnail artikel (rilis 40): bukan blok, jadi bukan path content.*. Hanya ID gambar yang ADA yang diterima.
    if ($basePath === "featured_image") {
      $id = (int) ($data["id"] ?? 0);
      if (
        $this->contentType === ContentType::Article &&
        $this->workflowCanEdit() &&
        $id > 0 &&
        $this->imageExists($id)
      ) {
        $this->featured_image = (string) $id;
      }

      return;
    }

    // Path datang dari browser: hanya terima bentuk content.{idBlok}.data.… untuk blok yang ada
    if (
      !preg_match(
        '/^content\.([A-Za-z0-9_]+)(\.[A-Za-z0-9_]+)*$/',
        $basePath,
        $m,
      ) ||
      !isset($this->content[$m[1]]) ||
      !str_contains($basePath, ".data")
    ) {
      return;
    }
    $path = preg_replace("/^content\./", "", $basePath);

    // Rilis 45: url & alt diambil dari model Media berdasarkan id; url/alt kiriman klien tidak dipercaya (alamat tidak terpanggang host)
    $mediaId = (int) ($data["id"] ?? 0);
    $media = $mediaId > 0 ? Media::query()->find($mediaId) : null;
    if ($media === null) {
      return;
    }
    $alt =
      (string) ($media->alt_text ?:
      mb_substr((string) ($data["alt_text"] ?? ""), 0, 255));
    data_set($this->content, $path . ".url", $media->url());
    data_set($this->content, $path . ".media_id", (int) $media->id);
    data_set($this->content, $path . ".alt_text", $alt);

    $this->dispatch("update-block-alt", componentId: $basePath, altText: $alt);
  }

  // ------------------------------------------------------------------ thumbnail artikel (rilis 40)

  private function imageExists(int $id): bool
  {
    $m = Media::query()->find($id);

    return $m !== null && $m->isImage();
  }

  /** Thumbnail terpilih untuk panel Halaman: ['kind' => none|media|legacy|missing, 'id', 'url', 'name']. */
  #[Computed]
  public function featuredPreview(): array
  {
    if ($this->contentType !== ContentType::Article) {
      return ["kind" => "none", "id" => 0, "url" => "", "name" => ""];
    }
    $p = ArticleCover::parse($this->featured_image);
    if ($p["kind"] === "media") {
      $m = Media::query()->find($p["id"]);

      return $m && $m->isImage()
        ? [
          "kind" => "media",
          "id" => $p["id"],
          "url" => $m->url(),
          "name" => (string) $m->original_name,
        ]
        : ["kind" => "missing", "id" => $p["id"], "url" => "", "name" => ""];
    }
    if ($p["kind"] === "legacy") {
      $tpl = (string) config("cms.public.cover", ""); // mis. "/storage/articles/{file}"; kosong di CMS yang tidak memasangnya

      return [
        "kind" => "legacy",
        "id" => 0,
        "url" =>
          $tpl !== ""
            ? str_replace("{file}", rawurlencode($p["name"]), $tpl)
            : "",
        "name" => $p["name"],
      ];
    }

    return ["kind" => "none", "id" => 0, "url" => "", "name" => ""];
  }

  /**
   * Gambar yang dipasang di isi artikel (state SAAT INI, belum perlu disimpan) sebagai pilihan thumbnail. Dipanggil panel hanya saat
   * dibuka; tidak merender ulang builder. Hanya media gambar yang ada; maks. ArticleCover::MAX_CHOICES.
   *
   * @return list<array{id:int,url:string,name:string,alt:string}>
   */
  #[Renderless]
  public function thumbnailChoices(): array
  {
    abort_unless(
      auth()->check() && $this->contentType === ContentType::Article,
      403,
    );

    $row = fn(Media $m): array => [
      "id" => (int) $m->id,
      "url" => $m->url(),
      "name" => (string) $m->original_name,
      "alt" => (string) $m->alt_text,
      "image" => $m->isImage(),
      "path" => (string) $m->path,
    ];

    return ArticleCover::choices(
      ArticleCover::references($this->content, $this->blockOrder),
      fn(array $ids) => Media::query()
        ->whereIn("id", $ids)
        ->get()
        ->map($row)
        ->all(),
      fn(array $paths) => Media::query()
        ->whereIn("path", $paths)
        ->get()
        ->map($row)
        ->all(),
    );
  }

  /** Lepas thumbnail (tombol di panel). */
  public function clearThumbnail(): void
  {
    if (
      $this->contentType === ContentType::Article &&
      $this->workflowCanEdit()
    ) {
      $this->featured_image = "";
    }
  }

  /** Pilih salah satu gambar artikel sebagai thumbnail. Hanya ID gambar yang ada. */
  public function setThumbnail(int $id): void
  {
    if (
      $this->contentType === ContentType::Article &&
      $this->workflowCanEdit() &&
      $id > 0 &&
      $this->imageExists($id)
    ) {
      $this->featured_image = (string) $id;
    }
  }

  // ------------------------------------------------------------------ riwayat perubahan (audit trail)

  /** Tab "Riwayat" tampil bagi admin/editor pada record yang sudah tersimpan. Computed: method private tidak terjangkau dari view. */
  #[Computed]
  public function canViewAudit(): bool
  {
    return $this->canPublish() && (bool) $this->record?->exists;
  }

  /**
   * Riwayat perubahan record ini dari tabel activity_log (spatie/laravel-activitylog), 20 per muatan, terbaru dulu (rilis 42).
   * Subjek SELALU record yang sedang dibuka (tidak ada argumen subjek dari browser); hanya admin/editor, hanya record yang sudah tersimpan.
   * #[Renderless]: dipanggil panel saat tab "Riwayat" dibuka dan tidak merender ulang builder. Kegagalan membaca (tabel belum ada, dsb.)
   * dibalas ready=false, bukan galat. Format dan aturan penyaringan ada di App\Content\AuditTrail.
   *
   * @return array{ready:bool,entries:list<array<string,mixed>>,more:bool,next:int}
   */
  #[Renderless]
  public function auditTrail(int $offset = 0): array
  {
    abort_unless(auth()->check() && $this->canViewAudit, 403);

    try {
      $query = DB::connection(config("activitylog.database_connection"))->table(
        (string) config("activitylog.table_name", "activity_log"),
      );
      $page = AuditTrail::fetch(
        $query,
        array_values(
          array_unique([$this->record->getMorphClass(), $this->record::class]),
        ),
        $this->record->getKey(),
        20,
        max(0, $offset),
      );

      // Nama pelaku, kategori, dan penulis dicari sekali untuk seluruh muatan (bukan satu kueri per baris)
      $userTypes = [
        new \App\Models\User()->getMorphClass(),
        \App\Models\User::class,
      ];
      $userIds = [];
      $categoryIds = [];
      foreach ($page["rows"] as $row) {
        if (
          ($row["causer_id"] ?? null) &&
          (blank($row["causer_type"] ?? null) ||
            in_array($row["causer_type"], $userTypes, true))
        ) {
          $userIds[] = (int) $row["causer_id"];
        }
        $attrs = AuditTrail::attributes($row);
        foreach ([$attrs["attributes"], $attrs["old"]] as $set) {
          ctype_digit((string) ($set["user_id"] ?? "")) &&
            ($userIds[] = (int) $set["user_id"]);
          ctype_digit((string) ($set["category_id"] ?? "")) &&
            ($categoryIds[] = (int) $set["category_id"]);
        }
      }
      $names =
        $userIds === []
          ? []
          : \App\Models\User::query()
            ->whereIn("id", array_values(array_unique($userIds)))
            ->pluck("name", "id")
            ->all();
      $categories = [];
      if ($categoryIds !== []) {
        foreach (
          \App\Models\Category::query()
            ->whereIn("id", array_values(array_unique($categoryIds)))
            ->get()
          as $category
        ) {
          $categories[(string) $category->getKey()] = Names::of(
            $category->getRawOriginal("name"),
            "id",
          );
        }
      }
      $lookups = [
        "status" => $this->contentType->statusLabels(),
        "category_id" => $categories,
        "user_id" => array_map("strval", $names),
      ];

      $entries = AuditTrail::entries($page["rows"], $names, $lookups);
    } catch (\Throwable) {
      return ["ready" => false, "entries" => [], "more" => false, "next" => 0]; // tabel activity_log belum ada: php artisan migrate
    }

    return [
      "ready" => true,
      "entries" => $entries,
      "more" => $page["more"],
      "next" => max(0, $offset) + count($entries),
    ];
  }

  // ------------------------------------------------------------------ pratinjau (kanvas)

  /**
   * Menitipkan isi yang BELUM disimpan di cache dan mengembalikan token untuk bingkai kanvas (lihat PreviewStore). Tidak menyentuh record
   * (menggantikan saveAndPreview() lama yang menyimpan dulu). #[Renderless]: tidak merender ulang builder, hanya mengirim data.
   * Mengembalikan ['error' => ...] (bukan melempar) supaya kanvas menampilkan pesan, bukan modal galat Livewire.
   *
   * @return array{token?:string,rev?:int,error?:string}
   */
  #[Renderless]
  public function publishPreview(?string $token = null): array
  {
    if (!auth()->check()) {
      return ["error" => "Sesi berakhir. Muat ulang halaman."];
    }

    try {
      return PreviewStore::make()->publish(
        $this->type,
        auth()->id(),
        $token,
        $this->content,
        $this->blockOrder,
        $this->settings,
        $this->activeLocales,
        $this->titles,
      );
    } catch (\LengthException $e) {
      return ["error" => $e->getMessage()];
    }
  }

  /**
   * Alamat pratinjau versi TERSIMPAN (dari database) untuk record ini. Kosong bila record belum pernah disimpan atau rutenya belum dipasang.
   * Beda dengan kanvas: kanvas menampilkan isi yang belum disimpan; ini menampilkan yang sudah tersimpan.
   */
  #[Computed]
  public function savedPreviewUrl(): string
  {
    if (!$this->record?->exists) {
      return "";
    }

    $segments = explode(".", $this->routeName);
    $at = array_search($this->type, $segments, true);
    $prefix = $at ? implode(".", array_slice($segments, 0, $at)) . "." : "";

    foreach ([$prefix . "preview.record", "preview.record"] as $name) {
      if (Route::has($name)) {
        return route($name, [
          "type" => $this->type,
          "id" => $this->record->getKey(),
        ]);
      }
    }

    return "";
  }

  /** Alamat bingkai kanvas dengan penanda __TOKEN__ (diisi di browser). Kosong bila rute pratinjau belum dipasang. */
  #[Computed]
  public function previewFrameUrl(): string
  {
    // awalan rute saat ini ("v2." pada "v2.page.create") dipertahankan, lalu cadangan tanpa awalan
    $segments = explode(".", $this->routeName);
    $at = array_search($this->type, $segments, true);
    $prefix = $at ? implode(".", array_slice($segments, 0, $at)) . "." : "";

    foreach ([$prefix . "preview.frame", "preview.frame"] as $name) {
      if (Route::has($name)) {
        return route($name, ["token" => "__TOKEN__"]);
      }
    }

    return "";
  }
};
?>

{{-- Harus berada DI LUAR elemen akar, dan view hanya boleh punya SATU elemen akar. --}}
<x-slot:title>
  {{ $this->pageTitle }}
</x-slot:title>

{{-- x-effect: judul tab mengikuti ketikan (slot di atas hanya dievaluasi saat halaman dirender penuh). --}}
<div
  x-data="fitViewport"
  x-init="
    $store.editor.clear();
    fit();
  "
  x-on:resize.window.debounce.100ms="fit()"
  x-bind:style="h ? 'height:' + h + 'px' : ''"
  x-effect="
    const t = (($wire.titles || {})[@js(app()->getLocale())] || '').trim()
    document.title = (t || @js($this->pageTitle)) + ' — ' + @js(config('app.name'))
  "
  class="flex h-dvh min-h-0 flex-col overflow-hidden rounded-xl bg-white"
>
  {{-- ======= HEADER (atas-tengah) ======= --}}
  <header class="flex items-center gap-3 border-b px-4 py-2">
    <a href="{{ $this->indexUrl }}" wire:navigate class="text-sm font-semibold"
      >←</a
    >
    <h1 class="flex-1 truncate text-sm font-extrabold">
      {{ $this->headerLabel }}
    </h1>
    {{-- Rilis 50: siapa lagi yang sedang membuka record ini (denyut 30 dtk selagi tab terlihat; hanya informasi) --}}
    @if ($record?->exists)
      <span
        x-data="editorPresence(@js(['interval' => EditorPresence::HEARTBEAT * 1000]))"
        x-show="others.length"
        x-cloak
        role="status"
        class="inline-flex max-w-[22rem] items-center gap-1.5 truncate rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-900"
        data-presence
      >
        <span class="size-2 shrink-0 rounded-full bg-sky-500"></span>
        <span class="truncate" x-text="label"></span>
      </span>
    @endif
    {{-- Bahasa kolom isian (Ganda / ID / EN). Tab Metadata/Konten = tab Halaman/Blok di kanan; Pratinjau = kanvas; Minimap = outline. --}}
    <x-editor.lang-tabs :locales="$activeLocales" />
    @if ($type === "article")
      {{-- Lencana status = pintasan ke tab Review (status, penulis, tombol alur, percakapan) --}}
      <button
        type="button"
        x-on:click="$store.editor.tab = 'review'"
        title="Buka tab Review"
        class="rounded-full focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:outline-none"
        data-open-review
      >
        <x-content.status-badge
          :tone="$this->workflow['tone']"
          :label="$this->workflow['label']"
        />
      </button>
    @endif
    @if ($type !== "article" || $this->workflow["canEdit"])
      <button
        type="button"
        wire:click="save"
        class="rounded-full bg-emerald-800 px-4 py-1.5 text-xs font-bold text-white"
      >
        Simpan
      </button>
    @else
      <span
        class="rounded-full bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-500"
        data-readonly
        >Hanya baca</span
      >
    @endif
  </header>

  {{-- Rilis 49 rev 3: orang lain menyimpan lebih dulu. Simpan ditolak; pilih muat ulang (buang perubahan Anda) atau timpa (buang perubahan mereka). --}}
  @if ($conflict !== "")
    <div
      role="alert"
      class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-amber-300 bg-amber-50 px-4 py-2 text-xs text-amber-900"
      data-conflict
    >
      <p class="min-w-0 flex-1">
        <b>Konten ini sudah diubah orang lain</b> sejak Anda membukanya{{
          $record?->updated_at
            ? " (terakhir " . $record->updated_at->format("d M Y H:i") . ")"
            : ""
        }}. Simpan dibatalkan supaya pekerjaan mereka tidak tertimpa. Salin dulu
        bagian yang ingin Anda simpan.
      </p>
      <button
        type="button"
        onclick="window.location.reload()"
        class="rounded-md bg-amber-600 px-3 py-1.5 font-bold text-white hover:bg-amber-700"
        data-conflict-reload
      >
        Muat ulang (buang perubahan saya)
      </button>
      @if ($type !== "article" || $this->workflow["canEdit"])
        <button
          type="button"
          wire:click="overwrite"
          wire:confirm="Timpa versi terbaru dengan versi Anda? Perubahan orang lain akan hilang."
          class="rounded-md border border-amber-400 bg-white px-3 py-1.5 font-bold text-amber-900 hover:bg-amber-100"
          data-conflict-overwrite
        >
          Timpa dengan versi saya
        </button>
      @endif
    </div>
  @endif

  {{-- Ringkasan galat validasi: tanpa ini, Simpan yang gagal tidak menampilkan apa pun. --}}
  @if ($errors->any())
    <div
      role="alert"
      class="border-b border-red-200 bg-red-50 px-4 py-2 text-xs text-red-700"
    >
      <p class="font-bold">Belum bisa disimpan:</p>
      <ul class="list-disc pl-4">
        @foreach ($errors->all() as $message)
          <li>{{ $message }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Artikel terkunci (dalam tinjauan untuk penulis, atau ditolak): jelaskan kenapa tidak ada tombol Simpan. --}}
  @if ($type === "article" && !$this->workflow["canEdit"])
    <div
      role="status"
      class="border-b border-gray-200 bg-gray-50 px-4 py-2 text-xs text-gray-700"
      data-readonly-note
    >
      <p><b>Hanya baca.</b> {{
        $this->workflow[
          "hint"
        ]
      }}</p>
    </div>
  @endif

  {{-- Isi lama (HTML dari editor artikel lama) diimpor sebagai satu blok Paragraf. --}}
  @if ($importedLegacy)
    <div
      role="status"
      class="border-b border-amber-200 bg-amber-50 px-4 py-2 text-xs text-amber-800"
    >
      <p class="font-bold">Isi lama diimpor sebagai satu blok Paragraf.</p>
      <p>Periksa hasilnya. Isi baru berformat blok baru tersimpan setelah Anda menekan Simpan; sebelum itu data lama di database tidak berubah.</p>
    </div>
  @endif

  <div class="grid min-h-0 flex-1 grid-cols-[250px_1fr_330px]">
    {{-- ======= KIRI: pohon struktur (tambah / pindah / duplikat / hapus) ======= --}}
    <div class="overflow-y-auto border-r p-3">
      <x-editor.outline :content="$content" :order="$blockOrder" />
    </div>

    {{-- ======= TENGAH: kanvas + pratinjau (Fase 2) ======= --}}
    <x-content.canvas
      :frame-url="$this->previewFrameUrl"
      :saved-url="$this->savedPreviewUrl"
      :locales="$activeLocales"
    />

    {{-- ======= KANAN: tab "Halaman" (judul, slug, status, SEO) dan "Blok" (properti yang difokus) ======= --}}
    <aside class="flex min-h-0 flex-col border-l">
      {{-- Bilah tab hanya-ikon (rilis 49 rev 3): tab aktif = ikon + nama; yang lain ikon saja, namanya muncul sebagai tooltip saat hover/fokus (tanpa animasi). --}}
      @php
        $tabs = [
          ["key" => "page", "label" => "Halaman"],
          ["key" => "block", "label" => "Blok"],
        ];
        if ($type === "article") {
          $tabs[] = ["key" => "review", "label" => "Review"];
        }
        if ($this->canViewAudit) {
          $tabs[] = ["key" => "audit", "label" => "Riwayat"];
        }
      @endphp
      <div
        class="flex shrink-0 border-b text-xs font-bold"
        role="tablist"
        aria-label="Panel editor"
      >
        @foreach ($tabs as $t)
          <button
            type="button"
            role="tab"
            aria-label="{{ $t['label'] }}"
            x-on:click="$store.editor.tab = '{{ $t['key'] }}'"
            x-bind:aria-selected="$store.editor.tab === '{{ $t['key'] }}'"
            x-bind:class="$store.editor.tab === '{{ $t['key'] }}' ? 'border-foresty text-foresty flex-1 border-b-2' : 'w-11 shrink-0 text-gray-500 hover:text-gray-700'"
            class="group relative flex items-center justify-center gap-1.5 px-3 py-2.5 focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:outline-none focus-visible:ring-inset"
            data-tab="{{ $t['key'] }}"
          >
            <x-content.tab-icon :name="$t['key']" />
            <span
              x-show="$store.editor.tab === '{{ $t['key'] }}'"
              class="truncate"
              >{{ $t["label"] }}</span
            >
            <span
              x-show="$store.editor.tab !== '{{ $t['key'] }}'"
              role="tooltip"
              class="pointer-events-none invisible absolute top-full z-30 mt-1 rounded bg-gray-900 px-2 py-1 text-[10px] font-semibold whitespace-nowrap text-white group-hover:visible group-focus-visible:visible {{ $loop->last ? 'right-0' : 'left-0' }}"
              data-tab-tooltip
              >{{ $t["label"] }}</span
            >
            @if ($t["key"] === "review")
              <span
                x-show="$store.editor.reviewUnread > 0"
                x-cloak
                x-text="
                  $store.editor.reviewUnread > 9
                    ? '9+'
                    : $store.editor.reviewUnread
                "
                class="absolute top-0.5 right-0.5 min-w-4 rounded-full bg-red-600 px-1 text-center text-[9px] leading-4 font-extrabold text-white"
                data-review-unread
              ></span>
              @if ($this->workflow["needsDecision"])
                <span
                  x-show="!($store.editor.reviewUnread > 0)"
                  class="absolute top-1.5 right-1.5 size-2 rounded-full bg-amber-500"
                  data-review-decision
                  title="Menunggu keputusan Anda"
                ></span>
              @endif
            @endif
          </button>
        @endforeach
      </div>

      <div class="mb-8 min-h-0 flex-1 overflow-y-auto p-3">
        <div x-show="$store.editor.tab === 'page'">
          <x-content.page-settings
            :type="$type"
            :locales="$activeLocales"
            :is-new="!$record?->exists"
            :statuses="$this->statusOptions"
            :workflow="$this->workflow"
            :categories="$this->categoryOptions"
            :tags="$this->tagOptions"
            :featured="$this->featuredPreview"
          />
        </div>
        <div x-show="$store.editor.tab === 'block'" x-cloak>
          <x-editor.inspector :locales="$activeLocales" />
        </div>
        @if ($type === "article")
          <div x-show="$store.editor.tab === 'review'" x-cloak>
            <x-content.article-workflow :workflow="$this->workflow" />
          </div>
        @endif
        @if ($this->canViewAudit)
          <div x-show="$store.editor.tab === 'audit'" x-cloak>
            <x-content.audit-panel />
          </div>
        @endif
      </div>
    </aside>
  </div>

  {{-- Satu modal untuk semua tombol ikon. Sprite ikon (<x-editor.icon-sprite />) dipasang di LAYOUT, bukan di sini. --}}
  <x-editor.icon-picker-modal />

  {{-- Panel debug: hanya tampil bila APP_DEBUG=true --}}
  <x-editor.debug />
</div>
