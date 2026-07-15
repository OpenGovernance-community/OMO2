<?php

require_once('../config.php');
require_once('../shared_functions.php');
require_once('../common/auth.php');
require_once('../common/avatar.php');

header('Content-Type: application/json; charset=UTF-8');

if (!checklogin()) {
    echo json_encode([
        'status' => false,
        'success' => false,
        'message' => 'Login requis',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$currentUserId = function_exists('commonGetCurrentUserId')
    ? (int)commonGetCurrentUserId()
    : (int)($_SESSION['currentUser'] ?? 0);
$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$user = new \dbObject\User();

if ($currentUserId <= 0 || !$user->load($currentUserId)) {
    echo json_encode([
        'status' => false,
        'success' => false,
        'message' => 'Utilisateur inconnu',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$displayName = (string)$user->getScopedDisplayName($currentOrganizationId);
$initials = (string)$user->getScopedInitials($currentOrganizationId);
$avatarPalette = commonBuildAvatarPalette(
    $initials,
    (int)$user->getId(),
    commonBuildAvatarSeedLabel($displayName, (string)$user->getScopedEmail($currentOrganizationId))
);

echo json_encode([
    'status' => true,
    'success' => true,
    'profile' => [
        'displayName' => $displayName,
        'email' => (string)$user->getScopedEmail($currentOrganizationId),
        'username' => (string)$user->getScopedUsername($currentOrganizationId),
        'photoUrl' => (string)$user->getScopedProfilePhotoUrl($currentOrganizationId),
        'initials' => $initials,
        'avatarStyle' => 'background-color: ' . $avatarPalette['background'] . '; color: ' . $avatarPalette['foreground'] . ';',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

