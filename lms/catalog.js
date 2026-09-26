
const lmsIndexViewer = {
    userId: window.publicLmsCatalogConfig.userId,
    organizationId: window.publicLmsCatalogConfig.organizationId,
    isEmbedded: window.publicLmsCatalogConfig.isEmbedded
};
const lmsParcoursBasePath = window.publicLmsCatalogConfig.lmsParcoursBasePath;

function getAnonymousProgressKey(parcoursId) {
    return `lms_progress_${lmsIndexViewer.organizationId}_${parcoursId}`;
}

function getAnonymousDoneMissionIds(parcoursId) {
    try {
        const rawValue = localStorage.getItem(getAnonymousProgressKey(parcoursId));
        if (!rawValue) {
            return [];
        }

        const parsed = JSON.parse(rawValue);
        const missions = parsed && parsed.missions && typeof parsed.missions === 'object'
            ? Object.keys(parsed.missions)
            : [];

        return missions
            .map((value) => Number(value))
            .filter((value) => Number.isInteger(value) && value > 0);
    } catch (error) {
        return [];
    }
}

function renderProgressCircle(el, percent) {
    const radius = 26;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (percent / 100) * circumference;

    el.innerHTML = `
        <svg width="60" height="60">
            <circle class="bg" cx="30" cy="30" r="${radius}" />
            <circle class="progress" cx="30" cy="30" r="${radius}" stroke-dasharray="${circumference}" stroke-dashoffset="${offset}" />
        </svg>
        <div class="label">${percent}%</div>
    `;
}

function resolveCardPercent(card, fallbackPercent) {
    const usesLocalProgress = card.getAttribute('data-local-progress') === '1';
    if (!usesLocalProgress) {
        return fallbackPercent;
    }

    const parcoursId = Number(card.getAttribute('data-parcours-id') || 0);
    const total = Number(card.getAttribute('data-total-missions') || 0);
    if (parcoursId <= 0 || total <= 0) {
        return fallbackPercent;
    }

    const done = getAnonymousDoneMissionIds(parcoursId).length;
    return Math.max(0, Math.min(100, Math.round((done / total) * 100)));
}

document.querySelectorAll('.progress-circle').forEach((el) => {
    const card = el.closest('[data-parcours-card="1"]');
    const percent = card ? resolveCardPercent(card, Number(el.getAttribute('data-percent') || 0)) : Number(el.getAttribute('data-percent') || 0);
    renderProgressCircle(el, percent);
});

function updateParcoursSectionsByProgress() {
    const pendingGrid = document.getElementById('lms-parcours-pending-grid');
    const completedGrid = document.getElementById('lms-parcours-completed-grid');
    const completedSection = document.getElementById('lms-parcours-section-completed');

    if (!pendingGrid || !completedGrid || !completedSection) {
        return;
    }

    const parcoursCards = Array.from(document.querySelectorAll('[data-parcours-card="1"]'));
    parcoursCards.forEach((card) => {
        if (card.getAttribute('data-is-pack') === '1') {
            return;
        }

        const progressElement = card.querySelector('.progress-circle');
        const percent = resolveCardPercent(card, progressElement ? Number(progressElement.getAttribute('data-percent') || 0) : 0);
        const targetGrid = percent >= 100 ? completedGrid : pendingGrid;

        if (card.parentElement !== targetGrid) {
            targetGrid.appendChild(card);
        }
    });

    completedSection.hidden = completedGrid.querySelector('[data-parcours-card="1"]') === null;
}

updateParcoursSectionsByProgress();

function goToParcours(id) {
    const targetUrl = new URL(lmsParcoursBasePath, window.location.origin);
    targetUrl.searchParams.set('idp', String(id));
    if (lmsIndexViewer.isEmbedded) {
        targetUrl.searchParams.set('embed', '1');
    }
    window.location.href = targetUrl.pathname + targetUrl.search + targetUrl.hash;
}

