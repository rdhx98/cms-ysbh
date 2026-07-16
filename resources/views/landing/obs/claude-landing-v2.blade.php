<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yayasan Sinar Bhakti Husada — Sinar Sehat untuk Setiap Rumah</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    darkMode: 'class',
    theme: {
      extend: {
        colors: {
          forest: '#064F3B',
          'forest-dark': '#043328',
          'forest-tint': '#0E6A4F',
          gold: '#EBCC26',
          'gold-bright': '#F5DB4A',
          coral: '#E42326',
          'coral-dark': '#BE1417',
          'coral-light': '#FF7A7D',
          sage: '#E8F0EA',
          butter: '#FBF0C7',
          blush: '#F6D9D6',
          paper: '#FAF8F2',
          ink: '#12241C',
          'ink-soft': '#54655C',
          night: '#0B1A13',
          'night-surface': '#132A20',
          'night-surface-2': '#1A3327',
          cream: '#F3EFE2',
          'cream-soft': '#B9C4BB',
        },
        fontFamily: {
          display: ['Manrope', 'sans-serif'],
          body: ['"Plus Jakarta Sans"', 'sans-serif'],
        },
        keyframes: {
          float: { '0%,100%': { transform: 'translateY(0px)' }, '50%': { transform: 'translateY(-16px)' } },
        },
        animation: {
          float: 'float 7s ease-in-out infinite',
        },
      }
    }
  }
</script>
</head>
<body class="m-0 bg-paper dark:bg-night text-ink dark:text-cream font-body antialiased leading-relaxed overflow-x-hidden transition-colors duration-300">

<!-- NAV -->
<div class="fixed top-3 sm:top-4 inset-x-0 z-50 px-3 sm:px-4">
  <div class="max-w-[1180px] mx-auto flex items-center justify-between gap-3 rounded-full bg-white/85 dark:bg-night-surface/85 backdrop-blur-md shadow-lg shadow-black/5 border border-forest/10 dark:border-cream/10 px-4 sm:px-5 py-2.5 transition-colors duration-300">
    <a href="#home" class="flex items-center gap-2.5 shrink-0">
      <svg viewBox="0 0 300 300" class="w-9 h-9" xmlns="http://www.w3.org/2000/svg">
        <circle cx="150" cy="150" r="146" class="fill-gold"/>
        <path d="M150 216C150 216 96 174 96 137C96 111 114 92 136 92C142.5 92 146.5 95 150 101C153.5 95 157.5 92 164 92C186 92 204 111 204 137C204 174 150 216 150 216Z" class="fill-forest"/>
        <rect x="137" y="112" width="26" height="60" rx="4" class="fill-white"/>
        <rect x="120" y="129" width="60" height="26" rx="4" class="fill-white"/>
      </svg>
      <span class="font-display font-extrabold text-forest dark:text-cream text-[15px] hidden sm:inline whitespace-nowrap">Sinar Bhakti Husada</span>
    </a>

    <nav id="scrollspyNav" class="hidden lg:flex items-center gap-1">
      <a data-section="program" href="#program" class="nav-link px-4 py-2 rounded-full text-sm font-semibold text-ink-soft dark:text-cream-soft hover:text-forest dark:hover:text-cream transition-colors">Program</a>
      <a data-section="dampak" href="#dampak" class="nav-link px-4 py-2 rounded-full text-sm font-semibold text-ink-soft dark:text-cream-soft hover:text-forest dark:hover:text-cream transition-colors">Dampak</a>
      <a data-section="cerita" href="#cerita" class="nav-link px-4 py-2 rounded-full text-sm font-semibold text-ink-soft dark:text-cream-soft hover:text-forest dark:hover:text-cream transition-colors">Cerita</a>
      <a data-section="faq" href="#faq" class="nav-link px-4 py-2 rounded-full text-sm font-semibold text-ink-soft dark:text-cream-soft hover:text-forest dark:hover:text-cream transition-colors">FAQ</a>
      <a data-section="kontak" href="#kontak" class="nav-link px-4 py-2 rounded-full text-sm font-semibold text-ink-soft dark:text-cream-soft hover:text-forest dark:hover:text-cream transition-colors">Kontak</a>
    </nav>

    <div class="flex items-center gap-2 shrink-0">
      <button id="themeToggle" aria-label="Ganti tema gelap/terang" class="w-9 h-9 rounded-full flex items-center justify-center bg-sage dark:bg-night-surface-2 text-forest dark:text-gold-bright hover:scale-105 active:scale-95 transition-transform">
        <svg class="hidden dark:block w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        <svg class="block dark:hidden w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/></svg>
      </button>
      <a href="#donasi" class="hidden sm:inline-flex items-center px-5 py-2.5 rounded-full bg-coral text-white text-sm font-bold hover:bg-coral-dark transition-colors">Donasi</a>
      <button id="burgerBtn" aria-label="Buka menu" class="lg:hidden w-9 h-9 flex items-center justify-center rounded-full bg-sage dark:bg-night-surface-2 text-forest dark:text-cream">
        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
      </button>
    </div>
  </div>

  <div id="mobileMenu" class="hidden lg:hidden max-w-[1180px] mx-auto mt-2 rounded-3xl bg-white dark:bg-night-surface shadow-lg border border-forest/10 dark:border-cream/10 p-3 flex flex-col gap-1 transition-colors duration-300">
    <a href="#program" class="mobile-link px-4 py-2.5 rounded-2xl text-[15px] font-semibold text-ink-soft dark:text-cream-soft hover:bg-sage dark:hover:bg-night-surface-2">Program</a>
    <a href="#dampak" class="mobile-link px-4 py-2.5 rounded-2xl text-[15px] font-semibold text-ink-soft dark:text-cream-soft hover:bg-sage dark:hover:bg-night-surface-2">Dampak</a>
    <a href="#cerita" class="mobile-link px-4 py-2.5 rounded-2xl text-[15px] font-semibold text-ink-soft dark:text-cream-soft hover:bg-sage dark:hover:bg-night-surface-2">Cerita</a>
    <a href="#faq" class="mobile-link px-4 py-2.5 rounded-2xl text-[15px] font-semibold text-ink-soft dark:text-cream-soft hover:bg-sage dark:hover:bg-night-surface-2">FAQ</a>
    <a href="#kontak" class="mobile-link px-4 py-2.5 rounded-2xl text-[15px] font-semibold text-ink-soft dark:text-cream-soft hover:bg-sage dark:hover:bg-night-surface-2">Kontak</a>
    <a href="#donasi" class="mobile-link mt-1 px-4 py-2.5 rounded-2xl text-[15px] font-bold text-center bg-coral text-white">Donasi Sekarang</a>
  </div>
</div>

<main id="home">

  <!-- HERO -->
  <section class="relative pt-32 sm:pt-40 pb-16 px-4">
    <div class="max-w-[1180px] mx-auto grid grid-cols-1 lg:grid-cols-[1.05fr_0.95fr] gap-12 items-center">
      <div>
        <span class="inline-flex items-center gap-2 text-[13px] font-bold tracking-[0.14em] uppercase text-coral-dark dark:text-coral-light">
          <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
          Yayasan Kesehatan Masyarakat
        </span>
        <h1 class="font-display font-extrabold text-[clamp(2.2rem,4.6vw,3.4rem)] leading-[1.12] text-forest dark:text-cream mt-4 mb-5">
          Menyalakan
          <span class="relative inline-block px-1">
            <span class="absolute inset-x-0 bottom-1.5 h-3 bg-gold/60 dark:bg-gold/25 -z-10 rounded"></span>
            Sinar Sehat
          </span>
          di Setiap Rumah
        </h1>
        <p class="text-lg text-ink-soft dark:text-cream-soft max-w-[500px] mb-6">Kami mendampingi ibu hamil, melindungi anak lewat imunisasi lengkap, dan memutus rantai malaria di desa-desa dengan akses layanan kesehatan yang masih terbatas.</p>

        <div class="flex items-center gap-4 flex-wrap text-sm text-ink-soft dark:text-cream-soft mb-7">
          <span><b class="text-forest dark:text-gold-bright font-display">2014</b> Berdiri</span>
          <span class="w-1 h-1 rounded-full bg-ink-soft/40 dark:bg-cream-soft/40"></span>
          <span><b class="text-forest dark:text-gold-bright font-display">76</b> Desa Dampingan</span>
          <span class="w-1 h-1 rounded-full bg-ink-soft/40 dark:bg-cream-soft/40"></span>
          <span><b class="text-forest dark:text-gold-bright font-display">18</b> Kabupaten</span>
        </div>

        <div class="flex gap-4 flex-wrap mb-8">
          <a href="#donasi" class="inline-flex items-center gap-2 font-bold text-[15px] px-7 py-3.5 rounded-full bg-coral text-white hover:bg-coral-dark hover:-translate-y-0.5 shadow-lg shadow-coral/20 transition-all duration-300">Donasi Sekarang</a>
          <a href="#program" class="inline-flex items-center gap-2 font-bold text-[15px] px-7 py-3.5 rounded-full bg-sage dark:bg-night-surface-2 text-forest dark:text-cream hover:-translate-y-0.5 transition-all duration-300">Lihat Program</a>
        </div>

        <div class="flex gap-3 flex-wrap">
          <button data-goto-tab="0" class="program-chip flex items-center gap-2 bg-butter dark:bg-night-surface-2 rounded-full py-2 pr-4 pl-2.5 font-semibold text-sm text-forest dark:text-cream hover:-translate-y-0.5 transition-transform">
            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.6-9.3-9.2C1.4 8.6 3 5 6.6 5c2 0 3.4 1.1 4.4 2.6C12 6.1 13.4 5 15.4 5 19 5 20.6 8.6 19.3 11.8 17 16.4 12 21 12 21z"/></svg>
            Ibu &amp; Anak
          </button>
          <button data-goto-tab="1" class="program-chip flex items-center gap-2 bg-sage dark:bg-night-surface-2 rounded-full py-2 pr-4 pl-2.5 font-semibold text-sm text-forest dark:text-cream hover:-translate-y-0.5 transition-transform">
            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Z"/><path d="M9 12l2 2 4-4"/></svg>
            Imunisasi
          </button>
          <button data-goto-tab="2" class="program-chip flex items-center gap-2 bg-blush dark:bg-night-surface-2 rounded-full py-2 pr-4 pl-2.5 font-semibold text-sm text-forest dark:text-cream hover:-translate-y-0.5 transition-transform">
            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20c3-6 13-6 16 0"/><circle cx="12" cy="9" r="5"/><path d="M9 9h6M12 6v6"/></svg>
            Malaria
          </button>
        </div>
      </div>

      <div class="relative flex items-center justify-center h-[340px] sm:h-[400px]">
        <div class="absolute w-64 h-64 bg-butter dark:bg-gold/10 rounded-full blur-3xl opacity-70 dark:opacity-40 animate-float"></div>
        <div class="absolute right-4 top-6 w-56 h-56 bg-blush dark:bg-coral/10 rounded-full blur-3xl opacity-60 dark:opacity-30 animate-float [animation-delay:1.5s]"></div>
        <div class="absolute left-2 bottom-4 w-60 h-60 bg-sage dark:bg-forest-tint/20 rounded-full blur-3xl opacity-70 dark:opacity-30 animate-float [animation-delay:3s]"></div>
        <svg viewBox="0 0 300 300" class="relative w-[220px] sm:w-[260px] h-auto drop-shadow-xl">
          <circle cx="150" cy="150" r="146" class="fill-gold"/>
          <path d="M150 216C150 216 96 174 96 137C96 111 114 92 136 92C142.5 92 146.5 95 150 101C153.5 95 157.5 92 164 92C186 92 204 111 204 137C204 174 150 216 150 216Z" class="fill-forest"/>
          <rect x="137" y="112" width="26" height="60" rx="4" class="fill-white"/>
          <rect x="120" y="129" width="60" height="26" rx="4" class="fill-white"/>
        </svg>
      </div>
    </div>
  </section>

  <!-- PROGRAM TABS (interactive) -->
  <section class="py-16 sm:py-20 px-4" id="program">
    <div class="max-w-[1180px] mx-auto">
      <div class="max-w-[600px] mb-10 reveal opacity-0 translate-y-6 transition-all duration-700 motion-reduce:transition-none">
        <span class="inline-flex items-center gap-2 text-[13px] font-bold tracking-[0.14em] uppercase text-coral-dark dark:text-coral-light">
          <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
          Fokus Program
        </span>
        <h2 class="font-display font-extrabold text-[clamp(1.8rem,3.2vw,2.4rem)] text-forest dark:text-cream mt-3">Pilih Program untuk Melihat Detail</h2>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-6 reveal opacity-0 translate-y-6 transition-all duration-700 motion-reduce:transition-none">
        <div class="flex lg:flex-col gap-2 overflow-x-auto lg:overflow-visible pb-2 lg:pb-0">
          <button class="tab-btn active flex items-center gap-3 shrink-0 w-full text-left px-5 py-4 rounded-2xl font-bold text-[15px] transition-colors bg-butter dark:bg-night-surface-2 text-forest dark:text-cream" data-tab="0">
            <svg viewBox="0 0 24 24" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.6-9.3-9.2C1.4 8.6 3 5 6.6 5c2 0 3.4 1.1 4.4 2.6C12 6.1 13.4 5 15.4 5 19 5 20.6 8.6 19.3 11.8 17 16.4 12 21 12 21z"/></svg>
            Kesehatan Ibu &amp; Anak
          </button>
          <button class="tab-btn flex items-center gap-3 shrink-0 w-full text-left px-5 py-4 rounded-2xl font-bold text-[15px] transition-colors text-ink-soft dark:text-cream-soft hover:bg-sage dark:hover:bg-night-surface-2" data-tab="1">
            <svg viewBox="0 0 24 24" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Z"/><path d="M9 12l2 2 4-4"/></svg>
            Imunisasi
          </button>
          <button class="tab-btn flex items-center gap-3 shrink-0 w-full text-left px-5 py-4 rounded-2xl font-bold text-[15px] transition-colors text-ink-soft dark:text-cream-soft hover:bg-sage dark:hover:bg-night-surface-2" data-tab="2">
            <svg viewBox="0 0 24 24" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20c3-6 13-6 16 0"/><circle cx="12" cy="9" r="5"/><path d="M9 9h6M12 6v6"/></svg>
            Penanganan Malaria
          </button>
        </div>

        <div class="rounded-3xl bg-white dark:bg-night-surface border border-forest/10 dark:border-cream/10 p-7 sm:p-9 transition-colors duration-300">
          <div class="tab-panel" data-panel="0">
            <div class="w-14 h-14 rounded-2xl bg-butter dark:bg-night-surface-2 text-forest dark:text-gold-bright flex items-center justify-center mb-5">
              <svg viewBox="0 0 24 24" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.6-9.3-9.2C1.4 8.6 3 5 6.6 5c2 0 3.4 1.1 4.4 2.6C12 6.1 13.4 5 15.4 5 19 5 20.6 8.6 19.3 11.8 17 16.4 12 21 12 21z"/></svg>
            </div>
            <h3 class="font-display font-extrabold text-2xl text-forest dark:text-cream mb-3">Mendampingi Sejak dalam Kandungan</h3>
            <p class="text-ink-soft dark:text-cream-soft mb-4">Pemeriksaan kehamilan berkala, pendampingan persalinan aman bersama bidan desa, dan pemantauan tumbuh kembang balita hingga usia lima tahun.</p>
            <div class="inline-flex items-center gap-2 text-sm font-bold text-forest dark:text-gold-bright bg-sage dark:bg-night-surface-2 px-4 py-2 rounded-full">12.480 ibu hamil didampingi</div>
          </div>
          <div class="tab-panel hidden" data-panel="1">
            <div class="w-14 h-14 rounded-2xl bg-sage dark:bg-night-surface-2 text-forest dark:text-gold-bright flex items-center justify-center mb-5">
              <svg viewBox="0 0 24 24" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Z"/><path d="M9 12l2 2 4-4"/></svg>
            </div>
            <h3 class="font-display font-extrabold text-2xl text-forest dark:text-cream mb-3">Vaksin Lengkap, Sampai ke Pelosok</h3>
            <p class="text-ink-soft dark:text-cream-soft mb-4">Menjangkau anak-anak di dusun terpencil dengan imunisasi dasar lengkap, cold chain vaksin yang terjaga, serta edukasi rutin bagi orang tua.</p>
            <div class="inline-flex items-center gap-2 text-sm font-bold text-forest dark:text-gold-bright bg-sage dark:bg-night-surface-2 px-4 py-2 rounded-full">34.900 anak diimunisasi lengkap</div>
          </div>
          <div class="tab-panel hidden" data-panel="2">
            <div class="w-14 h-14 rounded-2xl bg-blush dark:bg-night-surface-2 text-coral dark:text-coral-light flex items-center justify-center mb-5">
              <svg viewBox="0 0 24 24" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20c3-6 13-6 16 0"/><circle cx="12" cy="9" r="5"/><path d="M9 9h6M12 6v6"/></svg>
            </div>
            <h3 class="font-display font-extrabold text-2xl text-forest dark:text-cream mb-3">Memutus Rantai Penularan</h3>
            <p class="text-ink-soft dark:text-cream-soft mb-4">Distribusi kelambu berinsektisida, tes cepat untuk deteksi dini, serta pengobatan tuntas bekerja sama dengan puskesmas di wilayah endemis.</p>
            <div class="inline-flex items-center gap-2 text-sm font-bold text-forest dark:text-gold-bright bg-sage dark:bg-night-surface-2 px-4 py-2 rounded-full">8.650 kelambu anti-malaria dibagikan</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- IMPACT STATS (count-up) -->
  <section class="py-16 sm:py-20 px-4 bg-forest dark:bg-night-surface transition-colors duration-300" id="dampak">
    <div class="max-w-[1180px] mx-auto">
      <div class="max-w-[600px] mb-10 reveal opacity-0 translate-y-6 transition-all duration-700 motion-reduce:transition-none">
        <span class="inline-flex items-center gap-2 text-[13px] font-bold tracking-[0.14em] uppercase text-gold-bright">
          <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
          Dampak Kami
        </span>
        <h2 class="font-display font-extrabold text-[clamp(1.8rem,3.2vw,2.4rem)] text-white mt-3">Sejak 2014, Bersama Warga Dampingan</h2>
      </div>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 motion-reduce:transition-none text-center px-4 py-8 rounded-3xl bg-white/[0.06]">
          <span class="counter font-display font-extrabold text-[clamp(1.8rem,4vw,2.4rem)] text-gold-bright block" data-target="12480">0</span>
          <span class="text-[13px] text-white/75 mt-1 block">Ibu hamil didampingi</span>
        </div>
        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 motion-reduce:transition-none text-center px-4 py-8 rounded-3xl bg-white/[0.06]">
          <span class="counter font-display font-extrabold text-[clamp(1.8rem,4vw,2.4rem)] text-gold-bright block" data-target="34900">0</span>
          <span class="text-[13px] text-white/75 mt-1 block">Anak diimunisasi</span>
        </div>
        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 motion-reduce:transition-none text-center px-4 py-8 rounded-3xl bg-white/[0.06]">
          <span class="counter font-display font-extrabold text-[clamp(1.8rem,4vw,2.4rem)] text-gold-bright block" data-target="8650">0</span>
          <span class="text-[13px] text-white/75 mt-1 block">Kelambu dibagikan</span>
        </div>
        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 motion-reduce:transition-none text-center px-4 py-8 rounded-3xl bg-white/[0.06]">
          <span class="counter font-display font-extrabold text-[clamp(1.8rem,4vw,2.4rem)] text-gold-bright block" data-target="76">0</span>
          <span class="text-[13px] text-white/75 mt-1 block">Desa dampingan</span>
        </div>
      </div>
    </div>
  </section>

  <!-- TESTIMONIALS (carousel) -->
  <section class="py-16 sm:py-20 px-4" id="cerita">
    <div class="max-w-[1180px] mx-auto">
      <div class="max-w-[600px] mb-10 reveal opacity-0 translate-y-6 transition-all duration-700 motion-reduce:transition-none">
        <span class="inline-flex items-center gap-2 text-[13px] font-bold tracking-[0.14em] uppercase text-coral-dark dark:text-coral-light">
          <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
          Cerita dari Lapangan
        </span>
        <h2 class="font-display font-extrabold text-[clamp(1.8rem,3.2vw,2.4rem)] text-forest dark:text-cream mt-3">Suara Mereka yang Kami Dampingi</h2>
      </div>

      <div class="reveal opacity-0 translate-y-6 transition-all duration-700 motion-reduce:transition-none relative max-w-[720px] mx-auto">
        <div class="rounded-3xl bg-white dark:bg-night-surface border border-forest/10 dark:border-cream/10 p-8 sm:p-10 min-h-[240px] transition-colors duration-300">
          <div class="testi-slide" data-slide="0">
            <svg class="w-8 h-8 text-gold mb-4" viewBox="0 0 24 24" fill="currentColor"><path d="M7 7C4.8 7 3 8.8 3 11c0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1C7.3 17 6 18.5 4 19v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4zm10 0c-2.2 0-4 1.8-4 4 0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1-.6 2.1-1.9 3.6-3.9 4.1v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4z"/></svg>
            <p class="font-display font-medium text-lg text-forest dark:text-cream leading-[1.5] mb-5">Kader posyandu di kampung kami jadi lebih percaya diri mendampingi ibu hamil sejak ada pelatihan rutin dari yayasan.</p>
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-full bg-sage dark:bg-night-surface-2 flex items-center justify-center text-forest dark:text-cream font-display font-bold shrink-0">SW</div>
              <div><div class="font-bold text-[14.5px] text-ink dark:text-cream">Sri Wahyuni</div><div class="text-[13px] text-ink-soft dark:text-cream-soft">Kader Posyandu, Sikka · NTT</div></div>
            </div>
          </div>
          <div class="testi-slide hidden" data-slide="1">
            <svg class="w-8 h-8 text-gold mb-4" viewBox="0 0 24 24" fill="currentColor"><path d="M7 7C4.8 7 3 8.8 3 11c0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1C7.3 17 6 18.5 4 19v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4zm10 0c-2.2 0-4 1.8-4 4 0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1-.6 2.1-1.9 3.6-3.9 4.1v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4z"/></svg>
            <p class="font-display font-medium text-lg text-forest dark:text-cream leading-[1.5] mb-5">Anak saya sekarang lengkap imunisasinya. Petugas datang langsung ke dusun, kami tidak perlu jalan jauh lagi.</p>
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-full bg-sage dark:bg-night-surface-2 flex items-center justify-center text-forest dark:text-cream font-display font-bold shrink-0">F</div>
              <div><div class="font-bold text-[14.5px] text-ink dark:text-cream">Fatimah</div><div class="text-[13px] text-ink-soft dark:text-cream-soft">Warga, Waepana · Manggarai</div></div>
            </div>
          </div>
          <div class="testi-slide hidden" data-slide="2">
            <svg class="w-8 h-8 text-gold mb-4" viewBox="0 0 24 24" fill="currentColor"><path d="M7 7C4.8 7 3 8.8 3 11c0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1C7.3 17 6 18.5 4 19v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4zm10 0c-2.2 0-4 1.8-4 4 0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1-.6 2.1-1.9 3.6-3.9 4.1v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4z"/></svg>
            <p class="font-display font-medium text-lg text-forest dark:text-cream leading-[1.5] mb-5">Sejak dapat kelambu dan edukasi rutin, kasus malaria di dusun kami turun jauh dibanding tiga tahun lalu.</p>
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-full bg-sage dark:bg-night-surface-2 flex items-center justify-center text-forest dark:text-cream font-display font-bold shrink-0">YB</div>
              <div><div class="font-bold text-[14.5px] text-ink dark:text-cream">Yosef Bunga</div><div class="text-[13px] text-ink-soft dark:text-cream-soft">Kepala Dusun, Sumba Timur</div></div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-center gap-4 mt-5">
          <button id="testiPrev" aria-label="Sebelumnya" class="w-10 h-10 rounded-full bg-sage dark:bg-night-surface-2 text-forest dark:text-cream flex items-center justify-center hover:-translate-x-0.5 transition-transform">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
          </button>
          <div class="flex gap-2">
            <button class="testi-dot w-2.5 h-2.5 rounded-full bg-forest dark:bg-gold-bright" data-dot="0"></button>
            <button class="testi-dot w-2.5 h-2.5 rounded-full bg-forest/25 dark:bg-cream/25" data-dot="1"></button>
            <button class="testi-dot w-2.5 h-2.5 rounded-full bg-forest/25 dark:bg-cream/25" data-dot="2"></button>
          </div>
          <button id="testiNext" aria-label="Berikutnya" class="w-10 h-10 rounded-full bg-sage dark:bg-night-surface-2 text-forest dark:text-cream flex items-center justify-center hover:translate-x-0.5 transition-transform">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 6l6 6-6 6"/></svg>
          </button>
        </div>
      </div>
      <p class="text-xs text-ink-soft dark:text-cream-soft text-center mt-5">*Cerita di atas adalah contoh ilustratif — mudah diganti dengan testimoni asli.</p>
    </div>
  </section>

  <!-- FAQ (accordion) -->
  <section class="py-16 sm:py-20 px-4 bg-sage dark:bg-night-surface transition-colors duration-300" id="faq">
    <div class="max-w-[800px] mx-auto">
      <div class="mb-10 reveal opacity-0 translate-y-6 transition-all duration-700 motion-reduce:transition-none">
        <span class="inline-flex items-center gap-2 text-[13px] font-bold tracking-[0.14em] uppercase text-coral-dark dark:text-coral-light">
          <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
          Pertanyaan Umum
        </span>
        <h2 class="font-display font-extrabold text-[clamp(1.8rem,3.2vw,2.4rem)] text-forest dark:text-cream mt-3">Yang Sering Ditanyakan</h2>
      </div>

      <div class="space-y-3 reveal opacity-0 translate-y-6 transition-all duration-700 motion-reduce:transition-none">
        <div class="faq-item rounded-2xl bg-white dark:bg-night-surface-2 border border-forest/10 dark:border-cream/10 overflow-hidden">
          <button class="faq-q w-full flex items-center justify-between gap-4 text-left px-6 py-5 font-bold text-[15.5px] text-forest dark:text-cream">
            Bagaimana cara donasi ke yayasan ini?
            <svg class="faq-chevron w-5 h-5 shrink-0 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 9l6 6 6-6"/></svg>
          </button>
          <div class="faq-a hidden px-6 pb-5 text-ink-soft dark:text-cream-soft text-[15px]">Anda dapat berdonasi melalui transfer bank ke rekening yang tertera di bagian donasi, atau menghubungi kami langsung untuk metode lain seperti dompet digital.</div>
        </div>
        <div class="faq-item rounded-2xl bg-white dark:bg-night-surface-2 border border-forest/10 dark:border-cream/10 overflow-hidden">
          <button class="faq-q w-full flex items-center justify-between gap-4 text-left px-6 py-5 font-bold text-[15.5px] text-forest dark:text-cream">
            Apakah donasi saya bisa dilacak penggunaannya?
            <svg class="faq-chevron w-5 h-5 shrink-0 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 9l6 6 6-6"/></svg>
          </button>
          <div class="faq-a hidden px-6 pb-5 text-ink-soft dark:text-cream-soft text-[15px]">Ya, kami menerbitkan laporan keuangan berkala kepada donatur tetap dan dapat memberikan laporan penggunaan dana atas permintaan.</div>
        </div>
        <div class="faq-item rounded-2xl bg-white dark:bg-night-surface-2 border border-forest/10 dark:border-cream/10 overflow-hidden">
          <button class="faq-q w-full flex items-center justify-between gap-4 text-left px-6 py-5 font-bold text-[15.5px] text-forest dark:text-cream">
            Bagaimana cara menjadi relawan?
            <svg class="faq-chevron w-5 h-5 shrink-0 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 9l6 6 6-6"/></svg>
          </button>
          <div class="faq-a hidden px-6 pb-5 text-ink-soft dark:text-cream-soft text-[15px]">Isi formulir kontak atau hubungi kami melalui WhatsApp; tim kami akan menjelaskan program relawan yang sedang berjalan sesuai lokasi Anda.</div>
        </div>
        <div class="faq-item rounded-2xl bg-white dark:bg-night-surface-2 border border-forest/10 dark:border-cream/10 overflow-hidden">
          <button class="faq-q w-full flex items-center justify-between gap-4 text-left px-6 py-5 font-bold text-[15.5px] text-forest dark:text-cream">
            Di daerah mana saja yayasan ini beroperasi?
            <svg class="faq-chevron w-5 h-5 shrink-0 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 9l6 6 6-6"/></svg>
          </button>
          <div class="faq-a hidden px-6 pb-5 text-ink-soft dark:text-cream-soft text-[15px]">Saat ini kami aktif di 76 desa dampingan yang tersebar di 18 kabupaten, dengan fokus di wilayah Nusa Tenggara Timur, Maluku, dan Papua.</div>
        </div>
      </div>
    </div>
  </section>

  <!-- DONATION -->
  <section class="py-16 sm:py-20 px-4" id="donasi">
    <div class="max-w-[1180px] mx-auto">
      <div class="reveal opacity-0 translate-y-6 transition-all duration-700 motion-reduce:transition-none rounded-[32px] bg-forest dark:bg-night-surface p-8 sm:p-12 grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] gap-10 items-center transition-colors duration-300">
        <div>
          <h2 class="font-display font-extrabold text-[clamp(1.8rem,3.4vw,2.4rem)] text-white leading-[1.15] mb-4">Setiap Sumbangan Adalah Sinar Baru</h2>
          <p class="text-white/80 text-[15.5px] mb-6 max-w-[440px]">Pilih nominal donasi, atau masukkan jumlah sendiri. Setiap kontribusi langsung disalurkan ke program dampingan kami.</p>
          <div class="flex gap-2.5 flex-wrap mb-6">
            <button class="amount-chip active px-5 py-2.5 rounded-full font-bold text-sm bg-gold text-forest" data-amount="50000">Rp50rb</button>
            <button class="amount-chip px-5 py-2.5 rounded-full font-bold text-sm bg-white/10 text-white hover:bg-white/20 transition-colors" data-amount="150000">Rp150rb</button>
            <button class="amount-chip px-5 py-2.5 rounded-full font-bold text-sm bg-white/10 text-white hover:bg-white/20 transition-colors" data-amount="500000">Rp500rb</button>
            <button class="amount-chip px-5 py-2.5 rounded-full font-bold text-sm bg-white/10 text-white hover:bg-white/20 transition-colors" data-amount="0">Lainnya</button>
          </div>
          <a href="#kontak" class="inline-flex items-center gap-2 font-bold text-[15px] px-7 py-3.5 rounded-full bg-white text-forest hover:-translate-y-0.5 transition-all duration-300">Lanjutkan Donasi</a>
        </div>
        <div class="rounded-3xl bg-white/10 border border-white/20 p-7 backdrop-blur-sm">
          <div class="text-xs tracking-[0.1em] uppercase text-white/70 mb-2">Transfer Bank</div>
          <div class="font-display text-xl font-bold text-white mb-1">BCA · 123 4567 890</div>
          <div class="text-sm text-white/80 mb-5">a.n. Yayasan Sinar Bhakti Husada</div>
          <button id="copyBtn" class="bg-white text-forest rounded-full px-5 py-2.5 font-bold text-sm hover:bg-white/90 transition-colors">Salin Nomor Rekening</button>
        </div>
      </div>
    </div>
  </section>

  <!-- CONTACT -->
  <section class="py-16 sm:py-20 px-4" id="kontak">
    <div class="max-w-[1180px] mx-auto">
      <div class="max-w-[600px] mb-10 reveal opacity-0 translate-y-6 transition-all duration-700 motion-reduce:transition-none">
        <span class="inline-flex items-center gap-2 text-[13px] font-bold tracking-[0.14em] uppercase text-coral-dark dark:text-coral-light">
          <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
          Hubungi Kami
        </span>
        <h2 class="font-display font-extrabold text-[clamp(1.8rem,3.2vw,2.4rem)] text-forest dark:text-cream mt-3">Mari Berbincang atau Berkunjung</h2>
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 reveal opacity-0 translate-y-6 transition-all duration-700 motion-reduce:transition-none">
        <div class="rounded-3xl bg-sage dark:bg-night-surface p-6 flex items-start gap-4 transition-colors duration-300">
          <div class="w-11 h-11 rounded-2xl bg-white dark:bg-night-surface-2 flex items-center justify-center shrink-0 text-forest dark:text-gold-bright">
            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Z"/><path d="m22 6-10 7L2 6"/></svg>
          </div>
          <div><div class="font-bold text-[14.5px] text-forest dark:text-cream mb-1">Email</div><div class="text-[14.5px] text-ink-soft dark:text-cream-soft">kontak@sinarbhaktihusada.org</div></div>
        </div>
        <div class="rounded-3xl bg-sage dark:bg-night-surface p-6 flex items-start gap-4 transition-colors duration-300">
          <div class="w-11 h-11 rounded-2xl bg-white dark:bg-night-surface-2 flex items-center justify-center shrink-0 text-forest dark:text-gold-bright">
            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.9.6 2.8a2 2 0 0 1-.5 2.1L7.9 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.8.6a2 2 0 0 1 1.7 2Z"/></svg>
          </div>
          <div><div class="font-bold text-[14.5px] text-forest dark:text-cream mb-1">WhatsApp</div><div class="text-[14.5px] text-ink-soft dark:text-cream-soft">+62 812-3456-7890</div></div>
        </div>
        <div class="rounded-3xl bg-sage dark:bg-night-surface p-6 flex items-start gap-4 transition-colors duration-300">
          <div class="w-11 h-11 rounded-2xl bg-white dark:bg-night-surface-2 flex items-center justify-center shrink-0 text-forest dark:text-gold-bright">
            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
          </div>
          <div><div class="font-bold text-[14.5px] text-forest dark:text-cream mb-1">Sekretariat</div><div class="text-[14.5px] text-ink-soft dark:text-cream-soft">Jl. Melati No. 21, Kupang, NTT</div></div>
        </div>
      </div>
    </div>
  </section>

</main>

<footer class="px-4 pb-8 pt-4">
  <div class="max-w-[1180px] mx-auto border-t border-forest/10 dark:border-cream/10 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-[13px] text-ink-soft dark:text-cream-soft">
    <span>© 2026 Yayasan Sinar Bhakti Husada. Seluruh hak cipta dilindungi.</span>
    <span>Dibuat dengan bhakti, untuk sinar yang terus menyala.</span>
  </div>
</footer>

<!-- Back to top -->
<button id="backToTop" aria-label="Kembali ke atas" class="hidden fixed bottom-6 right-6 z-40 w-11 h-11 rounded-full bg-forest dark:bg-gold-bright text-white dark:text-forest-dark shadow-lg items-center justify-center hover:-translate-y-1 transition-all duration-300">
  <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
</button>

<script>
  // Theme init
  const root = document.documentElement;
  if (window.matchMedia('(prefers-color-scheme: dark)').matches) root.classList.add('dark');
  document.getElementById('themeToggle').addEventListener('click', () => root.classList.toggle('dark'));

  // Mobile menu
  const burger = document.getElementById('burgerBtn');
  const mobileMenu = document.getElementById('mobileMenu');
  burger.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
  mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => mobileMenu.classList.add('hidden')));

  // Scrollspy
  const navLinks = document.querySelectorAll('.nav-link');
  const sections = ['program','dampak','cerita','faq','kontak'].map(id => document.getElementById(id));
  const activeClasses = ['text-forest','dark:text-cream','bg-sage','dark:bg-night-surface-2'];
  const spyIO = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        navLinks.forEach(l => {
          const isMatch = l.dataset.section === entry.target.id;
          l.classList.toggle('text-forest', isMatch);
          l.classList.toggle('dark:text-cream', isMatch);
          l.classList.toggle('bg-sage', isMatch);
          l.classList.toggle('dark:bg-night-surface-2', isMatch);
          l.classList.toggle('text-ink-soft', !isMatch);
          l.classList.toggle('dark:text-cream-soft', !isMatch);
        });
      }
    });
  }, { rootMargin: '-40% 0px -55% 0px' });
  sections.forEach(s => s && spyIO.observe(s));

  // Reveal on scroll
  const revealEls = document.querySelectorAll('.reveal');
  const revealIO = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.remove('opacity-0', 'translate-y-6');
        e.target.classList.add('opacity-100', 'translate-y-0');
        revealIO.unobserve(e.target);
      }
    });
  }, { threshold: 0.12 });
  revealEls.forEach(el => revealIO.observe(el));

  // Program tabs
  const tabBtns = document.querySelectorAll('.tab-btn');
  const tabPanels = document.querySelectorAll('.tab-panel');
  function activateTab(idx) {
    idx = String(idx);
    tabBtns.forEach(b => {
      const match = b.dataset.tab === idx;
      b.classList.toggle('bg-butter', match);
      b.classList.toggle('dark:bg-night-surface-2', match);
      b.classList.toggle('text-forest', match);
      b.classList.toggle('dark:text-cream', match);
      b.classList.toggle('text-ink-soft', !match);
      b.classList.toggle('dark:text-cream-soft', !match);
    });
    tabPanels.forEach(p => p.classList.toggle('hidden', p.dataset.panel !== idx));
  }
  tabBtns.forEach(b => b.addEventListener('click', () => activateTab(b.dataset.tab)));
  document.querySelectorAll('[data-goto-tab]').forEach(chip => {
    chip.addEventListener('click', () => {
      activateTab(chip.dataset.gotoTab);
      document.getElementById('program').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  // Count-up stats
  const counters = document.querySelectorAll('.counter');
  const countIO = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el = entry.target;
        const target = parseInt(el.dataset.target, 10);
        const duration = 1500;
        const start = performance.now();
        function tick(now) {
          const progress = Math.min((now - start) / duration, 1);
          const eased = 1 - Math.pow(1 - progress, 3);
          el.textContent = Math.floor(eased * target).toLocaleString('id-ID');
          if (progress < 1) requestAnimationFrame(tick);
          else el.textContent = target.toLocaleString('id-ID');
        }
        requestAnimationFrame(tick);
        countIO.unobserve(el);
      }
    });
  }, { threshold: 0.4 });
  counters.forEach(c => countIO.observe(c));

  // Testimonial carousel
  const slides = document.querySelectorAll('.testi-slide');
  const dots = document.querySelectorAll('.testi-dot');
  let currentSlide = 0;
  function showSlide(idx) {
    slides.forEach(s => s.classList.toggle('hidden', s.dataset.slide !== String(idx)));
    dots.forEach(d => {
      const match = d.dataset.dot === String(idx);
      d.classList.toggle('bg-forest', match);
      d.classList.toggle('dark:bg-gold-bright', match);
      d.classList.toggle('bg-forest/25', !match);
      d.classList.toggle('dark:bg-cream/25', !match);
    });
    currentSlide = idx;
  }
  document.getElementById('testiPrev').addEventListener('click', () => showSlide((currentSlide - 1 + slides.length) % slides.length));
  document.getElementById('testiNext').addEventListener('click', () => showSlide((currentSlide + 1) % slides.length));
  dots.forEach(d => d.addEventListener('click', () => showSlide(parseInt(d.dataset.dot, 10))));

  // FAQ accordion
  document.querySelectorAll('.faq-item').forEach(item => {
    const btn = item.querySelector('.faq-q');
    const ans = item.querySelector('.faq-a');
    const chevron = item.querySelector('.faq-chevron');
    btn.addEventListener('click', () => {
      const isOpen = !ans.classList.contains('hidden');
      ans.classList.toggle('hidden', isOpen);
      chevron.classList.toggle('rotate-180', !isOpen);
    });
  });

  // Donation amount chips
  document.querySelectorAll('.amount-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      document.querySelectorAll('.amount-chip').forEach(c => {
        c.classList.remove('bg-gold', 'text-forest', 'active');
        c.classList.add('bg-white/10', 'text-white');
      });
      chip.classList.remove('bg-white/10', 'text-white');
      chip.classList.add('bg-gold', 'text-forest', 'active');
    });
  });

  // Copy account number
  document.getElementById('copyBtn').addEventListener('click', () => {
    navigator.clipboard.writeText('1234567890').then(() => {
      const btn = document.getElementById('copyBtn');
      const original = btn.textContent;
      btn.textContent = 'Tersalin ✓';
      setTimeout(() => btn.textContent = original, 2000);
    });
  });

  // Back to top
  const backToTop = document.getElementById('backToTop');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 500) { backToTop.classList.remove('hidden'); backToTop.classList.add('flex'); }
    else { backToTop.classList.add('hidden'); backToTop.classList.remove('flex'); }
  });
  backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
</script>

</body>
</html>
