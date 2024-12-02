<?
	require_once("../config.php");
	require_once("../shared_functions.php");

	// Il faut être connecté pour pouvoir partager.
	// Initialise le login
	$connected=checklogin();
	if ($connected) {
		// Si connecté, regarde si un ID est passé en paramètre depuis le bouton
		if (isset($_GET["pvid"])) {
			// Si oui, charge l'élément et affiche les liens et boutons pour partager le contenu
			$pv=new \dbObject\PV();
			$pv->load($_GET["pvid"]);
			// Contrôle que l'object existe
			if (!$pv->get("id")>0) {
				echo T_("Objet inconnu");
				exit;
			}
			// Contrôle que l'utilisateur a bien les droits dessus
			if (!$pv->canEdit()) {
				echo T_("Accès interdit");
				exit;			
			}
			// Affiche les boutons et les éléments de formulaire permettant de partager les liens
			echo "Liens de partage";
		} else {
			// Si non, affiche les boutons qui généreront la création de l'élément lorsque appuyé
			echo "Liens de partage";
		}
	} else {
		// Affiche un bouton pour soit se connecter, soit s'inscrire
		echo T_("Vous devez être connecté pour pouvoir partager un lien vers un ordre du jour ou un PV.");
		echo T_("Se connecter");
		echo T_("Créer un compte");
		
	}

?>
