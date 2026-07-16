<!-- PROGRAMS -->
<section class="py-16 sm:py-24 bg-mist w-full" id="program">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div class=" mb-14 reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
            <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark">
            <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
            Fokus Program
            </span>
            <h2 class="font-display font-semibold text-[clamp(1.9rem,3.2vw,2.6rem)] leading-[1.15] text-foresty mt-3.5">Tiga Pilar yang Kami Jaga Bersama</h2>
            <p class="text-ink-soft text-[16.5px] mt-4">Bukan proyek satu kali jalan — tiga program ini berjalan berdampingan, saling menguatkan, di setiap desa dampingan kami.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-7">
            <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none bg-white rounded-[28px] p-8 border border-foresty/15 shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-1.5 transition-transform duration-300 flex flex-col">
                <div class="w-16 h-16 rounded-[18px] flex items-center justify-center mb-5 bg-goldy-soft text-foresty">
                    <svg viewBox="0 0 24 24" class="w-[30px] h-[30px]" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.6-9.3-9.2C1.4 8.6 3 5 6.6 5c2 0 3.4 1.1 4.4 2.6C12 6.1 13.4 5 15.4 5 19 5 20.6 8.6 19.3 11.8 17 16.4 12 21 12 21z"/></svg>
                </div>
                <div class="text-xs font-bold tracking-[0.1em] uppercase mb-3.5 text-coral-dark">Kesehatan Ibu &amp; Anak</div>
                <h3 class="font-display text-[21px] font-semibold text-foresty mb-2.5">Mendampingi Sejak dalam Kandungan</h3>
                <p class="text-ink-soft text-[15px] flex-1">Pemeriksaan kehamilan berkala, pendampingan persalinan aman bersama bidan desa, dan pemantauan tumbuh kembang balita hingga usia lima tahun.</p>
                <a href="{{ route('programs-kia') }}" wire:navigate class="group mt-5 font-bold text-[14.5px] inline-flex items-center gap-1.5 text-foresty">Selengkapnya
                    <svg viewBox="0 0 24 24" class="w-[15px] h-[15px] transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>

            <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none bg-white rounded-[28px] p-8 border border-foresty/15 shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-1.5 transition-transform duration-300 flex flex-col">
                <div class="w-16 h-16 rounded-[18px] flex items-center justify-center mb-5 bg-mist text-foresty">
                    <svg viewBox="0 0 24 24" class="w-[30px] h-[30px]" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Z"/><path d="M9 12l2 2 4-4"/></svg>
                </div>
                <div class="text-xs font-bold tracking-[0.1em] uppercase mb-3.5 text-foresty-tint">Imunisasi</div>
                <h3 class="font-display text-[21px] font-semibold text-foresty mb-2.5">Vaksin Lengkap, Sampai ke Pelosok</h3>
                <p class="text-ink-soft text-[15px] flex-1">Menjangkau anak-anak di dusun terpencil dengan imunisasi dasar lengkap, cold chain vaksin yang terjaga, serta edukasi rutin bagi orang tua.</p>
                <a href="{{ route('programs-imunisasi') }}" wire:navigate class="group mt-5 font-bold text-[14.5px] inline-flex items-center gap-1.5 text-foresty">Selengkapnya
                    <svg viewBox="0 0 24 24" class="w-[15px] h-[15px] transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>

            <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none bg-white rounded-[28px] p-8 border border-foresty/15 shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-1.5 transition-transform duration-300 flex flex-col">
            <div class="w-16 h-16 rounded-[18px] flex items-center justify-center mb-5 bg-[#FBE6E6] text-coral">
                <svg viewBox="0 0 24 24" class="w-[30px] h-[30px]" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20c3-6 13-6 16 0"/><circle cx="12" cy="9" r="5"/><path d="M9 9h6M12 6v6"/></svg>
            </div>
            <div class="text-xs font-bold tracking-[0.1em] uppercase mb-3.5 text-coral-dark">Penanganan Malaria</div>
                <h3 class="font-display text-[21px] font-semibold text-foresty mb-2.5">Memutus Rantai Penularan</h3>
                <p class="text-ink-soft text-[15px] flex-1">Distribusi kelambu berinsektisida, tes cepat untuk deteksi dini, serta pengobatan tuntas bekerja sama dengan puskesmas di wilayah endemis.</p>
                <a href="{{ route('programs-malaria') }}" wire:navigate class="group mt-5 font-bold text-[14.5px] inline-flex items-center gap-1.5 text-foresty">Selengkapnya
                    <svg viewBox="0 0 24 24" class="w-[15px] h-[15px] transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>
