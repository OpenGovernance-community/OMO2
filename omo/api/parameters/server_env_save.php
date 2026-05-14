<?php
require_once __DIR__ . '/../bootstrap.php';
require_once dirname(__DIR__, 3) . '/includes/server_env_admin.php';

header('Content-Type: application/json; charset=UTF-8');

if ((int)commonGetCurrentUserId() <= 0) {
    http_response_code(401);
    echo json_encode([
        'status' => false,
        'message' => 'Connexion requise.',
    ]);
    exit;
}

if (!commonCurrentUserIsSiteAdmin()) {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'message' => 'Acces reserve a l admin du serveur.',
    ]);
    exit;
}

if (!serverEnvAdminIsUnlocked()) {
    http_response_code(423);
    echo json_encode([
        'status' => false,
        'message' => 'Confirmation du mot de passe requise.',
        'requiresUnlock' => true,
    ]);
    exit;
}

try {
    $submittedValues = serverEnvAdminReadSubmittedValues($_POST);
    $currentValues = serverEnvAdminBuildCurrentValues();
    $mergedValues = serverEnvAdminMergeSubmittedValues($submittedValues, $currentValues);
    $errors = serverEnvAdminValidateValues($mergedValues);

    if ($errors !== []) {
        http_response_code(422);
        echo json_encode([
            'status' => false,
            'message' => implode(' ', $errors),
            'errors' => $errors,
        ]);
        exit;
    }

    serverEnvAdminWriteValues($mergedValues);

    echo json_encode([
        'status' => true,
        'message' => 'Le fichier .env a ete mis a jour.',
        'configuredSecrets' => serverEnvAdminBuildSecretStateMap($mergedValues),
    ]);
    exit;
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => $exception->getMessage(),
    ]);
    exit;
}
