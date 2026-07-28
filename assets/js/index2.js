(function () {
    'use strict';

    var toggle = document.querySelector('[data-menu-toggle]');
    var menu = document.querySelector('[data-menu]');

    if (!toggle || !menu) {
        return;
    }

    function setMenu(open) {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        menu.classList.toggle('is-open', open);
        document.body.classList.toggle('landing-menu-open', open);
    }

    toggle.addEventListener('click', function () {
        setMenu(toggle.getAttribute('aria-expanded') !== 'true');
    });

    menu.addEventListener('click', function (event) {
        if (event.target.closest('a')) {
            setMenu(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            setMenu(false);
            toggle.focus();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 960) {
            setMenu(false);
        }
    });
}());
