<?php
	require_once("../config.php");
	require_once("../shared_functions.php");


// Contrôle le format des données
if (isset($_GET['id'])) {
    // Traitement de vos données ici...
    $pv= new \dbObject\PV();
    if ($_GET['id']>0) {
		$pv->load($_GET['id']);
		
		if ($pv->get("IDuser")==$_SESSION["currentUser"]) {
			echo urldecode($pv->get("data"));
		} else {
			echo json_encode(array('error' => 'true', 'errorMsg' => T_('Accès refusé')));
		}
	} 

} 
?>
