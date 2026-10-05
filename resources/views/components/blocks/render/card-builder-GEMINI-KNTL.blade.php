@props (["data", "lang" => "id", "isPreview" => false])

@php
  $gridCols = $data["grid"]["cols"] ?? 3;
  $gridMargin = $data["grid"]["margin_bottom"] ?? "mb-8";
  $cards = $data["cards"] ?? [];
@endphp

<div
  class="grid grid-cols-1 md:grid-cols-{{ $gridCols }} gap-4 lg:gap-6 {{ $gridMargin }}"
>
  @foreach ($cards as $card)
    @php
      $container = $card["container"] ?? [];
      $bg = $container["bg"] ?? "bg-white";
      $borderWidth = $container["border_width"] ?? "border";
      $borderStyle = $container["border_style"] ?? "border-solid";
      $borderColor = $container["border_color"] ?? "border-gray-200";
      $radius = $container["radius"] ?? "rounded-[18px]";
      $padding = $container["padding"] ?? "p-4";
      $alignY = $container["align_y"] ?? "items-start";

      // Matikan efek hover dan link jika sedang di dalam Live Preview Editor
      $hover = $isPreview
        ? ""
        : $container["hover"] ??
          "hover:-translate-y-1 hover:shadow-md transition-all duration-300";
      $cardUrl = $isPreview ? "" : $container["url"] ?? "";

      $columns = $card["layout"]["children"] ?? [];
      $totalElements = collect($columns)->sum(function ($col) {
        return count($col["children"] ?? []);
      });

      $gridTemplate = collect($columns)
        ->map(function ($c) {
          $w = $c["width"] ?? 1;
          return $w === "auto" ? "auto" : $w . "fr";
        })
        ->implode(" ");
    @endphp

    <{{ $cardUrl ? 'a' : 'div' }}
      {!! $cardUrl ? 'href="' . htmlspecialchars($cardUrl) . '"' : "" !!}
      class="block transform-gpu transition-all duration-500 ease-in-out h-full overflow-hidden {{ $bg }} {{ $borderWidth }} {{ $borderStyle }} {{ $borderColor }} {{ $radius }} {{ $hover }}"
    >
      <div class="{{ $padding }} h-full flex flex-col min-w-0">
        {{-- 🌟 PEMBUNGKUS BARIS (ROW) --}}
        <div
          class="grid {{ $alignY }} gap-4 lg:gap-5"
          style="grid-template-columns: {{ $gridTemplate }};"
        >
          @if ($totalElements === 0 && $isPreview)
            <div
              class="col-span-full flex w-full flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-white/50 px-4 py-10 text-center opacity-80 mix-blend-luminosity"
            >
              <x-dynamic-component
                component="lucide-layout"
                class="mb-3 h-8 w-8 text-gray-400"
                stroke-width="1.5"
              />
              <span
                class="text-[11px] font-extrabold tracking-widest text-gray-400 uppercase"
                >Kartu Masih Kosong</span
              >
              <span class="mt-1 text-[10px] font-semibold text-gray-400"
                >Tambahkan elemen dari editor</span
              >
            </div>
          @endif

          @foreach ($columns as $col)
            @php
              // Tarik properti align dari data kolom (default rata kiri/atas)
              $colAlign = $col["align"] ?? "items-start text-left";
            @endphp
            {{-- 🌟 PEMBUNGKUS KOLOM (COLUMN) --}}
            <div
              class="flex min-w-0 flex-col {{ $colAlign }} [&>*:last-child]:mb-0!"
            >
              @foreach ($col["children"] ?? [] as $el)
                @php
                  $type = $el["elementType"] ?? "text";
                  $style = $el["data"]["style"] ?? [];
                  $content = $el["data"]["content"] ?? [];
                @endphp

                {{-- 1. RENDER TEKS --}}
                @if ($type === "text")
                  @php
                    $text = $content[$lang] ?? ($content["id"] ?? "");
                    $isPill = $style["is_pill"] ?? false;
                    $margin = $style["margin"] ?? "mb-1.5";
                    $color = $style["color"] ?? "text-ink-soft";
                    $transform = $style["text_transform"] ?? "normal-case"; // 🌟 BARU: Dukungan Text Transform
                  @endphp

                  @if (!empty(trim($text)))
                    @if ($isPill)
                      @php
                        $pillBg = $style["pill_bg"] ?? "bg-goldy-soft";
                        $pillRadius = $style["pill_radius"] ?? "rounded-md";
                      @endphp
                      <div class="{{ $margin }}">
                        <span
                          class="inline-block {{ $pillBg }} {{ $color }} {{ $pillRadius }} {{ $transform }} px-2.5 py-1 text-[11px] font-bold tracking-widest"
                        >
                          {{ $text }}
                        </span>
                      </div>
                    @else
                      @php
                        $font = $style["font"] ?? "font-sans";
                        $size = $style["size"] ?? "text-[15px]";
                        $weight = $style["weight"] ?? "font-normal";
                      @endphp
                      <div
                        class="{{ $font }} {{ $size }} {{ $weight }} {{ $color }} {{ $margin }} {{ $transform }}"
                      >
                        {{ $text }}
                      </div>
                    @endif
                  @endif

                  {{-- 2. RENDER IKON --}}
                @elseif ($type === "icon")
                  @php
                    $icon = $content["icon"] ?? "";
                    $iconBg = $style["bg"] ?? "bg-mist";
                    $iconColor = $style["color"] ?? "text-foresty";
                    $iconSize = $style["size"] ?? "w-10 h-10";
                    $iconRadius = $style["radius"] ?? "rounded-[14px]";
                    $iconMargin = $style["margin"] ?? "mb-3";
                  @endphp

                  @if ($icon)
                    <div
                      class="shrink-0 flex items-center justify-center {{ $iconBg }} {{ $iconColor }} {{ $iconSize }} {{ $iconRadius }} {{ $iconMargin }}"
                    >
                      <x-dynamic-component
                        :component="'lucide-' . $icon"
                        class="h-1/2 w-1/2"
                        stroke-width="2"
                      />
                    </div>
                  @endif

                  {{-- 3. RENDER PROFIL (Foto, Nama, Jabatan) --}}
                @elseif ($type === "profile")
                  @php
                    $imgUrl = $content["image_url"] ?? "";
                    $name = $content["name"] ?? "";
                    $role = $content["role"][$lang] ?? ($content["role"]["id"] ?? "");

                    $avatarSize = $style["avatar_size"] ?? "w-10 h-10";
                    $avatarRadius = $style["avatar_radius"] ?? "rounded-full";
                    $align = $style["align"] ?? "items-center";
                    $margin = $style["margin"] ?? "mb-4";
                  @endphp

                  @if ($name || $imgUrl)
                    <div class="flex {{ $align }} gap-3 {{ $margin }} min-w-0">
                      @if ($imgUrl)
                        <img
                          src="{{ $imgUrl }}"
                          alt="{{ $name }}"
                          class="shrink-0 object-cover shadow-sm {{ $avatarSize }} {{ $avatarRadius }}"
                        />
                      @else
                        {{-- Fallback jika foto kosong tapi profil ditambahkan --}}
                        <div
                          class="shrink-0 flex items-center justify-center bg-gray-100 text-gray-400 border border-gray-200 shadow-sm {{ $avatarSize }} {{ $avatarRadius }}"
                        >
                          <x-dynamic-component
                            component="lucide-user"
                            class="h-1/2 w-1/2"
                          />
                        </div>
                      @endif

                      <div class="flex min-w-0 flex-col">
                        @if ($name)
                          <span
                            class="truncate text-sm font-bold text-gray-900"
                            >{{ $name }}</span
                          >
                        @endif
                        @if ($role)
                          <span
                            class="truncate text-xs text-gray-500"
                            >{{ $role }}</span
                          >
                        @endif
                      </div>
                    </div>
                  @endif

                  {{-- 4. RENDER INISIAL --}}
                @elseif ($type === "initial")
                  @php
                    $initials = $content["text"] ?? "";
                    $bgColor = $style["bg_color"] ?? "bg-foresty";
                    $textColor = $style["text_color"] ?? "text-white";
                    $size = $style["size"] ?? "w-10 h-10";
                    $radius = $style["radius"] ?? "rounded-full";
                    $fontSize = $style["font_size"] ?? "text-sm";
                    $fontWeight = $style["weight"] ?? "font-bold";
                    $margin = $style["margin"] ?? "mb-2";
                  @endphp

                  @if ($initials)
                    <div
                      class="shrink-0 flex items-center justify-center uppercase tracking-wide shadow-sm {{ $margin }} {{ $bgColor }} {{ $textColor }} {{ $size }} {{ $radius }} {{ $fontSize }} {{ $fontWeight }}"
                    >
                      {{ $initials }}
                    </div>
                  @endif

                  {{-- 5. RENDER TOMBOL AKSI --}}
                @elseif ($type === "button")
                  @php
                    // Label menggunakan multi-lang, URL tidak
                    $btnLabel = $content["label"][$lang] ?? ($content["label"]["id"] ?? "");
                    $btnUrl = $content["url"] ?? "#";
                    $btnVariant = $style["variant"] ?? "solid";
                    $btnColor = $style["color"] ?? "foresty";
                    $btnRadius = $style["radius"] ?? "rounded-lg";
                    $btnAlign = $style["align"] ?? "self-start";

                    // Pemetaan Tema Warna
                    $btnClasses = match ($btnVariant . "-" . $btnColor) {
                      "solid-foresty" => "bg-foresty text-white hover:bg-emerald-800",
                      "solid-coral" => "bg-coral text-white hover:bg-orange-600",
                      "solid-dark" => "bg-gray-800 text-white hover:bg-gray-900",
                      "outline-foresty"
                        => "border-2 border-foresty text-foresty hover:bg-foresty hover:text-white",
                      "outline-coral"
                        => "border-2 border-coral text-coral hover:bg-coral hover:text-white",
                      "outline-dark"
                        => "border-2 border-gray-800 text-gray-800 hover:bg-gray-800 hover:text-white",
                      "ghost-foresty" => "text-foresty hover:bg-sage-soft",
                      "ghost-coral" => "text-coral hover:bg-orange-50",
                      "ghost-dark" => "text-gray-800 hover:bg-gray-200",
                      default => "bg-gray-200 text-gray-800 hover:bg-gray-300",
                    };

                    // Konversi self-start ke justify-start untuk pembungkus tombol
                    $wrapperAlign = str_replace("self-", "justify-", $btnAlign);
                    if (str_contains($colAlign, "items-center")) {
                      $wrapperAlign = "justify-center";
                    } elseif (str_contains($colAlign, "items-end")) {
                      $wrapperAlign = "justify-end";
                    }
                  @endphp

                  @if ($btnLabel)
                    <div class="mb-3 flex w-full {{ $wrapperAlign }}">
                      <{{ $cardUrl ? 'span' : 'a' }}
                        {!! $cardUrl ? "" : 'href="' . htmlspecialchars($btnUrl) . '"' !!}
                        class="inline-block px-5 py-2 text-sm font-bold text-center transition-colors {{ $btnRadius }} {{ $btnClasses }} {{ $btnAlign === 'w-full' ? 'w-full' : '' }}"
                      >
                        {{ $btnLabel }}
                      </{{ $cardUrl ? 'span' : 'a' }}>
                    </div>
                  @endif

                  {{-- 6. RENDER AKORDION / FAQ --}}
                @elseif ($type === "accordion")
                  @php
                    $question = $content["question"][$lang] ?? ($content["question"]["id"] ?? "");
                    $answer = $content["answer"][$lang] ?? ($content["answer"]["id"] ?? "");
                    $theme = $style["theme"] ?? "foresty";
                    $margin = $style["margin"] ?? "mb-3";

                    $activeText = match ($theme) {
                      "coral" => "text-coral",
                      "dark" => "text-gray-900",
                      default => "text-foresty",
                    };
                    $activeBg = match ($theme) {
                      "coral" => "bg-orange-50",
                      "dark" => "bg-gray-200",
                      default => "bg-sage-soft",
                    };
                    $hoverBorder = match ($theme) {
                      "coral" => "hover:border-coral/50",
                      "dark" => "hover:border-gray-500",
                      default => "hover:border-foresty/50",
                    };
                  @endphp

                  @if (!empty($question) || !empty($answer))
                    <div
                      x-data="{ open: false }"
                      class="overflow-hidden w-full rounded-xl border border-gray-200 bg-white shadow-sm transition-colors duration-300 {{ $hoverBorder }} {{ $margin }}"
                      x-on:click.prevent
                    >
                      <button
                        type="button"
                        x-on:click="open = !open"
                        class="flex w-full cursor-pointer items-center justify-between gap-4 px-5 py-4 text-left outline-none"
                      >
                        <span
                          class="text-[15px] font-semibold transition-colors"
                          x-bind:class="open ? '{{ $activeText }}' : 'text-gray-800'"
                        >
                          {{ $question }}
                        </span>

                        <div
                          class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-50 transition-transform duration-300"
                          x-bind:class="open ? 'rotate-180 {{ $activeBg }} {{ $activeText }}' : 'text-gray-400'"
                        >
                          <x-dynamic-component
                            component="lucide-chevron-down"
                            class="h-4 w-4"
                            stroke-width="2.5"
                          />
                        </div>
                      </button>

                      <div x-show="open" x-collapse x-cloak>
                        <div
                          class="border-t border-gray-100 px-5 pt-3 pb-5 text-sm leading-relaxed text-gray-600"
                        >
                          {!!
                            nl2br(
                              e($answer),
                            )
                          !!}
                        </div>
                      </div>
                    </div>
                  @endif
                @endif

              @endforeach
            </div>
          @endforeach
        </div>
      </div>
    </{{ $cardUrl ? 'a' : 'div' }}>
  @endforeach
</div>
