<?php

use dbObject\Holon;
use dbObject\Organization;

if (!function_exists('omoPolicySourceLang')) {
    function omoPolicySourceLang()
    {
        return [
            'policy.title' => ['text' => 'Reglement', 'context' => 'Policy application title.'],
            'policy.description' => ['text' => 'Regles applicables au contexte courant.', 'context' => 'Policy application description.'],
            'policy.new' => ['text' => 'Nouvelle regle', 'context' => 'Create a new local rule.'],
            'policy.empty' => ['text' => 'Aucune regle dans ce contexte.', 'context' => 'Empty policy list.'],
            'policy.intention' => ['text' => 'Intention', 'context' => 'Rule intent section title.'],
            'policy.description_label' => ['text' => 'Regle', 'context' => 'Rule content section title.'],
            'policy.review' => ['text' => 'A requestionner le {date}', 'context' => 'Rule review date label.'],
            'policy.expiration' => ['text' => 'Echeance le {date}', 'context' => 'Rule expiration date label.'],
            'policy.drawer.title' => ['text' => 'Nouvelle regle locale', 'context' => 'Local rule creation drawer title.'],
            'policy.drawer.description' => ['text' => 'Cette regle sera rattachee au contexte actuel.', 'context' => 'Local rule creation drawer description.'],
            'policy.field.title' => ['text' => 'Titre', 'context' => 'Rule title field.'],
            'policy.field.intention' => ['text' => 'Intention', 'context' => 'Rule intent field.'],
            'policy.field.description' => ['text' => 'Regle', 'context' => 'Rule HTML content field.'],
            'policy.field.review_date' => ['text' => 'Date de requestionnement', 'context' => 'Rule review date field.'],
            'policy.field.expiration_date' => ['text' => 'Date d echeance', 'context' => 'Rule expiration date field.'],
            'policy.save' => ['text' => 'Enregistrer', 'context' => 'Save local rule action.'],
            'policy.close' => ['text' => 'Fermer', 'context' => 'Close drawer action.'],
            'policy.error.context' => ['text' => 'Contexte invalide ou inaccessible.', 'context' => 'Invalid policy context.'],
            'policy.error.forbidden' => ['text' => 'Vous ne pouvez pas creer de regle dans ce contexte.', 'context' => 'Unauthorized rule creation.'],
            'policy.error.method' => ['text' => 'Cette action doit etre envoyee en POST.', 'context' => 'Invalid HTTP method.'],
            'policy.error.load' => ['text' => 'Impossible de charger le formulaire.', 'context' => 'Local rule editor load error.'],
            'policy.error.save' => ['text' => 'Impossible d enregistrer la regle.', 'context' => 'Rule save error.'],
            'policy.success.save' => ['text' => 'Regle enregistree.', 'context' => 'Rule creation confirmation.'],
        ];
    }
}

if (!function_exists('omoPolicyLoadTranslationBundle')) {
    function omoPolicyLoadTranslationBundle()
    {
        static $bundle = null;
        if ($bundle === null) {
            $bundle = omoLoadTranslationBundle('omo_policy', omoPolicySourceLang());
        }
        return $bundle;
    }
}

if (!function_exists('omoPolicyT')) {
    function omoPolicyT($key, array $replace = [])
    {
        return t($key, $replace, omoPolicyLoadTranslationBundle(), omoPolicySourceLang());
    }
}

if (!function_exists('omoPolicyResolveContext')) {
    function omoPolicyResolveContext($organizationId, $holonId = 0)
    {
        $organization = new Organization();
        if ((int)$organizationId <= 0 || !$organization->load((int)$organizationId) || !$organization->canViewDetail()) {
            return ['status' => false, 'message' => omoPolicyT('policy.error.context')];
        }

        $rootHolon = $organization->getEnabledStructuralRootHolon();
        $currentHolon = $rootHolon instanceof Holon ? $rootHolon : null;
        if ((int)$holonId > 0) {
            $candidate = new Holon();
            if (!($rootHolon instanceof Holon) || !$candidate->load((int)$holonId) || !$candidate->isDescendantOf((int)$rootHolon->getId(), true) || !$candidate->canViewDetail()) {
                return ['status' => false, 'message' => omoPolicyT('policy.error.context')];
            }
            $currentHolon = $candidate;
        }

        return [
            'status' => $currentHolon instanceof Holon,
            'message' => $currentHolon instanceof Holon ? '' : omoPolicyT('policy.error.context'),
            'organization' => $organization,
            'currentHolon' => $currentHolon,
        ];
    }
}

if (!function_exists('omoPolicyCanCreateLocalRule')) {
    function omoPolicyCanCreateLocalRule(array $context)
    {
        $holon = $context['currentHolon'] ?? null;
        return $holon instanceof Holon && $holon->canEdit();
    }
}
