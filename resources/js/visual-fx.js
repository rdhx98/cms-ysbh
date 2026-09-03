function initScrollReveal() {
    const revealEls = document.querySelectorAll('.reveal');
    if (revealEls.length === 0) return;

    revealEls.forEach(el => {
        const rect = el.getBoundingClientRect();
        if (rect.top > window.innerHeight) {
            el.classList.remove('is-revealed'); // Hapus penanda jika ada di luar layar bawah
        }
    });

    const io = new IntersectionObserver((entries, observer) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('is-revealed'); // 🌟 JS hanya bertugas menambah ini
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.15 });

    revealEls.forEach(el => io.observe(el));
}