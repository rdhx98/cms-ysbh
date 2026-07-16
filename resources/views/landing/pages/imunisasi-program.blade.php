<x-layouts.app>
    <x-slot:title>
        Imunisasi
    </x-slot>

    <!-- BREADCRUMB + HERO -->
    <section class="pt-12 pb-10 sm:pt-12 sm:pb-10">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="flex items-center gap-2 text-[13.5px] text-ink-soft mb-8 flex-wrap reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <a href="sinar-bhakti-husada-landing.html" class="font-semibold hover:text-foresty">Beranda</a>
                <span class="opacity-50">/</span>
                <a href="sinar-bhakti-husada-landing.html#program" class="font-semibold hover:text-foresty">Program</a>
                <span class="opacity-50">/</span>
                <span class="text-foresty font-bold opacity-100">Imunisasi</span>
            </div>

            <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <div class="w-[72px] h-[72px] rounded-[22px] bg-mist text-foresty flex items-center justify-center mb-[22px]">
                    <svg class="w-[34px] h-[34px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Z"/><path d="M9 12l2 2 4-4"/></svg>
                </div>
                <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
                    Program Kesehatan · Papua Tengah
                </span>

                <h1 class="font-display text-[clamp(2rem,4vw,3rem)] font-semibold leading-[1.12] text-foresty mt-4 mb-5 max-w-[820px]">Melindungi Masa Depan Generasi Papua Tengah</h1>

                <p class="text-lg text-ink-soft max-w-[760px]">Program Imunisasi Yayasan Sinar Bhakti Husada (YSBH) berkomitmen mendampingi pemerintah daerah dalam memastikan setiap anak di Papua Tengah memiliki benteng perlindungan terhadap penyakit yang dapat dicegah. Melalui pendekatan inovatif dan sensitif budaya, kami berupaya menurunkan angka anak <em class="italic">zero dose</em> demi memastikan keberlangsungan generasi masa depan Papua yang sehat dan tangguh.</p>

                <img src="{{ asset('images/imun-hero.jpg') }}" alt="Kegiatan Program Imunisasi Papua Tengah" class="w-full rounded-[28px] mt-10 shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] object-cover max-h-[420px]">
            </div>
        </div>
    </section>

    <!-- CRISIS SECTION -->
    <section class="py-16 sm:py-24 bg-mist" id="contentStart">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="max-w-[680px] mb-14 reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01M10.3 3.9 2.5 17a2 2 0 0 0 1.7 3h15.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                    Situasi Darurat
                </span>
                <h2 class="font-display font-semibold text-[clamp(1.9rem,3.2vw,2.6rem)] leading-[1.15] text-foresty mt-3.5">Kami Bertindak di Tengah Krisis Imunisasi</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-[0.85fr_1.15fr] gap-9 md:gap-12 items-start reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <div>
                    <img src="{{ asset('images/imun-crisis.jpg') }}" alt="Situasi krisis imunisasi Papua Tengah" class="w-full rounded-[18px] shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] object-cover">
                </div>
                <div class="text-ink-soft">
                    <p class="mb-4">Situasi imunisasi di Provinsi Papua Tengah saat ini memerlukan perhatian mendesak dari seluruh pemangku kepentingan. Hingga tahun 2024, cakupan Imunisasi Dasar Lengkap (IDL) baru mencapai 12%, sebuah penurunan drastis dibandingkan tahun sebelumnya. Dampaknya adalah kemunculan kembali wabah penyakit mematikan yang silih berganti menyerang anak-anak kita dalam beberapa tahun terakhir.</p>

                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-6 bg-foresty rounded-[28px] p-8 sm:p-[32px_36px] my-7">
                        <div class="font-display font-bold text-[clamp(2.4rem,5vw,3.2rem)] text-goldy leading-none">12%</div>
                        <div class="text-white/85 text-[15px] leading-relaxed">Cakupan Imunisasi Dasar Lengkap (IDL) di Papua Tengah hingga tahun 2024 — penurunan drastis dari tahun sebelumnya.</div>
                    </div>

                    <div class="flex flex-wrap gap-3 my-5">
                        <span class="flex items-center gap-2.5 bg-[#FBE6E6] text-coral-dark rounded-full px-4.5 py-2.5 text-[14.5px] font-bold">
                            <svg class="w-4 h-4 text-coral shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01M10.3 3.9 2.5 17a2 2 0 0 0 1.7 3h15.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                            Campak
                        </span>
                        <span class="flex items-center gap-2.5 bg-[#FBE6E6] text-coral-dark rounded-full px-4.5 py-2.5 text-[14.5px] font-bold">
                            <svg class="w-4 h-4 text-coral shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01M10.3 3.9 2.5 17a2 2 0 0 0 1.7 3h15.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                            Polio
                        </span>
                        <span class="flex items-center gap-2.5 bg-[#FBE6E6] text-coral-dark rounded-full px-4.5 py-2.5 text-[14.5px] font-bold">
                            <svg class="w-4 h-4 text-coral shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01M10.3 3.9 2.5 17a2 2 0 0 0 1.7 3h15.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                            Difteri
                        </span>
                    </div>

                    <p>Ketertinggalan ini bukan sekadar angka, melainkan ancaman nyata bagi keberlangsungan masyarakat Papua. Setiap wabah yang terjadi mengakibatkan duka mendalam bagi keluarga dan beban ekonomi yang besar. Tanpa intervensi yang sistematis dan berkelanjutan, kita berisiko kehilangan generasi emas yang akan membangun Tanah Papua di masa depan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- LIMA PILAR -->
    <section class="py-16 sm:py-24" id="program">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="max-w-[680px] mb-14 reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
                    Strategi Intervensi
                </span>
                <h2 class="font-display font-semibold text-[clamp(1.9rem,3.2vw,2.6rem)] leading-[1.15] text-foresty mt-3.5">Lima Pilar Intervensi Program Imunisasi</h2>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3.5 reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <div class="bg-white border border-foresty/15 rounded-[18px] p-[18px_14px] text-center shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)]">
                    <span class="font-display font-bold text-goldy text-xl block mb-1.5">01</span>
                    <span class="text-[12.5px] font-bold text-foresty leading-[1.3]">Regulasi & Kebijakan</span>
                </div>
                <div class="bg-white border border-foresty/15 rounded-[18px] p-[18px_14px] text-center shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)]">
                    <span class="font-display font-bold text-goldy text-xl block mb-1.5">02</span>
                    <span class="text-[12.5px] font-bold text-foresty leading-[1.3]">Puskesmas Model OJT</span>
                </div>
                <div class="bg-white border border-foresty/15 rounded-[18px] p-[18px_14px] text-center shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)]">
                    <span class="font-display font-bold text-goldy text-xl block mb-1.5">03</span>
                    <span class="text-[12.5px] font-bold text-foresty leading-[1.3]">Human Centered Design</span>
                </div>
                <div class="bg-white border border-foresty/15 rounded-[18px] p-[18px_14px] text-center shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)]">
                    <span class="font-display font-bold text-goldy text-xl block mb-1.5">04</span>
                    <span class="text-[12.5px] font-bold text-foresty leading-[1.3]">Kolaborasi Lintas Sektor</span>
                </div>
                <div class="bg-white border border-foresty/15 rounded-[18px] p-[18px_14px] text-center shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)]">
                    <span class="font-display font-bold text-goldy text-xl block mb-1.5">05</span>
                    <span class="text-[12.5px] font-bold text-foresty leading-[1.3]">Tim Fasilitator Provinsi</span>
                </div>
            </div>
        </div>
    </section>

    <!-- PILLAR 1 -->
    <section class="py-16 sm:py-24 bg-mist">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="grid grid-cols-1 md:grid-cols-[0.85fr_1.15fr] gap-9 md:gap-12 items-start reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <div>
                    <img src="{{ asset('images/imun-pillar1-regulasi.jpg') }}" alt="Penguatan regulasi imunisasi" class="w-full rounded-[18px] shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] object-cover">
                </div>
                <div class="text-ink-soft">
                    <div class="inline-flex items-center gap-2.5 mb-3.5">
                        <span class="w-8 h-8 rounded-[10px] bg-goldy-soft text-foresty font-display font-bold text-sm flex items-center justify-center shrink-0">01</span>
                        <span class="text-[12.5px] font-bold tracking-[0.1em] uppercase text-coral-dark">Penguatan Regulasi dan Kebijakan Imunisasi</span>
                    </div>
                    <p class="mb-4">Kami percaya bahwa keberlanjutan program imunisasi harus berakar pada komitmen politik dan hukum yang kuat di tingkat daerah. YSBH memberikan pendampingan teknis kepada pemerintah provinsi dan kabupaten untuk menyusun payung hukum yang kuat, termasuk Peraturan Gubernur Papua Tengah serta Peraturan Bupati di beberapa kabupaten dampingan. Regulasi ini sangat krusial untuk memastikan adanya alokasi anggaran daerah yang pasti, tata kelola logistik vaksin yang lebih baik, serta integrasi layanan imunisasi ke dalam rencana pembangunan daerah.</p>
                    <p>Melalui regulasi yang jelas, imunisasi tidak lagi dipandang sebagai kegiatan kesehatan rutin semata, melainkan sebagai kewajiban pemerintah untuk melindungi hak asasi anak-anak Papua. Kebijakan ini juga bertujuan untuk mengunci dukungan jangka panjang dari para pengambil keputusan, sehingga program imunisasi tetap berjalan stabil meskipun terjadi pergantian kepemimpinan atau dinamika politik di daerah.</p>

                    <div class="flex flex-wrap gap-2 mt-4.5">
                        <span class="bg-white border border-foresty/10 text-foresty font-semibold text-[13px] px-3.5 py-[7px] rounded-full">Kab. Nabire</span>
                        <span class="bg-white border border-foresty/10 text-foresty font-semibold text-[13px] px-3.5 py-[7px] rounded-full">Kab. Dogiyai</span>
                        <span class="bg-white border border-foresty/10 text-foresty font-semibold text-[13px] px-3.5 py-[7px] rounded-full">Kab. Deiyai</span>
                        <span class="bg-white border border-foresty/10 text-foresty font-semibold text-[13px] px-3.5 py-[7px] rounded-full">Kab. Puncak Jaya</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PILLAR 2 -->
    <section class="py-16 sm:py-24">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="grid grid-cols-1 md:grid-cols-[1.15fr_0.85fr] gap-9 md:gap-12 items-start reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <div class="md:order-last">
                    <img src="{{ asset('images/imun-pillar2-ojt.jpg') }}" alt="Puskesmas Model OJT Center" class="w-full rounded-[18px] shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] object-cover">
                </div>
                <div class="text-ink-soft">
                    <div class="inline-flex items-center gap-2.5 mb-3.5">
                        <span class="w-8 h-8 rounded-[10px] bg-goldy-soft text-foresty font-display font-bold text-sm flex items-center justify-center shrink-0">02</span>
                        <span class="text-[12.5px] font-bold tracking-[0.1em] uppercase text-coral-dark">Pengembangan Puskesmas Model OJT Center</span>
                    </div>
                    <p class="mb-4">Tantangan geografis Papua membuat biaya pelatihan konvensional menjadi sangat mahal, sementara rotasi petugas kesehatan yang cepat seringkali meninggalkan kekosongan tenaga terlatih di daerah terpencil. Sebagai solusinya, YSBH mengembangkan Puskesmas Model OJT (<em class="italic">On-the-Job Training</em>) Center di lokasi-lokasi strategis. Pusat ini berfungsi sebagai laboratorium pembelajaran praktis bagi tenaga medis untuk memperdalam keterampilan imunisasi secara langsung di lapangan.</p>
                    <p>Model ini sangat efektif karena memanfaatkan pola mobilitas petugas kesehatan dari daerah terpencil yang sering berkunjung ke ibu kota kabupaten untuk urusan logistik atau pribadi. Saat mereka berada di ibu kota, mereka dapat mengikuti magang singkat dengan waktu fleksibel tanpa harus meninggalkan tugas mereka terlalu lama. Pendekatan ini tidak hanya menekan biaya transportasi secara signifikan, tetapi juga membangun keterikatan dan budaya saling belajar antar Puskesmas.</p>

                    <div class="flex flex-wrap gap-2 mt-4.5">
                        <span class="bg-mist text-foresty font-semibold text-[13px] px-3.5 py-[7px] rounded-full">Puskesmas Wanggar Sari · Nabire</span>
                        <span class="bg-mist text-foresty font-semibold text-[13px] px-3.5 py-[7px] rounded-full">Puskesmas Topo · Nabire</span>
                        <span class="bg-mist text-foresty font-semibold text-[13px] px-3.5 py-[7px] rounded-full">Puskesmas Waghete · Deiyai</span>
                        <span class="bg-mist text-foresty font-semibold text-[13px] px-3.5 py-[7px] rounded-full">Puskesmas Moanemani · Dogiyai</span>
                        <span class="bg-mist text-foresty font-semibold text-[13px] px-3.5 py-[7px] rounded-full">Puskesmas Mulia · Puncak Jaya</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PILLAR 3 -->
    <section class="py-16 sm:py-24 bg-mist">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="grid grid-cols-1 md:grid-cols-[0.85fr_1.15fr] gap-9 md:gap-12 items-start reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <div>
                    <img src="{{ asset('images/imun-pillar3-hcd.jpg') }}" alt="Human Centered Design imunisasi" class="w-full rounded-[18px] shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] object-cover">
                </div>
                <div class="text-ink-soft">
                    <div class="inline-flex items-center gap-2.5 mb-3.5">
                        <span class="w-8 h-8 rounded-[10px] bg-goldy-soft text-foresty font-display font-bold text-sm flex items-center justify-center shrink-0">03</span>
                        <span class="text-[12.5px] font-bold tracking-[0.1em] uppercase text-coral-dark">Implementasi Human Centered Design (HCD)</span>
                    </div>
                    <p class="mb-4">Program imunisasi seringkali menghadapi hambatan berupa keraguan atau ketakutan masyarakat terhadap vaksin. Melalui implementasi <em class="italic">Human-Centered Design</em> (HCD), YSBH berupaya memahami sisi kemanusiaan dan sosial-budaya di balik rendahnya cakupan imunisasi. Kami tidak hanya memberikan edukasi satu arah, melainkan duduk bersama orang tua, tokoh masyarakat, dan petugas kesehatan untuk mendengarkan kekhawatiran mereka dan bersama-sama merancang solusi yang sesuai dengan konteks lokal.</p>
                    <p>Pendekatan ini bertujuan untuk menciptakan layanan imunisasi yang lebih ramah, berempati, dan dapat dipercaya oleh masyarakat Papua. Dengan memahami hambatan dari sudut pandang pengguna layanan, kami dapat mengembangkan strategi komunikasi risiko yang menyentuh hati dan mengubah persepsi masyarakat terhadap vaksin. HCD memastikan bahwa setiap anak mendapatkan layanan yang tidak hanya berkualitas secara medis, tetapi juga dihargai secara budaya.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- PILLAR 4 -->
    <section class="py-16 sm:py-24">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="grid grid-cols-1 md:grid-cols-[1.15fr_0.85fr] gap-9 md:gap-12 items-start reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <div class="md:order-last">
                    <img src="{{ asset('images/imun-pillar4-kolaborasi.jpg') }}" alt="Kolaborasi lintas sektor imunisasi" class="w-full rounded-[18px] shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] object-cover">
                </div>
                <div class="text-ink-soft">
                    <div class="inline-flex items-center gap-2.5 mb-3.5">
                        <span class="w-8 h-8 rounded-[10px] bg-goldy-soft text-foresty font-display font-bold text-sm flex items-center justify-center shrink-0">04</span>
                        <span class="text-[12.5px] font-bold tracking-[0.1em] uppercase text-coral-dark">Penguatan Kolaborasi Lintas Sektor</span>
                    </div>
                    <p class="mb-4">Imunisasi adalah tanggung jawab bersama yang tidak bisa hanya dipikul oleh sektor kesehatan sendirian. YSBH aktif menggerakkan kolaborasi lintas sektor yang melibatkan tokoh adat, pemuka agama, pemerintah desa, hingga sektor swasta di Papua Tengah. Kami percaya bahwa ketika seorang pendeta, kepala suku, atau kepala desa menyuarakan pentingnya imunisasi, pesan tersebut akan memiliki daya terima yang jauh lebih kuat di tengah masyarakat.</p>
                    <p>Kerja sama ini mencakup pemanfaatan dana desa untuk mendukung mobilisasi sasaran, keterlibatan tokoh agama dalam sosialisasi di rumah-rumah ibadah, hingga dukungan logistik dari sektor lain. Dengan memperkuat sinergi ini, kami membangun ekosistem pendukung yang solid untuk melindungi generasi penerus bangsa — kunci untuk mencapai setiap anak, bahkan di wilayah yang paling sulit dijangkau sekalipun.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- PILLAR 5 -->
    <section class="py-16 sm:py-24 bg-mist">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="grid grid-cols-1 md:grid-cols-[0.85fr_1.15fr] gap-9 md:gap-12 items-start reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <div>
                    <img src="{{ asset('images/imun-pillar5-fasilitator.jpg') }}" alt="Tim Fasilitator Imunisasi Provinsi" class="w-full rounded-[18px] shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] object-cover">
                </div>
                <div class="text-ink-soft">
                    <div class="inline-flex items-center gap-2.5 mb-3.5">
                        <span class="w-8 h-8 rounded-[10px] bg-goldy-soft text-foresty font-display font-bold text-sm flex items-center justify-center shrink-0">05</span>
                        <span class="text-[12.5px] font-bold tracking-[0.1em] uppercase text-coral-dark">Pembentukan Tim Fasilitator Imunisasi Provinsi</span>
                    </div>
                    <p class="mb-4">Untuk menjamin kualitas dan keberlanjutan pendampingan teknis, YSBH memfasilitasi pembentukan Tim Fasilitator Imunisasi di tingkat Provinsi Papua Tengah. Tim ini terdiri dari para tenaga ahli lokal yang telah dibekali dengan pengetahuan mendalam mengenai manajemen imunisasi, pemantauan kualitas, hingga teknik komunikasi persuasif. Peran mereka adalah menjadi penggerak utama dalam memberikan bimbingan teknis (<em class="italic">technical assistance</em>) yang berkelanjutan kepada kabupaten-kabupaten dampingan.</p>
                    <p>Keberadaan tim fasilitator ini bertujuan untuk menciptakan kemandirian daerah dalam jangka panjang. Dengan memiliki tim ahli yang siap sedia di tingkat provinsi, proses transfer pengetahuan ke Puskesmas-Puskesmas akan berjalan lebih cepat dan konsisten — upaya kami untuk meninggalkan warisan berupa sistem kesehatan yang tangguh dan SDM lokal yang mumpuni untuk menjaga kesehatan anak-anak Papua tanpa ketergantungan pada pihak luar di masa depan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- INVESTASI KEMANUSIAAN -->
    <section class="py-16 sm:py-24 text-center reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 flex flex-col items-center">
            <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark mb-5">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.6-9.3-9.2C1.4 8.6 3 5 6.6 5c2 0 3.4 1.1 4.4 2.6C12 6.1 13.4 5 15.4 5 19 5 20.6 8.6 19.3 11.8 17 16.4 12 21 12 21z"/></svg>
                Investasi Kemanusiaan
            </span>
            <p class="font-display font-medium text-[clamp(1.4rem,2.6vw,1.9rem)] text-foresty leading-relaxed max-w-[820px]">Setiap dukungan yang Anda berikan membantu kami memperkuat Puskesmas Model, melatih fasilitator lokal, dan memastikan vaksin sampai ke lengan anak-anak di kampung-kampung terpencil. Bersama-sama, kita bisa memastikan bahwa anak-anak Papua tumbuh <em class="italic text-coral">sehat, cerdas, dan siap</em> untuk memimpin masa depan mereka sendiri.</p>
        </div>
    </section>

    <!-- DONATION CTA -->
    <section class="py-16 sm:py-24 bg-coral text-white relative overflow-hidden" id="donasi">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 grid grid-cols-1 lg:grid-cols-[1.2fr_0.8fr] gap-12 items-center relative z-10">
            <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <h2 class="font-display font-semibold text-[clamp(1.9rem,3.4vw,2.5rem)] leading-[1.15] text-white">Setiap Anak Papua Berhak atas Masa Depan yang Sehat</h2>
                <p class="text-white/90 text-[16.5px] mt-4 mb-7 max-w-[460px]">Dukungan Anda membantu menurunkan angka anak zero-dose dan memastikan vaksin sampai ke kampung-kampung paling terpencil di Papua Tengah.</p>
                <div class="flex gap-4 flex-wrap">
                    <a href="#kontak" class="inline-flex items-center justify-center gap-2 font-bold text-[15.5px] px-7 py-3.5 rounded-full border-2 border-transparent bg-white text-foresty hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-14px_rgba(0,0,0,0.35)] transition-all duration-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-white focus-visible:outline-offset-[3px]">Donasi via Transfer</a>
                    <a href="#kontak" class="inline-flex items-center justify-center gap-2 font-bold text-[15.5px] px-7 py-3.5 rounded-full border-2 border-white/60 bg-transparent text-white hover:bg-white/10 hover:border-white transition-all duration-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-white focus-visible:outline-offset-[3px]">Jadi Mitra Program</a>
                </div>
            </div>
            <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none bg-white/10 border border-white/30 rounded-[18px] p-7 backdrop-blur-sm">
                <div class="text-[12px] tracking-[0.1em] uppercase text-white/75 mb-2">Transfer Bank</div>
                <div class="font-display text-[22px] font-semibold mb-1" id="acctNumber">BCA · 123 4567 890</div>
                <div class="text-[14px] text-white/85 mb-4">a.n. Yayasan Sinar Bhakti Husada</div>
                <button class="bg-white text-coral rounded-full px-4.5 py-2 font-bold text-[13.5px] hover:bg-white/90 transition-colors" id="copyBtn">Salin Nomor Rekening</button>
            </div>
        </div>
    </section>

    <!-- CONTACT -->
    <section class="py-16 sm:py-24" id="kontak">
        <div class="max-w-7xl mx-auto px-5 sm:px-8">
            <div class="max-w-[640px] mb-14 reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
                    Hubungi Kami
                </span>
                <h2 class="font-display font-semibold text-[clamp(1.9rem,3.2vw,2.6rem)] leading-[1.15] text-foresty mt-3.5">Mari Berdiskusi Soal Kemitraan Program</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-9 lg:gap-14">
                <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
                    <div class="flex gap-4 mb-[26px]">
                        <div class="w-[46px] h-[46px] rounded-[14px] bg-white border border-foresty/15 flex items-center justify-center shrink-0 text-foresty">
                            <svg class="w-[21px] h-[21px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Z"/><path d="m22 6-10 7L2 6"/></svg>
                        </div>
                        <div>
                            <div class="font-bold text-[14.5px] text-foresty mb-[3px]">Email</div>
                            <div class="text-[14.5px] text-ink-soft">kontak@sinarbhaktihusada.org</div>
                        </div>
                    </div>
                    <div class="flex gap-4 mb-[26px]">
                        <div class="w-[46px] h-[46px] rounded-[14px] bg-white border border-foresty/15 flex items-center justify-center shrink-0 text-foresty">
                            <svg class="w-[21px] h-[21px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.9.6 2.8a2 2 0 0 1-.5 2.1L7.9 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.8.6a2 2 0 0 1 1.7 2Z"/></svg>
                        </div>
                        <div>
                            <div class="font-bold text-[14.5px] text-foresty mb-[3px]">Telepon / WhatsApp</div>
                            <div class="text-[14.5px] text-ink-soft">+62 812-3456-7890</div>
                        </div>
                    </div>
                    <div class="flex gap-4 mb-[26px]">
                        <div class="w-[46px] h-[46px] rounded-[14px] bg-white border border-foresty/15 flex items-center justify-center shrink-0 text-foresty">
                            <svg class="w-[21px] h-[21px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div>
                            <div class="font-bold text-[14.5px] text-foresty mb-[3px]">Kantor Sekretariat</div>
                            <div class="text-[14.5px] text-ink-soft">Jl. Melati No. 21, Kupang, Nusa Tenggara Timur</div>
                        </div>
                    </div>
                </div>
                <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none rounded-[18px] overflow-hidden border border-foresty/15 h-full min-h-[280px] bg-paper-dim flex items-center justify-center text-ink-soft text-[14px]">
                    Peta lokasi kantor dapat ditambahkan di sini
                </div>
            </div>
        </div>
    </section>

</x-layouts.app>