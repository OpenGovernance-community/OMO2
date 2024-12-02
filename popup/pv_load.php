<?
	require_once("../config.php");
	require_once("../shared_functions.php");

	// Il faut être connecté pour pouvoir partager.
	// Initialise le login
	$connected=checklogin();
	if ($connected) {
		
		// Charge tous les PV de la personne
		$listePV=new \dbObject\ArrayPV();
		$listePV->load();
		echo "<H1>".T_("Réunions sauvegardées")."</H1>";
		echo "<div id='PVliste'>";
		foreach ($listePV as $pv) {
			$content=json_decode(urldecode($pv->get("data")),true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				echo 'Erreur JSON : ' . json_last_error_msg();
			}

			if ($content)
				echo "<div class='loadPV' data-src='".$pv->get("id")."'><div class='deletePV' float='right' data-src='".$pv->get("id")."'>Delete</div>".$pv->get("datemodification")->format("d.m.Y H:i")."/ ".($content["title"]!=""?"<b>".$content["title"]."</b>":"<i>".T_("sans titre")."</i>")."<br/><span style='font-size:80%'>".T_("Date de création").": ".$pv->get("datecreation")->format("d.m.Y H:i")."</span></div>";
		}
		

		echo "</div>";
		
	} else {
		// Affiche un bouton pour soit se connecter, soit s'inscrire
		echo T_("Vous devez être connecté pour pouvoir charger un ordre du jour ou un PV.");
		echo T_("Se connecter");
		echo T_("Créer un compte");
		
	}

?>
<script>
	// Charge le PV en Ajax

	$(function() {
		$("#PVliste").delegate(".deletePV","click",function (e) {
			e.stopPropagation();
			if (confirm("<?=T_("Êtes-vous sûr de vouloir effacer ce compte-rendu ?")?>")) {
				// Si supprimé la réunion courante, réinitialise l'ID (pour éviter les erreurs d'écrasement)
				if ($(this).attr("data-src")==$("#id").val())
					$("#id").val("");
				$.ajax({method: "POST",url: "/ajax/delete.php",data: { type:"PV", id:$(this).attr("data-src")}
				}).done(function( msg ) {if (msg!="") alert(msg); });						

				// Raffraîchi la liste
				refresh('#PVliste',"/popup/pv_load.php");	

			}		
		});
		$("#PVliste").delegate(".loadPV","click",function() {
			fetch('/ajax/loadpv.php?id='+$(this).attr("data-src"))
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
						data.id=$(this).attr("data-src");
						// Le défini comme élément de base
						localStorage.setItem("savedata", JSON.stringify(data));
						// Rafraichi l'affichage
						load();
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
	.loadPV {cursor:pointer;background:rgba(0,0,0,0.05); padding:5px; border-radius:3px; margin-bottom:5px;}
	.deletePV {display:none;}
	.loadPV:hover {background:rgba(0,0,0,0.1)}
	.loadPV:hover .deletePV {display:block; float:right; background:rgba(0,0,0,0.1); padding:3px; border-radius:3px;}
	.loadPV:hover .deletePV:hover {background:#FFFF00 }
</style>

