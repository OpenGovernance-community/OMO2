<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");

	// Il faut être connecté pour pouvoir partager.
	// Initialise le login
	$connected=checklogin();
	if ($connected) {
		
		// Charge tous les PV de la personne
		$listeOrga=new \dbObject\ArrayHolon();
		// Limite aux holons de type organisations, appartenants à la personne.
			$params= array();	
			$params["filter"] = "IDtypeholon=4 and active=1 and IDuser=".$_SESSION["currentUser"];
		$listeOrga->load($params);
		echo '<div id="accordion">';
		echo '<h3>'.T_("Charger depuis le serveur").'</h3>';
		echo '<div>';
		echo "<H1>".T_("Structures sauvegardées")."</H1>";
		echo "<div id='orgaliste' class='loading_liste'>";
		foreach ($listeOrga as $orga) {
			echo "<div class='loading_element loadOrga' data-src='".$orga->get("id")."'><div class='delete_option deleteOrga' float='right' data-src='".$orga->get("id")."'>Delete</div><b>".($orga->get("name"))."</b><br/><span style='font-size:80%'>".T_("Date de création").": ".$orga->get("datecreation")->format("d.m.Y H:i")."</span></div>";
		}
		echo "</div>";
		echo '</div>';
		echo '<h3>'.T_("Importer un fichier").'</h3>';
		echo '<div>';
		echo '</div>';
		echo '</div>';
		
	} else {
		// Affiche un bouton pour soit se connecter, soit s'inscrire
		echo T_("Vous devez être connecté pour pouvoir charger une structure en cercles et rôles.");
		echo T_("Se connecter");
		echo T_("Créer un compte");
		
	}

?>
<script>
	// Charge le PV en Ajax

	$(function() {
		
    // Crée l'accordeon
    $( "#accordion" ).accordion({heightStyle: "fill"});
    // Le met à jour lorsque la fenêtre change
    window.onresize = function() {
		$( "#accordion" ).accordion( "refresh" );
	};
		
		$("#orgaliste").delegate(".deleteOrga","click",function (e) {
			e.stopPropagation();
			if (confirm("<?=T_("Êtes-vous sûr de vouloir effacer cette organisation ?")?>")) {
				// Si supprimé la réunion courante, réinitialise l'ID (pour éviter les erreurs d'écrasement)
				if ($(this).attr("data-src")==$("#id").val()) {
					$("#id").val("");
					root.IDdb="";
					save();
					refreshCircle();
				}
				$.ajax({method: "POST",url: "/ajax/delete.php",data: { type:"Holon", id:$(this).attr("data-src")}
				}).done(function( msg ) {if (msg!="") alert(msg); });						

				// Raffraîchi la liste

				refresh('#orgaliste',"/popup/circle/load.php");	


			}		
		});
		$("#orgaliste").delegate(".loadOrga","click",function(e) {
			fetch('/ajax/loadorga.php?id='+$(this).attr("data-src"))
				.then(response => {
					if (!response.ok) {
						throw new Error("<?=T_("Erreur réseau lors de la récupération des données.")?>");
					}
					return response.json();
				})
				.then(data => {
					// S'assure qu'il était authorisé de lire ce PV
					if (data.error) {
						alert (data.errorMsg);
					
					} else
					
					if (confirm("<?=T_("Êtes-vous sûr de vouloir écraser le contenu de l'éditeur avec le compte-rendu chargé ?")?>")) {
						root=data;
						localStorage.setItem('circlestructure', JSON.stringify(root));
						currentnode=focusNode=root;
						
						// Raffraichi l'affichage
						refreshCircle();
												
						$("#saved").val("");
					} 
					// Ferme la fenêtre
					closePopup()
				})
				.catch(error => {
					console.error('Erreur:', error);
				});	
		});
	});

</script>
<style>
	.loading_element {cursor:pointer;background:rgba(0,0,0,0.05); padding:5px; border-radius:3px; margin-bottom:5px;}
	.delete_option {display:none;}
	.loading_element:hover {background:rgba(0,0,0,0.1)}
	.loading_element:hover .delete_option {display:block; float:right; background:rgba(0,0,0,0.1); padding:3px; border-radius:3px;}
	.loading_element:hover .delete_option:hover {background:#FFFF00 }
</style>

