{{--
  <x-editor.outline> — pohon struktur halaman (panel kiri). Menggantikan <x-editor.outline-sementara>.

  Props: $content (blok per ID) dan $order (urutan tingkat atas) — properti Livewire builder.
  Aksi yang dipanggil di server (semuanya divalidasi di sana): addBlockAt, moveBlock, duplicateBlock, removeBlock.
  Ikon memakai sprite (<x-editor.icon-sprite /> di layout).
--}}
@props(['content' => [], 'order' => []])

<nav x-data="outline" x-on:block-added.window="added($event)" aria-label="Struktur halaman" class="flex flex-col gap-0.5 text-xs">
  @forelse ($order as $i => $id)
    <x-editor.outline-node :id="$id" :content="$content" :index="$i" :count="count($order)" />
  @empty
    <p class="rounded-lg bg-gray-50 p-3 leading-relaxed text-gray-500">Belum ada blok. Tambahkan yang pertama di bawah.</p>
  @endforelse

  <div class="mt-2">
    <x-editor.add-menu :types="\App\Editor\BlockPalette::rootTypes()" label="Tambah blok" />
  </div>
</nav>
