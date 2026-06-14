@props([
    'command', // Nama perintah murni Tiptap (cth: 'toggleBold') atau fungsi penuh Alpine (cth: 'toggleHiddenMarks()')
    'activeName', // Identifikasi status aktif (cth: 'bold', 'left', '1', atau variabel 'showMarks')
    'activeParams' => '{}', // Argumen tambahan berbentuk string JSON objek (default: '{}')
    'activeType' => 'default', // Kategori penanganan adapter: 'default', 'heading', 'textAlign', atau 'alpine'
    'title' => '', // Teks petunjuk saat tombol diarahkan kursor (Tooltip)
    'icon' => null, // Nama ikon Lucide tanpa prefix 'lucide-'
])

@php
    /**
     * SANITISASI PARAMETER:
     * Jika activeParams tidak diisi atau bernilai string 'null', paksa kembali ke string objek kosong '{}'.
     * Ini krusial untuk mencegah syntax error 'Unexpected token' akibat koma kosong pada JavaScript di browser.
     */
    // $sanitizedParams = empty($activeParams) || $activeParams === 'null' ? '{}' : $activeParams;

    /**
     * LOGIKA REKAYASA @CLICK (EKSEKUSI PERINTAH):
     * 1. Jika tipe 'alpine': Jalankan isi variabel $command secara mentah karena merupakan fungsi lokal langsung.
     * 2. Jika tipe 'textAlign': Bungkus $activeName dengan petik tunggal agar dikirim sebagai string murni (cth: 'center').
     * 3. Jika tipe 'default'/'heading': Kirim nama fungsi dan operasikan objek parameter (jika kosong, kirim 'null').
     */
    // $clickExpression = $activeType === 'alpine'
    //     ? $command
    //     : ($activeType === 'textAlign'
    //         ? "runCommand('$command', '$activeName')"
    //         : "runCommand('$command', $sanitizedParams !== '{}' ? $sanitizedParams : null)");

    /**
     * LOGIKA REKAYASA :CLASS (WARNA AKTIF TOMBOL):
     * 1. Jika tipe 'alpine': Ikat variabel 'updatedAt' bersama variabel lokal Alpine agar reaktivitas visualnya sensitif.
     * 2. Jika tipe lainnya: Serahkan evaluasi kondisi aktif ke helper Tiptap 'checkButtonActive' di tiptap-editor.js.
     */
    // $classExpression = $activeType === 'alpine'
    //     ? '(updatedAt && ' . $activeName . ')'
    //     : "checkButtonActive('$activeName', $sanitizedParams, '$activeType')";
    // Sanitisasi parameter dari string bawaan Blade
    // $sanitizedParams =
    //     empty($activeParams) || $activeParams === 'null' || $activeParams === '{}' ? '{}' : $activeParams;

    // $clickExpression =
    //     $activeType === 'alpine'
    //         ? $command
    //         : ($activeType === 'textAlign'
    //             ? "runCommand('$command', '$activeName')"
    //             : "runCommand('$command', $sanitizedParams !== '{}' ? $sanitizedParams : null)");

    // // 💡 ADJUSTMENT BARU: Jika sanitizedParams kosong '{}', operasikan ke fungsi JS sebagai objek kosong asli atau null
    // $classExpression =
    //     $activeType === 'alpine'
    //         ? '(updatedAt && ' . $activeName . ')'
    //         : "checkButtonActive('$activeName', $sanitizedParams === '{}' ? {} : $sanitizedParams, '$activeType')";
    $sanitizedParams = empty($activeParams) || $activeParams === 'null' ? '{}' : $activeParams;

    /**
     * ADJUSTMENT TAMBAHAN UNTUK ORDERED LIST:
     * Jika tipe 'orderedList', panggil fungsi toggleCustomOrderedList('$activeName') dengan melemparkan string murninya.
     */
    $clickExpression =
        $activeType === 'alpine'
            ? $command
            : ($activeType === 'textAlign'
                ? "runCommand('$command', '$activeName')"
                : ($activeType === 'orderedList'
                    ? "toggleCustomOrderedList('$activeName')"
                    : "runCommand('$command', $sanitizedParams !== '{}' ? $sanitizedParams : null)"));

    $classExpression =
        $activeType === 'alpine'
            ? '(updatedAt && ' . $activeName . ')'
            : "checkButtonActive('$activeName', $sanitizedParams, '$activeType')";
@endphp

{{-- Sekarang tag pembuka elemen HTML button sangat bersih, ringkas, dan bebas dari resiko crash compile --}}
<button type="button" @click="{{ $clickExpression }}"
    :class="{{ $classExpression }}
        ?
        'bg-sage-soft text-forest border-forest font-semibold shadow-sm' :
        'bg-zinc-50 text-gray-700 border-transparent'"
    {{ $attributes->merge([
        'class' =>
            'p-1.5 min-w-[2.25rem] h-9 hover:bg-sage-soft hover:text-forest transition rounded flex items-center justify-center gap-1 text-sm cursor-pointer border',
    ]) }}
    title="{{ $title }}">

    @if ($icon)
        <x-dynamic-component :component="'lucide-' . $icon" class="h-4 w-4" stroke-width="2.5" />
    @else
        <x-dynamic-component :component="'lucide-bug'" class="h-4 w-4" stroke-width="2.5" />
    @endif

    {{ $slot }}
</button>
