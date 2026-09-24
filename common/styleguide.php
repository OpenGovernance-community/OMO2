<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/translation_bundles.php';

if (!checklogin()) {
    die('Login requis');
}
$sourceLang = [
    'forms.title' => ['text' => 'Formulaires de référence', 'context' => 'Style guide section heading.'],
    'forms.help' => ['text' => 'Les formulaires d’indicateurs et de tâches récurrentes donnent le rythme : sections séparées, champs compacts et actions regroupées.', 'context' => 'Shared form design guidance.'],
    'forms.identity' => ['text' => 'Informations générales', 'context' => 'Example form section.'],
    'forms.settings' => ['text' => 'Paramètres', 'context' => 'Example form section.'],
    'forms.name' => ['text' => 'Nom', 'context' => 'Example text field label.'],
    'forms.category' => ['text' => 'Catégorie', 'context' => 'Example select field label.'],
    'forms.description' => ['text' => 'Description', 'context' => 'Example textarea label.'],
    'forms.label' => ['text' => 'Libellé', 'context' => 'Example editable select label.'],
    'forms.hint' => ['text' => 'Placez les explications complémentaires dans une aide contextuelle pour garder le formulaire lisible.', 'context' => 'Example contextual help.'],
];
$locale = translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr');
$lang = loadTranslationBundle('styleguide', $locale, $sourceLang);
$styleguideT = static fn(string $key): string => htmlspecialchars(t($key, [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Styleguide generique</title>
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/components.css') ?>">
    <script src="<?= commonAssetUrl('/common/assets/components.js') ?>" defer></script>
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/styleguide.css') ?>">
</head>
<body>
    <main class="generic-page-shell generic-stack generic-stack--roomy">
        <section class="generic-section generic-section--stack generic-section--roomy">
            <div class="generic-card-title generic-card-title--eyebrow">Reference partagee</div>
            <h1 class="generic-card-title generic-card-title--large">Styleguide des composants generiques</h1>
            <p class="generic-description generic-description--relaxed">
                Cette page montre les primitives communes definies dans <code>/common/assets/components.css</code>.
                L'objectif est de reutiliser d'abord ces objets avant d'ecrire de nouveaux styles locaux.
            </p>
            <div class="generic-action-row generic-action-row--start">
                <span class="generic-badge">generic-section</span>
                <span class="generic-badge">generic-soft-panel</span>
                <span class="generic-badge">generic-hero-panel</span>
                <span class="generic-badge">generic-title</span>
                <span class="generic-badge">generic-description</span>
                <span class="generic-badge">generic-action-button</span>
                <span class="generic-badge">generic-drag-handle</span>
                <span class="generic-badge">generic-form-control</span>
                <span class="generic-badge">generic-tabs</span>
                <span class="generic-badge">generic-accordion</span>
                <span class="generic-badge">generic-file-list</span>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-section--roomy">
            <div class="generic-card-title generic-card-title--eyebrow">Typographie</div>
            <div class="generic-form-grid styleguide-grid">
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <div class="generic-title generic-title--eyebrow">Eyebrow</div>
                    <div class="generic-title generic-title--small">Small</div>
                    <div class="generic-title generic-title--medium">Medium</div>
                    <div class="generic-title generic-title--big">Big</div>
                    <div class="generic-title generic-title--section">Section</div>
                    <div class="generic-title generic-title--large">Large</div>
                    <div class="generic-title generic-title--hero">Titre hero</div>
                    <div class="generic-title generic-title--card">Titre de carte ou fiche</div>
                    <div class="generic-title generic-title--compact">Titre de liste compacte</div>
                    <div class="generic-title generic-title--subsection">Sous-section</div>
                    <p class="generic-description">Description standard, secondaire et lisible.</p>
                    <p class="generic-description generic-description--card">Description de carte ou de fiche.</p>
                    <p class="generic-description generic-description--compact">Description de ligne compacte.</p>
                    <div>
                        <span class="generic-meta-label">Contexte</span>
                        <span class="generic-meta-value">Information secondaire</span>
                    </div>
                    <label class="generic-form-label">Libelle de formulaire</label>
                    <p class="generic-help-text">Texte d aide plus compact pour un formulaire.</p>
                </div>
                <pre class="styleguide-code">generic-title
generic-title--eyebrow
generic-title--small
generic-title--medium
generic-title--big
generic-title--section
generic-title--large
generic-title--hero
generic-title--card
generic-title--compact
generic-title--subsection
generic-description
generic-description--small
generic-description--card
generic-description--compact
generic-description--primary
generic-description--relaxed
generic-meta
generic-meta--compact
generic-meta-label
generic-meta-label--compact
generic-meta-value
generic-meta-value--compact
generic-form-label
generic-form-label--eyebrow
generic-help-text
generic-help-text--regular

generic-card-title reste un alias compatible.</pre>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-section--roomy">
            <div class="generic-card-title generic-card-title--eyebrow">Panneaux</div>
            <div class="generic-form-grid styleguide-grid">
                <div class="generic-section generic-section--stack generic-section--roomy">
                    <div class="generic-card-title generic-card-title--small">Section standard</div>
                    <div class="generic-description">Bloc principal pour un contenu de page ou une fiche.</div>
                </div>
                <div class="generic-section generic-section--alt generic-section--stack">
                    <div class="generic-card-title generic-card-title--small">Section alt</div>
                    <div class="generic-description">Version alternative avec surface secondaire.</div>
                </div>
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <div class="generic-card-title generic-card-title--small">Soft panel</div>
                    <div class="generic-description">Sous-bloc interieur ou zone de details.</div>
                </div>
                <label class="generic-choice-card">
                    <input type="radio" name="styleguide-choice" checked>
                    <span class="generic-stack">
                        <strong>Carte de choix</strong>
                        <span class="generic-description">Selection claire, tactile et reutilisable.</span>
                    </span>
                </label>
                <div class="generic-hero-panel accent styleguide-surface">
                    <div class="generic-stack">
                        <div class="generic-card-title generic-card-title--eyebrow">Hero accent</div>
                        <div class="generic-card-title generic-card-title--big">Panneau de mise en avant</div>
                    </div>
                </div>
            </div>
            <pre class="styleguide-code">generic-page-shell
generic-choice-card</pre>
        </section>

        <section class="generic-section generic-section--stack generic-section--roomy">
            <div class="generic-card-title generic-card-title--eyebrow">Boutons</div>
            <div class="generic-action-row generic-action-row--start">
                <button type="button" class="generic-action-button generic-action-button--main">Action principale</button>
                <button type="button" class="generic-action-button generic-action-button--secondary">Action secondaire</button>
                <button type="button" class="generic-action-button generic-action-button--danger">Action danger</button>
                <button type="button" class="generic-action-button generic-action-button--main" disabled>Etat desactive</button>
            </div>
            <pre class="styleguide-code">generic-action-button generic-action-button--main
generic-action-button generic-action-button--secondary
generic-action-button generic-action-button--danger</pre>
        </section>

        <section class="generic-section generic-section--stack generic-section--roomy">
            <div class="generic-card-title generic-card-title--eyebrow">Poignees</div>
            <div class="generic-form-grid styleguide-grid">
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <div class="generic-action-row generic-action-row--start">
                        <button type="button" class="generic-drag-handle" aria-label="Deplacer">::</button>
                        <button type="button" class="generic-drag-handle generic-drag-handle--stretch" aria-label="Deplacer">::</button>
                        <span class="generic-drag-handle generic-drag-handle--static">::</span>
                    </div>
                    <div class="generic-description">Utiliser cette primitive pour les listes reordonnables au lieu de recreer une poignee locale.</div>
                </div>
                <pre class="styleguide-code">button.generic-drag-handle
button.generic-drag-handle.generic-drag-handle--stretch
span.generic-drag-handle.generic-drag-handle--static</pre>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-section--roomy">
            <h2 id="forms" class="generic-card-title generic-card-title--medium"><?= $styleguideT('forms.title') ?></h2>
            <p class="generic-description"><?= $styleguideT('forms.help') ?></p>
            <div class="generic-form-grid styleguide-grid">
                <form class="generic-form-stack generic-form-stack--compact">
                    <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
                        <h3 class="generic-card-title generic-card-title--small"><?= $styleguideT('forms.identity') ?></h3>
                        <div class="generic-form-grid generic-form-grid--pair">
                            <label class="generic-form-field">
                                <span class="generic-form-label"><?= $styleguideT('forms.name') ?></span>
                                <input type="text" class="generic-form-control generic-form-control--compact" value="Exemple de saisie">
                            </label>
                            <label class="generic-form-field">
                                <span class="generic-form-label"><?= $styleguideT('forms.category') ?></span>
                                <select class="generic-form-control generic-form-control--compact">
                                    <option>Choix 1</option>
                                    <option>Choix 2</option>
                                </select>
                            </label>
                        </div>
                    </section>
                    <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided generic-form-section--compact">
                        <div class="generic-heading-with-help">
                            <h3 class="generic-card-title generic-card-title--small"><?= $styleguideT('forms.settings') ?></h3>
                            <details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>
                                <summary aria-label="<?= $styleguideT('forms.settings') ?>">?</summary>
                                <div class="generic-context-help__content"><?= $styleguideT('forms.hint') ?></div>
                            </details>
                        </div>
                        <label class="generic-form-field">
                            <span class="generic-form-label"><?= $styleguideT('forms.description') ?></span>
                            <textarea class="generic-form-control generic-form-control--compact" rows="4">Texte multi-lignes de demonstration.</textarea>
                        </label>
                        <label class="generic-form-field">
                            <span class="generic-form-label"><?= $styleguideT('forms.label') ?></span>
                            <div class="generic-editable-select" data-generic-editable-select>
                                <div class="generic-editable-select__control">
                                    <input
                                        type="text"
                                        class="generic-form-control generic-form-control--compact generic-editable-select__input"
                                        value="Introduction"
                                        placeholder="Saisir ou choisir"
                                        data-generic-editable-select-input
                                    >
                                    <button type="button" class="generic-editable-select__toggle" data-generic-editable-select-toggle aria-label="Afficher les options"></button>
                                </div>
                                <div class="generic-editable-select__panel" data-generic-editable-select-panel hidden>
                                    <button type="button" class="generic-editable-select__option" data-generic-editable-select-option="Accueil">Accueil</button>
                                    <button type="button" class="generic-editable-select__option" data-generic-editable-select-option="Introduction">Introduction</button>
                                    <button type="button" class="generic-editable-select__option" data-generic-editable-select-option="Prise en main">Prise en main</button>
                                    <div class="generic-editable-select__empty" data-generic-editable-select-empty hidden>Aucune valeur existante.</div>
                                </div>
                            </div>
                        </label>
                    </section>
                    <div class="generic-form-actions generic-form-actions--stack-mobile">
                        <button type="button" class="generic-action-button generic-action-button--secondary">Annuler</button>
                        <button type="button" class="generic-action-button generic-action-button--main">Enregistrer</button>
                    </div>
                </form>
                <pre class="styleguide-code">generic-drawer-content
generic-form-stack generic-form-stack--compact
generic-section generic-section--stack generic-form-section
generic-form-section--divided generic-form-section--compact
generic-form-section__heading
generic-form-section__copy
generic-form-grid
generic-form-field
generic-form-label
generic-form-actions generic-form-actions--stack-mobile
input.generic-form-control
select.generic-form-control
textarea.generic-form-control
div.generic-editable-select[data-generic-editable-select]

Modificateurs pour les champs :
generic-form-control--compact

Grilles : generic-form-grid--pair, --trio, --main-aside
Aide : generic-context-help generic-context-help--compact

Les variables de configuration utilisent --param-*.
Exemple : --param-form-grid-min: 280px.
Ne pas redéfinir les variables internes --generic-*.

JS disponible apres injection dynamique:
window.initGenericEditableSelects(container);</pre>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-section--roomy" id="fieldsets">
            <h2 class="generic-card-title generic-card-title--medium">Groupes de champs (fieldset)</h2>
            <p class="generic-description">Dans un panneau ou un tiroir, utilisez un fieldset sans cadre imbriqué : sa légende forme un titre avec un séparateur. Le corps conserve les espacements des formulaires OMO.</p>
            <div class="generic-form-grid styleguide-grid">
                <div class="generic-form-stack">
                    <fieldset class="generic-fieldset">
                        <legend class="generic-card-title generic-card-title--medium">Colonnes visibles</legend>
                        <div class="generic-fieldset__body">
                            <p class="generic-help-text">Cochez une colonne et, si besoin, personnalisez son nom.</p>
                            <div class="generic-setting-row">
                                <label class="generic-checkbox"><input type="checkbox" checked> <span>Prêt</span></label>
                                <input class="generic-form-control" type="text" placeholder="Prêt" value="À faire" aria-label="Nom de la colonne Prêt">
                            </div>
                            <div class="generic-setting-row">
                                <label class="generic-checkbox"><input type="checkbox" checked> <span>En cours</span></label>
                                <input class="generic-form-control" type="text" placeholder="En cours" aria-label="Nom de la colonne En cours">
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="generic-fieldset">
                        <legend class="generic-card-title generic-card-title--medium">Options</legend>
                        <div class="generic-fieldset__body">
                            <label class="generic-checkbox"><input type="checkbox" checked> <span>Afficher les priorités</span></label>
                            <label class="generic-checkbox"><input type="checkbox"> <span>Afficher les tailles</span></label>
                        </div>
                    </fieldset>
                </div>
                <pre class="styleguide-code">&lt;fieldset class="generic-fieldset"&gt;
  &lt;legend class="generic-card-title generic-card-title--medium"&gt;
    Titre du groupe
  &lt;/legend&gt;
  &lt;div class="generic-fieldset__body"&gt;
    &lt;div class="generic-setting-row"&gt;
      &lt;label class="generic-checkbox"&gt;...&lt;/label&gt;
      &lt;input class="generic-form-control"&gt;
    &lt;/div&gt;
  &lt;/div&gt;
&lt;/fieldset&gt;

generic-fieldset : groupe sémantique, sans cadre
legend : titre et séparateur pleine largeur
generic-fieldset__body : contenu espacé verticalement
generic-setting-row : libellé et contrôle alignés

Utilisez generic-form-grid pour juxtaposer les groupes.
Les lignes passent sur une colonne sous 360 px.
Associez un label ou aria-label à chaque champ.</pre>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-section--roomy">
            <div class="generic-card-title generic-card-title--eyebrow">Aide contextuelle</div>
            <div class="generic-form-grid styleguide-grid">
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <div class="generic-heading-with-help">
                        <span class="generic-card-title generic-card-title--small">Titre du champ</span>
                        <details class="generic-context-help">
                            <summary aria-label="Afficher l aide">?</summary>
                            <div class="generic-context-help__content">Cette aide reste automatiquement dans la fenetre et peut s ouvrir au-dessus si la place manque en dessous.</div>
                        </details>
                    </div>
                </div>
                <pre class="styleguide-code">&lt;div class="generic-heading-with-help"&gt;
    &lt;span class="generic-card-title generic-card-title--small"&gt;Titre&lt;/span&gt;
    &lt;details class="generic-context-help"&gt;
        &lt;summary aria-label="Afficher l aide"&gt;?&lt;/summary&gt;
        &lt;div class="generic-context-help__content"&gt;Texte d aide&lt;/div&gt;
    &lt;/details&gt;
&lt;/div&gt;

Apres injection dynamique facultatif:
window.initGenericContextHelps(container);</pre>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-section--roomy">
            <div class="generic-card-title generic-card-title--eyebrow">Onglets</div>
            <div class="generic-form-grid styleguide-grid">
                <div class="generic-stack">
                    <div class="generic-tabs" data-generic-tabs>
                        <div class="generic-tabs__list" aria-label="Exemple d onglets">
                            <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="styleguide-tab-overview">Apercu</button>
                            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="styleguide-tab-form">Formulaire</button>
                            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="styleguide-tab-notes">Notes</button>
                        </div>
                        <div class="generic-tabs__panels">
                            <div id="styleguide-tab-overview" class="generic-tabs__panel generic-stack" data-generic-tab-panel>
                                <div class="generic-card-title generic-card-title--small">Container libre</div>
                                <div class="generic-description">Le systeme se contente d afficher le bon bloc et de masquer les autres.</div>
                                <div class="generic-action-row generic-action-row--start">
                                    <span class="generic-badge">JS minimal</span>
                                    <span class="generic-badge">sans jQuery</span>
                                </div>
                            </div>
                            <div id="styleguide-tab-form" class="generic-tabs__panel generic-stack" data-generic-tab-panel hidden>
                                <label class="generic-form-field">
                                    <span class="generic-form-label">Champ dans un onglet</span>
                                    <input type="text" class="generic-form-control" value="Le contenu peut etre interactif">
                                </label>
                            </div>
                            <div id="styleguide-tab-notes" class="generic-tabs__panel generic-stack" data-generic-tab-panel hidden>
                                <div class="generic-card-title generic-card-title--small">Usage recommande</div>
                                <div class="generic-description">Conserver le style dans le CSS partage et utiliser seulement des IDs de panneaux cote HTML.</div>
                                <div class="generic-description">Si un bloc est injecte apres un fetch, les clics sont maintenant captes par delegation. En cas de besoin, on peut aussi appeler <code>window.initGenericTabs(container)</code>.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <pre class="styleguide-code">&lt;?= commonStylesheetTags('/common/assets/components.css') ?&gt;
&lt;script src="<?= commonAssetUrl('/common/assets/components.js') ?>" defer&gt;&lt;/script&gt;

&lt;div class="generic-tabs" data-generic-tabs&gt;
    &lt;div class="generic-tabs__list"&gt;
        &lt;button class="generic-tabs__tab is-active"
            data-generic-tab
            data-generic-tab-target="panel-a"&gt;A&lt;/button&gt;
        &lt;button class="generic-tabs__tab"
            data-generic-tab
            data-generic-tab-target="panel-b"&gt;B&lt;/button&gt;
    &lt;/div&gt;
    &lt;div class="generic-tabs__panels"&gt;
        &lt;div id="panel-a" class="generic-tabs__panel" data-generic-tab-panel&gt;...&lt;/div&gt;
        &lt;div id="panel-b" class="generic-tabs__panel" data-generic-tab-panel hidden&gt;...&lt;/div&gt;
    &lt;/div&gt;
&lt;/div&gt;

Apres injection dynamique facultatif:
window.initGenericTabs(container);</pre>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-section--roomy">
            <div class="generic-card-title generic-card-title--eyebrow">Accordion</div>
            <div class="generic-stack">
                <div class="generic-accordion generic-accordion--card generic-accordion--collapsible is-collapsed" data-generic-accordion>
                    <div class="generic-accordion__header">
                        <div class="generic-card-title generic-card-title--small">Accordion simple</div>
                        <button type="button" class="generic-accordion__toggle" data-generic-accordion-toggle aria-label="Ouvrir ou fermer">&#9662;</button>
                    </div>
                    <div class="generic-accordion__content">
                        <div class="generic-description">Le header, la carte et le comportement pliable viennent des classes generiques.</div>
                    </div>
                </div>

                <div class="generic-accordion generic-accordion--card generic-section--stack">
                    <div class="generic-accordion__header">
                        <div class="generic-card-title generic-card-title--small">Accordion ouvert</div>
                        <span class="generic-accordion__toggle" aria-hidden="true">&#9662;</span>
                    </div>
                    <div class="generic-accordion__content">
                        <div class="generic-soft-panel generic-soft-panel--stack">
                            <div class="generic-card-title generic-card-title--eyebrow">Sous-contenu</div>
                            <div class="generic-description">Un accordion peut aussi contenir d'autres primitives partagees.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-section--roomy">
            <div class="generic-card-title generic-card-title--eyebrow">File List</div>
            <div class="generic-form-grid styleguide-grid">
                <div class="styleguide-file-list-demo generic-soft-panel">
                    <div class="generic-file-list generic-file-list--structured generic-file-list--stacked-sticky" data-generic-file-list>
                        <section class="generic-file-list__group">
                            <h3 class="generic-card-title generic-card-title--small generic-file-list__group-title">Aujourd hui</h3>
                            <div class="generic-file-list__table">
                                <div class="generic-file-list__header">
                                    <div class="generic-file-list__header-cell">Nom</div>
                                    <div class="generic-file-list__header-cell">Type</div>
                                    <div class="generic-file-list__header-cell">Tags</div>
                                    <div class="generic-file-list__header-cell">Modifie le</div>
                                </div>

                                <article class="generic-file-list__item-shell generic-file-list__item-shell--folder generic-file-list__item-shell--with-menu">
                                    <div class="generic-accordion generic-accordion--collapsible generic-file-list__folder" data-generic-accordion>
                                        <div class="generic-accordion__header generic-file-list__folder-header">
                                            <button type="button" class="generic-file-list__folder-toggle" data-generic-accordion-toggle>
                                                <div class="generic-file-list__row">
                                                    <div class="generic-file-list__cell generic-file-list__cell--name">
                                                        <div class="generic-file-list__name-main">
                                                            <span class="generic-file-list__icon-box"><span class="generic-file-list__icon-symbol">D</span></span>
                                                            <div class="generic-file-list__title-block">
                                                                <div class="generic-file-list__title-row">
                                                                    <strong class="generic-file-list__title">Mon dossier</strong>
                                                                    <span class="generic-file-list__count">3 elements</span>
                                                                </div>
                                                                <div class="generic-file-list__meta-line">Organisation &gt; Equipe produit</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="generic-file-list__cell" data-label="Type">
                                                        <span class="generic-file-list__type">Dossier</span>
                                                    </div>
                                                    <div class="generic-file-list__cell" data-label="Tags">
                                                        <div class="generic-file-list__tag-list">
                                                            <span class="generic-file-list__tag">#pilotage</span>
                                                        </div>
                                                    </div>
                                                    <div class="generic-file-list__cell generic-file-list__cell--date" data-label="Modifie le">5 juin 2026</div>
                                                </div>
                                                <span class="generic-accordion__toggle generic-file-list__folder-chevron" aria-hidden="true">&#9662;</span>
                                            </button>
                                            <div class="generic-file-list__menu">
                                                <button type="button" class="generic-file-list__menu-toggle">...</button>
                                            </div>
                                        </div>
                                        <div class="generic-accordion__content generic-file-list__folder-content">
                                            <div class="generic-file-list__children">
                                                <article class="generic-file-list__item-shell generic-file-list__item-shell--with-menu">
                                                    <div class="generic-file-list__row">
                                                        <div class="generic-file-list__cell generic-file-list__cell--name">
                                                            <div class="generic-file-list__name-main">
                                                                <span class="generic-file-list__icon-box"><span class="generic-file-list__icon-symbol">F</span></span>
                                                                <div class="generic-file-list__title-block">
                                                                    <div class="generic-file-list__title-row">
                                                                        <strong class="generic-file-list__title">Bilan annuel</strong>
                                                                    </div>
                                                                    <div class="generic-file-list__meta-line">Organisation &gt; Equipe produit</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="generic-file-list__cell" data-label="Type">
                                                            <span class="generic-file-list__type">Document</span>
                                                        </div>
                                                        <div class="generic-file-list__cell" data-label="Tags">
                                                            <div class="generic-file-list__tag-list">
                                                                <span class="generic-file-list__tag">#bilan</span>
                                                                <span class="generic-file-list__tag">#annuel</span>
                                                            </div>
                                                        </div>
                                                        <div class="generic-file-list__cell generic-file-list__cell--date" data-label="Modifie le">4 juin 2026</div>
                                                    </div>
                                                    <div class="generic-file-list__menu">
                                                        <button type="button" class="generic-file-list__menu-toggle">...</button>
                                                    </div>
                                                </article>

                                                <article class="generic-file-list__item-shell generic-file-list__item-shell--with-menu">
                                                    <div class="generic-file-list__row">
                                                        <div class="generic-file-list__cell generic-file-list__cell--name">
                                                            <div class="generic-file-list__name-main">
                                                                <span class="generic-file-list__icon-box"><span class="generic-file-list__icon-symbol">F</span></span>
                                                                <div class="generic-file-list__title-block">
                                                                    <div class="generic-file-list__title-row">
                                                                        <strong class="generic-file-list__title">Processus budget</strong>
                                                                    </div>
                                                                    <div class="generic-file-list__meta-line">Organisation &gt; Equipe produit</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="generic-file-list__cell" data-label="Type">
                                                            <span class="generic-file-list__type">Document</span>
                                                        </div>
                                                        <div class="generic-file-list__cell" data-label="Tags">
                                                            <div class="generic-file-list__tag-list">
                                                                <span class="generic-file-list__tag">#budget</span>
                                                            </div>
                                                        </div>
                                                        <div class="generic-file-list__cell generic-file-list__cell--date" data-label="Modifie le">3 juin 2026</div>
                                                    </div>
                                                    <div class="generic-file-list__menu">
                                                        <button type="button" class="generic-file-list__menu-toggle">...</button>
                                                    </div>
                                                </article>
                                            </div>
                                        </div>
                                    </div>
                                </article>

                                <article class="generic-file-list__item-shell generic-file-list__item-shell--with-menu">
                                    <div class="generic-file-list__row">
                                        <div class="generic-file-list__cell generic-file-list__cell--name">
                                            <div class="generic-file-list__name-main">
                                                <span class="generic-file-list__icon-box"><span class="generic-file-list__icon-symbol">F</span></span>
                                                <div class="generic-file-list__title-block">
                                                    <div class="generic-file-list__title-row">
                                                        <strong class="generic-file-list__title">Referentiel gouvernance</strong>
                                                    </div>
                                                    <div class="generic-file-list__meta-line">Organisation &gt; Conseil</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="generic-file-list__cell" data-label="Type">
                                            <span class="generic-file-list__type">Document</span>
                                        </div>
                                        <div class="generic-file-list__cell" data-label="Tags">
                                            <div class="generic-file-list__tag-list">
                                                <span class="generic-file-list__tag">#gouvernance</span>
                                                <span class="generic-file-list__tag">#process</span>
                                            </div>
                                        </div>
                                        <div class="generic-file-list__cell generic-file-list__cell--date" data-label="Modifie le">2 juin 2026</div>
                                    </div>
                                    <div class="generic-file-list__menu">
                                        <button type="button" class="generic-file-list__menu-toggle">...</button>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <section class="generic-file-list__group">
                            <h3 class="generic-card-title generic-card-title--small generic-file-list__group-title">Cette semaine</h3>
                            <div class="generic-file-list__table">
                                <div class="generic-file-list__header">
                                    <div class="generic-file-list__header-cell">Nom</div>
                                    <div class="generic-file-list__header-cell">Type</div>
                                    <div class="generic-file-list__header-cell">Tags</div>
                                    <div class="generic-file-list__header-cell">Modifie le</div>
                                </div>

                                <article class="generic-file-list__item-shell generic-file-list__item-shell--with-menu">
                                    <div class="generic-file-list__row">
                                        <div class="generic-file-list__cell generic-file-list__cell--name">
                                            <div class="generic-file-list__name-main">
                                                <span class="generic-file-list__icon-box"><span class="generic-file-list__icon-symbol">F</span></span>
                                                <div class="generic-file-list__title-block">
                                                    <div class="generic-file-list__title-row">
                                                        <strong class="generic-file-list__title">Compte rendu retro</strong>
                                                    </div>
                                                    <div class="generic-file-list__meta-line">Organisation &gt; Equipe produit</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="generic-file-list__cell" data-label="Type">
                                            <span class="generic-file-list__type">Document</span>
                                        </div>
                                        <div class="generic-file-list__cell" data-label="Tags">
                                            <div class="generic-file-list__tag-list">
                                                <span class="generic-file-list__tag">#retro</span>
                                            </div>
                                        </div>
                                        <div class="generic-file-list__cell generic-file-list__cell--date" data-label="Modifie le">1 juin 2026</div>
                                    </div>
                                    <div class="generic-file-list__menu">
                                        <button type="button" class="generic-file-list__menu-toggle">...</button>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </div>
                </div>
                <pre class="styleguide-code">&lt;div class="generic-file-list generic-file-list--structured generic-file-list--stacked-sticky"
    data-generic-file-list&gt;
    &lt;section class="generic-file-list__group"&gt;
        &lt;h3 class="generic-file-list__group-title"&gt;Aujourd hui&lt;/h3&gt;
        &lt;div class="generic-file-list__table"&gt;
            &lt;div class="generic-file-list__header"&gt;...&lt;/div&gt;
            &lt;article class="generic-file-list__item-shell"&gt;
                &lt;div class="generic-file-list__row"&gt;...&lt;/div&gt;
            &lt;/article&gt;
            &lt;article class="generic-file-list__item-shell generic-file-list__item-shell--folder"&gt;
                &lt;div class="generic-accordion generic-accordion--collapsible generic-file-list__folder"
                    data-generic-accordion&gt;
                    &lt;div class="generic-file-list__folder-header"&gt;...&lt;/div&gt;
                    &lt;div class="generic-file-list__folder-content"&gt;...&lt;/div&gt;
                &lt;/div&gt;
            &lt;/article&gt;
        &lt;/div&gt;
    &lt;/section&gt;
&lt;/div&gt;

Init auto:
data-generic-file-list

Resync manuel si besoin:
window.initGenericFileLists(container)
window.syncGenericFileLists(container)</pre>
            </div>
        </section>

        <section class="generic-section generic-section--stack generic-section--roomy">
            <div class="generic-card-title generic-card-title--eyebrow">Regle de travail</div>
            <div class="generic-stack generic-stack--compact">
                <div class="generic-description">Quand une page combine deja bordure, rayon, surface, spacing et typo avec les memes tokens, il faut d'abord se demander si l'objet existe deja ici.</div>
                <div class="generic-description">Si la reponse est non mais que le motif revient a plusieurs endroits, il vaut mieux etendre la bibliotheque generique que recopier le CSS.</div>
            </div>
        </section>
    </main>

</body>
</html>
