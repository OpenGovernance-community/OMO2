<?php
require_once dirname(__DIR__, 2) . '/common/topbar.php';
?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<?php
commonRenderTopbar([
    'appKey' => 'lms',
    'appLabel' => 'LMS',
    'organization' => isset($org) ? $org : null,
    'logoutReturnTo' => '/lms/',
    'search' => [
        'enabled' => true,
        'placeholder' => 'Rechercher un parcours ou une mission',
        'buttonLabel' => 'Recherche',
        'callback' => 'lmsHandleTopbarSearch',
    ],
    'profile' => [
        'buttonLabel' => 'Profil',
        'editTitle' => 'Votre profil',
        'editUrl' => '/popup/profil.php',
        'editMode' => 'fetch',
    ],
    'helpLabel' => 'Aide',
    'helpItems' => [
        [
            'key' => 'faq',
            'label' => 'FAQ',
            'description' => 'Accès aux questions les plus courantes, avec moteur de recherche pour trouver facilement la réponse à ses questions.',
            'title' => 'FAQ LMS',
            'html' => '<div class="common-help-list"><div class="common-help-card"><h4>FAQ</h4><p>Cette zone pourra accueillir les questions fréquentes du LMS. Pour l’instant, elle sert de point d’entrée commun.</p></div></div>',
        ],
        [
            'key' => 'tour',
            'label' => 'Visite guidée',
            'description' => 'Tour des fonctions visibles à l’écran avec explication pour chaque bouton et chaque possibilité.',
            'title' => 'Visite guidée',
            'html' => '<div class="common-help-list"><div class="common-help-card"><h4>Visite guidée</h4><p>La visite guidée du LMS pourra être branchée ici application par application.</p></div></div>',
        ],
        [
            'key' => 'tutorials',
            'label' => 'Tutoriels',
            'description' => 'Des formations ciblées pour monter en compétences dans l’utilisation du logiciel.',
            'title' => 'Tutoriels',
            'html' => '<div class="common-help-list"><div class="common-help-card"><h4>Tutoriels</h4><p>Cette section pourra ensuite lister des tutoriels vidéo, des guides pas à pas ou des supports de formation.</p></div></div>',
        ],
    ],
]);
?>
<script>
function lmsHandleTopbarSearch(query) {
    const normalized = (query || '').trim().toLowerCase();
    const cards = document.querySelectorAll('.card');

    cards.forEach(card => {
        const text = (card.textContent || '').toLowerCase();
        card.style.display = normalized === '' || text.indexOf(normalized) !== -1 ? '' : 'none';
    });

    document.querySelectorAll('.branch').forEach(branch => {
        const visibleCards = Array.from(branch.querySelectorAll('.card')).filter(card => card.style.display !== 'none');
        branch.style.display = visibleCards.length > 0 || normalized === '' ? '' : 'none';
    });
}
</script>
