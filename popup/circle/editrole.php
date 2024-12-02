<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");

	// Il faut être connecté pour pouvoir partager.
	// Initialise le login
	$connected=checklogin();

	// Si l'organisation n'est pas sauvegardée sur on compte et n'a pas de modèles de rôles spécifiques, applique les modèles de base
	// Affichage des champs de formulaires en fonction du type de rôle
	echo "<h1 id='role_type'></h1>";
	echo "<input id='role_field_name' placeholder='Nom'>";
	echo "<textarea id='role_field_rde' placeholder='Raison d&apos;être'></textarea>";
	echo "<textarea id='role_field_domain' placeholder='Domaines d&apos;autorité'></textarea>";
	echo "<textarea id='role_field_redevability' placeholder='Attendus'></textarea>";
	
	// Affiche le bouton pour sauver
	echo "<button id='btn_save_role_final'>Sauver</button>";


?>
<script>
  $( function() {
	  
	// Initialise les valeurs en fonction du noeud courant (ou celui passé en paramètre)
	$("[id^='role_field_']").each(function() {
		let elementId = $(this).attr("id");
		let key = elementId.replace("role_field_", ""); // Supprime le préfixe
		if (key=="name") $(this).val(currentnode[key]);
		else
			if (currentnode.data && currentnode.data[key] !== undefined) {
				$(this).val(currentnode.data[key]); // Initialise la valeur si la clé existe
			}
	});
	  
	  
	// Create account
	$("#btn_save_role_final").click(function (e) {
			// Ajoute un élément au noeud courant
		if (!currentnode.data) currentnode.data={};
		$("[id^='role_field_']").each(function() {
			let elementId = $(this).attr("id");
			let key = elementId.replace("role_field_", ""); // Supprime le préfixe

		if (key=="name") 
			currentnode[key]=$(this).val();
		else
			currentnode.data[key]=$(this).val(); // Initialise la valeur si la clé existe

		});
			
			
		localStorage.setItem('circlestructure', JSON.stringify(removeCircularReferences(root)));
		refreshCircle();
		// Ferme la fenêtre
		closePopup()
			
		
	});
});
</script>
<?		
	
?>
