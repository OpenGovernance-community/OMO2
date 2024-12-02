<?

	error_reporting(E_ERROR | E_PARSE);
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	
	// Initialise le login
	$connected=checklogin();
	
	// Est-ce qu'il y a redirection ?

	if (isset($_GET["id"])) {
		$qr=new \dbObject\qr();
		if ($_GET["id"]=="image") {
			$qr->set("url",$_GET["url"]);
		} else
		if (isset($_GET["uid"])) {
			// Cherche le QR correspondant
			$qr->load([["IDuser",$_GET["uid"]],["shortcut",$_GET["id"]]]); // 
		} else {
			$qr->load(["uniquekey",$_GET["id"]]); // 
		}	
		if ($qr->getId()>0 || $_GET["id"]=="image") {
			// Trouvé! Est-ce une génération d'image ou une redirection
			if ($_GET["id"]=="image" || (isset($_GET["output"]) && $_GET["output"]=="image")) {
				// Génération et sortie de l'image
				require_once($_SERVER['DOCUMENT_ROOT']."/lib/phpqrcode/qrlib.php");
				header ('Content-Type: image/jpg');
				header('Content-Disposition: inline; filename="QR_'.$qr->get("uniquekey").'_SD2.jpg"');

				$imageFond = imagecreatetruecolor(600, 600);

				ob_start();
				if ($_GET["id"]=="image")
					QRcode::png($qr->get("url"), null, QR_ECLEVEL_H, 10); // Génération du QR code
				else
					QRcode::png("https://systemdd.ch/qr/".(isset($_GET["uid"])?$_GET["uid"]."/":"").$_GET["id"], null, QR_ECLEVEL_H, 10); // Génération du QR code

				// créer une image à partir de la chaîne de caractères générée par qrlib
				$image = imagecreatefromstring(ob_get_contents());
				imagecopyresized($imageFond, $image,0, 0, 0, 0, 600,600,imagesx($image),imagesy($image));
				ob_end_clean();
			
				// Ajoute un rond blanc
				$white = imagecolorallocate($imageFond, 255, 255, 255);
				imagefilledellipse($imageFond, 300, 300, 150, 150, $white);	
				
				// Ajoute le logo au milieu
				$imageIncruste = imagecreatefrompng($_SERVER['DOCUMENT_ROOT']."/img/logoD.png");
				imagecopyresized($imageFond, $imageIncruste,245, 240, 0, 0, 120,120,imagesx($imageIncruste), imagesy($imageIncruste));
				imagedestroy($imageIncruste);
				
				imagejpeg($imageFond, null, 80);
				
					// Libération de la mémoire
				imagedestroy($imageFond);				
				imagedestroy($image);					
				
				exit;				
				
			} else {
				// Modifie les infos d'accès
				$qr->set("datelastaccess",(new \DateTime()));
				$qr->set("cpt",$qr->get("cpt")+1);
				$qr->save();
				// Redirige simplement vers le bon répertoire
				header("Location: ".$qr->get("url"));
				die();
			}
		} else {
			// Pas trouvé, affiche une erreur
			echo "QR invalide";
			if(isset($_SERVER['REDIRECT_URL'])){
				$url_reecrite = $_SERVER['REDIRECT_URL'];
				echo "L'URL réécrite est : $url_reecrite";
				echo "Les paramètres sont : ".$_SERVER['QUERY_STRING'];
				echo "L'ID est : ".$_GET['id'];
			}
			exit;
		}
	} else {
		// Affichage complet
?>
<html>
	<head>

		<?writeHeadContent("EasyQR - ".T_("Gardez les liens"));?>
<style>
	@media screen {

		#tools {background:var(--midlow-bg-color)}
		.left { background:var(--light-bg-color)}
		.contentleft {  background:var(--white-bg-color)}
		.contentright {background:var(--white-bg-color) }
		.right {  background:var(--light-bg-color)}
		#resizeelem { background:var(--midlow-bg-color);}

		.list-group-item.active {
			color:var(--dark-txt-color);
			background-color: var(--light-bg-color);
			border-color: var(--midlow-bg-color);
		}
		.list-group-item:not(.active):hover {background:var(--verylight-bg-color)}

		.sortable-placeholder {height:60px}		
		.screenOJ {width:100%; padding-right:3px ; height:100%; overflow:auto;position: absolute;}
		.odj {background: var(--light-bg-color);}
	}
	
	.screenOJ H3 {border:1px solid black; background-color:#EEE; padding:5px; margin:2px;}
	#chart { position:relative; height:calc(100% - 100px); width:100%}
	
	.displayTab {height:100%; width:100%}
	.leftTab {height:100%; width:100%}

	.left {height:calc(100% - 100px);width:300px; padding:2px;}
	.contentleft {height:100%; border-radius:5px;}
	.contentright {height:calc(100% - 4px); width:calc(100% - 4px); border-radius:5px;  overflow:hidden;position:absolute; left:2px; top:2px;}
	.right {height:calc(100% - 100px); padding:2px; position:relative;}

	.resize {width:5px;position:relative;}
	#resizeelem {width:5px;height:100%; cursor:e-resize;z-index:2}
	
	.odj {font-weight:bold; font-size:110%}
	
	
	div.menu {display:none;}
	div.menu.selected {display:inherit}
	
	.mainTitle {font-size:200%;width:100%}
	.horaires {font-color:#ccc;width:100%}

	.qr_list {
		padding: 5px;
	}
	.add_button {border-radius:50%; background-color:#0FF; position:absolute; bottom:20px; right:20px; width:40px; height:40px; text-align:center; font-size:35px;}


	#menu {position:fixed; top:0px; right:20px; border-radius: 0px 0px 10px 10px; padding:10px;background-color:#FFFFFF;box-shadow: 5px 5px 10px rgba(0,0,0,0.5)}


</style>
	</head>
	<body>
		<div id="menu"><select id='lang'>
			
			<option value=''>Français</option>
			<option value='DE' <?=(isset($_COOKIE["lang"]) && $_COOKIE["lang"]=="DE"?" selected":"");?>>Deutch</option>
			<option value='EN' <?=(isset($_COOKIE["lang"]) && $_COOKIE["lang"]=="EN"?" selected":"");?>>English</option>
			<option value='ES' <?=(isset($_COOKIE["lang"]) && $_COOKIE["lang"]=="ES"?" selected":"");?>>Español</option>
			
			</select> 
<? 
	if ($connected) {
		echo "<button id='profilbtn'>".T_("Profil")."</button>";
		echo "<form name='logoutform' id='logoutform' action='ajax/login.php' class='ajax' style='margin:0px;display:inline-block'><button id='logoutbtn' name='logoutbtn' value='1' type='button'>".T_("Se déconnecter")."</button></form>";		
	} else {
			echo "<button id='login'>".T_("Se connecter")."</button>";
		}
?>	
			</div>
		<table class='displayTab' cellspacing=0 cellpadding=0><tr><td  class='interface-top' colspan=4>
			<!-- <button id="save" style='float:right'>Sauver</button>
			<button id="load" style='float:right'>Charger</button> -->
			<div class='mainTitle' >EasyQR</div>
			<input autocomplete="off" id='location' class='horaires liketext' placeholder='<?=T_("Lieu, date et horaires",true);?>'></input>
		
		</td></tr>
		<tr><td class='left'><div class='contentleft'>
			<table class='leftTab' cellspacing=0 cellpadding=0><tr><td class='odj'>
			<div></div>
			</td></tr><tr><td style='height:100%; position: relative;vertical-align:top'><div class='screenOJ'>

		
			</div>
			</td></tr><tr><td style='background:#eee' class='noPrint'>
				<!-- Bas de la colonne de gauche -->
			</td></tr></table>			
		</div></td><td class='resize'><div id='resizeelem'></div></td><td class='right'><div id='contentright' class='contentright'>




			<table class='leftTab' cellspacing=0 cellpadding=0><tr><td class='odj'>
			</td></tr><tr><td style='height:100%; position: relative;vertical-align:top'><div class='screenOJ'>

			<!-- contenu -->
<?
	// Affiche tous les QR code de l'utilisateur
	if (isset($_SESSION["currentUser"]) && $_SESSION["currentUser"]>0) {
		// Charge et affiche les différents memo
		$qrs=new \dbObject\ArrayQR();
		$parameters=Array();
		$parameters["filter"]="IDuser=".$_SESSION["currentUser"];
		$qrs->load($parameters);

		echo "<div class='qr_list'>";
		foreach ($qrs as $qr) {
			// Affichage des codes QR, avec lien sur l'image
			echo "<div class='list_item qr_item' data-src='".$qr->getId()."'>";
			echo "<div>".$qr->get("description")."</div>";
			echo "<a href='https://".$_SERVER['SERVER_NAME']."/qr/image/".$qr->get("IDuser")."/".($qr->get("shortcut")?$qr->get("shortcut"):$qr->get("uniquekey"))."'>".$_SERVER['SERVER_NAME']."/qr/image/".$qr->get("IDuser")."/".($qr->get("shortcut")?$qr->get("shortcut"):$qr->get("uniquekey"))."</a>";
			echo "</div>";
		}
		echo "</div>";
		// Affichage du bouton pour ajouter un élément
		echo "<div class='add_button' id='add_qr'>+</div>";
		
?>
<script>
		// Script lorsque connecté
	$(function() {
		
		$(".qr_item").click(function() {
			showPopup("popup/qr.php?id="+$(this).attr("data-src"), "<?=T_("QR code",true)?>");
		});
		$("#add_qr").click(function() {
			showPopup("popup/qr.php", "<?=T_("QR code",true)?>");
		});
	});

</script>
<?
		
	} else {
		// Pas connecté, création de QR avec accès direct
		echo "<div style='text-align:center;'><div id='qr_place' style='background-size:cover;margin:auto; width:300px; height:300px; border:3px solid #aaa'></div>";
		echo "<input id='qr_caption'></div>";
?>
<script>
	// Script lorsque non connecté
	let refresh_fct;
	$(function() {
		

		
		function refreshQR() {
			$("#qr_place").css("background-image","url(/qr/image/?url="+encodeURIComponent($("#qr_caption").val())+")");
		}
		$("#qr_caption").on("keyup",function() {
			clearTimeout(refresh_fct);
			refresh_fct=setTimeout(refreshQR,1000);
			
		});
	});

</script>

<?		
		
		
	}
		


?>


			</div>
			</td></tr><tr><td style='background:#eee' class='noPrint'>

			</td></tr></table>		
		
		</div></td><td rowspan="2" id='tools' style='width:50px; vertical-align:top;'>
<?	
		//<!-- bouton pour le zoom -->
		echo "<img src='img/expand.png' class='imgbutton' id='btn_zoom' data-toggle='tooltip' data-placement='left' title='".T_('Plein écran',true)."'>";

		//<!-- bouton pour un nouveau fichier -->
		echo "<img src='img/newfile.png' class='imgbutton' id='btn_new' data-toggle='tooltip' data-placement='left' title='".T_('Nouveau document',true)."'>";

		//<!-- bouton pour l'aide -->
		echo "<img src='img/question.png' class='imgbutton' id='btn_help' data-toggle='tooltip' data-placement='left' title='".T_('Afficher l\'aide',true)."'>";

		//<!-- bouton pour les parameẗres -->
		if ($connected)
		echo "<img src='img/settings.png' class='imgbutton' id='btn_parameters' data-toggle='tooltip' data-placement='left' title='".T_('Paramètres',true)."'>";
?>		
		</td></tr>
		<tr><td class='interface-bottom' colspan=3><span style='float:right;'><img src='img/support.png' style='height:40px;' id='btn_support'></span></td></tr>
		</table>
		<div id='popupbackground'></div>
		<div id='popup'><div id='popup_content'></div><div id='popup_close'><button><img src='/img/icon_close.png'><?=T_("Fermer");?></button></div></div>

<script type="text/javascript">
var _iub = _iub || [];
_iub.csConfiguration = {"askConsentAtCookiePolicyUpdate":true,"enableFadp":true,"fadpApplies":true,"floatingPreferencesButtonDisplay":"bottom-right","lang":"fr","perPurposeConsent":true,"siteId":3500961,"whitelabel":false,"cookiePolicyId":75698617, "banner":{ "acceptButtonDisplay":true,"closeButtonDisplay":false,"customizeButtonDisplay":true,"explicitWithdrawal":true,"listPurposes":true,"position":"bottom","rejectButtonDisplay":true,"showTitle":false }};
</script>
<script type="text/javascript" src="https://cs.iubenda.com/autoblocking/3500961.js"></script>
<script type="text/javascript" src="//cdn.iubenda.com/cs/iubenda_cs.js" charset="UTF-8" async></script>
	
	</body>
</html>
				
<?
		}
	
	
?>
