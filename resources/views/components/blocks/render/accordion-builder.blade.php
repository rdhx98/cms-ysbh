{{--
  Render PUBLIK blok Akordion / FAQ (blocks.render.accordion-builder). Memakai <details>/<summary> asli: jalan tanpa JavaScript, bisa dioperasikan
  dengan papan ketik dan pembaca layar, dan isi jawaban tetap ada di HTML (baik untuk mesin pencari).

  Data dibersihkan LAGI di sini (AccordionBlock::sanitize). Jawaban = teks biasa yang di-escape lalu diberi struktur (FaqText): tidak ada
  HTML dari penulis yang lolos. Pertanyaan tanpa teks dilewati. Kelas CSS dari daftar tetap (AccordionStyle).
  Di kanvas editor ($canvasMode dibagikan oleh <x-content.sections>) semua pertanyaan terbuka supaya jawabannya terlihat.
--}}
@props ([
  "block" => [],
  "data" => [],
  "lang" => "id",
  "allContent" => [],
])

@php
  use App\Content\Blocks\AccordionStyle;
  use App\Content\Blocks\BlockSanitizer;
  use App\Content\Blocks\FaqText;

  $inCanvas = (bool) ($canvasMode ?? false);
  $clean = BlockSanitizer::forType(
    "accordion-builder",
    is_array($data) ? $data : [],
    array_values(array_unique([$lang, "id", "en"])),
  );

  // teks: bahasa halaman, lalu id, lalu en
  $pick = function (array $byLocale) use ($lang): string {
    foreach ([$lang, "id", "en"] as $l) {
      if (($byLocale[$l] ?? "") !== "") {
        return $byLocale[$l];
      }
    }

    return "";
  };

  $items = [];
  foreach ($clean["items"] as $item) {
    $question = $pick($item["question"]);
    if ($question === "") {
      continue;
    }
    $items[] = [
      "id" => $item["id"],
      "question" => $question,
      "html" => FaqText::toHtml($pick($item["answer"])),
    ];
  }

  // satu terbuka sekali waktu: atribut name yang sama membuat peramban menutup yang lain (tidak dipakai di kanvas: semua terbuka)
  $group =
    !$clean["allow_multiple"] && !$inCanvas
      ? "faq-" .
        preg_replace("/[^A-Za-z0-9_-]/", "", (string) ($block["id"] ?? "x"))
      : null;
  $style = $clean["style"];
  $color = $clean["color"];
@endphp

@if ($items)
  <div class="{{ AccordionStyle::wrapper($style) }}" data-faq>
    @foreach ($items as $i => $item)
      <details
        class="{{ AccordionStyle::item($style, $color) }}"
        @if ($group) name="{{ $group }}" @endif
        @if ($inCanvas || ($clean["first_open"] && $i === 0)) open @endif
      >
        <summary class="{{ AccordionStyle::summary($style, $color) }}">
          <span>{{
            $item[
              "question"
            ]
          }}</span>
          @if ($clean["marker"] === "plus")
            <svg class="{{ AccordionStyle::marker($color) }} group-open:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
            <svg class="{{ AccordionStyle::marker($color) }} hidden group-open:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14" /></svg>
          @else
            <svg class="{{ AccordionStyle::marker($color) }} transition-transform duration-200 group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
          @endif
        </summary>
        @if ($item["html"] !== "")
          <div class="{{ AccordionStyle::answer($style) }}">
            {!! $item["html"] !!}
          </div>
        @endif
      </details>
    @endforeach
  </div>

  @if ($clean["schema"])
    <script type="application/ld+json">
      {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($i) => ['@type' => 'Question', 'name' => $i['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $i['html']]], $items),
          ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
  @endif
@endif
