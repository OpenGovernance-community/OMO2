<?php
require_once dirname(__DIR__, 2) . '/common/auth.php';

$org = commonResolveOrganizationContext((int)($_SESSION["currentOrganization"] ?? 1));
?>
