<?
	// Inclus les éléments partagés entre plusieurs pages: base de donnée, instanciation de classe, etc...
	require_once("../config.php");

		if (strpos($_POST["type"],"\\")===false) {
			$class = "\\dbObject\\".$_POST["type"];
		} else
			$class = $_POST["type"];
		$obj=new $class();
		$obj->load($_POST["id"]);
		if ($obj->canEdit()) {
		// S'il y a un champ "actif" ou "active", le met à 0
			if ($obj->get("active")!="") {
				$obj->set("active",0);
				$obj->save();
			} else	
				// Sinon, efface l'enregistrement
				$obj->delete();
		} else echo "Vous n'avez pas les droits pour effacer cet objet";
?>
