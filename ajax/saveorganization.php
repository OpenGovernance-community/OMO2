<?php

require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");

header('Content-Type: application/json; charset=UTF-8');

$currentUserId = (int)commonGetCurrentUserId();
if ($currentUserId <= 0) {
    echo json_encode(array(
        "success" => false,
        "message" => "Connexion requise.",
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$organizationId = 0;
if (isset($_POST['id']) && is_numeric($_POST['id'])) {
    $organizationId = (int)$_POST['id'];
} elseif (isset($_GET['oid']) && is_numeric($_GET['oid'])) {
    $organizationId = (int)$_GET['oid'];
}

$isEditMode = $organizationId > 0;
$organization = new \dbObject\Organization();

if ($isEditMode) {
    if (!$organization->load($organizationId) || (int)$organization->getId() <= 0) {
        echo json_encode(array(
            "success" => false,
            "message" => "Organisation introuvable.",
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (!$organization->canEdit()) {
        echo json_encode(array(
            "success" => false,
            "message" => "Vous n'avez pas le droit de modifier cette organisation.",
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

$organization->loadFromArray($_POST);
if ($isEditMode) {
    $organization->set("id", $organizationId);
}

$name = trim((string)$organization->get("name"));
if ($name === "") {
    echo json_encode(array(
        "success" => false,
        "message" => "Le nom de l'organisation est obligatoire.",
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$saveResult = $organization->save();
if (empty($saveResult["status"]) || (int)$organization->getId() <= 0) {
    echo json_encode(array(
        "success" => false,
        "message" => !empty($saveResult["text"])
            ? (string)$saveResult["text"]
            : ($isEditMode
                ? "L'organisation n'a pas pu etre enregistree."
                : "L'organisation n'a pas pu etre creee."),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$isEditMode) {
    $link = new \dbObject\UserOrganization();
    if (!$link->load(array(
        array('IDuser', $currentUserId),
        array('IDorganization', (int)$organization->getId()),
    ))) {
        $link->set("IDuser", $currentUserId);
        $link->set("IDorganization", (int)$organization->getId());
    }

    $link->set("active", true);

    $user = new \dbObject\User();
    if ($user->load($currentUserId)) {
        $link->set("username", trim((string)$user->get("username")));
        $link->set("email", trim((string)$user->get("email")));
    }

    $parameters = json_decode((string)$link->get("parameters"), true);
    if (!is_array($parameters)) {
        $parameters = array();
    }
    $parameters["isAdmin"] = true;
    $link->set("parameters", $parameters);
    $link->save();
}

$shortname = trim((string)$organization->get("shortname"));
$redirectUrl = $shortname !== ''
    ? commonBuildUrl('/omo/', commonBuildOrganizationHost($shortname, commonGetRootHost()))
    : commonBuildUrl('/omo/o/' . (int)$organization->getId(), commonGetRootHost());

echo json_encode(array(
    "success" => true,
    "id" => (int)$organization->getId(),
    "organizationId" => (int)$organization->getId(),
    "mode" => $isEditMode ? "edit" : "create",
    "message" => $isEditMode ? "Organisation enregistree." : "Organisation creee.",
    "redirect" => $redirectUrl,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
