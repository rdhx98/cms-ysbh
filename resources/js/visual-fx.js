// OG f(n)
// window.initScrollReveal = function() {
//     const revealEls = document.querySelectorAll('.reveal');
//     if (revealEls.length === 0) return;

//     revealEls.forEach(el => {
//         const rect = el.getBoundingClientRect();
//         if (rect.top > window.innerHeight) {
//             el.classList.remove('is-revealed');
//         }
//     });

//     const io = new IntersectionObserver((entries, observer) => {
//         entries.forEach(e => {
//             if (e.isIntersecting) {
//                 e.target.classList.add('is-revealed');
//                 observer.unobserve(e.target);
//             }
//         });
//     }, { threshold: 0.15 });

//     revealEls.forEach(el => io.observe(el));
// };

// fix
window.initScrollReveal = function () {
    const els = document.querySelectorAll('.reveal:not(.is-revealed)');
    if (!els.length) return;

    const io = new IntersectionObserver((entries, observer) => {
        entries.forEach((e) => {
            // top < 0: sudah terlewati (mis. loncat lewat daftar isi), langsung ditampilkan
            if (e.isIntersecting || e.boundingClientRect.top < 0) {
                e.target.classList.add('is-revealed');
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0, rootMargin: '0px 0px -12% 0px' });

    els.forEach((el) => io.observe(el));
};

document.addEventListener('livewire:initialized', () => {
    // 1. Jalankan saat halaman pertama kali dimuat
    initScrollReveal();

    // 2. Cegat siklus pembaruan komponen Livewire
    Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
        // success() akan dieksekusi SETELAH Livewire menerima respons dari server
        // dan selesai melakukan 'morphing' (memperbarui) DOM di browser
        succeed(({ snapshot, effect }) => {
            // Beri sedikit waktu (nextTick) agar browser selesai menggambar elemen baru
            queueMicrotask(() => {
                initScrollReveal();
            });
        });
    });
});