(() => {
    const header = document.querySelector('[data-omo2-header]');
    const menuButton = document.querySelector('[data-omo2-menu-button]');
    const navigation = document.querySelector('[data-omo2-navigation]');
    const menuLabel = document.querySelector('[data-omo2-menu-label]');

    const setMenu = (open) => {
        if (!menuButton || !navigation) return;
        menuButton.setAttribute('aria-expanded', String(open));
        navigation.classList.toggle('is-open', open);
        document.body.classList.toggle('omo2-menu-open', open);
        if (menuLabel) menuLabel.textContent = open ? menuButton.dataset.labelClose : menuButton.dataset.labelOpen;
    };

    if (menuButton) {
        menuButton.addEventListener('click', () => setMenu(menuButton.getAttribute('aria-expanded') !== 'true'));
    }

    if (navigation) {
        navigation.addEventListener('click', (event) => {
            if (event.target.closest('a')) setMenu(false);
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setMenu(false);
    });

    const updateHeader = () => header?.classList.toggle('is-scrolled', window.scrollY > 20);
    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });

    document.querySelectorAll('[data-video-url]').forEach((button) => {
        button.addEventListener('click', () => {
            const url = button.dataset.videoUrl;
            if (!url || button.dataset.loaded === 'true') return;
            const iframe = document.createElement('iframe');
            iframe.src = `${url}${url.includes('?') ? '&' : '?'}autoplay=1`;
            iframe.title = button.dataset.videoTitle;
            iframe.allow = 'autoplay; fullscreen; picture-in-picture';
            iframe.allowFullscreen = true;
            iframe.loading = 'lazy';
            button.dataset.loaded = 'true';
            button.replaceWith(iframe);
        });
    });

    document.querySelectorAll('[data-omo2-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('.omo2-product-carousel__track');
        const slides = [...carousel.querySelectorAll('[data-carousel-slide]')];
        const dots = [...carousel.querySelectorAll('[data-carousel-dot]')];
        const previous = carousel.querySelector('[data-carousel-prev]');
        const next = carousel.querySelector('[data-carousel-next]');
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        let activeIndex = 0;
        let timer;

        if (!track || slides.length < 2 || !previous || !next) return;

        const show = (index) => {
            activeIndex = (index + slides.length) % slides.length;
            track.style.transform = `translateX(-${activeIndex * 100}%)`;
            slides.forEach((slide, slideIndex) => slide.setAttribute('aria-hidden', String(slideIndex !== activeIndex)));
            dots.forEach((dot, dotIndex) => {
                const isActive = dotIndex === activeIndex;
                dot.classList.toggle('is-active', isActive);
                if (isActive) dot.setAttribute('aria-current', 'true');
                else dot.removeAttribute('aria-current');
            });
        };

        const stop = () => window.clearInterval(timer);
        const start = () => {
            stop();
            if (!reducedMotion.matches) timer = window.setInterval(() => show(activeIndex + 1), 6500);
        };

        carousel.classList.add('is-ready');
        show(0);
        previous.addEventListener('click', () => { show(activeIndex - 1); start(); });
        next.addEventListener('click', () => { show(activeIndex + 1); start(); });
        dots.forEach((dot, index) => dot.addEventListener('click', () => { show(index); start(); }));
        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);
        carousel.addEventListener('focusin', stop);
        carousel.addEventListener('focusout', (event) => {
            if (!carousel.contains(event.relatedTarget)) start();
        });
        document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());
        reducedMotion.addEventListener('change', start);
        start();
    });
})();
