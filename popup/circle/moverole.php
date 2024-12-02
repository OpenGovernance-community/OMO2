<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");

	// Il faut être connecté pour pouvoir partager.
	// Initialise le login
	$connected=checklogin();

	// Affichage des champs de formulaires
	echo "<select id='dest_circle'></select>";
	echo "<button id='btn_move_role_final'>Déplacer</button>";


?>
<script>
  $( function() {
	transformJSONtoHTML(root, "/xslt/list_circle_select.xml","dest_circle","id",currentnode.ID);
 	  
	// Create account
	$("#btn_move_role_final").click(function (e) {
			// Ajoute un élément au noeud courant
			console.log(currentnode);
			
			// Récupère le pointeur sur le noeuc
			
			// Cherche la nouvelle destination
			console.log($("#dest_circle").val());
			const dest = idIndexedMap[$("#dest_circle").val()];
			// Si la destination n'a pas d'enfants, rajoute le neud
			if (!Array.isArray(dest.children)) {
				dest.children=[];
			}
			console.log(dest);
			
			// Ajoute le noeud à cet endroit
			const parent = currentnode.parent;

				  if (parent && Array.isArray(parent.children)) {
					// Rechercher l'index du nœud à supprimer dans le tableau Children
					const index = parent.children.findIndex(child => child === currentnode);

					if (index !== -1) {
						
					  // Déplace l'enfant	
					  dest.children.push(currentnode);	
					  currentnode.parent=dest
						
					  // Supprimer l'enfant du tableau
					  parent.children.splice(index, 1);
					  console.log ("Nouvelle structure");
					  console.log(root);

					  
					} else {console.log("pas trouvé");}
				  }  else {console.log("pas de parent");}

				  		
			// Supprime l'ancien
			
			localStorage.setItem('circlestructure', JSON.stringify(removeCircularReferences(root)));
			refreshCircle();
			// Ferme la fenêtre
			closePopup();
			 console.log(root);
		
		
	});
});
</script>
<?		
	
?>
