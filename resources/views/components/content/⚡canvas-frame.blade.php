<?php

use App\Content\PreviewStore;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Bingkai kanvas: halaman pratinjau yang dimuat DI DALAM <iframe> kanvas editor (atau dibuka di tab baru).
 * Membaca isi yang BELUM disimpan dari cache lewat token (App\Content\PreviewStore); tidak menyentuh database.
 * Memakai layout POLOS situs (layouts.landing.index: tanpa sidebar/bilah atas admin) supaya tampilannya sama dengan halaman publik.
 * JANGAN memakai layouts.landing.dynamic-preview: tanpa ?mode=raw ia memilih layouts.app (shell CMS), sehingga editor muncul di dalam editor.
 *
 * Protokol dengan editor (postMessage, hanya antar jendela yang SAMA asal): lihat blok @script di bawah.
 */
new #[Layout("layouts.landing.index")] class extends Component {
  #[Locked]
  public string $token = "";

  public string $lang = "id";

  /** @var string[] */
  public array $activeLocales = [];

  public function mount(string $token): void
  {
    abort_unless(PreviewStore::validToken($token), 404);

    $this->token = $token;
    $this->activeLocales = config("app.supported_locales", ["id", "en"]);

    $lang = (string) request()->query("lang", app()->getLocale());
    $this->lang = in_array($lang, $this->activeLocales, true)
      ? $lang
      : $this->activeLocales[0];

    // dibuka langsung dengan token milik orang lain / yang tidak ada: 404 (kedaluwarsa SETELAH terbuka ditangani halaman sendiri)
    abort_if($this->payload === null, 404);
  }

  #[Computed]
  public function payload(): ?array
  {
    return PreviewStore::make()->get($this->token, auth()->id());
  }

  /** Bahasa dari editor ("Lihat sebagai"); nilai di luar daftar bahasa dikembalikan, bukan dipercaya. */
  public function updatedLang(): void
  {
    if (!in_array($this->lang, $this->activeLocales, true)) {
      $this->lang = $this->activeLocales[0];
    }
  }
};
?>

<div
  class="box-border flex h-full w-full flex-col overflow-x-hidden"
  data-canvas-frame
>
  @if ($this->payload)
    <x-content.sections
      :blocks="$this->payload['blocks']"
      :order="$this->payload['order']"
      :settings="$this->payload['settings']"
      :lang="$lang"
      mode="canvas"
    />
  @else
    <div
      data-canvas-expired
      class="flex min-h-[40vh] items-center justify-center p-8 text-center text-sm text-gray-500"
    >
      Pratinjau kedaluwarsa. Mengambil ulang dari editor…
    </div>
  @endif
</div>

@script
  <script>
    // ---- Protokol bingkai <-> editor --------------------------------------------------------------------------------------------
    // Editor -> bingkai : {type:'canvas-refresh'} render ulang dari cache | {type:'canvas-focus', id, scroll} sorot blok
    //                     {type:'change-lang', lang} ganti bahasa tampilan (sama dengan protokol page-preview)
    // Bingkai -> editor : {type:'canvas-ready'} | {type:'canvas-select', id, blockType} blok diklik | {type:'canvas-expired'}
    // Pesan HANYA diterima dari jendela induk yang sama asalnya; di tab baru (tanpa induk) klik tetap berfungsi normal.
    if (!window.__canvasFrame) {
      window.__canvasFrame = true;

      const origin = window.location.origin;
      const parentWin = window.parent;
      const inFrame = parentWin !== window;
      const ID = /^[A-Za-z0-9_-]{1,64}$/;
      const send = (msg) => {
        if (inFrame) parentWin.postMessage(msg, origin);
      };

      // satu <style> untuk hover + sorotan fokus: selamat dari morph Livewire (tidak bergantung pada kelas di elemen)
      const style = document.createElement("style");
      style.id = "canvas-focus-style";
      document.head.appendChild(style);
      const BASE =
        "[data-block-id]{cursor:pointer}[data-block-id]:hover{outline:1px dashed rgba(6,79,59,.55);outline-offset:-1px}";
      let focusedId = null;
      const paint = () => {
        style.textContent = inFrame
          ? BASE +
            (focusedId
              ? `[data-block-id="${focusedId}"]{outline:2px solid #064F3B !important;outline-offset:-2px}`
              : "")
          : "";
      };
      paint();

      const scrollToBlock = (id) => {
        const el = document.querySelector(`[data-block-id="${id}"]`);
        if (el) el.scrollIntoView({ block: "center", behavior: "smooth" });
      };

      // klik = pilih blok (tautan dan tombol tidak berjalan di dalam kanvas)
      document.addEventListener(
        "click",
        (e) => {
          if (!inFrame) return;
          const el = e.target.closest
            ? e.target.closest("[data-block-id]")
            : null;
          if (!el) return;
          e.preventDefault();
          e.stopPropagation();
          send({
            type: "canvas-select",
            id: el.getAttribute("data-block-id"),
            blockType: el.getAttribute("data-block-type") || "",
          });
        },
        true,
      );

      window.addEventListener("message", (e) => {
        if (e.origin !== origin || e.source !== parentWin) return;
        const m = e.data;
        if (!m || typeof m !== "object") return;

        if (m.type === "canvas-refresh") {
          $wire.$refresh();
        } else if (m.type === "canvas-focus") {
          focusedId = typeof m.id === "string" && ID.test(m.id) ? m.id : null;
          paint();
          if (focusedId && m.scroll) scrollToBlock(focusedId);
        } else if (m.type === "change-lang" && typeof m.lang === "string") {
          $wire.$set("lang", m.lang);
        }
      });

      const checkExpired = () => {
        if (document.querySelector("[data-canvas-expired]"))
          send({ type: "canvas-expired" });
      };
      Livewire.hook("commit", ({ succeed }) =>
        succeed(() => setTimeout(checkExpired, 0)),
      );

      send({ type: "canvas-ready" });
      checkExpired();
    }
  </script>
@endscript
