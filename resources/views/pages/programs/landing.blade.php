<x-layouts.app>
    <x-slot:title>
        Halaman Beranda
    </x-slot>

    {{-- style="background-image: url('{{ asset('/banner-1.webp') }}');" --}}
    <div class="flex flex-col items-center justify-center">
        <section  class="bg-paper p-12 w-full flex items-start justify-start bg-cover bg-center bg-no-repeat ">
            <div class="max-w-7xl mx-auto px-5 sm:px-8 grid grid-cols-1 lg:grid-cols-[1.05fr_0.95fr] gap-12 lg:gap-14 items-center">
                <div>
                    <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark">
                    <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
                    Yayasan Kesehatan Masyarakat
                    </span>
                    <h1 class="font-display font-semibold text-[clamp(2.4rem,4.6vw,3.6rem)] leading-[1.08] text-foresty mt-4 mb-5">
                    Menyalakan <em class="italic text-coral font-medium">Sinar Sehat</em> di Setiap Rumah
                    </h1>
                    <p class="text-lg text-ink-soft max-w-[520px] mb-8">Kami mendampingi ibu hamil, melindungi anak lewat imunisasi lengkap, dan memutus rantai malaria di desa-desa dengan akses layanan kesehatan yang masih terbatas.</p>
                    <div class="flex gap-4 flex-wrap mb-11">
                    <a href="#donasi" class="inline-flex items-center justify-center gap-2 font-bold text-[15.5px] px-7 py-3.5 rounded-full border-2 border-transparent bg-coral text-white hover:bg-coral-dark hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-14px_rgba(228,35,38,0.55)] transition-all duration-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-coral focus-visible:outline-offset-[3px]">Donasi Sekarang</a>
                    <a href="#program" class="inline-flex items-center justify-center gap-2 font-bold text-[15.5px] px-7 py-3.5 rounded-full border-2 border-foresty bg-transparent text-foresty hover:bg-foresty hover:text-white hover:-translate-y-0.5 transition-all duration-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-coral focus-visible:outline-offset-[3px]">Lihat Program Kami</a>
                    </div>
                    <div class="flex gap-3.5 flex-wrap">
                    <a href="#program" class="flex items-center gap-2.5 bg-white border border-foresty/15 rounded-full py-[9px] pr-[18px] pl-2.5 font-semibold text-sm text-foresty shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)]">
                        <span class="w-[26px] h-[26px] rounded-full flex items-center justify-center shrink-0 bg-goldy-soft">
                        <svg viewBox="0 0 24 24" class="w-[15px] h-[15px]" fill="none" stroke="#064F3B" stroke-width="2"><path d="M12 21s-7-4.6-9.3-9.2C1.4 8.6 3 5 6.6 5c2 0 3.4 1.1 4.4 2.6C12 6.1 13.4 5 15.4 5 19 5 20.6 8.6 19.3 11.8 17 16.4 12 21 12 21z"/></svg>
                        </span>
                        Ibu &amp; Anak
                    </a>
                    <a href="#program" class="flex items-center gap-2.5 bg-white border border-foresty/15 rounded-full py-[9px] pr-[18px] pl-2.5 font-semibold text-sm text-foresty shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)]">
                        <span class="w-[26px] h-[26px] rounded-full flex items-center justify-center shrink-0 bg-mist">
                        <svg viewBox="0 0 24 24" class="w-[15px] h-[15px]" fill="none" stroke="#064F3B" stroke-width="2"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Z"/><path d="M9 12l2 2 4-4"/></svg>
                        </span>
                        Imunisasi
                    </a>
                    <a href="#program" class="flex items-center gap-2.5 bg-white border border-foresty/15 rounded-full py-[9px] pr-[18px] pl-2.5 font-semibold text-sm text-foresty shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)]">
                        <span class="w-[26px] h-[26px] rounded-full flex items-center justify-center shrink-0 bg-[#FBE6E6]">
                        <svg viewBox="0 0 24 24" class="w-[15px] h-[15px]" fill="none" stroke="#E42326" stroke-width="2"><path d="M4 20c3-6 13-6 16 0"/><circle cx="12" cy="9" r="5"/><path d="M9 9h6M12 6v6"/></svg>
                        </span>
                        Malaria
                    </a>
                    </div>
                </div>

                <div class="flex items-center justify-center" aria-hidden="true">
                    {{-- V1 --}}
                    {{-- <svg viewBox="0 0 300 300" class="w-[min(380px,80vw)] h-auto" xmlns="http://www.w3.org/2000/svg">
                        <g class="stroke-coral origin-[150px_150px] animate-spin-slow motion-reduce:animate-none" stroke-width="6" stroke-linecap="round">
                            <line x1="208.0" y1="150.0" x2="288.0" y2="150.0" />
                            <line x1="203.6" y1="172.2" x2="249.8" y2="191.3" />
                            <line x1="191.0" y1="191.0" x2="247.6" y2="247.6" />
                            <line x1="172.2" y1="203.6" x2="191.3" y2="249.8" />
                            <line x1="150.0" y1="208.0" x2="150.0" y2="288.0" />
                            <line x1="127.8" y1="203.6" x2="108.7" y2="249.8" />
                            <line x1="109.0" y1="191.0" x2="52.4" y2="247.6" />
                            <line x1="96.4" y1="172.2" x2="50.2" y2="191.3" />
                            <line x1="92.0" y1="150.0" x2="12.0" y2="150.0" />
                            <line x1="96.4" y1="127.8" x2="50.2" y2="108.7" />
                            <line x1="109.0" y1="109.0" x2="52.4" y2="52.4" />
                            <line x1="127.8" y1="96.4" x2="108.7" y2="50.2" />
                            <line x1="150.0" y1="92.0" x2="150.0" y2="12.0" />
                            <line x1="172.2" y1="96.4" x2="191.3" y2="50.2" />
                            <line x1="191.0" y1="109.0" x2="247.6" y2="52.4" />
                            <line x1="203.6" y1="127.8" x2="249.8" y2="108.7" />
                        </g>
                        <circle cx="150" cy="150" r="92" class="fill-goldy"/>
                        <circle cx="150" cy="150" r="92" class="fill-none stroke-foresty opacity-15" stroke-width="2"/>
                        <path d="M150 205C150 205 108 172 108 143C108 122 122 108 139 108C144.5 108 148 111 150 116C152 111 155.5 108 161 108C178 108 192 122 192 143C192 172 150 205 150 205Z" class="fill-foresty"/>
                        <rect x="140" y="128" width="20" height="46" rx="3" class="fill-white"/>
                        <rect x="127" y="141" width="46" height="20" rx="3" class="fill-white"/>
                    </svg> --}}
                    <svg viewBox="0 0 300 300" class="w-[min(380px,80vw)] h-auto" xmlns="http://www.w3.org/2000/svg">
                        <g class="stroke-coral origin-[150px_150px] animate-spin-slow motion-reduce:animate-none" stroke-width="6" stroke-linecap="round">
                            <line x1="208.0" y1="150.0" x2="288.0" y2="150.0" />
                            <line x1="203.6" y1="172.2" x2="249.8" y2="191.3" />
                            <line x1="191.0" y1="191.0" x2="247.6" y2="247.6" />
                            <line x1="172.2" y1="203.6" x2="191.3" y2="249.8" />
                            <line x1="150.0" y1="208.0" x2="150.0" y2="288.0" />
                            <line x1="127.8" y1="203.6" x2="108.7" y2="249.8" />
                            <line x1="109.0" y1="191.0" x2="52.4" y2="247.6" />
                            <line x1="96.4" y1="172.2" x2="50.2" y2="191.3" />
                            <line x1="92.0" y1="150.0" x2="12.0" y2="150.0" />
                            <line x1="96.4" y1="127.8" x2="50.2" y2="108.7" />
                            <line x1="109.0" y1="109.0" x2="52.4" y2="52.4" />
                            <line x1="127.8" y1="96.4" x2="108.7" y2="50.2" />
                            <line x1="150.0" y1="92.0" x2="150.0" y2="12.0" />
                            <line x1="172.2" y1="96.4" x2="191.3" y2="50.2" />
                            <line x1="191.0" y1="109.0" x2="247.6" y2="52.4" />
                            <line x1="203.6" y1="127.8" x2="249.8" y2="108.7" />
                        </g>
                        <circle cx="150" cy="150" r="92" class="fill-goldy"/>
                        <circle cx="150" cy="150" r="92" class="fill-none stroke-foresty opacity-15" stroke-width="2"/>
                        <circle cx="150" cy="101" r="17" class="fill-foresty"/>
                        <path d="M150 215C150 215 108 182 108 153C108 132 122 118 139 118C144.5 118 148 121 150 126C152 121 155.5 118 161 118C178 118 192 132 192 153C192 182 150 215 150 215Z" class="fill-foresty"/>
                        <rect x="140" y="138" width="20" height="46" rx="3" class="fill-white"/>
                        <rect x="127" y="151" width="46" height="20" rx="3" class="fill-white"/>
                    </svg>
                </div>
            </div>
        </section>
        <!-- ABOUT -->
        <section class="py-16 sm:py-24" id="contentStart">
            <div class="max-w-7xl mx-auto px-5 sm:px-8 grid grid-cols-1 lg:grid-cols-[0.9fr_1.1fr] gap-9 lg:gap-16 items-start">
                <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                    <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark">
                    <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
                    Tentang Kami
                    </span>
                    <p class="font-display font-medium text-[26px] leading-[1.4] text-foresty mt-4">"Sinar" berarti cahaya harapan. <em class="italic text-coral">"Bhakti"</em> berarti pengabdian. Keduanya menuntun setiap langkah kerja kami di lapangan.</p>
                </div>
                <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                    <p class="text-ink-soft mb-4">Yayasan Sinar Bhakti Husada berdiri untuk menjawab satu masalah nyata: banyak keluarga di daerah terpencil Indonesia masih sulit menjangkau layanan kesehatan dasar. Kami hadir langsung di dusun dan desa, bekerja sama dengan puskesmas, kader posyandu, dan pemerintah setempat.</p>
                    <p class="text-ink-soft mb-4">Tiga program utama kami — kesehatan ibu &amp; anak, imunisasi, dan penanganan malaria — dipilih karena ketiganya adalah titik kritis yang paling menentukan masa depan sebuah keluarga, terutama di wilayah endemis seperti Nusa Tenggara Timur, Maluku, dan Papua.</p>
                    <div class="flex gap-7 mt-7 flex-wrap">
                    <div><b class="block font-display text-[26px] text-foresty">2014</b><span class="text-[13px] text-ink-soft">Tahun berdiri</span></div>
                    <div><b class="block font-display text-[26px] text-foresty">76</b><span class="text-[13px] text-ink-soft">Desa dampingan</span></div>
                    <div><b class="block font-display text-[26px] text-foresty">18</b><span class="text-[13px] text-ink-soft">Kabupaten tersebar</span></div>
                    </div>
                </div>
            </div>
        </section>

        <x-cards.programs />
        <x-cards.impact />
        <x-cards.testimonials />
        <x-cards.cta />
        <x-cards.contact />

        {{-- DIVIDER --}}

    </div>

</x-layouts.app>
