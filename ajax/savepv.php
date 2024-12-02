<?php
	require_once("../config.php");
	require_once("../shared_functions.php");

// Récupérer les données JSON envoyées par jQuery
$tmp=file_get_contents('php://input');
$data = json_decode($tmp, true);

// Contrôle le format des données
if (isset($data['id'])) {
    // Traitement de vos données ici...
    $pv= new \dbObject\PV();
    if ($data['id']>0) {
		$pv->load($data['id']);
	} else {
		$pv->set("IDuser",$_SESSION["currentUser"]);
	}
	$pv->set("data",urlencode(json_encode($data)));
	$pv->save();
    // Réponse JSON avec statut et ID
    echo json_encode([
        'status' => 'ok',
        'id' => $pv->getID(),
    ]);
} else {
    // Réponse en cas d'erreur
    echo json_encode([
        'status' => 'error',
        'message' => 'Les données fournies sont invalides.'
    ]);
}
?>
