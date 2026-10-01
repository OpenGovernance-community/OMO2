<?php
	require_once("../config.php");
	require_once("../shared_functions.php");
	require_once("../common/patreon.php");

	// Il faut être connecté pour pouvoir partager.
	// Initialise le login
	$connected=checklogin();
	if ($connected) {
		if (!patreonUserCanUseAi((int)($_SESSION['currentUser'] ?? 0))) {
			echo 'Les fonctions IA sont reservees aux contributeurs Patreon actifs.';
			exit;
		}
		// Affichage des champs de formulaires
	echo "<form name='formulaire' id='param_cretetxt' action='/ajax/create_txt.php'>";

	echo "<input type='hidden' name='IDdocument' id='IDdocument' value='".$_GET["id"]."'>";

	echo "<select name='IDaiprompt' id='IDaiprompt'>";
	
	// Récupère les prompts disponibles
	$prompts=$_SESSION["userRef"]->getPrompt();
	foreach($prompts as $prompt) {
		echo "<option value='".$prompt->get("id")."'>".$prompt->get("title")."</option>";
	}
	
	echo "</select>";

	// Affiche le bouton
	echo "<button type='button' id='btn_cretetxt'>Générer le texte</button>";
	// Ferme le formaulaire
	echo "</form>";
		
		// ID du document
		
		// ID du modèle de transformation

?>
<script>
  $( function() {
	// Create account
	$("#btn_cretetxt").click(function (e) {
		sendForm($("#param_cretetxt"),success);
	});
});
</script>
<?php
	} else {
		// Affiche un bouton pour soit se connecter, soit s'inscrire
		echo T_("Vous devez être connecté pour pouvoir charger un ordre du jour ou un PV.");
		echo T_("Se connecter");
		echo T_("Créer un compte");
		
	}
?>
