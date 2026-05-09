<?
	require_once("../config.php");
	require_once("../shared_functions.php");

	function pvLoadDecodeSavedData($rawData) {
		$rawData = (string)$rawData;
		$decoded = json_decode($rawData, true);
		if (is_array($decoded)) {
			return $decoded;
		}

		$decoded = json_decode(urldecode($rawData), true);
		if (is_array($decoded)) {
			return $decoded;
		}

		return null;
	}

	// Il faut etre connecte pour pouvoir partager.
	$connected = checkLogin();
	if ($connected) {
		$listePV = new \dbObject\ArrayPV();
		$listePV->load([
			"filter" => "IDuser=".(int)$_SESSION["currentUser"]
		]);

		echo "<H1>".T_("Reunions sauvegardees")."</H1>";
		echo "<div id='PVliste'>";
		foreach ($listePV as $pv) {
			$content = pvLoadDecodeSavedData($pv->get("data"));
			$title = "<i>".T_("sans titre")."</i>";
			if (is_array($content) && isset($content["title"]) && trim((string)$content["title"]) !== "") {
				$title = "<b>".$content["title"]."</b>";
			} elseif (!is_array($content)) {
				$title = "<i>".T_("document incompatible")."</i>";
			}

			echo "<div class='loadPV' data-src='".$pv->get("id")."'><div class='deletePV' float='right' data-src='".$pv->get("id")."'>Delete</div>".$pv->get("datemodification")->format("d.m.Y H:i")."/ ".$title."<br/><span style='font-size:80%'>".T_("Date de creation").": ".$pv->get("datecreation")->format("d.m.Y H:i")."</span></div>";
		}

		echo "</div>";
	} else {
		echo T_("Vous devez etre connecte pour pouvoir charger un ordre du jour ou un PV.");
		echo T_("Se connecter");
		echo T_("Creer un compte");
	}
?>
<script>
	$(function() {
		$("#PVliste").delegate(".deletePV","click",function (e) {
			e.stopPropagation();
			if (confirm("<?=T_("Etes-vous sur de vouloir effacer ce compte-rendu ?")?>")) {
				if ($(this).attr("data-src")==$("#id").val())
					$("#id").val("");
				$.ajax({method: "POST",url: "/ajax/delete.php",data: { type:"PV", id:$(this).attr("data-src")}
				}).done(function( msg ) {if (msg!="") alert(msg); });

				refresh('#PVliste',"/popup/pv_load.php");
			}
		});
		$("#PVliste").delegate(".loadPV","click",function() {
			fetch('/ajax/loadpv.php?id='+$(this).attr("data-src"))
				.then(response => {
					if (!response.ok) {
						throw new Error("<?=T_("Erreur reseau lors de la recuperation des donnees.")?>");
					}
					return response.json();
				})
				.then(data => {
					if (data.error) {
						alert (data.errorMsg);
					} else if (confirm("<?=T_("Etes-vous sur de vouloir ecraser le contenu de l'editeur avec le compte-rendu charge ?")?>")) {
						data.id=$(this).attr("data-src");
						localStorage.setItem("savedata", JSON.stringify(data));
						load();
						$("#saved").val("");
					}
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
