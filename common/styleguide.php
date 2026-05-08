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
            --generic-hero-radius: 24px;
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
            border-radius: 14px;
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

        .styleguide-divider {
            height: 1px;
            background: color-mix(in srgb, var(--color-border, #e5e7eb) 85%, transparent);
        }

        .styleguide-meta {
            display: grid;
            gap: 8px;
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
        <section class="styleguide-header generic-hero-panel generic-hero-panel--accent">
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
                <span class="styleguide-pill">generic-card-title</span>
                <span class="styleguide-pill">generic-action-button</span>
                <span class="styleguide-pill">generic-form-control</span>
                <span class="styleguide-pill">generic-accordion</span>
            </div>
        </section>

        <section class="generic-section generic-section--stack">
            <div class="generic-card-title generic-card-title--eyebrow">Titres</div>
            <div class="styleguide-grid">
                <div class="generic-soft-panel generic-soft-panel--stack">
                    <div class="generic-card-title generic-card-title--eyebrow">Eyebrow</div>
                    <div class="generic-card-title generic-card-title--small">Small</div>
                    <div class="generic-card-title generic-card-title--medium">Medium</div>
                    <div class="generic-card-title generic-card-title--big">Big</div>
                    <div class="generic-card-title generic-card-title--section">Section</div>
                    <div class="generic-card-title generic-card-title--large">Large</div>
                </div>
                <pre class="styleguide-code">generic-card-title
generic-card-title--eyebrow
generic-card-title--small
generic-card-title--medium
generic-card-title--big
generic-card-title--section
generic-card-title--large</pre>
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
                <div class="generic-hero-panel generic-hero-panel--accent styleguide-surface">
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
            <div class="generic-card-title generic-card-title--eyebrow">Champs</div>
            <div class="styleguide-grid">
                <form class="styleguide-form generic-soft-panel generic-soft-panel--stack">
                    <label class="styleguide-field">
                        <span class="styleguide-label">Input texte</span>
                        <input type="text" class="generic-form-control" value="Exemple de saisie">
                    </label>
                    <label class="styleguide-field">
                        <span class="styleguide-label">Select</span>
                        <select class="generic-form-control">
                            <option>Choix 1</option>
                            <option>Choix 2</option>
                        </select>
                    </label>
                    <label class="styleguide-field">
                        <span class="styleguide-label">Textarea</span>
                        <textarea class="generic-form-control" rows="4">Texte multi-lignes de demonstration.</textarea>
                    </label>
                </form>
                <pre class="styleguide-code">input.generic-form-control
select.generic-form-control
textarea.generic-form-control

Overrides possibles via variables:
--generic-form-control-border
--generic-form-control-background
--generic-form-control-background-focus
--generic-form-control-textarea-min-height</pre>
            </div>
        </section>

        <section class="generic-section generic-section--stack">
            <div class="generic-card-title generic-card-title--eyebrow">Accordion</div>
            <div class="styleguide-accordion-list">
                <div class="generic-accordion generic-accordion--card generic-accordion--collapsible is-collapsed" data-styleguide-accordion>
                    <div class="generic-accordion__header">
                        <div class="generic-card-title generic-card-title--small">Accordion simple</div>
                        <button type="button" class="generic-accordion__toggle" data-styleguide-toggle aria-label="Ouvrir ou fermer">▾</button>
                    </div>
                    <div class="generic-accordion__content">
                        <div class="styleguide-note">Le header, la carte et le comportement pliable viennent des classes generiques.</div>
                    </div>
                </div>

                <div class="generic-accordion generic-accordion--card generic-section--stack">
                    <div class="generic-accordion__header">
                        <div class="generic-card-title generic-card-title--small">Accordion ouvert</div>
                        <span class="generic-accordion__toggle" aria-hidden="true">▾</span>
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
            <div class="generic-card-title generic-card-title--eyebrow">Regle de travail</div>
            <div class="styleguide-meta">
                <div class="styleguide-note">Quand une page combine deja bordure, rayon, surface, spacing et typo avec les memes tokens, il faut d'abord se demander si l'objet existe deja ici.</div>
                <div class="styleguide-note">Si la reponse est non mais que le motif revient a plusieurs endroits, il vaut mieux etendre la bibliotheque generique que recopier le CSS.</div>
            </div>
        </section>
    </main>

    <script>
    (function () {
        var accordions = document.querySelectorAll('[data-styleguide-accordion]');
        accordions.forEach(function (accordion) {
            var toggle = accordion.querySelector('[data-styleguide-toggle]');
            if (!toggle) {
                return;
            }

            toggle.addEventListener('click', function () {
                accordion.classList.toggle('is-collapsed');
            });
        });
    })();
    </script>
</body>
</html>
