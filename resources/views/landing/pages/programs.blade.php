<x-layouts.app>
    <x-slot:title>
        Daftar Artikel
    </x-slot:title>

    <!-- BREADCRUMB & HERO -->
    <section class="pt-12 pb-0 sm:pt-16 sm:pb-0">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="flex items-center gap-2 text-[13.5px] text-ink-soft mb-8 flex-wrap reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <a href="sinar-bhakti-husada-landing.html" class="font-semibold hover:text-foresty transition-colors">Beranda</a>
                <span class="opacity-50">/</span>
                <span class="text-foresty font-bold opacity-100">Artikel</span>
            </div>

            <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>
                    Artikel &amp; Cerita Lapangan
                </span>
                <h1 class="font-display text-[clamp(2rem,4vw,3rem)] font-semibold leading-[1.12] text-foresty mt-4 mb-3.5 max-w-[720px] tracking-tight">Kisah dan Catatan dari Lapangan</h1>
                <p class="text-[17px] text-ink-soft max-w-[640px]">Dokumentasi kegiatan, laporan progres program, dan cerita langsung dari tim kami yang bekerja di kampung-kampung dampingan.</p>
            </div>

            <!-- FILTER ROW -->
            <div class="flex gap-2.5 flex-wrap my-9 sm:my-11 reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <!-- Gunakan varian arbitrary [&.active] untuk merespon class toggle dari JS -->
                <button class="filter-chip active px-5 py-2.5 rounded-full border border-foresty/15 bg-white text-ink-soft font-bold text-[13.5px] transition-all duration-200 cursor-pointer hover:bg-foresty hover:text-white hover:border-foresty [&.active]:bg-foresty [&.active]:text-white [&.active]:border-foresty" data-filter="all">Semua</button>
                <button class="filter-chip px-5 py-2.5 rounded-full border border-foresty/15 bg-white text-ink-soft font-bold text-[13.5px] transition-all duration-200 cursor-pointer hover:bg-foresty hover:text-white hover:border-foresty [&.active]:bg-foresty [&.active]:text-white [&.active]:border-foresty" data-filter="kegiatan">Kegiatan</button>
                <button class="filter-chip px-5 py-2.5 rounded-full border border-foresty/15 bg-white text-ink-soft font-bold text-[13.5px] transition-all duration-200 cursor-pointer hover:bg-foresty hover:text-white hover:border-foresty [&.active]:bg-foresty [&.active]:text-white [&.active]:border-foresty" data-filter="laporan">Laporan</button>
                <button class="filter-chip px-5 py-2.5 rounded-full border border-foresty/15 bg-white text-ink-soft font-bold text-[13.5px] transition-all duration-200 cursor-pointer hover:bg-foresty hover:text-white hover:border-foresty [&.active]:bg-foresty [&.active]:text-white [&.active]:border-foresty" data-filter="cerita">Cerita Lapangan</button>
            </div>
        </div>
    </section>

    <section class="pb-16 sm:pb-24 pt-0">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">

            <!-- FEATURED ARTICLE -->
            <a href="#" class="grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] gap-10 items-center bg-white border border-foresty/15 rounded-[28px] p-5 shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] mb-14 reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none hover:-translate-y-1 hover:shadow-xl group">
                <div class="aspect-[4/3] rounded-[18px] bg-mist flex items-center justify-center text-ink-soft border-2 border-dashed border-foresty/15">
                    <svg class="w-9 h-9 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 8a2 2 0 0 1 2-2h1.5l1-1.5h7l1 1.5H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><circle cx="12" cy="12.5" r="3.5"/></svg>
                </div>
                <div class="pr-5 lg:pr-5 pb-5 lg:pb-0">
                    <span class="cat-badge cat-cerita inline-block text-[11.5px] font-extrabold tracking-[0.06em] uppercase px-3 py-1.5 rounded-full mb-3.5 bg-[#FBE6E6] text-coral-dark">Cerita Lapangan</span>
                    <h2 class="font-display font-semibold text-[clamp(1.5rem,2.6vw,2rem)] leading-[1.25] text-foresty mb-3 group-hover:text-goldy transition-colors">Menembus Medan Sulit: Kisah Tim Fasilitator Menuju Puncak Jaya</h2>
                    <p class="text-ink-soft text-[15px] mb-4.5">Perjalanan delapan jam melintasi jalan berbatu dan sungai yang harus disebrangi manual — begini rasanya menjadi fasilitator imunisasi di salah satu kabupaten paling terpencil di Papua Tengah.</p>
                    <div class="flex items-center gap-3.5 text-[13px] text-ink-soft">
                        <span>Tim YSBH</span>
                        <span class="w-1 h-1 rounded-full bg-ink-soft opacity-50"></span>
                        <span>12 Juni 2026</span>
                        <span class="w-1 h-1 rounded-full bg-ink-soft opacity-50"></span>
                        <span>6 menit baca</span>
                    </div>
                </div>
            </a>

            <!-- ARTICLE GRID -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-7">

                <!-- Card 1 -->
                <a href="#" class="article-card flex flex-col bg-white border border-foresty/15 rounded-[18px] overflow-hidden shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-1 transition-transform duration-300 reveal opacity-0 translate-y-6 ease-out motion-reduce:transition-none group">
                    <div class="aspect-[16/10] bg-mist flex items-center justify-center text-ink-soft border-b border-foresty/15">
                        <svg class="w-6.5 h-6.5 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 8a2 2 0 0 1 2-2h1.5l1-1.5h7l1 1.5H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><circle cx="12" cy="12.5" r="3.5"/></svg>
                    </div>
                    <div class="p-5.5 flex flex-col flex-1">
                        <div>
                            <span class="cat-badge cat-kegiatan inline-block text-[11.5px] font-extrabold tracking-[0.06em] uppercase px-3 py-1.5 rounded-full mb-3.5 bg-goldy-soft text-foresty">Kegiatan</span>
                        </div>
                        <h3 class="font-display font-semibold text-[17px] leading-[1.35] text-foresty mb-2.5 group-hover:text-goldy transition-colors">Cerita dari Waghete: Pelatihan Fasilitator Imunisasi di Deiyai</h3>
                        <p class="text-ink-soft text-[14px] flex-1 mb-3.5">Dua puluh tenaga kesehatan dari lima puskesmas berkumpul selama tiga hari untuk memperdalam praktik POCQI.</p>
                        <div class="flex items-center gap-3.5 text-[13px] text-ink-soft">
                            <span>Tim YSBH</span>
                            <span class="w-1 h-1 rounded-full bg-ink-soft opacity-50"></span>
                            <span>28 Mei 2026</span>
                        </div>
                    </div>
                </a>

                <!-- Card 2 -->
                <a href="#" class="article-card flex flex-col bg-white border border-foresty/15 rounded-[18px] overflow-hidden shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-1 transition-transform duration-300 reveal opacity-0 translate-y-6 ease-out motion-reduce:transition-none group">
                    <div class="aspect-[16/10] bg-mist flex items-center justify-center text-ink-soft border-b border-foresty/15">
                        <svg class="w-6.5 h-6.5 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 8a2 2 0 0 1 2-2h1.5l1-1.5h7l1 1.5H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><circle cx="12" cy="12.5" r="3.5"/></svg>
                    </div>
                    <div class="p-5.5 flex flex-col flex-1">
                        <div>
                            <span class="cat-badge cat-laporan inline-block text-[11.5px] font-extrabold tracking-[0.06em] uppercase px-3 py-1.5 rounded-full mb-3.5 bg-mist text-foresty-tint">Laporan</span>
                        </div>
                        <h3 class="font-display font-semibold text-[17px] leading-[1.35] text-foresty mb-2.5 group-hover:text-goldy transition-colors">Laporan Semester I: Progres Program KIA di 9 Kabupaten</h3>
                        <p class="text-ink-soft text-[14px] flex-1 mb-3.5">Ringkasan capaian pendampingan ibu hamil, bayi baru lahir, dan penguatan Puskesmas Model sepanjang enam bulan pertama.</p>
                        <div class="flex items-center gap-3.5 text-[13px] text-ink-soft">
                            <span>Tim YSBH</span>
                            <span class="w-1 h-1 rounded-full bg-ink-soft opacity-50"></span>
                            <span>15 Mei 2026</span>
                        </div>
                    </div>
                </a>

                <!-- Card 3 -->
                <a href="#" class="article-card flex flex-col bg-white border border-foresty/15 rounded-[18px] overflow-hidden shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-1 transition-transform duration-300 reveal opacity-0 translate-y-6 ease-out motion-reduce:transition-none group">
                    <div class="aspect-[16/10] bg-mist flex items-center justify-center text-ink-soft border-b border-foresty/15">
                        <svg class="w-6.5 h-6.5 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 8a2 2 0 0 1 2-2h1.5l1-1.5h7l1 1.5H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><circle cx="12" cy="12.5" r="3.5"/></svg>
                    </div>
                    <div class="p-5.5 flex flex-col flex-1">
                        <div>
                            <span class="cat-badge cat-cerita inline-block text-[11.5px] font-extrabold tracking-[0.06em] uppercase px-3 py-1.5 rounded-full mb-3.5 bg-[#FBE6E6] text-coral-dark">Cerita Lapangan</span>
                        </div>
                        <h3 class="font-display font-semibold text-[17px] leading-[1.35] text-foresty mb-2.5 group-hover:text-goldy transition-colors">Kunjungan ke Puskesmas Model Wanggar Sari</h3>
                        <p class="text-ink-soft text-[14px] flex-1 mb-3.5">Belajar langsung bagaimana OJT Center membentuk kebiasaan berbagi ilmu antar tenaga kesehatan dari berbagai wilayah.</p>
                        <div class="flex items-center gap-3.5 text-[13px] text-ink-soft">
                            <span>Tim YSBH</span>
                            <span class="w-1 h-1 rounded-full bg-ink-soft opacity-50"></span>
                            <span>2 Mei 2026</span>
                        </div>
                    </div>
                </a>

                <!-- Card 4 -->
                <a href="#" class="article-card flex flex-col bg-white border border-foresty/15 rounded-[18px] overflow-hidden shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-1 transition-transform duration-300 reveal opacity-0 translate-y-6 ease-out motion-reduce:transition-none group">
                    <div class="aspect-[16/10] bg-mist flex items-center justify-center text-ink-soft border-b border-foresty/15">
                        <svg class="w-6.5 h-6.5 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 8a2 2 0 0 1 2-2h1.5l1-1.5h7l1 1.5H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><circle cx="12" cy="12.5" r="3.5"/></svg>
                    </div>
                    <div class="p-5.5 flex flex-col flex-1">
                        <div>
                            <span class="cat-badge cat-kegiatan inline-block text-[11.5px] font-extrabold tracking-[0.06em] uppercase px-3 py-1.5 rounded-full mb-3.5 bg-goldy-soft text-foresty">Kegiatan</span>
                        </div>
                        <h3 class="font-display font-semibold text-[17px] leading-[1.35] text-foresty mb-2.5 group-hover:text-goldy transition-colors">Kolaborasi Lintas Sektor: Ketika Tokoh Adat Bicara Soal Imunisasi</h3>
                        <p class="text-ink-soft text-[14px] flex-1 mb-3.5">Bagaimana suara kepala suku dan pemuka agama membantu menurunkan keraguan warga terhadap vaksin.</p>
                        <div class="flex items-center gap-3.5 text-[13px] text-ink-soft">
                            <span>Tim YSBH</span>
                            <span class="w-1 h-1 rounded-full bg-ink-soft opacity-50"></span>
                            <span>20 April 2026</span>
                        </div>
                    </div>
                </a>

                <!-- Card 5 -->
                <a href="#" class="article-card flex flex-col bg-white border border-foresty/15 rounded-[18px] overflow-hidden shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-1 transition-transform duration-300 reveal opacity-0 translate-y-6 ease-out motion-reduce:transition-none group">
                    <div class="aspect-[16/10] bg-mist flex items-center justify-center text-ink-soft border-b border-foresty/15">
                        <svg class="w-6.5 h-6.5 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 8a2 2 0 0 1 2-2h1.5l1-1.5h7l1 1.5H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><circle cx="12" cy="12.5" r="3.5"/></svg>
                    </div>
                    <div class="p-5.5 flex flex-col flex-1">
                        <div>
                            <span class="cat-badge cat-laporan inline-block text-[11.5px] font-extrabold tracking-[0.06em] uppercase px-3 py-1.5 rounded-full mb-3.5 bg-mist text-foresty-tint">Laporan</span>
                        </div>
                        <h3 class="font-display font-semibold text-[17px] leading-[1.35] text-foresty mb-2.5 group-hover:text-goldy transition-colors">Mengapa Angka Zero-Dose di Papua Tengah Masih Tinggi?</h3>
                        <p class="text-ink-soft text-[14px] flex-1 mb-3.5">Menelusuri akar penyebab rendahnya cakupan imunisasi dasar lengkap dan langkah percepatan yang kami dorong.</p>
                        <div class="flex items-center gap-3.5 text-[13px] text-ink-soft">
                            <span>Tim YSBH</span>
                            <span class="w-1 h-1 rounded-full bg-ink-soft opacity-50"></span>
                            <span>8 April 2026</span>
                        </div>
                    </div>
                </a>

                <!-- Card 6 -->
                <a href="#" class="article-card flex flex-col bg-white border border-foresty/15 rounded-[18px] overflow-hidden shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-1 transition-transform duration-300 reveal opacity-0 translate-y-6 ease-out motion-reduce:transition-none group">
                    <div class="aspect-[16/10] bg-mist flex items-center justify-center text-ink-soft border-b border-foresty/15">
                        <svg class="w-6.5 h-6.5 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 8a2 2 0 0 1 2-2h1.5l1-1.5h7l1 1.5H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><circle cx="12" cy="12.5" r="3.5"/></svg>
                    </div>
                    <div class="p-5.5 flex flex-col flex-1">
                        <div>
                            <span class="cat-badge cat-cerita inline-block text-[11.5px] font-extrabold tracking-[0.06em] uppercase px-3 py-1.5 rounded-full mb-3.5 bg-[#FBE6E6] text-coral-dark">Cerita Lapangan</span>
                        </div>
                        <h3 class="font-display font-semibold text-[17px] leading-[1.35] text-foresty mb-2.5 group-hover:text-goldy transition-colors">Satu Hari Bersama Kader Posyandu di Sarmi</h3>
                        <p class="text-ink-soft text-[14px] flex-1 mb-3.5">Mengikuti rutinitas kader dari rumah ke rumah, memantau tumbuh kembang balita di wilayah pesisir.</p>
                        <div class="flex items-center gap-3.5 text-[13px] text-ink-soft">
                            <span>Tim YSBH</span>
                            <span class="w-1 h-1 rounded-full bg-ink-soft opacity-50"></span>
                            <span>29 Maret 2026</span>
                        </div>
                    </div>
                </a>
            </div>

            <!-- LOAD MORE BUTTON -->
            <div class="text-center mt-12 reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <button class="inline-flex items-center justify-center gap-2 font-bold text-[15.5px] px-[28px] py-[14px] rounded-full border-2 border-foresty bg-transparent text-foresty hover:bg-foresty hover:text-white hover:-translate-y-0.5 transition-all duration-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-foresty focus-visible:outline-offset-[3px]">
                    Muat Artikel Lainnya
                </button>
            </div>

        </div>
    </section>

</x-layouts.app>
