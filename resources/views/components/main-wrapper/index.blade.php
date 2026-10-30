@props (["isPreview" => false])

@if ($isPreview)
  <!-- 🟢 MODE PREVIEW: Layout khusus untuk membaca artikel -->
  <div
    {{
      $attributes->merge([
        "class" =>
          "w-full h-[calc(100vh-4rem)] flex flex-col bg-paper rounded-lg border-2 border-forest overflow-hidden",
      ])
    }}
  >
    <!-- AREA HEADER (Berdiri Sendiri) -->
    @if (isset($header))
      <div
        class="border-forest/10 w-full flex-none border-b bg-white p-4 px-6 lg:px-12"
      >
        <div
          class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 md:flex-row"
        >
          {{ $header }}
        </div>
      </div>
    @endif

    <!-- AREA ARTIKEL (Memenuhi Sisa Ruang & Bisa di-Scroll) -->
    <div class="w-full flex-1 overflow-x-hidden overflow-y-auto px-6 lg:px-12">
      <article
        class="tiptap [&>*:not(.tiptap-full-bleed):not([data-type='column-block'])]:max-w-4xl [&>*:not(.tiptap-full-bleed):not([data-type='column-block'])]:mx-auto [&_img]:rounded-xl [&_img]:shadow-sm [&_a]:text-forest mx-auto my-8 max-w-7xl"
      >
        {{ $slot }}
      </article>
    </div>
  </div>
@else
  <!-- 🔵 MODE STANDAR: Layout untuk form, tabel, detail user, dll bg-white -->
  <div
    {{
      $attributes->merge([
        "class" =>
          " rounded-lg w-full md:max-w-none flex flex-col items-center justify-center p-4 flex-1 grow",
      ])
    }}
  >
    <div
      class="flex h-full max-h-[calc(100vh-6rem)] min-h-0 w-full max-w-7xl min-w-0 scrollbar-thin flex-col items-center justify-start overflow-y-auto **:scrollbar-thin"
    >
      {{ $slot }}
    </div>
  </div>
@endif
