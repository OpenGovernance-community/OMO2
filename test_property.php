<?
	// Charge les librairies
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");

	
	// Charge l'ensemble des propriétés du noeud 13
	$liste=new \dbObject\arrayholonproperty();
	$liste->loadAllValues(13);
	
	// Affiche le résultat
	foreach ($liste as $holonproperty) {
		echo "-".$holonproperty->get("id")." ".$holonproperty->get("name")."<br>";
	}



?>
