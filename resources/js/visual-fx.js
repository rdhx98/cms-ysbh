// function initScrollReveal() {
//     const revealEls = document.querySelectorAll('.reveal');
//     if (revealEls.length === 0) return;

//     revealEls.forEach(el => {
//         const rect = el.getBoundingClientRect();
//         if (rect.top > window.innerHeight) {
//             el.classList.remove('is-revealed'); // Hapus penanda jika ada di luar layar bawah
//         }
//     });

//     const io = new IntersectionObserver((entries, observer) => {
//         entries.forEach(e => {
//             if (e.isIntersecting) {
//                 e.target.classList.add('is-revealed'); // 🌟 JS hanya bertugas menambah ini
//                 observer.unobserve(e.target);
//             }
//         });
//     }, { threshold: 0.15 });

//     revealEls.forEach(el => io.observe(el));
// }

window.initScrollReveal = function() {
    const revealEls = document.querySelectorAll('.reveal');
    if (revealEls.length === 0) return;

    revealEls.forEach(el => {
        const rect = el.getBoundingClientRect();
        if (rect.top > window.innerHeight) {
            el.classList.remove('is-revealed');
        }
    });

    const io = new IntersectionObserver((entries, observer) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('is-revealed');
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.15 });

    revealEls.forEach(el => io.observe(el));
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