<?php
declare(strict_types=1);

function assertMemberAssignmentPopupUserReturn(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$popup = (string)file_get_contents($root . '/omo/api/team/member_assignment_popup.php');

assertMemberAssignmentPopupUserReturn(
    str_contains($popup, "(string)(\$returnPopupParts['path'] ?? '') === '/popup/user.php'")
        && str_contains($popup, "(int)(\$returnPopupQuery['id'] ?? 0) === \$userId")
        && str_contains($popup, "(int)(\$returnPopupQuery['oid'] ?? 0) === \$organizationId"),
    'The assignment editor must only return to the matching local user popup.'
);
assertMemberAssignmentPopupUserReturn(
    str_contains($popup, 'commonTopbarRefreshModalContent(returnPopupUrl)'),
    'The assignment editor must refresh the user popup after a successful save.'
);
assertMemberAssignmentPopupUserReturn(
    str_contains($popup, 'data-assignment-cancel')
        && str_contains($popup, "omoTeamT('team.assignment_popup.cancel'")
        && str_contains($popup, 'justify-content: flex-end'),
    'The assignment editor must provide a cancel action and align its buttons to the right.'
);

echo "member_assignment_popup_user_return_test: OK\n";
