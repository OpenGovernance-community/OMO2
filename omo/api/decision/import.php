<?php
require_once __DIR__ . '/ui_translations.php';
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/modules/context.php';
require_once __DIR__ . '/modules/import_common.php';

use dbObject\DbObject;
use dbObject\DecisionGroup;
use dbObject\DecisionProcess;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    omoDecisionImportJsonResponse(405, [
        'status' => false,
        'message' => omoDecisionUiT('methode_non_autorisee'),
    ]);
}

$context = omoDecisionResolveEditorContext($_POST);
if (empty($context['status'])) {
    omoDecisionImportJsonResponse((int)($context['code'] ?? 400), [
        'status' => false,
        'message' => omoDecisionUiT('contexte_d_import_invalide'),
    ]);
}

if (!empty($context['decision']) || !empty($context['decisionId'])) {
    omoDecisionImportJsonResponse(400, [
        'status' => false,
        'message' => omoDecisionUiT('l_import_est_reserve_a_la_creation_d_un_nouveau_processus'),
    ]);
}

if (empty($context['canManage'])) {
    omoDecisionImportJsonResponse(403, [
        'status' => false,
        'message' => omoDecisionUiT('acces_refuse_pour_cet_import'),
    ]);
}

if (empty($_FILES['import_file']) || !is_array($_FILES['import_file'])) {
    omoDecisionImportJsonResponse(400, [
        'status' => false,
        'message' => omoDecisionUiT('aucun_fichier_d_import_n_a_ete_envoye'),
    ]);
}

$loadedFile = omoDecisionImportLoadUploadedFile($_FILES['import_file']);
if (empty($loadedFile['status'])) {
    omoDecisionImportJsonResponse(400, [
        'status' => false,
        'message' => (string)($loadedFile['message'] ?? omoDecisionUiT('impossible_de_lire_le_fichier_d_import')),
    ]);
}

$normalizedImport = omoDecisionImportNormalizePayload($loadedFile);
if (empty($normalizedImport['status'])) {
    omoDecisionImportJsonResponse(422, [
        'status' => false,
        'message' => (string)($normalizedImport['message'] ?? omoDecisionUiT('impossible_d_interpreter_le_fichier_d_import')),
    ]);
}

$blocks = is_array($normalizedImport['blocks'] ?? null) ? $normalizedImport['blocks'] : [];
if (count($blocks) === 0) {
    omoDecisionImportJsonResponse(422, [
        'status' => false,
        'message' => omoDecisionUiT('aucun_bloc_importable_n_a_ete_trouve'),
    ]);
}

$firstBlock = $blocks[0];
$processBlueprint = is_array($normalizedImport['process'] ?? null) ? $normalizedImport['process'] : [];
$currentUserId = (int)($context['currentUserId'] ?? 0);
$organizationId = (int)($context['organizationId'] ?? 0);
$targetHolonId = (int)($context['targetHolonId'] ?? 0);

$pdo = DbObject::getPdo();
if (!$pdo) {
    omoDecisionImportJsonResponse(500, [
        'status' => false,
        'message' => omoDecisionUiT('connexion_a_la_base_impossible'),
    ]);
}

try {
    $pdo->beginTransaction();

    $decision = new DecisionProcess();
    $decision->set('IDorganization', $organizationId);
    $decision->set('IDuser', $currentUserId);
    if ($targetHolonId > 0) {
        $decision->set('IDholon', $targetHolonId);
    }
    $decision->set('title', trim((string)($processBlueprint['title'] ?? '')) !== '' ? trim((string)$processBlueprint['title']) : omoDecisionImportBuildFallbackTitleFromFilename((string)$loadedFile['name']));
    $decision->set('description', trim((string)($processBlueprint['description'] ?? '')) !== '' ? trim((string)$processBlueprint['description']) : null);
    $decision->set('decision_type', DecisionProcess::normalizeDecisionType((string)($firstBlock['decision_type'] ?? ($processBlueprint['decision_type'] ?? DecisionProcess::TYPE_DECISION))));
    $decision->set('status', DecisionProcess::STATUS_DRAFT);
    $decision->set('evaluation_method', DecisionProcess::normalizeEvaluationMethod((string)($firstBlock['method'] ?? DecisionProcess::METHOD_SIMPLE_VOTE)));
    $decision->set('visibility_type', DecisionProcess::normalizeVisibilityType((string)($processBlueprint['visibility_type'] ?? DecisionProcess::getDefaultVisibilityType())));
    $decision->set('parameters', []);
    $decision->set('consultation_start_at', null);
    $decision->set('consultation_end_at', null);
    $decision->set('evaluation_start_at', null);
    $decision->set('evaluation_end_at', null);
    $decision->set('results_published_at', null);
    $decision->set('archived_at', null);

    $resolvedVisibility = $decision->resolveVisibilityRuleInput((string)$decision->get('visibility_type'));
    if (($resolvedVisibility['status'] ?? false) !== true) {
        throw new InvalidArgumentException(
            trim((string)($resolvedVisibility['text'] ?? omoDecisionUiT('visibilite_invalide_pour_ce_contexte_d_import')))
        );
    }

    $decision->set('visibility_type', (string)($resolvedVisibility['type'] ?? DecisionProcess::getDefaultVisibilityType()));

    $saveDecision = $decision->save();
    if (empty($saveDecision['status']) || (int)$decision->getId() <= 0) {
        throw new RuntimeException('decision_save_failed');
    }

    $ownerResult = omoDecisionImportEnsureOwnerParticipant($decision, $currentUserId);
    if (empty($ownerResult['status'])) {
        throw new RuntimeException('owner_participant_failed');
    }

    $primaryGroup = $decision->ensurePrimaryGroup();
    if (!$primaryGroup instanceof DecisionGroup) {
        throw new RuntimeException('primary_group_failed');
    }

    foreach ($blocks as $blockIndex => $blockBlueprint) {
        $method = DecisionProcess::normalizeEvaluationMethod((string)($blockBlueprint['method'] ?? ''));
        $definition = omoDecisionEnsureMethodImportLoaded($method);
        if (!$definition || empty($definition['import_function']) || !function_exists((string)$definition['import_function'])) {
            throw new InvalidArgumentException('Methode d import non supportee: ' . $method);
        }

        $isPrimaryGroup = $blockIndex === 0;
        $group = $isPrimaryGroup
            ? $primaryGroup
            : $decision->addDecisionGroup(
                $method,
                DecisionProcess::normalizeDecisionType((string)($blockBlueprint['decision_type'] ?? DecisionProcess::TYPE_DECISION)),
                trim((string)($blockBlueprint['title'] ?? '')),
                trim((string)($blockBlueprint['description'] ?? '')) !== '' ? trim((string)$blockBlueprint['description']) : null
            );

        if (!$group instanceof DecisionGroup) {
            throw new RuntimeException('group_create_failed');
        }

        $importResult = call_user_func(
            (string)$definition['import_function'],
            $decision,
            $group,
            $processBlueprint,
            $blockBlueprint,
            $isPrimaryGroup
        );

        if (!is_array($importResult) || empty($importResult['status'])) {
            throw new InvalidArgumentException(
                trim((string)($importResult['message'] ?? omoDecisionUiT('impossible_d_importer_un_bloc_du_scrutin')))
            );
        }
    }

    $pdo->commit();
} catch (InvalidArgumentException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    omoDecisionImportJsonResponse(422, [
        'status' => false,
        'message' => trim((string)$exception->getMessage()) !== ''
            ? trim((string)$exception->getMessage())
            : 'Impossible d importer ce fichier.',
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    omoDecisionImportJsonResponse(500, [
        'status' => false,
        'message' => omoDecisionUiT('impossible_d_importer_ce_scrutin_pour_le_moment'),
    ]);
}

$primaryGroup = $decision->getPrimaryGroup(true);
$redirectMethod = $primaryGroup instanceof DecisionGroup
    ? trim((string)$primaryGroup->get('evaluation_method'))
    : trim((string)$decision->get('evaluation_method'));
$redirectGroupId = $primaryGroup instanceof DecisionGroup ? (int)$primaryGroup->getId() : 0;

omoDecisionImportJsonResponse(200, [
    'status' => true,
    'message' => omoDecisionUiT('scrutin_importe'),
    'decisionId' => (int)$decision->getId(),
    'redirectUrl' => omoDecisionBuildEditorUrl($organizationId, $targetHolonId, (int)$decision->getId(), $redirectMethod, 'manage', $redirectGroupId),
    'drawerTitle' => 'Prises de decision',
]);
