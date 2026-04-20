<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");

	// Il faut être connecté pour pouvoir partager.
	// Initialise le login
	$connected=checklogin();

	// Si l'organisation n'est pas sauvegardée sur on compte et n'a pas de modèles de rôles spécifiques, applique les modèles de base
	// Affichage des champs de formulaires en fonction du type de rôle

	echo "<div id='form_edit_node'></div>";
	// Affiche le bouton pour sauver
	echo "<button id='btn_save_role_final'>Sauver</button>";


?>
<script>
	// Récuipère l'ID du noeud permettant de générer la liste des propriétés
	// Si l'IDdb du noeud n'est pas spécifié, s'appuie sur son template. Et si pas de template, récupère l'ID de base, qui doit correspondre à une valeur dans la base de données
	function getRef(node) {
	    if (node.IDdb !== undefined && node.IDdb !== null && node.IDdb !== "" && !isNaN(Number(node.IDdb))) return "n="+Number(node.IDdb);
		if (node.ID !== undefined && node.ID !== null && node.ID !== "" && !isNaN(Number(node.ID))) return "n="+Number(node.ID);
		if (node.t !== undefined && node.t !== null && node.t !== "" && !isNaN(Number(node.t))) return "t="+Number(node.t);
		return 2; // Par défaut
	}

  $( function() {
	  
	 // Commence par charger les champs 
	$("#form_edit_node").load("/popup/circle/editinterface.php?"+getRef(currentnode),function(response, status, xhr) {

		// Initialise les valeurs en fonction du noeud courant (ou celui passé en paramètre)
		$("[id^='role_field_']").each(function() {
			let elementId = $(this).attr("id");
			let key = elementId.replace("role_field_", ""); // Supprime le préfixe
			if (key=="name") $(this).val(currentnode[key]);
			else {
				console.log(key);
				if (currentnode.data && currentnode.data["d"+key] !== undefined) {
					$(this).val(currentnode.data["d"+key].value); // Initialise la valeur si la clé existe
				}
			}
		});
			
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
					currentnode.data["d"+key]={"value" : $(this).val(),"ancestor" : $("#role_parent_"+key).html()}; // Initialise la valeur si la clé existe


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
