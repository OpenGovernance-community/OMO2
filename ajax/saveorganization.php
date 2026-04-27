<?php

require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");

$currentUserId = (int)commonGetCurrentUserId();
if ($currentUserId <= 0) {
    echo json_encode(array(
        "success" => false,
        "message" => "Connexion requise.",
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$organization = new \dbObject\Organization();
$organization->loadFromArray($_POST);

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
        "message" => "L'organisation n'a pas pu être créée.",
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$link = new \dbObject\UserOrganization();
$link->set("IDuser", $currentUserId);
$link->set("IDorganization", (int)$organization->getId());
$link->set("active", true);

$user = new \dbObject\User();
if ($user->load($currentUserId)) {
    $link->set("username", trim((string)$user->get("username")));
    $link->set("email", trim((string)$user->get("email")));
}

$link->set("parameters", json_encode(array(
    "isAdmin" => true,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
$link->save();

$shortname = trim((string)$organization->get("shortname"));
$redirectUrl = $shortname !== ''
    ? commonBuildUrl('/omo/', commonBuildOrganizationHost($shortname, commonGetRootHost()))
    : commonBuildUrl('/omo/o/' . (int)$organization->getId(), commonGetRootHost());

echo json_encode(array(
    "success" => true,
    "id" => (int)$organization->getId(),
    "message" => "Organisation créée.",
    "redirect" => $redirectUrl,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
