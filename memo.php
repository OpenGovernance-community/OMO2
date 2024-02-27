<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared/telegram.php");
	
	// Initialise le login
	$connected=checklogin();
	
	// *********************************************************
	// Affichage de l'interface d'administration
	// *********************************************************

	if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
?>		
<html>
	<head>
		<title>EasyCircle - <?=T_("Dessinez votre organization !");?>?></title>
		<meta charset="utf-8">
		
		<!-- JQuery et jquery UI -->
		<script src="https://code.jquery.com/jquery-3.6.0.js"></script>
		<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
		<script>
			
		// Change JQueryUI plugin names to fix name collision with Bootstrap.
		$.widget.bridge('uitooltip', $.ui.tooltip);
		$.widget.bridge('uibutton', $.ui.button);
		</script>
		
		<!-- Bootstrap (for html editor) Summernote-->
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.bundle.min.js"></script>

		<!-- include summernote css/js -->
		<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
		<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
	
		<!-- Fonctions partagées entre plusieurs pages -->
		<script src="shared_functions.js"></script>
		<link href="shared_css.css" rel="stylesheet">
		
		<!-- Script Paypal -->
		<script src="https://www.paypal.com/sdk/js?client-id=AYZnt2y7GXObIwaEE4lE00M5aqQbPnZo2ghT8323MbwnHI9dxGtLLVAQ4LLNVZnPbr9usFpnpra-lvSL&vault=true&intent=subscription" data-sdk-integration-source="button-factory" data-namespace="paypal_sdk"></script>
		<script src="https://www.paypalobjects.com/donate/sdk/donate-sdk.js" charset="UTF-8"></script>
		
		<!-- Script spécifique à la page -->
		<script>
			// Fonctions appelées après le chargement complet de la page
			$(function() {
				
				// **************************************
				// Colonne de gauche
				// **************************************
				
				// Adaptation en largeur de la colonne de gauche
				$( "#resizeelem" ).draggable({ axis: "x" ,

				  stop: function(event, ui) {
					$(".left").css("width",$(".left").width()+ui.position.left+4);
					$( "#resizeelem" ).css("left",0);
				  }
				});
				
	
				
				// ***************************************
				// Editeur HTML
				// ***************************************
				
				var mytoolbar= [
					['style', ['style']],
					['font', ['bold', 'italic', 'underline', 'clear']],
					['fontsize', ['fontsize']],
					['color', ['color']],
					['para', ['ul', 'ol', 'paragraph']],
					 ['insert', ['link', 'picture', 'video']],
					  ['view', ['fullscreen', 'help']],
				];
				  
				var mystyles= [
					'p','h1', 'h2','h3',
					{ title: 'Decision', tag: 'div', className: 'monstyledemenu', value: 'h4' },
					{ title: 'Tâche/action', tag: 'div', className: 'monstyledemenu', value: 'h5' },
					   
				];

				// *******************************************************
				// Menu utilisateur en haut
				// ******************************************************

				$("body").delegate("#profilbtn","click", function (e) {
					showPopup("popup/profil.php", "<?=T_("Profil personnel",true)?>");
				});
					
				// *******************************************************
				// Menu d'option à droite
				// ******************************************************
				
				// Difflrents boutons de la page
				$("#btn_save").click(function () {
					save();
				});
				$("#btn_new").click(function () {
					newDoc();
				});
				$("#btn_load").click(function () {
					load();
				});
				$("#btn_help").click(function () {
					showPopup("popup/help.php", "<?=T_("Aide",true)?>");
				});			
				$("#login").click(function () {
					showPopup("popup/login.php", "<?=T_("Se connecter",true)?>");
				});			
				$("#btn_download").click(function () {
					showPopup("popup/download.php", "<?=T_("Télécharger",true)?>");
				});			
				$("#popup_close").click(function () {
					closePopup(); 
				});
				$("#btn_zoom").click(function () {
					enterFullscreen(document.documentElement);  
				});
				$("#btn_parameters").click(function () {
					showPopup("popup/parameters.php", "<?=T_("Paramètres",true)?>");
				});			
				$("#btn_support").click(function () {
					showPopup("popup/support.php", "<?=T_("Soutenez-nous !",true)?>");
				});			
				
				// Activation des tooltips (peut être partout, mais essentiellement à droite
				$('[data-toggle="tooltip"]').tooltip()
				
				// ************** Menu user du haut **************************3
				
				// Changement de langue
				$("body").delegate("#lang","change",function (e) {
					// Pose un cookie pour la langue (cookie pour le rendre accessible du côté serveur)
					setCookie("lang",$(this).val(),365);
					// Recharge la page
					location.reload();
					
				});				

				// Chargement des données si sauvegardée localement
				if (localStorage.savecircle)
					load();
			});
			
			// *********************************************************
			// Définition des fonction appelées par les boutons
			// *********************************************************
			


					
			function load() {

				
			}
			
			function newDoc() {

			}
						
			function save() {

			}

		
		</script>
	
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
	
	
	.displayTab {height:100%; width:100%}
	.leftTab {height:100%; width:100%}
	.top {height:50px;}
	.left {height:calc(100% - 100px);width:300px; padding:2px;}
	.contentleft {height:100%; border-radius:5px;}
	.contentright {height:calc(100% - 4px); width:calc(100% - 4px); border-radius:5px;  overflow:auto;position:absolute; left:2px; top:2px;}
	.right {height:calc(100% - 100px); padding:2px; position:relative;}
	.bottom {height:50px;}
	.resize {width:5px;position:relative;}
	#resizeelem {width:5px;height:100%; cursor:e-resize;z-index:2}
	
	.odj {font-weight:bold; font-size:110%}
	
	.list-group-item {border:2px solid #DDDD; margin:2px; cursor:pointer; padding:5px 5px 5px 15px;}
	 ul:has(:nth-child(9)) .list-group-item {padding:0px 5px 0px 15px;}
	 ul:has(:nth-child(12)) .list-group-item:not(.active) {height:27px;overflow:hidden}
	.pv {min-height:40px; border:2px solid #DDDD; margin:2px;}
	
		.page {
			border:1px solid #BBBBBB;
			min-height:136px;
			margin:10px;
			padding:0px;
			box-shadow: 3px 3px 5px rgba(0,0,0,0.3);
			position:relative;
		}
		.content {padding:10px; background:#FFFFFF;}
		.page.selected .content{padding:8px}
		.page.selected {
			border-width:3px;
			padding:0px;
		}
		.page .title {background:#EEE; padding:5px;}
		
		.panel-heading {background:#eee;border-bottom:1px solid #ddd}
		.note-editable {background:#FFF}
		.tension-sortable:empty {min-height:60px; border:2px dotted #DDD;}
		
		
		.buttons {
			background: #EEE;
			padding: 3px;
			border: 1px solid #BBB;
			 
			border-bottom: 0px;
			border-radius: 5px 5px 0px 0px;
			position:absolute; 
			left:10px; top:-30px; 
			z-index:1;
			height:30px;
			display:none;}
		.buttons button {margin-left:2px; margin-right:2px;}
		.page.selected .buttons {display:inherit}
		div.menu {display:none;}
		div.menu.selected {display:inherit}
		
		.mainTitle {font-size:200%;width:100%}
		.horaires {font-color:#ccc;width:100%}
		.liketext {border:0px !important;}
		.liketext:focus {outline: none; border:1px solid black;}
	
	

	.content h4, .note-editable h4 {font-size:inherit; background:rgba(0,255,0,0.3); padding:5px;padding-left:20px;    padding-left: 35px;
    background-image: url(img/thumb-up.png);
    background-size: 21px;
    background-repeat: no-repeat;
    background-position: 8px;}
	.content h5, .note-editable h5 {font-size:inherit; background:rgba(255,255,0,0.3); padding:5px;padding-left:20px;    padding-left: 35px;
    background-image: url(img/clipboard.png);
    background-size: 21px;
    background-repeat: no-repeat;
    background-position: 8px;}
    
	.list-group-item:not(.active) input:not([type=checkbox]) {pointer-events:none}
	.list-group-item.active input::placeholder {
color: rgba(255,255,255,0.5);
}

	 .divedit:empty::after {
  content: attr(placeholder);
  position: absolute;
  left: 0px;
  top: 0px;
  color: #AAAAAA;
  z-index: 1; 
} 


	.list-group-item:has(input:checked) input.tension {text-decoration: line-through;}
	.divedit { display:block;}

	.imgbutton {width:30px;margin:10px;opacity:0.5}
	.imgbutton:hover {opacity:0.8}


#menu {position:fixed; top:0px; right:20px; border-radius: 0px 0px 10px 10px; padding:10px;background-color:#FFFFFF;box-shadow: 5px 5px 10px rgba(0,0,0,0.5)}
	  /* All your print styles go here */
	  @media print { 
		  input:autofill {
			  -webkit-box-shadow: 0 0 0px 1000px white inset;
			}

			input:-webkit-autofill {
			  -webkit-box-shadow: 0 0 0px 1000px white inset;
			}
		 .tooltip { display: none; }
		 .noPrint {display:none}
		 .list-group-item, .list-group-item.active {border:0px; border-bottom:1px solid black; margin:2px; background:#FFFFFF; border-radius:0px !important; color:#000}
		.top {padding-bottom:20px;}
	  	.displayTab {height:inherit !important; }
	  	.leftTab {height:inherit !important; }
		.buttons {display:none !important;}
		.right, .left {height:inherit; vertical-align:top; }
		.contentright, .contentleft {height:inherit;}
		.page {
			border:0px !important;
			border-bottom:1px solid #ccc !important;
			min-height:inherit;
			margin:10px;
			padding:10px !important;
		}
		input::placeholder {color:#FFF ; opacity:0 }
		.panel-heading, .note-resizebar, .note-status-output {display:none}
		.note-editable {padding:0px !important;}
		.note-editor {border:0px !important;}
		.page:has(.content:empty) {display:none}
		#listepresence tr:has(.divedit:empty) {display:none}
		#listepresence:not(:has(.divedit:not(:empty))) {display:none}
		.page:has(#listepresence):not(:has(.divedit:not(:empty))) {display:none}
		#menu, #tools {display:none}
		
	}
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
		<table class='displayTab' cellspacing=0 cellpadding=0><tr><td  class='top' colspan=4>
			<!-- <button id="save" style='float:right'>Sauver</button>
			<button id="load" style='float:right'>Charger</button> -->
			<input autocomplete="off" id='title' class='mainTitle liketext' placeholder='<?=T_("Nom de votre organisation",true);?>'></input><br>
			<input autocomplete="off" id='location' class='horaires liketext' placeholder='<?=T_("Lieu, date et horaires",true);?>'></input>
		
		</td></tr>
		<tr><td class='left'><div class='contentleft'>
			<table class='leftTab' cellspacing=0 cellpadding=0><tr><td class='odj'>
			<div><?=T_("Ordre du jour");?><span class='noPrint' style='float:right; background:#FFF; border-radius:5px 5px 0px 0px'><img src='img/addentry.png' class='imgbutton' style='margin:0px;' id='btn_add'  data-toggle='tooltip' data-placement='bottom' title='<?=T_('Ajouter une tension',true)?>'>  

			
			
			</span></div>
			</td></tr><tr><td style='height:100%; position: relative;vertical-align:top'><div class='screenOJ'>
			<ul id="sortable" class="tension-sortable list-group">
			  <li id='menu_1' class="list-group-item active ui-icon ui-icon-arrowthick-2-n-s" data="1"><table cellspacing=0 cellpadding=0 style='width:100%;'><tr><td><input type='checkbox' tabindex='-1' id='cb_1' class='cb'></td><td style='width:100%'><input autocomplete="off" id='tension_1' class='liketext tension' style='width:100%' placeholder='Description brève'></td></tr><tr><td></td><td><input autocomplete="off" id='qui_1' class='liketext' style='width:60%;font-size:70%' placeholder='Qui'><input autocomplete="off" id='duree_1' class='liketext duration' style='width:30%;font-size:70%;text-align:right' placeholder='Durée'></td></tr></table></li>


			</ul>
		
			</div>
			</td></tr><tr><td style='background:#eee' class='noPrint'>
			<?=T_("Durée totale");?> : <span id='totalTime'></span><br>
			<?=T_("Durée restante");?> : <span id='restTime'></span><br>
			<?=T_("Heure de fin");?> : <span id='finalTime'></span><br>
			</td></tr></table>
		</div></td><td class='resize'><div id='resizeelem'></div></td><td class='right'><div id='contentright' class='contentright'>
			
		
		</div></td><td rowspan="2" id='tools' style='width:50px; vertical-align:top;'>
<?	
		//<!-- bouton pour le zoom -->
		echo "<img src='img/expand.png' class='imgbutton' id='btn_zoom' data-toggle='tooltip' data-placement='right' title='".T_('Plein écran',true)."'>";

		//<!-- bouton pour un nouveau fichier -->
		echo "<img src='img/newfile.png' class='imgbutton' id='btn_new' data-toggle='tooltip' data-placement='right' title='".T_('Nouveau document',true)."'>";

		//<!-- bouton pour imprimer -->
		echo "<img src='img/printing.png' onclick='window.print();' class='imgbutton' id='btn_print' data-toggle='tooltip' data-placement='right' title='".T_('Imprimer',true)."'>";

		//<!-- bouton pour partager -->
		if ($connected)
		echo "<img src='img/share.png' class='imgbutton' id='btn_share' data-toggle='tooltip' data-placement='right' title='".T_('Partager',true)."'>";

		//<!-- bouton pour télécharger -->
		if ($connected)
		echo "<img src='img/download.png' class='imgbutton' id='btn_download' data-toggle='tooltip' data-placement='right' title='".T_('Télécharger',true)."'>";

		//<!-- bouton pour l'aide -->
		echo "<img src='img/question.png' class='imgbutton' id='btn_help' data-toggle='tooltip' data-placement='right' title='".T_('Afficher l\'aide',true)."'>";

		//<!-- bouton pour les parameẗres -->
		if ($connected)
		echo "<img src='img/settings.png' class='imgbutton' id='btn_parameters' data-toggle='tooltip' data-placement='right' title='".T_('Paramètres',true)."'>";
?>		
		</td></tr>
		<tr><td class='bottom' colspan=3><span style='float:right;'><img src='img/support.png' style='height:40px;' id='btn_support'></span></td></tr>
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
	
	
	
	
	// *********************************************************
	// Affichage d'un document sélectionné
	// *********************************************************
	else 
	{
	
	// Charge le document
	$doc=new \dbObject\Document();
	$doc->load($_GET["id"]);
	
	// Pour l'instant, réservé à l'auteur où à un mot de passe valide
	if ((!$doc->canView() && !isset($_GET["pwd"])) || (isset($_GET["pwd"]) && $_GET["pwd"] != $doc->get("codeview"))) {
		echo "<html><head>";
		writeHeadContent("Identification nécessaire");
		echo "</head><body>";
		echo "<div class='bkg' style='background:url(/img/memo.jpg); background-size:cover; height:100%; width:calc(100% - 600px);'></div>";
		echo "<div id='popup' style='opacity:1; right:0px;'>";
		include("popup/login.php");
		echo "</div></body></html>";
		exit;  // Quitte pour éviter un else
	} 
	// Défini une variable de session pour donner l'accès à ce fichier, afin de faciliter le visionnage des médias associés
	$_SESSION["doc_".$doc->getId()]=true;
	
?>
<html><head>
	<?=writeHeadContent("EasyMEMO - ".$doc->get("title"));?>

<style>
		* {  box-sizing: border-box;}
		body, table {font-family: Ubuntu;font-size: 11pt;font-style: normal;font-variant: normal;font-weight: 400;}	
		.memo_resume {background:rgba(0,0,0,0.1); padding:10px; border-radius:5px;font-style:italic;}
		.media-div {height:50px; padding-left:60px;
		background-size: contain;
		position:relative;
		background-repeat:no-repeat;
		background-position: left center;
		background-color: #eee;
		border-radius: 10px;
		overflow: hidden;
		margin-bottom: 5px;}

		.memo_footer {
			position:absolute; bottom:0px; color:#555555; text-decoration:italic;
		}
				
	@media screen {
		#tools {
			position:fixed; height:100%; width:50px; top:0px; right:0px; background: var(--midlow-bg-color);
		}
		html, body {background:#EEEEEE}

		.memo_doc {
			margin:auto; 
			margin-top:30px;
			margin-bottom:10px; 
			padding:20px;
			min-height:calc(100vh - 40px);
			max-width:800px;
			width:100%;
			border:1px solid #BBBBBB;
			background:#FFFFFF;
			box-shadow: 5px 5px 5px rgba(0,0,0,0.3);
			position:relative;
		}
		.import_txt {border:1px dashed #DDDDDD}
		.footer{
			max-width:800px;
			width:100%; color:rgba(0,0,0,0.5);
			margin:auto}
	}
	@media only screen and (max-width: 900px) {
		#tools {position:relative; width:100%; height:inherit;height:50px; padding-top:0px !important;}
		body  {overflow:auto}
}
	@media print {
	  .footer {
		color:rgba(0,0,0,0.5);
		position: fixed;
		width:100%;
		bottom: 0;
	  }		
	  #tools {
		  display:none;
		}
	}
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
		echo "<form name='logoutform' id='logoutform' action='/ajax/login.php' class='ajax' style='margin:0px;display:inline-block'><button id='logoutbtn' name='logoutbtn' value='1' type='button'>".T_("Se déconnecter")."</button></form>";		
	} else {
			echo "<button id='login'>".T_("Se connecter")."</button>";
		}
?>	
			</div>
<?
	
	// Et l'affiche
	echo "<div class='memo_doc'>";
	echo "<h1>".$doc->get("title")."</h1>";
	echo "<div class='memo_resume'>".$doc->get("description")."</div>";
	echo "<div class='memo_content'>".$doc->get("content")."</div>";
	
	// Regarde s'il y a des médias connectés à ce document, et les liste
	$medias=$doc->getMedias();
	if (count($medias)>0) {
		echo "<h3>Medias associés:</h3><div >";
		foreach ($medias as $media) {
			

			// Sons
			if ($media->get("IDtype")==1) {
				echo "<div class='media-div' style='background-image:url(/img/icon_sound.png);'>";
				echo '<audio controls style="float:right">';
				echo '  <source src="/shared/getfile.php?id='.$media->getId().'" type="audio/ogg">';
				echo '</audio>';
				echo "<div class='vertical'>".$media->get("title")."</div>";
				echo "</div>";
			} else
			// Images
			if ($media->get("IDtype")==2) {
				echo "<div class='media-div' style='background-image:url(/img/icon_image.png);'>";
				echo "<img style='float:right;' src='/shared/getImg.php?url=".urlencode("https://systemdd.ch/shared/getfile.php?id=".$media->getId())."&x=75&y=50&ext=png'>";
				echo "<div class='vertical'><div>".$media->get("title")."</div>";
				echo "<a href='/shared/getfile.php?id=".$media->getId()."'>Cliquez ici pour voir l'image</a>"."</div>";
				echo "</div>";
			}

		}
		echo "</div>";
	}
	
	
	echo "<div class='memo_footer'>"."Créé par ".$doc->get("user")->get("username").", le ".$doc->get("datecreation")->format("d.m.Y")."</div>";
	echo "</div>";
	echo "<div id='tools' style='padding-top:77px;'>";
	

		//<!-- bouton pour le zoom -->
		echo "<img src='/img/expand.png' class='imgbutton' id='btn_zoom' data-toggle='tooltip' data-placement='right' title='".T_('Plein écran',true)."'>";

		//<!-- bouton pour éditer -->
		if ($connected && $doc->canEdit())
		echo "<img src='/img/icon_edit.png' class='imgbutton' id='btn_edit' data-toggle='tooltip' data-placement='right' title='".T_('Editer le document',true)."'>";

		//<!-- bouton pour imprimer -->
		echo "<img src='/img/printing.png' onclick='window.print();' class='imgbutton' id='btn_print' data-toggle='tooltip' data-placement='right' title='".T_('Imprimer',true)."'>";

		//<!-- bouton pour partager -->
		if ($connected)
		echo "<img src='/img/share.png' class='imgbutton' id='btn_share' data-toggle='tooltip' data-placement='right' title='".T_('Partager',true)."'>";

		//<!-- bouton pour télécharger -->
		if ($connected)
		echo "<img src='/img/download.png' class='imgbutton' id='btn_download' data-toggle='tooltip' data-placement='right' title='".T_('Télécharger',true)."'>";

		//<!-- bouton pour l'aide -->
		echo "<img src='/img/question.png' class='imgbutton' id='btn_help' data-toggle='tooltip' data-placement='right' title='".T_('Afficher l\'aide',true)."'>";

		//<!-- bouton pour les paramẗres -->
		if ($connected)
		echo "<img src='/img/settings.png' class='imgbutton' id='btn_parameters' data-toggle='tooltip' data-placement='right' title='".T_('Paramètres',true)."'>";
		
	
	
	echo "</div>";
	echo "<div id='popupbackground'></div>";
	echo "<div id='popup'><div id='popup_content'></div><div id='popup_close'><button><img src='/img/icon_close.png'>".T_("Fermer")."</button></div></div>";


	}
?>
