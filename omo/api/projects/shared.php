<?php

use dbObject\Holon;
use dbObject\Organization;
use dbObject\Project;

if (!function_exists('omoProjectsSourceLang')) {
    function omoProjectsSourceLang()
    {
        return [
            'projects.title' => ['text' => 'Projets', 'context' => 'Main title of the projects application.'],
            'projects.action.new' => ['text' => 'Nouveau projet', 'context' => 'Primary action opening project creation.'],
            'projects.action.edit' => ['text' => 'Modifier', 'context' => 'Button opening project edition from the detail header.'],
            'projects.action.close' => ['text' => 'Fermer', 'context' => 'Button closing the project subdrawer.'],
            'projects.action.save' => ['text' => 'Enregistrer', 'context' => 'Submit action saving a project.'],
            'projects.action.cancel' => ['text' => 'Annuler', 'context' => 'Button cancelling project creation.'],
            'projects.action.move' => ['text' => 'Deplacer', 'context' => 'Context menu action moving a project to another holon.'],
            'projects.action.archive' => ['text' => 'Archiver', 'context' => 'Context menu action archiving a project.'],
            'projects.action.delete' => ['text' => 'Supprimer', 'context' => 'Context menu action permanently deleting a project.'],
            'projects.scope.contextual' => ['text' => 'Local', 'context' => 'Scope showing projects attached to the current holon.'],
            'projects.scope.children' => ['text' => 'Enfants directs', 'context' => 'Scope showing projects attached to the current holon and its direct children.'],
            'projects.scope.descendants' => ['text' => 'Descendants', 'context' => 'Scope showing projects attached to the current holon and its descendants.'],
            'projects.view.aria' => ['text' => 'Mode d affichage', 'context' => 'Accessible label for the project display mode selector.'],
            'projects.view.kanban' => ['text' => 'Kanban', 'context' => 'Project display mode button.'],
            'projects.view.list' => ['text' => 'Liste', 'context' => 'Project display mode button.'],
            'projects.sort.aria' => ['text' => 'Classer les projets', 'context' => 'Accessible label for the project list sort selector.'],
            'projects.sort.planned' => ['text' => 'Planification', 'context' => 'Project list sort button.'],
            'projects.sort.priority' => ['text' => 'Priorite', 'context' => 'Project list sort button.'],
            'projects.sort.holon' => ['text' => 'Holon', 'context' => 'Project list sort button.'],
            'projects.list.planned.overdue' => ['text' => 'En retard', 'context' => 'Project list planned group for past dates.'],
            'projects.list.planned.today' => ['text' => 'Aujourd hui', 'context' => 'Project list planned group for today.'],
            'projects.list.planned.tomorrow' => ['text' => 'Demain', 'context' => 'Project list planned group for tomorrow.'],
            'projects.list.planned.this_week' => ['text' => 'Cette semaine', 'context' => 'Project list planned group for the rest of this week.'],
            'projects.list.planned.next_week' => ['text' => 'La semaine prochaine', 'context' => 'Project list planned group for next week.'],
            'projects.list.planned.later' => ['text' => 'Plus tard', 'context' => 'Project list planned group for future dates.'],
            'projects.list.planned.none' => ['text' => 'Sans planification', 'context' => 'Project list planned group without dates.'],
            'projects.list.priority.none' => ['text' => 'Sans priorite', 'context' => 'Project list priority group without priority.'],
            'projects.empty.contextual' => ['text' => 'Aucun projet dans ce contexte.', 'context' => 'Empty state for the local project scope.'],
            'projects.empty.children' => ['text' => 'Aucun projet dans ce contexte ou ses enfants directs.', 'context' => 'Empty state for the direct child holon scope.'],
            'projects.empty.descendants' => ['text' => 'Aucun projet dans ce contexte ou ses descendants.', 'context' => 'Empty state for the descendant project scope.'],
            'projects.empty.column' => ['text' => 'Aucun projet dans cette colonne.', 'context' => 'Empty state for one empty Kanban column.'],
            'projects.loading' => ['text' => 'Chargement du projet...', 'context' => 'Loading message shown inside the project subdrawer.'],
            'projects.loading_error' => ['text' => 'Impossible de charger ce projet.', 'context' => 'Error shown when a project drawer cannot be loaded.'],
            'projects.status_update_error' => ['text' => 'Impossible de changer le statut.', 'context' => 'Fallback error shown when a project status cannot be changed.'],
            'projects.action_error' => ['text' => 'Impossible de mettre a jour le projet.', 'context' => 'Fallback error shown for a project context action.'],
            'projects.delete.confirm' => ['text' => 'Supprimer definitivement ce projet et ses {count} sous-projets ? Cette action est irreversible.', 'context' => 'Confirmation before permanent project deletion.'],
            'projects.archive.confirm' => ['text' => 'Ce projet n est pas termine. L archiver quand meme ?', 'context' => 'Confirmation before archiving an unfinished project.'],
            'projects.move.title' => ['text' => 'Deplacer le projet', 'context' => 'Title of the project holon move dialog.'],
            'projects.move.hint' => ['text' => 'Choisissez le holon de destination dans la structure.', 'context' => 'Instruction in the project holon move dialog.'],
            'projects.move.submit' => ['text' => 'Deplacer ici', 'context' => 'Submit button in the project holon move dialog.'],
            'projects.move.select_required' => ['text' => 'Choisissez un holon de destination.', 'context' => 'Validation message when no target holon is selected.'],
            'projects.column.previous' => ['text' => 'Colonne precedente', 'context' => 'Accessible label for the previous mobile Kanban column button.'],
            'projects.column.next' => ['text' => 'Colonne suivante', 'context' => 'Accessible label for the next mobile Kanban column button.'],
            'projects.error.organization' => ['text' => 'Organisation invalide ou inaccessible.', 'context' => 'Error for an invalid organization context.'],
            'projects.error.context' => ['text' => 'Contexte invalide ou inaccessible.', 'context' => 'Error for an invalid holon context.'],
            'projects.error.not_found' => ['text' => 'Projet introuvable.', 'context' => 'Error when a project cannot be loaded.'],
            'projects.error.forbidden' => ['text' => 'Vous ne pouvez pas modifier ce projet.', 'context' => 'Error when project mutation is forbidden.'],
            'projects.error.method' => ['text' => 'Cette action doit etre envoyee en POST.', 'context' => 'Error for a mutation sent with the wrong HTTP method.'],
            'projects.error.action' => ['text' => 'Action inconnue.', 'context' => 'Error for an unsupported project action.'],
            'projects.error.title' => ['text' => 'Le titre est obligatoire.', 'context' => 'Validation error for a missing project title.'],
            'projects.error.status' => ['text' => 'Le statut du projet est invalide.', 'context' => 'Validation error for an invalid project status.'],
            'projects.error.save' => ['text' => 'Impossible d enregistrer le projet.', 'context' => 'Generic project persistence error.'],
            'projects.error.holon' => ['text' => 'Le holon de destination est invalide ou inaccessible.', 'context' => 'Error for an invalid project move target holon.'],
            'projects.success.save' => ['text' => 'Projet enregistre.', 'context' => 'Success message after project creation.'],
            'projects.success.status' => ['text' => 'Statut mis a jour.', 'context' => 'Success message after changing project status.'],
            'projects.drawer.title' => ['text' => 'Projet', 'context' => 'Default title of the project subdrawer.'],
            'projects.drawer.description' => ['text' => 'Details et informations du projet.', 'context' => 'Default description of the project subdrawer.'],
            'projects.detail.badge' => ['text' => 'Projet', 'context' => 'Eyebrow label shown above the project detail title.'],
            'projects.detail.description' => ['text' => 'Description', 'context' => 'Project detail description section label.'],
            'projects.detail.context' => ['text' => 'Contexte', 'context' => 'Project detail holon section label.'],
            'projects.detail.schedule' => ['text' => 'Dates planifiees', 'context' => 'Project detail planned dates section label.'],
            'projects.detail.organisation' => ['text' => 'Organisation', 'context' => 'Project detail organization label.'],
            'projects.detail.responsible' => ['text' => 'Responsable', 'context' => 'Project detail responsible person label.'],
            'projects.detail.status' => ['text' => 'Statut', 'context' => 'Project detail status label.'],
            'projects.detail.priority' => ['text' => 'Priorite', 'context' => 'Project detail priority label.'],
            'projects.detail.importance' => ['text' => 'Importance', 'context' => 'Project detail importance label.'],
            'projects.detail.size' => ['text' => 'Taille', 'context' => 'Project detail project size label.'],
            'projects.detail.parent' => ['text' => 'Projet parent', 'context' => 'Project detail parent label.'],
            'projects.detail.subprojects' => ['text' => 'Sous-projets', 'context' => 'Project detail subprojects section label.'],
            'projects.detail.created' => ['text' => 'Cree le', 'context' => 'Project detail creation date label.'],
            'projects.detail.empty_description' => ['text' => 'Aucune description pour ce projet.', 'context' => 'Fallback when the project has no description.'],
            'projects.detail.none' => ['text' => 'Non renseigne', 'context' => 'Fallback for missing project metadata.'],
            'projects.detail.date_start' => ['text' => 'Debut', 'context' => 'Planned start date label.'],
            'projects.detail.date_end' => ['text' => 'Fin', 'context' => 'Planned end date label.'],
            'projects.subprojects.label' => ['text' => 'Etat des sous-projets', 'context' => 'Accessible label for the recursive subproject status bar.'],
            'projects.detail.priority_level' => ['one' => '{count}/5', 'other' => '{count}/5', 'context' => 'Project priority level.'],
            'projects.detail.importance_level' => ['one' => '{count}/5', 'other' => '{count}/5', 'context' => 'Project importance level.'],
            'projects.form.title' => ['text' => 'Nouveau projet', 'context' => 'Project creation form title.'],
            'projects.form.description' => ['text' => 'Definissez le but, les dates et le niveau d attention du projet.', 'context' => 'Project creation form introduction.'],
            'projects.form.submit' => ['text' => 'Creer le projet', 'context' => 'Submit button creating a project.'],
            'projects.form.edit_title' => ['text' => 'Modifier le projet', 'context' => 'Project edition form title.'],
            'projects.form.edit_description' => ['text' => 'Mettez a jour le but, les dates et les parametres du projet.', 'context' => 'Project edition form introduction.'],
            'projects.form.edit_submit' => ['text' => 'Enregistrer les modifications', 'context' => 'Submit button saving project changes.'],
            'projects.form.description_field' => ['text' => 'Description HTML simple', 'context' => 'Label for the project HTML description editor.'],
            'projects.status.someday' => ['text' => 'Un jour peut-etre', 'context' => 'Project status label.'],
            'projects.status.ready' => ['text' => 'Pret', 'context' => 'Project status label.'],
            'projects.status.in_progress' => ['text' => 'En cours', 'context' => 'Project status label.'],
            'projects.status.blocked' => ['text' => 'Bloque', 'context' => 'Project status label.'],
            'projects.status.review' => ['text' => 'A verifier', 'context' => 'Project status label.'],
            'projects.status.done' => ['text' => 'Termine', 'context' => 'Project status label.'],
            'projects.status_move' => ['text' => 'Changer le statut', 'context' => 'Accessible label for the project status move control.'],
            'projects.priority.none' => ['text' => 'Non definie', 'context' => 'Empty option for project priority.'],
            'projects.importance.none' => ['text' => 'Non definie', 'context' => 'Empty option for project importance.'],
            'projects.field.priority' => ['text' => 'Priorite', 'context' => 'Project creation priority field label.'],
            'projects.field.importance' => ['text' => 'Importance', 'context' => 'Project creation importance field label.'],
            'projects.field.status' => ['text' => 'Statut initial', 'context' => 'Project creation status field label.'],
            'projects.field.start_date' => ['text' => 'Debut planifie', 'context' => 'Project planned start date field label.'],
            'projects.field.end_date' => ['text' => 'Fin planifiee', 'context' => 'Project planned end date field label.'],
            'projects.field.parent' => ['text' => 'Projet parent', 'context' => 'Project parent field label.'],
            'projects.field.responsible' => ['text' => 'Responsable', 'context' => 'Project responsible user field label.'],
        ];
    }
}

if (!function_exists('omoProjectsLoadTranslationBundle')) {
    function omoProjectsLoadTranslationBundle()
    {
        static $bundle = null;
        if ($bundle === null) {
            $sourceLang = omoProjectsSourceLang();
            $bundle = omoLoadTranslationBundle('omo_projects', $sourceLang);
        }
        return $bundle;
    }
}

if (!function_exists('omoProjectsT')) {
    function omoProjectsT($key, array $replace = [])
    {
        $sourceLang = omoProjectsSourceLang();
        return t($key, $replace, omoProjectsLoadTranslationBundle(), $sourceLang);
    }
}

if (!function_exists('omoProjectsResolveContext')) {
    function omoProjectsResolveContext($organizationId, $currentHolonId = 0)
    {
        $organizationId = (int)$organizationId;
        $currentHolonId = (int)$currentHolonId;
        $organization = new Organization();

        if ($organizationId <= 0 || !$organization->load($organizationId) || !$organization->canViewDetail()) {
            return ['status' => false, 'message' => omoProjectsT('projects.error.organization')];
        }

        $rootHolon = $organization->getEnabledStructuralRootHolon();
        $currentHolon = $rootHolon instanceof Holon ? $rootHolon : null;
        if ($currentHolonId > 0) {
            $candidate = new Holon();
            if (
                !$candidate->load($currentHolonId)
                || !($rootHolon instanceof Holon)
                || !$candidate->isDescendantOf((int)$rootHolon->getId(), true)
                || !$candidate->canViewDetail()
            ) {
                return ['status' => false, 'message' => omoProjectsT('projects.error.context')];
            }
            $currentHolon = $candidate;
        }

        return [
            'status' => true,
            'organization' => $organization,
            'rootHolon' => $rootHolon instanceof Holon ? $rootHolon : null,
            'currentHolon' => $currentHolon,
        ];
    }
}

if (!function_exists('omoProjectsCanManageContext')) {
    function omoProjectsCanManageContext(array $context)
    {
        $currentHolon = $context['currentHolon'] ?? null;
        if ($currentHolon instanceof Holon) {
            return $currentHolon->canEdit();
        }

        $organization = $context['organization'] ?? null;
        return $organization instanceof Organization && $organization->canEdit();
    }
}

if (!function_exists('omoProjectsCanManageProject')) {
    function omoProjectsCanManageProject(Project $project, array $context)
    {
        $projectHolon = $project->getHolon();
        if ($projectHolon instanceof Holon) {
            return $projectHolon->canEdit();
        }

        $organization = $context['organization'] ?? null;
        return $organization instanceof Organization && $organization->canEdit();
    }
}

if (!function_exists('omoProjectsIsKanbanVisible')) {
    function omoProjectsIsKanbanVisible(Project $project, array $projectsById, array $projectsByParent, array $visibleProjectIds = [])
    {
        $children = $projectsByParent[(int)$project->getId()] ?? [];
        if (count($children) > 0) {
            return true;
        }

        $parentId = (int)$project->get('IDproject_parent');
        if ($parentId <= 0) {
            return true;
        }

        $parent = $projectsById[$parentId] ?? null;
        if (!($parent instanceof Project)) {
            return true;
        }

        return !isset($visibleProjectIds[$parentId]);
    }
}

if (!function_exists('omoProjectsCountDescendants')) {
    function omoProjectsCountDescendants($projectId, array $childrenByParent, array &$visited = [])
    {
        $projectId = (int)$projectId;
        if ($projectId <= 0 || isset($visited[$projectId])) {
            return 0;
        }

        $visited[$projectId] = true;
        $count = 0;
        foreach ($childrenByParent[$projectId] ?? [] as $child) {
            if (!($child instanceof Project)) {
                continue;
            }
            $childId = (int)$child->getId();
            if ($childId <= 0 || isset($visited[$childId])) {
                continue;
            }
            $count++;
            $count += omoProjectsCountDescendants($childId, $childrenByParent, $visited);
        }
        return $count;
    }
}

if (!function_exists('omoProjectsFormatDate')) {
    function omoProjectsFormatDate($value)
    {
        return $value instanceof \DateTimeInterface ? $value->format('d.m.Y') : '';
    }
}

if (!function_exists('omoProjectsStatusLabel')) {
    function omoProjectsStatusLabel($status)
    {
        $status = Project::normalizeStatus($status);
        return omoProjectsT('projects.status.' . $status);
    }
}

if (!function_exists('omoProjectsScopeContainsProject')) {
    function omoProjectsScopeContainsProject(Project $project, $scope, $currentHolonId, array $descendantHolonIds = [])
    {
        $projectHolonId = (int)$project->get('IDholon');
        $scope = trim(mb_strtolower((string)$scope, 'UTF-8'));
        if ($scope === 'descendants') {
            return in_array($projectHolonId, array_merge([(int)$currentHolonId], array_map('intval', $descendantHolonIds)), true);
        }
        if ($scope === 'children') {
            return in_array($projectHolonId, array_map('intval', $descendantHolonIds), true);
        }
        return $projectHolonId === (int)$currentHolonId || ($projectHolonId === 0 && (int)$currentHolonId === 0);
    }
}

if (!function_exists('omoProjectsGetUserLabel')) {
    function omoProjectsGetUserLabel($user)
    {
        if (!is_object($user)) {
            return omoProjectsT('projects.detail.none');
        }

        $name = trim(trim((string)$user->get('firstname')) . ' ' . trim((string)$user->get('lastname')));
        if ($name !== '') {
            return $name;
        }

        $username = trim((string)$user->get('username'));
        return $username !== '' ? $username : trim((string)$user->get('email'));
    }
}

if (!function_exists('omoProjectsCreateEmptyStatusSummary')) {
    function omoProjectsCreateEmptyStatusSummary()
    {
        return [
            'total' => 0,
            'counts' => array_fill_keys(Project::statuses(), 0),
            'weights' => array_fill_keys(Project::statuses(), 0.0),
            'leaves' => [],
        ];
    }
}

if (!function_exists('omoProjectsMergeStatusSummaries')) {
    function omoProjectsMergeStatusSummaries(array $summary, array $addition)
    {
        foreach (Project::statuses() as $status) {
            $summary['counts'][$status] = (int)($summary['counts'][$status] ?? 0) + (int)($addition['counts'][$status] ?? 0);
            $summary['weights'][$status] = (float)($summary['weights'][$status] ?? 0) + (float)($addition['weights'][$status] ?? 0);
        }
        $summary['total'] += (int)($addition['total'] ?? 0);
        if (!empty($addition['leaves']) && is_array($addition['leaves'])) {
            $summary['leaves'] = array_merge($summary['leaves'], $addition['leaves']);
        }
        return $summary;
    }
}

if (!function_exists('omoProjectsScaleStatusSummary')) {
    function omoProjectsScaleStatusSummary(array $summary, $factor)
    {
        $factor = (float)$factor;
        foreach ($summary['weights'] as $status => $weight) {
            $summary['weights'][$status] = (float)$weight * $factor;
        }
        foreach ($summary['leaves'] as &$leaf) {
            $leaf['weight'] = (float)($leaf['weight'] ?? 0) * $factor;
        }
        unset($leaf);
        return $summary;
    }
}

if (!function_exists('omoProjectsGetChildrenWeight')) {
    function omoProjectsGetChildrenWeight(array $children)
    {
        $weight = 0;
        foreach ($children as $child) {
            if ($child instanceof Project) {
                $weight += Project::getSizeWeight($child->get('project_size'));
            }
        }
        return $weight > 0 ? $weight : 1;
    }
}

if (!function_exists('omoProjectsGetLeafStatusSummary')) {
    function omoProjectsCreateLeafStatusSummary(Project $project)
    {
        $status = Project::normalizeStatus($project->get('status'));
        $summary = omoProjectsCreateEmptyStatusSummary();
        $summary['total'] = 1;
        $summary['counts'][$status] = 1;
        $summary['weights'][$status] = 1.0;
        $summary['leaves'][] = ['status' => $status, 'weight' => 1.0];
        return $summary;
    }

    function omoProjectsGetLeafStatusSummary(Project $project, array $childrenByParent, array &$memo = [], array &$path = [])
    {
        $projectId = (int)$project->getId();
        if ($projectId <= 0) {
            return omoProjectsCreateEmptyStatusSummary();
        }
        if (isset($memo[$projectId])) {
            return $memo[$projectId];
        }
        if (isset($path[$projectId])) {
            return omoProjectsCreateEmptyStatusSummary();
        }

        $path[$projectId] = true;
        $children = $childrenByParent[$projectId] ?? [];
        $summary = omoProjectsCreateEmptyStatusSummary();
        if (count($children) === 0) {
            $summary = omoProjectsCreateLeafStatusSummary($project);
        } else {
            $childrenWeight = omoProjectsGetChildrenWeight($children);
            foreach ($children as $child) {
                if ($child instanceof Project) {
                    $summary = omoProjectsMergeStatusSummaries(
                        $summary,
                        omoProjectsScaleStatusSummary(
                            omoProjectsGetLeafStatusSummary($child, $childrenByParent, $memo, $path),
                            Project::getSizeWeight($child->get('project_size')) / $childrenWeight
                        )
                    );
                }
            }
        }
        unset($path[$projectId]);
        $memo[$projectId] = $summary;
        return $summary;
    }
}

if (!function_exists('omoProjectsBuildStatusBar')) {
    function omoProjectsBuildStatusBar(Project $project, array $childrenByParent, array &$memo = [], $includeSelfWhenLeaf = false)
    {
        $children = $childrenByParent[(int)$project->getId()] ?? [];
        if (count($children) === 0) {
            return $includeSelfWhenLeaf
                ? omoProjectsCreateLeafStatusSummary($project)
                : omoProjectsCreateEmptyStatusSummary();
        }

        $summary = omoProjectsCreateEmptyStatusSummary();
        $childrenWeight = omoProjectsGetChildrenWeight($children);
        $path = [(int)$project->getId() => true];
        foreach ($children as $child) {
            if ($child instanceof Project) {
                $summary = omoProjectsMergeStatusSummaries(
                    $summary,
                    omoProjectsScaleStatusSummary(
                        omoProjectsGetLeafStatusSummary($child, $childrenByParent, $memo, $path),
                        Project::getSizeWeight($child->get('project_size')) / $childrenWeight
                    )
                );
            }
        }
        return $summary;
    }
}

if (!function_exists('omoProjectsStatusDisplayOrder')) {
    function omoProjectsStatusDisplayOrder()
    {
        return [
            Project::STATUS_READY,
            Project::STATUS_IN_PROGRESS,
            Project::STATUS_BLOCKED,
            Project::STATUS_REVIEW,
            Project::STATUS_DONE,
            Project::STATUS_SOMEDAY,
        ];
    }
}

if (!function_exists('omoProjectsStatusSummaryLabel')) {
    function omoProjectsStatusSummaryLabel(array $summary)
    {
        $parts = [];
        foreach (omoProjectsStatusDisplayOrder() as $status) {
            $count = (int)($summary['counts'][$status] ?? 0);
            if ($count > 0) {
                $weight = (float)($summary['weights'][$status] ?? 0);
                $percentage = rtrim(rtrim(number_format($weight * 100, 1, '.', ''), '0'), '.');
                $parts[] = omoProjectsStatusLabel($status) . ': ' . $count . ' (' . $percentage . '%)';
            }
        }
        return omoProjectsT('projects.subprojects.label') . ': ' . implode(', ', $parts);
    }
}

if (!function_exists('omoProjectsRenderStatusBar')) {
    function omoProjectsRenderStatusBar(array $summary, $extraClass = '')
    {
        if ((int)($summary['total'] ?? 0) <= 0 || empty($summary['leaves'])) {
            return '';
        }

        $className = trim('omo-project-status-bar ' . (string)$extraClass);
        $label = omoProjectsStatusSummaryLabel($summary);
        $html = '<div class="' . omoApiEscape($className) . '" role="img" aria-label="' . omoApiEscape($label) . '" title="' . omoApiEscape($label) . '">';
        $weightsByStatus = array_fill_keys(omoProjectsStatusDisplayOrder(), 0.0);
        foreach ($summary['leaves'] as $leaf) {
            $status = Project::normalizeStatus($leaf['status'] ?? '');
            if (!array_key_exists($status, $weightsByStatus)) {
                continue;
            }
            $weightsByStatus[$status] += max(0, (float)($leaf['weight'] ?? 0));
        }
        foreach (omoProjectsStatusDisplayOrder() as $status) {
            $segmentWidth = max(0, min(100, $weightsByStatus[$status] * 100));
            if ($segmentWidth <= 0) {
                continue;
            }
            $html .= '<span class="omo-project-status-bar__segment omo-project-status-bar__segment--' . omoApiEscape($status) . '" style="flex: 0 0 ' . omoApiEscape(number_format($segmentWidth, 6, '.', '')) . '%;" aria-hidden="true"></span>';
        }
        return $html . '</div>';
    }
}
