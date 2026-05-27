<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Holon;
use dbObject\Organization;

function omoShareResolveManageContext(array $input)
{
    $currentUserId = (int)commonGetCurrentUserId();
    if ($currentUserId <= 0) {
        return array(
            'status' => false,
            'code' => 401,
            'message' => 'Connexion requise.',
        );
    }

    $organizationId = (int)($input['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
    $currentHolonId = isset($input['cid']) && is_numeric($input['cid']) ? (int)$input['cid'] : 0;

    if ($organizationId <= 0) {
        return array(
            'status' => false,
            'code' => 400,
            'message' => 'Organisation invalide.',
        );
    }

    $organization = new Organization();
    if (!$organization->load($organizationId) || !$organization->canViewDetail()) {
        return array(
            'status' => false,
            'code' => 403,
            'message' => 'Acces refuse a cette organisation.',
        );
    }

    $currentHolon = $organization->getStructuralRootHolon();
    if (!$currentHolon) {
        return array(
            'status' => false,
            'code' => 404,
            'message' => 'Aucun holon racine disponible.',
        );
    }

    if ($currentHolonId > 0 && (int)$currentHolon->getId() !== $currentHolonId) {
        $candidate = new Holon();
        if (!$candidate->load($currentHolonId) || !$candidate->isDescendantOf($currentHolon->getId())) {
            return array(
                'status' => false,
                'code' => 404,
                'message' => 'Holon introuvable pour cette organisation.',
            );
        }

        if (!$candidate->canViewDetail()) {
            return array(
                'status' => false,
                'code' => 403,
                'message' => 'Acces refuse a ce holon.',
            );
        }

        $currentHolon = $candidate;
    }

    return array(
        'status' => true,
        'code' => 200,
        'currentUserId' => $currentUserId,
        'organizationId' => $organizationId,
        'organization' => $organization,
        'currentHolon' => $currentHolon,
    );
}
