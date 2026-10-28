<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/shared_functions.php';

function pvEmailExpect(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

// Exercise the actual permission policy without granting object or holon privileges.
$document = new class extends dbObject\Document {
    public function hasObjectPermission(string $permissionKey, int $userId, bool $useSessionCache = false): bool { return false; }
    public function getPvPermissionHolon(int $organizationId = 0) { return null; }
};
foreach (['id' => 123, 'documenttype' => 'pv', 'IDorganization' => 1, 'IDholon' => 1,
    'IDusercreation' => 7, 'IDuser_pv_editor' => 7, 'IDuser_pv_official_editor' => 7] as $field => $value) {
    $document->set($field, $value);
}
foreach (['preparation', 'meeting', 'review'] as $stage) {
    $document->set('pvstage', $stage);
    pvEmailExpect($document->canUserSendPvInvitations(1, 7), 'The official editor can email participants in ' . $stage);
    pvEmailExpect(!$document->canUserSendPvInvitations(1, 8), 'An ordinary participant cannot email everyone in ' . $stage);
    pvEmailExpect(!$document->canUserSendPvInvitations(2, 7), 'A different organization cannot authorize email delivery.');
    pvEmailExpect(!$document->canUserSendPvInvitations(1, 0), 'Anonymous participation cannot authorize email delivery.');
}
$document->set('IDuser_pv_editor', 8);
pvEmailExpect(!$document->canUserSendPvInvitations(1, 8), 'A replacement without structure management rights cannot email participants.');
$document->set('IDuser_pv_editor', 7);
$document->set('pvstage', 'validated');
pvEmailExpect(!$document->canUserSendPvInvitations(1, 7), 'The validated PV keeps its existing management lock.');
$document->set('documenttype', 'html');
pvEmailExpect(!$document->canUserSendPvInvitations(1, 7), 'Other document types cannot use PV invitations.');

echo "pv_email_permissions_test: OK\n";
