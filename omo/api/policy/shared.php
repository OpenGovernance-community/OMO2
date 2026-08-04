<?php

use dbObject\ArrayAuthority;
use dbObject\Holon;
use dbObject\Organization;

if (!function_exists('omoPolicySourceLang')) {
    function omoPolicySourceLang()
    {
        return [
            'policy.title' => ['text' => 'Reglement', 'context' => 'Policy application title.'],
            'policy.description' => ['text' => 'Regles applicables au perimetre choisi.', 'context' => 'Policy application description.'],
            'policy.scope' => ['text' => 'Perimetre', 'context' => 'Label for the policy context scope selector.'],
            'policy.scope.contextual' => ['text' => 'Local', 'context' => 'Policy scope showing rules applicable to the current holon.'],
            'policy.scope.children' => ['text' => 'Enfants directs', 'context' => 'Policy scope showing rules applicable to the current holon and direct children.'],
            'policy.scope.descendants' => ['text' => 'Descendants', 'context' => 'Policy scope showing rules applicable to the current holon and all descendants.'],
            'policy.sort' => ['text' => 'Ordre', 'context' => 'Label for the policy rule ordering selector.'],
            'policy.sort.alpha' => ['text' => 'Alphabetique', 'context' => 'Policy rules ordered alphabetically.'],
            'policy.sort.created' => ['text' => 'Par date de creation', 'context' => 'Policy rules ordered by creation date.'],
            'policy.sort.updated' => ['text' => 'Par date de modification', 'context' => 'Policy rules ordered by modification date.'],
            'policy.group' => ['text' => 'Regroupement', 'context' => 'Label for the policy rule grouping selector.'],
            'policy.group.holon' => ['text' => 'Par holon', 'context' => 'Policy rules grouped by holon tree.'],
            'policy.group.authority' => ['text' => 'Par autorite', 'context' => 'Policy rules grouped by authority tree.'],
            'policy.group.none' => ['text' => 'Sans regroupement', 'context' => 'Policy rules displayed in one flat list without grouping.'],
            'policy.group.local_rules' => ['text' => 'Regles locales - {holon}', 'context' => 'Local rules when policy rules are grouped by authority.'],
            'policy.group.unnamed_authority' => ['text' => 'Autorite sans libelle', 'context' => 'Fallback title for an authority without a label.'],
            'policy.group.unknown' => ['text' => 'Rattachement inconnu', 'context' => 'Fallback title for a rule without a usable holon or authority.'],
            'policy.filters.aria' => ['text' => 'Filtres du reglement', 'context' => 'Accessible label for the compact policy scope filter.'],
            'policy.filters.apply' => ['text' => 'Appliquer', 'context' => 'Button applying the temporary policy scope selection.'],
            'policy.filters.save_view' => ['text' => 'Enregistrer la vue', 'context' => 'Button saving the policy scope selection for this holon.'],
            'policy.filters.more_actions' => ['text' => 'Autres options de vue', 'context' => 'Accessible label for additional policy view preference actions.'],
            'policy.filters.apply_everywhere' => ['text' => 'Appliquer partout', 'context' => 'Action setting the current policy view as the default and clearing specific views.'],
            'policy.filters.set_default' => ['text' => 'Definir comme vue par defaut', 'context' => 'Action saving the current policy view as the default view.'],
            'policy.filters.restore_default' => ['text' => 'Restaurer la vue par defaut', 'context' => 'Action removing the current holon specific policy view.'],
            'policy.search.aria' => ['text' => 'Filtrer les regles affichees', 'context' => 'Accessible label for the policy quick search input.'],
            'policy.search.placeholder' => ['text' => 'Filtrer les regles', 'context' => 'Placeholder for the policy quick search input.'],
            'policy.search.empty' => ['text' => 'Aucune regle ne correspond a cette recherche.', 'context' => 'Empty state when the policy quick search hides every rule.'],
            'policy.empty.contextual' => ['text' => 'Aucune regle dans ce contexte.', 'context' => 'Empty policy list for the local scope.'],
            'policy.empty.children' => ['text' => 'Aucune regle dans ce contexte ou ses enfants directs.', 'context' => 'Empty policy list for the direct children scope.'],
            'policy.empty.descendants' => ['text' => 'Aucune regle dans ce contexte ou ses descendants.', 'context' => 'Empty policy list for the descendants scope.'],
            'policy.empty.title' => ['text' => 'Aucune regle pour le moment', 'context' => 'Title for the policy empty state.'],
            'policy.new' => ['text' => 'Nouvelle regle', 'context' => 'Create a new rule.'],
            'policy.edit' => ['text' => 'Modifier la regle', 'context' => 'Edit an existing rule.'],
            'policy.delete' => ['text' => 'Supprimer la règle', 'context' => 'Delete an existing rule.'],
            'policy.delete.confirm' => ['text' => "Supprimer cette règle ?\n\nCette action est définitive.", 'context' => 'Confirmation shown before deleting an existing rule.'],
            'policy.empty' => ['text' => 'Aucune regle dans ce contexte.', 'context' => 'Empty policy list.'],
            'policy.intention' => ['text' => 'Intention', 'context' => 'Rule intent section title.'],
            'policy.review' => ['text' => 'A requestionner le {date}', 'context' => 'Rule review date label.'],
            'policy.expiration' => ['text' => 'Echeance le {date}', 'context' => 'Rule expiration date label.'],
            'policy.status.review' => ['text' => 'A verifier', 'context' => 'Status badge for a rule whose review date has been reached.'],
            'policy.status.expired' => ['text' => 'Obsolete', 'context' => 'Status badge for a rule past its expiration date.'],
            'policy.created' => ['text' => 'Creee le {date} par {user}', 'context' => 'Rule creation metadata.'],
            'policy.updated' => ['text' => 'Modifiee le {date} par {user}', 'context' => 'Rule modification metadata.'],
            'policy.holon' => ['text' => 'Holon : {holon}', 'context' => 'Rule holon attachment metadata.'],
            'policy.authority' => ['text' => 'Autorite : {authority}', 'context' => 'Rule authority attachment metadata.'],
            'policy.documentation' => ['text' => 'Informations et tracabilite', 'context' => 'Collapsed legal and audit metadata for a rule.'],
            'policy.drawer.title' => ['text' => 'Nouvelle regle', 'context' => 'Rule creation drawer title.'],
            'policy.drawer.description' => ['text' => 'Cette regle peut etre rattachee au holon courant ou a l une de ses autorites.', 'context' => 'Rule creation drawer description.'],
            'policy.drawer.title_edit' => ['text' => 'Modifier la regle', 'context' => 'Rule edit drawer title.'],
            'policy.drawer.description_edit' => ['text' => 'Modifiez le contenu, le rattachement ou les dates de cette regle.', 'context' => 'Rule edit drawer description.'],
            'policy.field.title' => ['text' => 'Titre', 'context' => 'Rule title field.'],
            'policy.field.intention' => ['text' => 'Intention', 'context' => 'Rule intent field.'],
            'policy.field.description' => ['text' => 'Regle', 'context' => 'Rule HTML content field.'],
            'policy.field.authority' => ['text' => 'Autorite associee', 'context' => 'Optional direct authority used as the rule attachment.'],
            'policy.field.authority_local' => ['text' => 'Aucune (regle locale au holon)', 'context' => 'Authority selector option for a direct local rule.'],
            'policy.field.review_date' => ['text' => 'Date de requestionnement', 'context' => 'Rule review date field.'],
            'policy.field.expiration_date' => ['text' => 'Date d echeance', 'context' => 'Rule expiration date field.'],
            'policy.save' => ['text' => 'Enregistrer', 'context' => 'Save local rule action.'],
            'policy.close' => ['text' => 'Fermer', 'context' => 'Close drawer action.'],
            'policy.error.context' => ['text' => 'Contexte invalide ou inaccessible.', 'context' => 'Invalid policy context.'],
            'policy.error.authority' => ['text' => 'L autorite choisie doit etre rattachee directement au holon courant.', 'context' => 'An invalid authority was selected for a new rule.'],
            'policy.error.forbidden' => ['text' => 'Vous ne pouvez pas creer de regle dans ce contexte.', 'context' => 'Unauthorized rule creation.'],
            'policy.error.method' => ['text' => 'Cette action doit etre envoyee en POST.', 'context' => 'Invalid HTTP method.'],
            'policy.error.load' => ['text' => 'Impossible de charger le formulaire.', 'context' => 'Local rule editor load error.'],
            'policy.error.save' => ['text' => 'Impossible d enregistrer la regle.', 'context' => 'Rule save error.'],
            'policy.error.delete' => ['text' => 'Impossible de supprimer la règle.', 'context' => 'Rule delete error.'],
            'policy.success.save' => ['text' => 'Regle enregistree.', 'context' => 'Rule creation confirmation.'],
            'policy.success.update' => ['text' => 'Regle modifiee.', 'context' => 'Rule update confirmation.'],
            'policy.success.delete' => ['text' => 'Règle supprimée.', 'context' => 'Rule deletion confirmation.'],
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
            'rootHolon' => $rootHolon,
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

if (!function_exists('omoPolicyNormalizeSort')) {
    function omoPolicyNormalizeSort($sort)
    {
        return in_array($sort, ['created', 'updated'], true) ? $sort : 'alpha';
    }
}

if (!function_exists('omoPolicyNormalizeGroup')) {
    function omoPolicyNormalizeGroup($group)
    {
        return $group === 'authority' ? 'authority' : ($group === 'none' ? 'none' : 'holon');
    }
}

if (!function_exists('omoPolicyGetDirectAuthorities')) {
    function omoPolicyGetDirectAuthorities(Holon $holon, ?Organization $organization = null)
    {
        if ($organization instanceof Organization) {
            $organization->ensureTemplateAuthorityInstancesForHolon($holon);
        }

        $authorities = new ArrayAuthority();
        $authorities->loadForHolon((int)$holon->getId());
        return $authorities;
    }
}
