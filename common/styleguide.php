<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once __DIR__ . '/auth.php';

if (!checklogin()) {
    die('Login requis');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Styleguide generique</title>
    <link rel="stylesheet" href="/common/assets/components.css">
    <script src="/common/assets/components.js" defer></script>
    <style>
        :root {
            color-scheme: light;
        }

        body {
            margin: 0;
            padding: 24px;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, 0.08), transparent 28%),
                linear-gradient(180deg, #f8fafc, #eef2f7);
            color: var(--color-text, #1f2937);
            font-family: Arial, Helvetica, sans-serif;
        }

        .styleguide-shell {
            width: min(1120px, 100%);
            margin: 0 auto;
            display: grid;
            gap: 20px;
        }

        .styleguide-header {
            --generic-hero-gap: 12px;
            --generic-hero-padding: 24px;
            --generic-hero-radius: var(--radius-md);
            --generic-hero-shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
        }

        .styleguide-lead {
            margin: 0;
            max-width: 760px;
            line-height: 1.6;
            color: var(--color-text-light, #6b7280);
        }

        .styleguide-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
        }

        .styleguide-stack {
            display: grid;
            gap: 12px;
        }

        .styleguide-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .styleguide-code {
            margin: 0;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            background: #0f172a;
            color: #e2e8f0;
            font: 13px/1.5 Consolas, "Courier New", monospace;
            white-space: pre-wrap;
        }

        .styleguide-note {
            color: var(--color-text-light, #6b7280);
            line-height: 1.5;
        }

        .styleguide-form {
            display: grid;
            gap: 12px;
        }

        .styleguide-field {
            display: grid;
            gap: 6px;
        }

        .styleguide-label {
            font-size: 13px;
            font-weight: 700;
            color: var(--color-text, #1f2937);
        }

        .styleguide-pill {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--color-primary, #2563eb);
            border: 1px solid rgba(37, 99, 235, 0.18);
            font-size: 12px;
            font-weight: 700;
        }

        .styleguide-surface {
            min-height: 74px;
            display: grid;
            place-items: center;
            text-align: center;
        }

        .styleguide-accordion-list {
            display: grid;
            gap: 10px;
        }

        .styleguide-tab-example {
            display: grid;
            gap: 12px;
        }

        .styleguide-divider {
            height: 1px;
            background: color-mix(in srgb, var(--color-border, #e5e7eb) 85%, transparent);
        }

        .styleguide-meta {
            display: grid;
            gap: 8px;
        }

        .styleguide-file-list-demo {
            max-height: 460px;
            overflow: auto;
            padding: 0;
        }

        .styleguide-file-list-root {
            --generic-file-list-surface: #ffffff;
            --generic-file-list-surface-alt: #f8fafc;
            --generic-file-list-title-gap: 18px;
            --generic-file-list-table-margin-inline: 12px;
        }

        .styleguide-file-list-root .generic-file-list__group-title {
            padding: 15px 12px;
            font-size: 0.9rem;
        }

        .styleguide-file-list-root .generic-file-list__row {
            min-height: 64px;
        }

        @media (max-width: 640px) {
            body {
                padding: 16px;
            }

            .styleguide-header {
                --generic-hero-padding: 18px;
            }
        }
    </style>
</head>
<body>
    <main class="styleguide-shell">
        <section class="styleguide-header generic-hero-panel accent">
            <div class="generic-card-title generic-card-title--eyebrow">Reference partagee</div>
            <h1 class="generic-card-title generic-card-title--large">Styleguide des composants generiques</h1>
            <p class="styleguide-lead">
                Cette page montre les primitives communes definies dans <code>/common/assets/components.css</code>.
                L'objectif est de reutiliser d'abord ces objets avant d'ecrire de nouveaux styles locaux.
            </p>
            <div class="styleguide-row">
                <span class="styleguide-pill">generic-section</span>
                <span class="styleguide-pill">generic-soft-panel</span>
                <span class="styleguide-pill">generic-hero-panel</span>
                <span class="styleguide-pill">generic-title</span>
                <span class="styleguide-pill">generic-description</span>
                <span class="styleguide-pill">generic-action-button</span>
                <span class="styleguide-pill">generic-drag-handle</span>
                <span class="styleguide-pill">generic-form-control</span>
                <span class="styleguide-pill">generic-tabs</span>
                <span class="styleguide-pill">generic-accordion</span>
                <span class="styleguide-pill">generic-file-list</span>
            </div>
        </section>

        <section class="generic-section generic-section--stack">
            <div class="generic-card-title generic-card-title--eyebrow">Typographie</div>
            <div class="styleguide-grid">
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

        <section class="generic-section generic-section--stack">
            <div class="generic-card-title generic-card-title--eyebrow">Panneaux</div>
            <div class="styleguide-grid">
                <div class="generic-section generic-section--stack">
                    <div class="generic-card-title generic-card-title--small">Section standard</div>
                    <div class="styleguide-note">Bloc principal pour un contenu de page ou une fiche.</div>
                </div>
                <div class="generic-section generic-section--alt generic-section--stack">
                    <div class="generic-card-title generic-card-title--small">Section alt</div>
                    <div class="styleguide-note">Version alternative avec surface secondaire.</div>
                </div>
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <div class="generic-card-title generic-card-title--small">Soft panel</div>
                    <div class="styleguide-note">Sous-bloc interieur ou zone de details.</div>
                </div>
                <div class="generic-hero-panel accent styleguide-surface">
                    <div class="styleguide-stack">
                        <div class="generic-card-title generic-card-title--eyebrow">Hero accent</div>
                        <div class="generic-card-title generic-card-title--big">Panneau de mise en avant</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="generic-section generic-section--stack">
            <div class="generic-card-title generic-card-title--eyebrow">Boutons</div>
            <div class="styleguide-row">
                <button type="button" class="generic-action-button generic-action-button--main">Action principale</button>
                <button type="button" class="generic-action-button generic-action-button--secondary">Action secondaire</button>
                <button type="button" class="generic-action-button generic-action-button--danger">Action danger</button>
                <button type="button" class="generic-action-button generic-action-button--main" disabled>Etat desactive</button>
            </div>
            <pre class="styleguide-code">generic-action-button generic-action-button--main
generic-action-button generic-action-button--secondary
generic-action-button generic-action-button--danger</pre>
        </section>

        <section class="generic-section generic-section--stack">
            <div class="generic-card-title generic-card-title--eyebrow">Poignees</div>
            <div class="styleguide-grid">
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <div class="styleguide-row">
                        <button type="button" class="generic-drag-handle" aria-label="Deplacer">::</button>
                        <button type="button" class="generic-drag-handle generic-drag-handle--stretch" aria-label="Deplacer">::</button>
                        <span class="generic-drag-handle generic-drag-handle--static">::</span>
                    </div>
                    <div class="styleguide-note">Utiliser cette primitive pour les listes reordonnables au lieu de recreer une poignee locale.</div>
                </div>
                <pre class="styleguide-code">button.generic-drag-handle
button.generic-drag-handle.generic-drag-handle--stretch
span.generic-drag-handle.generic-drag-handle--static</pre>
            </div>
        </section>

        <section class="generic-section generic-section--stack">
            <div class="generic-card-title generic-card-title--eyebrow">Champs</div>
            <div class="styleguide-grid">
                <form class="styleguide-form generic-section generic-section--stack generic-form-section generic-form-stack">
                    <div class="generic-form-section__heading">
                        <div class="generic-form-section__copy">
                            <div class="generic-title generic-title--medium">Exemple de formulaire</div>
                            <div class="generic-description">Une section, des champs et une rangee d actions reutilisables dans les drawers.</div>
                        </div>
                    </div>
                    <label class="styleguide-field generic-form-field">
                        <span class="styleguide-label generic-form-label">Input texte</span>
                        <input type="text" class="generic-form-control" value="Exemple de saisie">
                    </label>
                    <label class="styleguide-field generic-form-field">
                        <span class="styleguide-label generic-form-label">Select</span>
                        <select class="generic-form-control">
                            <option>Choix 1</option>
                            <option>Choix 2</option>
                        </select>
                    </label>
                    <label class="styleguide-field generic-form-field">
                        <span class="styleguide-label generic-form-label">Textarea</span>
                        <textarea class="generic-form-control" rows="4">Texte multi-lignes de demonstration.</textarea>
                    </label>
                    <label class="styleguide-field generic-form-field">
                        <span class="styleguide-label generic-form-label">Select editable</span>
                        <div class="generic-editable-select" data-generic-editable-select>
                            <div class="generic-editable-select__control">
                                <input
                                    type="text"
                                    class="generic-form-control generic-editable-select__input"
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
                    <div class="generic-form-actions generic-form-actions--stack-mobile">
                        <button type="button" class="generic-action-button generic-action-button--secondary">Annuler</button>
                        <button type="button" class="generic-action-button generic-action-button--main">Enregistrer</button>
                    </div>
                </form>
                <pre class="styleguide-code">generic-drawer-content
generic-form-stack
generic-section generic-section--stack generic-form-section
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

Overrides possibles via variables:
--generic-form-control-border
--generic-form-control-background
--generic-form-control-background-focus
--generic-form-control-textarea-min-height

JS disponible apres injection dynamique:
window.initGenericEditableSelects(container);</pre>
            </div>
        </section>

        <section class="generic-section generic-section--stack">
            <div class="generic-card-title generic-card-title--eyebrow">Onglets</div>
            <div class="styleguide-grid">
                <div class="styleguide-tab-example">
                    <div class="generic-tabs" data-generic-tabs>
                        <div class="generic-tabs__list" aria-label="Exemple d onglets">
                            <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="styleguide-tab-overview">Apercu</button>
                            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="styleguide-tab-form">Formulaire</button>
                            <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="styleguide-tab-notes">Notes</button>
                        </div>
                        <div class="generic-tabs__panels">
                            <div id="styleguide-tab-overview" class="generic-tabs__panel styleguide-stack" data-generic-tab-panel>
                                <div class="generic-card-title generic-card-title--small">Container libre</div>
                                <div class="styleguide-note">Le systeme se contente d afficher le bon bloc et de masquer les autres.</div>
                                <div class="styleguide-row">
                                    <span class="styleguide-pill">JS minimal</span>
                                    <span class="styleguide-pill">sans jQuery</span>
                                </div>
                            </div>
                            <div id="styleguide-tab-form" class="generic-tabs__panel styleguide-stack" data-generic-tab-panel hidden>
                                <label class="styleguide-field">
                                    <span class="styleguide-label">Champ dans un onglet</span>
                                    <input type="text" class="generic-form-control" value="Le contenu peut etre interactif">
                                </label>
                            </div>
                            <div id="styleguide-tab-notes" class="generic-tabs__panel styleguide-stack" data-generic-tab-panel hidden>
                                <div class="generic-card-title generic-card-title--small">Usage recommande</div>
                                <div class="styleguide-note">Conserver le style dans le CSS partage et utiliser seulement des IDs de panneaux cote HTML.</div>
                                <div class="styleguide-note">Si un bloc est injecte apres un fetch, les clics sont maintenant captes par delegation. En cas de besoin, on peut aussi appeler <code>window.initGenericTabs(container)</code>.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <pre class="styleguide-code">&lt;link rel="stylesheet" href="/common/assets/components.css"&gt;
&lt;script src="/common/assets/components.js" defer&gt;&lt;/script&gt;

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

        <section class="generic-section generic-section--stack">
            <div class="generic-card-title generic-card-title--eyebrow">Accordion</div>
            <div class="styleguide-accordion-list">
                <div class="generic-accordion generic-accordion--card generic-accordion--collapsible is-collapsed" data-generic-accordion>
                    <div class="generic-accordion__header">
                        <div class="generic-card-title generic-card-title--small">Accordion simple</div>
                        <button type="button" class="generic-accordion__toggle" data-generic-accordion-toggle aria-label="Ouvrir ou fermer">&#9662;</button>
                    </div>
                    <div class="generic-accordion__content">
                        <div class="styleguide-note">Le header, la carte et le comportement pliable viennent des classes generiques.</div>
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
                            <div class="styleguide-note">Un accordion peut aussi contenir d'autres primitives partagees.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="generic-section generic-section--stack">
            <div class="generic-card-title generic-card-title--eyebrow">File List</div>
            <div class="styleguide-grid">
                <div class="styleguide-file-list-demo generic-soft-panel">
                    <div class="generic-file-list generic-file-list--structured generic-file-list--stacked-sticky styleguide-file-list-root" data-generic-file-list>
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
                                                                        <strong class="generic-file-list__title">Checklist budget</strong>
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

        <section class="generic-section generic-section--stack">
            <div class="generic-card-title generic-card-title--eyebrow">Regle de travail</div>
            <div class="styleguide-meta">
                <div class="styleguide-note">Quand une page combine deja bordure, rayon, surface, spacing et typo avec les memes tokens, il faut d'abord se demander si l'objet existe deja ici.</div>
                <div class="styleguide-note">Si la reponse est non mais que le motif revient a plusieurs endroits, il vaut mieux etendre la bibliotheque generique que recopier le CSS.</div>
            </div>
        </section>
    </main>

</body>
</html>
