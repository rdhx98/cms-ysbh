<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yayasan Sinar Bhakti Husada — Sinar Sehat untuk Setiap Rumah</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;0,9..144,900;1,9..144,500;1,9..144,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          forest: '#064F3B',
          'forest-dark': '#043328',
          'forest-tint': '#0E6A4F',
          gold: '#EBCC26',
          'gold-soft': '#F7EBAF',
          coral: '#E42326',
          'coral-dark': '#BE1417',
          paper: '#FBF7EA',
          'paper-dim': '#F3ECD6',
          ink: '#12241C',
          'ink-soft': '#4B5D53',
          mist: '#E9F1EB',
        },
        fontFamily: {
          display: ['Fraunces', 'serif'],
          body: ['"Plus Jakarta Sans"', 'sans-serif'],
        },
        keyframes: {
          spin: { from: { transform: 'rotate(0deg)' }, to: { transform: 'rotate(360deg)' } },
        },
        animation: {
          'spin-slow': 'spin 90s linear infinite',
          'spin-slower': 'spin 120s linear infinite',
        },
      }
    }
  }
</script>
</head>
<body class="m-0 bg-paper text-ink font-body antialiased leading-relaxed overflow-x-hidden">

<header class="sticky top-0 z-[100] bg-paper/90 backdrop-blur-md border-b border-forest/15">
  <div class="flex items-center justify-between px-5 sm:px-8 py-4 max-w-[1180px] mx-auto">
    <a href="#home" class="flex items-center gap-3">
      <svg viewBox="0 0 60 60" class="w-[42px] h-[42px]" xmlns="http://www.w3.org/2000/svg">
        <circle cx="30" cy="30" r="29" class="fill-gold"/>
        <path d="M30 46C30 46 14 36.8 14 25.6C14 19.2 19 15 24 15C27 15 29 16.6 30 18.6C31 16.6 33 15 36 15C41 15 46 19.2 46 25.6C46 36.8 30 46 30 46Z" class="fill-forest"/>
        <rect x="26.5" y="21" width="7" height="17" rx="1.5" class="fill-white"/>
        <rect x="21.5" y="26" width="17" height="7" rx="1.5" class="fill-white"/>
      </svg>
      <div class="font-display font-bold text-[17px] text-forest leading-[1.15]">
        Sinar Bhakti Husada
        <span class="block font-body font-semibold text-[10.5px] tracking-[0.14em] text-coral uppercase mt-0.5">Yayasan Kesehatan</span>
      </div>
    </a>

    <nav id="navLinks" class="fixed inset-0 top-[72px] flex flex-col items-start justify-start gap-6 bg-paper p-8 border-t border-forest/15 translate-x-full transition-transform duration-300 ease-out
                               lg:static lg:flex lg:flex-row lg:items-center lg:gap-9 lg:p-0 lg:border-0 lg:bg-transparent lg:translate-x-0 lg:transition-none">
      <a href="#tentang" class="text-lg lg:text-[15px] font-semibold text-ink-soft hover:text-forest focus-visible:outline focus-visible:outline-2 focus-visible:outline-coral focus-visible:outline-offset-4">Tentang</a>
      <a href="#program" class="text-lg lg:text-[15px] font-semibold text-ink-soft hover:text-forest focus-visible:outline focus-visible:outline-2 focus-visible:outline-coral focus-visible:outline-offset-4">Program</a>
      <a href="#dampak" class="text-lg lg:text-[15px] font-semibold text-ink-soft hover:text-forest focus-visible:outline focus-visible:outline-2 focus-visible:outline-coral focus-visible:outline-offset-4">Dampak</a>
      <a href="#cerita" class="text-lg lg:text-[15px] font-semibold text-ink-soft hover:text-forest focus-visible:outline focus-visible:outline-2 focus-visible:outline-coral focus-visible:outline-offset-4">Cerita</a>
      <a href="#kontak" class="text-lg lg:text-[15px] font-semibold text-ink-soft hover:text-forest focus-visible:outline focus-visible:outline-2 focus-visible:outline-coral focus-visible:outline-offset-4">Kontak</a>
    </nav>

    <div class="flex items-center gap-[18px]">
      <a href="#kontak" class="hidden lg:inline-flex items-center justify-center gap-2 font-bold text-sm px-[22px] py-[11px] rounded-full border-2 border-forest text-forest hover:bg-forest hover:text-white transition-colors duration-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-coral focus-visible:outline-offset-4">Hubungi Kami</a>
      <a href="#donasi" class="inline-flex items-center justify-center gap-2 font-bold text-sm px-[22px] py-[11px] rounded-full border-2 border-transparent bg-coral text-white hover:bg-coral-dark transition-colors duration-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-coral focus-visible:outline-offset-4">Donasi Sekarang</a>
      <button id="burgerBtn" class="lg:hidden bg-transparent border-0 cursor-pointer p-1.5" aria-label="Buka menu">
        <span class="block w-6 h-[2.5px] bg-forest my-[5px] rounded"></span>
        <span class="block w-6 h-[2.5px] bg-forest my-[5px] rounded"></span>
        <span class="block w-6 h-[2.5px] bg-forest my-[5px] rounded"></span>
      </button>
    </div>
  </div>
</header>

<main id="home">

  <!-- HERO -->
  <section class="relative pt-16 sm:pt-[88px] pb-14 sm:pb-16 overflow-hidden">
    <div class="max-w-[1180px] mx-auto px-5 sm:px-8 grid grid-cols-1 lg:grid-cols-[1.05fr_0.95fr] gap-12 lg:gap-14 items-center">
      <div>
        <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark">
          <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
          Yayasan Kesehatan Masyarakat
        </span>
        <h1 class="font-display font-semibold text-[clamp(2.4rem,4.6vw,3.6rem)] leading-[1.08] text-forest mt-4 mb-5">
          Menyalakan <em class="italic text-coral font-medium">Sinar Sehat</em> di Setiap Rumah
        </h1>
        <p class="text-lg text-ink-soft max-w-[520px] mb-8">Kami mendampingi ibu hamil, melindungi anak lewat imunisasi lengkap, dan memutus rantai malaria di desa-desa dengan akses layanan kesehatan yang masih terbatas.</p>
        <div class="flex gap-4 flex-wrap mb-11">
          <a href="#donasi" class="inline-flex items-center justify-center gap-2 font-bold text-[15.5px] px-7 py-3.5 rounded-full border-2 border-transparent bg-coral text-white hover:bg-coral-dark hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-14px_rgba(228,35,38,0.55)] transition-all duration-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-coral focus-visible:outline-offset-[3px]">Donasi Sekarang</a>
          <a href="#program" class="inline-flex items-center justify-center gap-2 font-bold text-[15.5px] px-7 py-3.5 rounded-full border-2 border-forest bg-transparent text-forest hover:bg-forest hover:text-white hover:-translate-y-0.5 transition-all duration-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-coral focus-visible:outline-offset-[3px]">Lihat Program Kami</a>
        </div>
        <div class="flex gap-3.5 flex-wrap">
          <a href="#program" class="flex items-center gap-2.5 bg-white border border-forest/15 rounded-full py-[9px] pr-[18px] pl-2.5 font-semibold text-sm text-forest shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)]">
            <span class="w-[26px] h-[26px] rounded-full flex items-center justify-center shrink-0 bg-gold-soft">
              <svg viewBox="0 0 24 24" class="w-[15px] h-[15px]" fill="none" stroke="#064F3B" stroke-width="2"><path d="M12 21s-7-4.6-9.3-9.2C1.4 8.6 3 5 6.6 5c2 0 3.4 1.1 4.4 2.6C12 6.1 13.4 5 15.4 5 19 5 20.6 8.6 19.3 11.8 17 16.4 12 21 12 21z"/></svg>
            </span>
            Ibu &amp; Anak
          </a>
          <a href="#program" class="flex items-center gap-2.5 bg-white border border-forest/15 rounded-full py-[9px] pr-[18px] pl-2.5 font-semibold text-sm text-forest shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)]">
            <span class="w-[26px] h-[26px] rounded-full flex items-center justify-center shrink-0 bg-mist">
              <svg viewBox="0 0 24 24" class="w-[15px] h-[15px]" fill="none" stroke="#064F3B" stroke-width="2"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Z"/><path d="M9 12l2 2 4-4"/></svg>
            </span>
            Imunisasi
          </a>
          <a href="#program" class="flex items-center gap-2.5 bg-white border border-forest/15 rounded-full py-[9px] pr-[18px] pl-2.5 font-semibold text-sm text-forest shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)]">
            <span class="w-[26px] h-[26px] rounded-full flex items-center justify-center shrink-0 bg-[#FBE6E6]">
              <svg viewBox="0 0 24 24" class="w-[15px] h-[15px]" fill="none" stroke="#E42326" stroke-width="2"><path d="M4 20c3-6 13-6 16 0"/><circle cx="12" cy="9" r="5"/><path d="M9 9h6M12 6v6"/></svg>
            </span>
            Malaria
          </a>
        </div>
      </div>

      <div class="flex items-center justify-center" aria-hidden="true">
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
          <circle cx="150" cy="150" r="92" class="fill-gold"/>
          <circle cx="150" cy="150" r="92" class="fill-none stroke-forest opacity-15" stroke-width="2"/>
          <path d="M150 205C150 205 108 172 108 143C108 122 122 108 139 108C144.5 108 148 111 150 116C152 111 155.5 108 161 108C178 108 192 122 192 143C192 172 150 205 150 205Z" class="fill-forest"/>
          <rect x="140" y="128" width="20" height="46" rx="3" class="fill-white"/>
          <rect x="127" y="141" width="46" height="20" rx="3" class="fill-white"/>
        </svg>
      </div>
    </div>
  </section>

  <!-- ABOUT -->
  <section class="py-16 sm:py-24" id="tentang">
    <div class="max-w-[1180px] mx-auto px-5 sm:px-8 grid grid-cols-1 lg:grid-cols-[0.9fr_1.1fr] gap-9 lg:gap-16 items-start">
      <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
        <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark">
          <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
          Tentang Kami
        </span>
        <p class="font-display font-medium text-[26px] leading-[1.4] text-forest mt-4">"Sinar" berarti cahaya harapan. <em class="italic text-coral">"Bhakti"</em> berarti pengabdian. Keduanya menuntun setiap langkah kerja kami di lapangan.</p>
      </div>
      <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
        <p class="text-ink-soft mb-4">Yayasan Sinar Bhakti Husada berdiri untuk menjawab satu masalah nyata: banyak keluarga di daerah terpencil Indonesia masih sulit menjangkau layanan kesehatan dasar. Kami hadir langsung di dusun dan desa, bekerja sama dengan puskesmas, kader posyandu, dan pemerintah setempat.</p>
        <p class="text-ink-soft mb-4">Tiga program utama kami — kesehatan ibu &amp; anak, imunisasi, dan penanganan malaria — dipilih karena ketiganya adalah titik kritis yang paling menentukan masa depan sebuah keluarga, terutama di wilayah endemis seperti Nusa Tenggara Timur, Maluku, dan Papua.</p>
        <div class="flex gap-7 mt-7 flex-wrap">
          <div><b class="block font-display text-[26px] text-forest">2014</b><span class="text-[13px] text-ink-soft">Tahun berdiri</span></div>
          <div><b class="block font-display text-[26px] text-forest">76</b><span class="text-[13px] text-ink-soft">Desa dampingan</span></div>
          <div><b class="block font-display text-[26px] text-forest">18</b><span class="text-[13px] text-ink-soft">Kabupaten tersebar</span></div>
        </div>
      </div>
    </div>
  </section>

  <!-- PROGRAMS -->
  <section class="py-16 sm:py-24 bg-mist" id="program">
    <div class="max-w-[1180px] mx-auto px-5 sm:px-8">
      <div class="max-w-[640px] mb-14 reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
        <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark">
          <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
          Fokus Program
        </span>
        <h2 class="font-display font-semibold text-[clamp(1.9rem,3.2vw,2.6rem)] leading-[1.15] text-forest mt-3.5">Tiga Pilar yang Kami Jaga Bersama</h2>
        <p class="text-ink-soft text-[16.5px] mt-4">Bukan proyek satu kali jalan — tiga program ini berjalan berdampingan, saling menguatkan, di setiap desa dampingan kami.</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-7">
        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none bg-white rounded-[28px] p-8 border border-forest/15 shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-1.5 transition-transform duration-300 flex flex-col">
          <div class="w-16 h-16 rounded-[18px] flex items-center justify-center mb-5 bg-gold-soft text-forest">
            <svg viewBox="0 0 24 24" class="w-[30px] h-[30px]" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.6-9.3-9.2C1.4 8.6 3 5 6.6 5c2 0 3.4 1.1 4.4 2.6C12 6.1 13.4 5 15.4 5 19 5 20.6 8.6 19.3 11.8 17 16.4 12 21 12 21z"/></svg>
          </div>
          <div class="text-xs font-bold tracking-[0.1em] uppercase mb-3.5 text-coral-dark">Kesehatan Ibu &amp; Anak</div>
          <h3 class="font-display text-[21px] font-semibold text-forest mb-2.5">Mendampingi Sejak dalam Kandungan</h3>
          <p class="text-ink-soft text-[15px] flex-1">Pemeriksaan kehamilan berkala, pendampingan persalinan aman bersama bidan desa, dan pemantauan tumbuh kembang balita hingga usia lima tahun.</p>
          <a href="#kontak" class="group mt-5 font-bold text-[14.5px] inline-flex items-center gap-1.5 text-forest">Selengkapnya
            <svg viewBox="0 0 24 24" class="w-[15px] h-[15px] transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </a>
        </div>

        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none bg-white rounded-[28px] p-8 border border-forest/15 shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-1.5 transition-transform duration-300 flex flex-col">
          <div class="w-16 h-16 rounded-[18px] flex items-center justify-center mb-5 bg-mist text-forest">
            <svg viewBox="0 0 24 24" class="w-[30px] h-[30px]" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-4Z"/><path d="M9 12l2 2 4-4"/></svg>
          </div>
          <div class="text-xs font-bold tracking-[0.1em] uppercase mb-3.5 text-forest-tint">Imunisasi</div>
          <h3 class="font-display text-[21px] font-semibold text-forest mb-2.5">Vaksin Lengkap, Sampai ke Pelosok</h3>
          <p class="text-ink-soft text-[15px] flex-1">Menjangkau anak-anak di dusun terpencil dengan imunisasi dasar lengkap, cold chain vaksin yang terjaga, serta edukasi rutin bagi orang tua.</p>
          <a href="#kontak" class="group mt-5 font-bold text-[14.5px] inline-flex items-center gap-1.5 text-forest">Selengkapnya
            <svg viewBox="0 0 24 24" class="w-[15px] h-[15px] transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </a>
        </div>

        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none bg-white rounded-[28px] p-8 border border-forest/15 shadow-[0_20px_50px_-25px_rgba(6,45,35,0.35)] hover:-translate-y-1.5 transition-transform duration-300 flex flex-col">
          <div class="w-16 h-16 rounded-[18px] flex items-center justify-center mb-5 bg-[#FBE6E6] text-coral">
            <svg viewBox="0 0 24 24" class="w-[30px] h-[30px]" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20c3-6 13-6 16 0"/><circle cx="12" cy="9" r="5"/><path d="M9 9h6M12 6v6"/></svg>
          </div>
          <div class="text-xs font-bold tracking-[0.1em] uppercase mb-3.5 text-coral-dark">Penanganan Malaria</div>
          <h3 class="font-display text-[21px] font-semibold text-forest mb-2.5">Memutus Rantai Penularan</h3>
          <p class="text-ink-soft text-[15px] flex-1">Distribusi kelambu berinsektisida, tes cepat untuk deteksi dini, serta pengobatan tuntas bekerja sama dengan puskesmas di wilayah endemis.</p>
          <a href="#kontak" class="group mt-5 font-bold text-[14.5px] inline-flex items-center gap-1.5 text-forest">Selengkapnya
            <svg viewBox="0 0 24 24" class="w-[15px] h-[15px] transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- IMPACT -->
  <section class="py-16 sm:py-24 bg-forest text-white" id="dampak">
    <div class="max-w-[1180px] mx-auto px-5 sm:px-8">
      <div class="max-w-[640px] mb-14 reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
        <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-gold">
          <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
          Dampak Kami
        </span>
        <h2 class="font-display font-semibold text-[clamp(1.9rem,3.2vw,2.6rem)] leading-[1.15] text-white mt-3.5">Sejak 2014, Bersama Warga Dampingan</h2>
        <p class="text-white/75 text-[16.5px] mt-4">Angka-angka ini adalah hasil kerja bersama kader posyandu, bidan desa, relawan, dan tentu saja para donatur.</p>
      </div>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none text-center px-4 py-[30px] border border-dashed border-white/[0.28] rounded-[18px]">
          <span class="font-display font-bold text-[clamp(2rem,4vw,2.6rem)] text-gold block">12.480</span>
          <span class="text-[13.5px] text-white/[0.78] mt-2 block">Ibu hamil didampingi</span>
        </div>
        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none text-center px-4 py-[30px] border border-dashed border-white/[0.28] rounded-[18px]">
          <span class="font-display font-bold text-[clamp(2rem,4vw,2.6rem)] text-gold block">34.900</span>
          <span class="text-[13.5px] text-white/[0.78] mt-2 block">Anak diimunisasi lengkap</span>
        </div>
        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none text-center px-4 py-[30px] border border-dashed border-white/[0.28] rounded-[18px]">
          <span class="font-display font-bold text-[clamp(2rem,4vw,2.6rem)] text-gold block">8.650</span>
          <span class="text-[13.5px] text-white/[0.78] mt-2 block">Kelambu anti-malaria dibagikan</span>
        </div>
        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none text-center px-4 py-[30px] border border-dashed border-white/[0.28] rounded-[18px]">
          <span class="font-display font-bold text-[clamp(2rem,4vw,2.6rem)] text-gold block">76</span>
          <span class="text-[13.5px] text-white/[0.78] mt-2 block">Desa wilayah dampingan</span>
        </div>
      </div>
    </div>
  </section>

  <!-- TESTIMONIALS -->
  <section class="py-16 sm:py-24" id="cerita">
    <div class="max-w-[1180px] mx-auto px-5 sm:px-8">
      <div class="max-w-[640px] mb-14 reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
        <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark">
          <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
          Cerita dari Lapangan
        </span>
        <h2 class="font-display font-semibold text-[clamp(1.9rem,3.2vw,2.6rem)] leading-[1.15] text-forest mt-3.5">Suara Mereka yang Kami Dampingi</h2>
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none bg-white rounded-[18px] py-[30px] px-7 border border-forest/15 relative">
          <svg class="w-[30px] h-[30px] text-gold mb-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M7 7C4.8 7 3 8.8 3 11c0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1C7.3 17 6 18.5 4 19v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4zm10 0c-2.2 0-4 1.8-4 4 0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1-.6 2.1-1.9 3.6-3.9 4.1v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4z"/></svg>
          <p class="font-display italic text-[17px] text-forest leading-[1.5] mb-5">Kader posyandu di kampung kami jadi lebih percaya diri mendampingi ibu hamil sejak ada pelatihan rutin dari yayasan.</p>
          <div class="flex items-center gap-3">
            <div class="w-[42px] h-[42px] rounded-full bg-mist flex items-center justify-center text-forest font-display font-bold shrink-0">SW</div>
            <div><div class="font-bold text-[14.5px] text-ink">Sri Wahyuni</div><div class="text-[13px] text-ink-soft">Kader Posyandu, Sikka · NTT</div></div>
          </div>
        </div>
        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none bg-white rounded-[18px] py-[30px] px-7 border border-forest/15 relative">
          <svg class="w-[30px] h-[30px] text-gold mb-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M7 7C4.8 7 3 8.8 3 11c0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1C7.3 17 6 18.5 4 19v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4zm10 0c-2.2 0-4 1.8-4 4 0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1-.6 2.1-1.9 3.6-3.9 4.1v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4z"/></svg>
          <p class="font-display italic text-[17px] text-forest leading-[1.5] mb-5">Anak saya sekarang lengkap imunisasinya. Petugas datang langsung ke dusun, kami tidak perlu jalan jauh lagi.</p>
          <div class="flex items-center gap-3">
            <div class="w-[42px] h-[42px] rounded-full bg-mist flex items-center justify-center text-forest font-display font-bold shrink-0">F</div>
            <div><div class="font-bold text-[14.5px] text-ink">Fatimah</div><div class="text-[13px] text-ink-soft">Warga, Waepana · Manggarai</div></div>
          </div>
        </div>
        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none bg-white rounded-[18px] py-[30px] px-7 border border-forest/15 relative">
          <svg class="w-[30px] h-[30px] text-gold mb-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M7 7C4.8 7 3 8.8 3 11c0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1C7.3 17 6 18.5 4 19v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4zm10 0c-2.2 0-4 1.8-4 4 0 2.2 1.8 4 4 4 .3 0 .6 0 .9-.1-.6 2.1-1.9 3.6-3.9 4.1v2c4-.5 7-3.3 7-7.5V11c0-2.2-1.8-4-4-4z"/></svg>
          <p class="font-display italic text-[17px] text-forest leading-[1.5] mb-5">Sejak dapat kelambu dan edukasi rutin, kasus malaria di dusun kami turun jauh dibanding tiga tahun lalu.</p>
          <div class="flex items-center gap-3">
            <div class="w-[42px] h-[42px] rounded-full bg-mist flex items-center justify-center text-forest font-display font-bold shrink-0">YB</div>
            <div><div class="font-bold text-[14.5px] text-ink">Yosef Bunga</div><div class="text-[13px] text-ink-soft">Kepala Dusun, Sumba Timur</div></div>
          </div>
        </div>
      </div>
      <p class="text-xs text-ink-soft mt-6">*Cerita di atas adalah contoh ilustratif — mudah diganti dengan testimoni asli dari penerima manfaat Anda.</p>
    </div>
  </section>

  <!-- DONATION CTA -->
  <section class="py-16 sm:py-24 bg-coral text-white relative overflow-hidden" id="donasi">
    <div class="absolute top-1/2 -right-20 -translate-y-1/2 opacity-[0.15] w-[520px] h-[520px]" aria-hidden="true">
      <svg viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
        <g class="stroke-white origin-[150px_150px] animate-spin-slower motion-reduce:animate-none" stroke-width="7" stroke-linecap="round">
          <line x1="208.0" y1="150.0" x2="288.0" y2="150.0" /><line x1="203.6" y1="172.2" x2="249.8" y2="191.3" />
          <line x1="191.0" y1="191.0" x2="247.6" y2="247.6" /><line x1="172.2" y1="203.6" x2="191.3" y2="249.8" />
          <line x1="150.0" y1="208.0" x2="150.0" y2="288.0" /><line x1="127.8" y1="203.6" x2="108.7" y2="249.8" />
          <line x1="109.0" y1="191.0" x2="52.4" y2="247.6" /><line x1="96.4" y1="172.2" x2="50.2" y2="191.3" />
          <line x1="92.0" y1="150.0" x2="12.0" y2="150.0" /><line x1="96.4" y1="127.8" x2="50.2" y2="108.7" />
          <line x1="109.0" y1="109.0" x2="52.4" y2="52.4" /><line x1="127.8" y1="96.4" x2="108.7" y2="50.2" />
          <line x1="150.0" y1="92.0" x2="150.0" y2="12.0" /><line x1="172.2" y1="96.4" x2="191.3" y2="50.2" />
          <line x1="191.0" y1="109.0" x2="247.6" y2="52.4" /><line x1="203.6" y1="127.8" x2="249.8" y2="108.7" />
        </g>
      </svg>
    </div>
    <div class="max-w-[1180px] mx-auto px-5 sm:px-8 grid grid-cols-1 lg:grid-cols-[1.2fr_0.8fr] gap-12 items-center relative z-[2]">
      <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
        <h2 class="font-display font-semibold text-[clamp(1.9rem,3.4vw,2.5rem)] leading-[1.15] text-white">Setiap Sumbangan Adalah Sinar Baru</h2>
        <p class="text-white/[0.88] text-[16.5px] mt-4 mb-7 max-w-[460px]">Rp150.000 dapat menyediakan satu kelambu anti-malaria. Rp500.000 dapat mendampingi satu ibu hamil selama satu bulan penuh. Bergabunglah dengan kami.</p>
        <div class="flex gap-4 flex-wrap">
          <a href="#kontak" class="inline-flex items-center justify-center gap-2 font-bold text-[15.5px] px-7 py-3.5 rounded-full border-2 border-transparent bg-white text-forest hover:-translate-y-0.5 hover:shadow-[0_14px_28px_-14px_rgba(0,0,0,0.35)] transition-all duration-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-forest-dark focus-visible:outline-offset-[3px]">Donasi via Transfer</a>
          <a href="#kontak" class="inline-flex items-center justify-center gap-2 font-bold text-[15.5px] px-7 py-3.5 rounded-full border-2 border-white/60 bg-transparent text-white hover:bg-white/10 hover:border-white transition-all duration-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-white focus-visible:outline-offset-[3px]">Jadi Relawan</a>
        </div>
      </div>
      <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none bg-white/10 border border-white/30 rounded-[18px] px-7 py-[26px] backdrop-blur-sm">
        <div class="text-xs tracking-[0.1em] uppercase text-white/75 mb-2">Transfer Bank</div>
        <div id="acctNumber" class="font-display text-[22px] font-semibold mb-1">BCA · 123 4567 890</div>
        <div class="text-sm text-white/85 mb-4">a.n. Yayasan Sinar Bhakti Husada</div>
        <button id="copyBtn" class="bg-white text-coral border-0 rounded-full px-[18px] py-[9px] font-bold text-[13.5px] cursor-pointer hover:bg-white/90 transition-colors">Salin Nomor Rekening</button>
      </div>
    </div>
  </section>

  <!-- CONTACT -->
  <section class="py-16 sm:py-24" id="kontak">
    <div class="max-w-[1180px] mx-auto px-5 sm:px-8">
      <div class="max-w-[640px] mb-14 reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
        <span class="inline-flex items-center gap-2.5 text-[13px] font-bold tracking-[0.16em] uppercase text-coral-dark">
          <svg viewBox="0 0 24 24" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2"/></svg>
          Hubungi Kami
        </span>
        <h2 class="font-display font-semibold text-[clamp(1.9rem,3.2vw,2.6rem)] leading-[1.15] text-forest mt-3.5">Mari Berbincang atau Berkunjung</h2>
        <p class="text-ink-soft text-[16.5px] mt-4">Untuk kerja sama program, kunjungan lapangan, atau pertanyaan seputar donasi.</p>
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-9 lg:gap-14">
        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none">
          <div class="flex gap-4 mb-[26px]">
            <div class="w-[46px] h-[46px] rounded-[14px] bg-white border border-forest/15 flex items-center justify-center shrink-0 text-forest">
              <svg viewBox="0 0 24 24" class="w-[21px] h-[21px]" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Z"/><path d="m22 6-10 7L2 6"/></svg>
            </div>
            <div><div class="font-bold text-[14.5px] text-forest mb-[3px]">Email</div><div class="text-[14.5px] text-ink-soft">kontak@sinarbhaktihusada.org</div></div>
          </div>
          <div class="flex gap-4 mb-[26px]">
            <div class="w-[46px] h-[46px] rounded-[14px] bg-white border border-forest/15 flex items-center justify-center shrink-0 text-forest">
              <svg viewBox="0 0 24 24" class="w-[21px] h-[21px]" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.9.6 2.8a2 2 0 0 1-.5 2.1L7.9 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.8.6a2 2 0 0 1 1.7 2Z"/></svg>
            </div>
            <div><div class="font-bold text-[14.5px] text-forest mb-[3px]">Telepon / WhatsApp</div><div class="text-[14.5px] text-ink-soft">+62 812-3456-7890</div></div>
          </div>
          <div class="flex gap-4 mb-[26px]">
            <div class="w-[46px] h-[46px] rounded-[14px] bg-white border border-forest/15 flex items-center justify-center shrink-0 text-forest">
              <svg viewBox="0 0 24 24" class="w-[21px] h-[21px]" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div><div class="font-bold text-[14.5px] text-forest mb-[3px]">Kantor Sekretariat</div><div class="text-[14.5px] text-ink-soft">Jl. Melati No. 21, Kupang, Nusa Tenggara Timur</div></div>
          </div>
        </div>
        <div class="reveal opacity-0 translate-y-6 transition-all duration-700 ease-out motion-reduce:transition-none rounded-[18px] overflow-hidden border border-forest/15 min-h-[280px] h-full bg-paper-dim flex items-center justify-center text-ink-soft text-sm">
          Peta lokasi kantor dapat ditambahkan di sini
        </div>
      </div>
    </div>
  </section>

</main>

<footer class="bg-forest-dark text-white/[0.72] pt-16 pb-7">
  <div class="max-w-[1180px] mx-auto px-5 sm:px-8">
    <div class="grid grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr] gap-10 mb-12">
      <div>
        <div class="flex items-center gap-2.5 mb-3.5">
          <svg viewBox="0 0 60 60" class="w-9 h-9" xmlns="http://www.w3.org/2000/svg">
            <circle cx="30" cy="30" r="29" class="fill-gold"/>
            <path d="M30 46C30 46 14 36.8 14 25.6C14 19.2 19 15 24 15C27 15 29 16.6 30 18.6C31 16.6 33 15 36 15C41 15 46 19.2 46 25.6C46 36.8 30 46 30 46Z" class="fill-forest"/>
            <rect x="26.5" y="21" width="7" height="17" rx="1.5" class="fill-white"/>
            <rect x="21.5" y="26" width="17" height="7" rx="1.5" class="fill-white"/>
          </svg>
          <span class="font-display font-bold text-white text-base">Sinar Bhakti Husada</span>
        </div>
        <p class="text-sm max-w-[280px]">Yayasan kesehatan yang mendampingi ibu, anak, dan komunitas di wilayah terpencil Indonesia sejak 2014.</p>
        <div class="flex gap-2.5 mt-4">
          <a href="#" aria-label="Instagram" class="w-[38px] h-[38px] rounded-full bg-white/[0.08] flex items-center justify-center hover:bg-coral transition-colors"><svg viewBox="0 0 24 24" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></svg></a>
          <a href="#" aria-label="Facebook" class="w-[38px] h-[38px] rounded-full bg-white/[0.08] flex items-center justify-center hover:bg-coral transition-colors"><svg viewBox="0 0 24 24" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3Z"/></svg></a>
          <a href="#" aria-label="YouTube" class="w-[38px] h-[38px] rounded-full bg-white/[0.08] flex items-center justify-center hover:bg-coral transition-colors"><svg viewBox="0 0 24 24" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3Z" fill="currentColor"/></svg></a>
        </div>
      </div>
      <div>
        <h4 class="text-white text-[13.5px] tracking-[0.08em] uppercase mb-[18px] font-bold">Program</h4>
        <ul class="list-none m-0 p-0">
          <li class="mb-3"><a href="#program" class="text-[14.5px] hover:text-gold">Kesehatan Ibu &amp; Anak</a></li>
          <li class="mb-3"><a href="#program" class="text-[14.5px] hover:text-gold">Imunisasi</a></li>
          <li class="mb-3"><a href="#program" class="text-[14.5px] hover:text-gold">Penanganan Malaria</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-white text-[13.5px] tracking-[0.08em] uppercase mb-[18px] font-bold">Yayasan</h4>
        <ul class="list-none m-0 p-0">
          <li class="mb-3"><a href="#tentang" class="text-[14.5px] hover:text-gold">Tentang Kami</a></li>
          <li class="mb-3"><a href="#dampak" class="text-[14.5px] hover:text-gold">Dampak</a></li>
          <li class="mb-3"><a href="#cerita" class="text-[14.5px] hover:text-gold">Cerita Lapangan</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-white text-[13.5px] tracking-[0.08em] uppercase mb-[18px] font-bold">Ambil Bagian</h4>
        <ul class="list-none m-0 p-0">
          <li class="mb-3"><a href="#donasi" class="text-[14.5px] hover:text-gold">Donasi</a></li>
          <li class="mb-3"><a href="#kontak" class="text-[14.5px] hover:text-gold">Jadi Relawan</a></li>
          <li class="mb-3"><a href="#kontak" class="text-[14.5px] hover:text-gold">Kerja Sama Program</a></li>
        </ul>
      </div>
    </div>
    <div class="border-t border-white/[0.12] pt-6 flex justify-between flex-wrap gap-3 text-[13px]">
      <span>© 2026 Yayasan Sinar Bhakti Husada. Seluruh hak cipta dilindungi.</span>
      <span>Dibuat dengan bhakti, untuk sinar yang terus menyala.</span>
    </div>
  </div>
</footer>

<script>
  // Mobile nav toggle
  const burger = document.getElementById('burgerBtn');
  const navLinks = document.getElementById('navLinks');
  burger.addEventListener('click', () => {
    navLinks.classList.toggle('translate-x-full');
    navLinks.classList.toggle('translate-x-0');
  });
  navLinks.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
    navLinks.classList.add('translate-x-full');
    navLinks.classList.remove('translate-x-0');
  }));

  // Scroll reveal
  const revealEls = document.querySelectorAll('.reveal');
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.remove('opacity-0', 'translate-y-6');
        e.target.classList.add('opacity-100', 'translate-y-0');
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.15 });
  revealEls.forEach(el => io.observe(el));

  // Copy account number
  const copyBtn = document.getElementById('copyBtn');
  copyBtn.addEventListener('click', () => {
    navigator.clipboard.writeText('1234567890').then(() => {
      const original = copyBtn.textContent;
      copyBtn.textContent = 'Tersalin ✓';
      setTimeout(() => copyBtn.textContent = original, 2000);
    });
  });
</script>

</body>
</html>
