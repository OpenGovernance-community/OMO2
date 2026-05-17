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

$password = isset($_POST['password']) ? (string)$_POST['password'] : '';
if ($password === '') {
    http_response_code(422);
    echo json_encode([
        'status' => false,
        'message' => 'Veuillez renseigner votre mot de passe.',
    ]);
    exit;
}

if (!serverEnvAdminHasLocalPassword()) {
    http_response_code(409);
    echo json_encode([
        'status' => false,
        'message' => 'Ce compte ne dispose pas de mot de passe local verifiable.',
    ]);
    exit;
}

if (!serverEnvAdminVerifyCurrentUserPassword($password)) {
    http_response_code(422);
    echo json_encode([
        'status' => false,
        'message' => 'Mot de passe invalide.',
    ]);
    exit;
}

serverEnvAdminRememberUnlocked();

echo json_encode([
    'status' => true,
    'message' => 'Verification effectuee.',
]);
exit;
