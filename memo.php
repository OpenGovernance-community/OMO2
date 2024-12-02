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
		<?writeHeadContent(T_("Enregistrer vos idées et vos prises de conscience !"),"EasyMEMO");?>
		
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
					
		

			jQuery.expr[':'].icontains = function(a, i, m) {
			  return jQuery(a).text().normalize("NFD").replace(/[\u0300-\u036f]/g, "").toUpperCase()
				  .indexOf(m[3].normalize("NFD").replace(/[\u0300-\u036f]/g, "").toUpperCase()) >= 0;
			};

	$("body").delegate("#btn_delete_memo", "click", function() {
		alldeleted=$(".memo_item:has(input:checked)");
		if (alldeleted.length==1)
				$msg="Voulez-vous vraiment effacer cet élément ?";
			else
				$msg="Voulez-vous vraiment effacer ces "+alldeleted.length+" éléments ?";
		if (confirm($msg)) {
			$(".memo_item:has(input:checked)").each(function(index,value) {
				// Appel la fonction pour supprimer 
				$.ajax({method: "POST",url: "/ajax/delete.php",data: { type:"Document", id:$(this).attr("data-src")}
				}).done(function( msg ) {if (msg!="") alert(msg); });						
			});
			// Raffraîchi la liste
			refresh('#contentright');
			showselectedmemooptions();
		}
	});
	
	function showselectedmemooptions() {
		alldeleted=$(".memo_item:has(input:checked)");
		if (alldeleted.length>=1) {
			$("#btn_delete_memo").show();
		} else $("#btn_delete_memo").hide();
	}
	
   function highlightText(text) {
        var content = $(".memo_list");
        var regex = new RegExp('(>[^<]*?)(' + text + ')([^<]*?<)', 'gi');

        content.find(".highlight").each(function() {
            $(this).replaceWith($(this).text()); // Remplace les balises <span> par leur contenu
        });

        var contentHTML = content.html();
        content.html(contentHTML.replace(regex, function(match, p1, p2, p3) {
            return p1 + '<span class="highlight">' + p2 + '</span>' + p3;
        }));
    }
	
	
	showselectedmemooptions();

	$("body").delegate("#chk_select_all","click", function () {
		// Si sélectionné, active toute les checkbox
		$( ".memo_item input[type='checkbox']" ).prop( "checked", $(this).is(':checked') );
		showselectedmemooptions();
	});

	$("body").delegate(".memo_item","click",function () {
		// Décoche tous les autres
		$(".memo_item input[type='checkbox']").prop( "checked", false);
		// Si sélectionné, active toute les checkbox
		elem=$(this).find("input[type='checkbox']" );
		elem.prop( "checked", !(elem.is(":checked")));
		showselectedmemooptions();
	});
	
	$("body").delegate(".memo_item input[type='checkbox']","click",function (e) {
		e.stopPropagation();
		showselectedmemooptions();
	});

	$("body").delegate("#quickfilter","keyup", function () {
		// Cache toute les lignes
		$(".memo_item").hide();
		// Affiche toute les lignes qui contiennent le texte
		$('.memo_item:icontains('+$(this).val()+')').show();
		highlightText($(this).val())
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
	.memo_item .highlight {background:#FF0}
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

	.group_memo:not(:has(.memo_item:not([style*="display: none"]))) {display:none}
	.memo_list {padding:5px}
	.list-group-item:has(input:checked) input.tension {text-decoration: line-through;}
	.divedit { display:block;}

	.imgbutton {width:30px;margin:10px;opacity:0.5}
	.imgbutton:hover {opacity:0.8}
		.memo_item {background:#EEE; padding:5px; border-radius:5px;margin-bottom:5px;}
		
		.memo_item:has(input:checked) {border:3px solid black;}
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
		<table class='displayTab' cellspacing=0 cellpadding=0><tr><td  class='interface-top' colspan=4>
			<!-- <button id="save" style='float:right'>Sauver</button>
			<button id="load" style='float:right'>Charger</button> -->
			<div class='mainTitle' >Votre nom</div>
			<input autocomplete="off" id='location' class='horaires liketext' placeholder='<?=T_("Lieu, date et horaires",true);?>'></input>
		
		</td></tr>
		<tr><td class='left'><div class='contentleft'>
			<table class='leftTab' cellspacing=0 cellpadding=0><tr><td class='odj'>
			<div><?=T_("Recherche");?><span class='noPrint' style='float:right; background:#FFF; border-radius:5px 5px 0px 0px'><img src='img/addentry.png' class='imgbutton' style='margin:0px;' id='btn_add'  data-toggle='tooltip' data-placement='bottom' title='<?=T_('Ajouter une tension',true)?>'>  
			</span></div>
			</td></tr><tr><td style='height:100%; position: relative;vertical-align:top'><div class='screenOJ'>
			<ul id="sortable" class="tension-sortable list-group">
			  <li id='menu_1' class="list-group-item active ui-icon ui-icon-arrowthick-2-n-s" data="1"><table cellspacing=0 cellpadding=0 style='width:100%;'><tr><td><input type='checkbox' tabindex='-1' id='cb_1' class='cb'></td><td style='width:100%'><input autocomplete="off" id='tension_1' class='liketext tension' style='width:100%' placeholder='Description brève'></td></tr><tr><td></td><td><input autocomplete="off" id='qui_1' class='liketext' style='width:60%;font-size:70%' placeholder='Qui'><input autocomplete="off" id='duree_1' class='liketext duration' style='width:30%;font-size:70%;text-align:right' placeholder='Durée'></td></tr></table></li>


			</ul>
		
			</div>
			</td></tr><tr><td style='background:#eee' class='noPrint'>
				<!-- Bas de la colonne de gauche -->
			</td></tr></table>			
		</div></td><td class='resize'><div id='resizeelem'></div></td><td class='right'><div id='contentright' class='contentright'>




			<table class='leftTab' cellspacing=0 cellpadding=0><tr><td class='odj'>
			<div style='font-weight:normal;'>&nbsp;<input type='checkbox' id='chk_select_all'> <?=T_("Tout sélectionner");?>
			
			<img src="/img/icon_delete.png" id='btn_delete_memo' class='imgbutton' style='margin:0px; background:var(--midlow-bg-color);border-radius:4px;'>
			
			<span class='noPrint' style='float:right; background:#FFF; border-radius:5px 5px 0px 0px'>
			<img src='/img/filter.png'  style='height:20px'><input id='quickfilter' placeholder='Filtre rapide' style='font-size: 13px; margin: 4px;'>	 
			</span></div>
			</td></tr><tr><td style='height:100%; position: relative;vertical-align:top'><div class='screenOJ'>
			
<?
	if (isset($_SESSION["currentUser"]) && $_SESSION["currentUser"]>0) {
		


	// Charge et affiche les différents memo
	$memos=new \dbObject\ArrayDocument();
	$parameters=Array();
	$parameters["order"]="datecreation DESC";
	$parameters["filter"]="IDuser=".$_SESSION["currentUser"];
	
	$memos->load($parameters);
	echo "<div class='memo_list'>";
	
	$now=new DateTime();
	$lastmonth=new DateTime();
	$lastmonth->modify('-1 month');
	$dayofweek=(int)$now->format('N');
	$dayofmonth=(int)$now->format('d');
		
$steps = json_decode('[
    {"duration": 0, "label": "'.T_('Aujourd\'hui',true).'"},
    {"duration": 1, "label": "'.T_('Hier',true).'"},
    {"duration": '.min($dayofweek,2).', "label": "'.T_('Cette semaine',true).'"},
    {"duration": '.max(2,$dayofweek).', "label": "'.T_('La semaine passée',true).'"},
    {"duration": '.max($dayofweek+1,$dayofweek+7).', "label": "'.T_('Ce mois',true).'"},
    {"duration": '.max($dayofmonth,$dayofweek+8).', "label": "'.T_('Le mois passé',true).'"},
    {"duration": '.($dayofmonth+cal_days_in_month(CAL_GREGORIAN, (int)$lastmonth->format("m"), (int)$lastmonth->format("y"))).', "label": "'.T_('Cette année',true).'"},
    {"duration": 730, "label": "'.T_('L\'année passée',true).'"},
    {"duration": 1100, "label": "'.T_('Précédemment',true).'"},
    {"duration": 9999, "label": "'.T_('Trop loin',true).'"}
]');
//print_r ($steps);
	$cpt=0;
	echo "<div>";
	$formatter = new IntlDateFormatter('fr-FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);
	$formatter->setPattern('d MMM');

	foreach ($memos as $memo) {
		
		// Calcul le nombre de jours de différence entre aujourd'hui et la date de l'événement en cous
		$interval = $memo->get("datecreation")->setTime(0, 0)->diff($now->setTime(0, 0));		
		$interval=(int)$interval->format('%a');
		//echo $interval;
		// Cherche le label qui correspond le mieux, à partir de où on en est ($cpt)
		while ($steps[$cpt+1]->duration<=$interval && $cpt<10) {
			$cpt+=1;
			//echo $steps[$cpt+1]->duration." ".$interval;
		}
		// Affiche ce label si pertienent, et passe au suivant
		if ($interval>=$steps[$cpt]->duration) {
			echo "</div><div class='group_memo'><div><b>".$steps[$cpt]->label."</b></div>";
			$cpt+=1;
		}
			
		echo "<div class='memo_item' data-src='".$memo->get("id")."'>";
		echo "<input type='checkbox'> ";
		//echo "cpt:".$cpt." duration:".$steps[$cpt]->duration." interval:".$interval;
		echo "<a target='_blank' href='/memo/".$memo->get("id")."'>";
		echo $formatter->format($memo->get("datecreation"))." | ".$memo->get("title");
		echo "</a>";
		echo "<div style='font-size:80%; color:rgba(0,0,0,0.7);'>".$memo->get("keywords")."</div>";
		echo "</div>";
		
	}
	echo "</div>";
	echo "</div>";
	} else {
		echo "Le login est nécessaire pour utiliser EasyMEMO. Veuillez créer un compte ou vous connecter.";
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
	<?=writeHeadContent($doc->get("title"),"EasyMEMO");?>

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
			 width:50px; background: var(--midlow-bg-color);
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
		.memo_content {padding:10px;}
		.import_txt {border:1px dashed #DDDDDD}
		.footer{
			max-width:800px;
			width:100%; color:rgba(0,0,0,0.5);
			margin:auto}
	}
	@media only screen and (max-width: 900px) {
		.mainTable td {display:block;}
		#tools {padding-top:0px !important; width:100%;}
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
<script>

	$(function() {
		// Activation des tooltips (peut être partout, mais essentiellement à droite
		$('[data-toggle="tooltip"]').tooltip();
				
	});
</script>
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
			
<style>
	.tabs {margin-top:5px; margin-bottom:5px;}
	.tabs_header {position:relative;    white-space: nowrap; overflow:hidden; overflow-x: auto;scrollbar-width: thin;}
	.tab_header {cursor:pointer; position:relative; bottom:-1px; display:inline-block; margin-right:5px; border:1px solid #CCCCCC; background:#FFFFFF; border-radius:5px 5px 0px 0px; border-bottom:0px; padding:5px;}
	.tab_content {display: none; border:1px solid #DDDDDD; }
	.tab_content.active {display:block;}
	.tab_header:hover:not(.active) {  background: #EEEEEE;}
	.tabs_content:not(:has(.tab_content.active)) .tab_content:first-child {display:block;}
	.tab_header.active, .tabs_header:not(:has(.tab_header.active)) .tab_header:first-child {background:#DDDDDD}
	.btn_add_text {display:inline-block; border-radius:50%; width:20px; height:20px; text-align:center; line-height:15px;font-size:25px; overflow:hidden;background:#CCCCCC; vertical-align:middle} 
	.alttext_option {background:#DDDDDD; text-align:right;}
	.mainTable {height:100%; width:100%;table-layout:fixed}
	.btn_option_txt {background:rgba(255,255,255,0.5); cursor:pointer; padding:2px; display:inline-block; margin:2px; border-radius:3px;}
	.btn_option_txt:hover {background:#FFFFFF}
</style>
<script>
  $(function () {
	 $(".btn_add_text").click(function() {
		 // Ouvre le générateur de texte

		showPopup("/popup/transcript.php?id=<?=$doc->get("id")?>", "<?=T_("Transcript",true)?>");
	});

    $('.tab_header').on('click', function () {
      const $clickedTab = $(this); // Onglet cliqué
      const $tabsContainer = $clickedTab.closest('.tabs'); // Conteneur parent (classe "tabs")

      // Supprimer la classe "active" de tous les onglets et contenus dans ce conteneur
      $tabsContainer.find('.tab_header').removeClass('active');
      $tabsContainer.find('.tab_content').removeClass('active');

      // Ajouter la classe "active" à l'onglet cliqué
      $clickedTab.addClass('active');

      // Activer le contenu correspondant
      const contentId = $clickedTab.attr('id') + '_content'; // ID de l'onglet + "_content"
      $tabsContainer.find('#' + contentId).addClass('active');
    });
    
     $('.btn_copy_txt').click(async function () {
                // Sélectionner le contenu de la DIV
                var memoContentDiv=$(this).parent().next('.memo_content');
                var htmlToCopy = memoContentDiv.html();

 // Utiliser le Clipboard API pour copier du contenu HTML
      try {
        await navigator.clipboard.write([
          new ClipboardItem({
            "text/html": new Blob([htmlToCopy], { type: "text/html" }),
            "text/plain": new Blob([memoContentDiv.text()], { type: "text/plain" }) // Texte brut pour compatibilité
          })
        ]);

        // Facultatif : notifier l'utilisateur
        alert('Contenu formaté copié dans le presse-papier !');
      } catch (err) {
        console.error('Échec de la copie : ', err);
        alert('Impossible de copier le contenu.');
      }
      
            });
    
  });

</script>
			
			
<?
	// Récupère les alternatives de texte
	$alttext=$doc->getAltText();
	
	// Et l'affiche
	echo "<table class='mainTable'><tr><td><div style='height:100%; overflow-y:auto;'>";
	echo "<div class='memo_doc'>";
	echo "<h1>".$doc->get("title")."</h1>";
	echo "<div class='memo_resume'>".$doc->get("description")."</div>";
	
	// Affiche le système d'onglets pour les différentes versions
	echo "<div class='tabs'><div class='tabs_header'>";
	echo "<span class='tab_header' id='original'>Original</span>";

	foreach($alttext as $txt) {
		echo "<span class='tab_header' id='txt_".$txt->getId()."'>".$txt->get("aiprompt")->get("title")."</span>";
	}
	echo "<span class='btn_add_text'>+</span>";
	echo "</div><div class='tabs_content'>";
	
	echo "<div class='tab_content' id='original_content'>";
	echo "<div class='memo_content'>".$doc->get("content")."</div>";
	echo "</div>";
	
	foreach($alttext as $txt) {
		echo "<div class='tab_content' id='txt_".$txt->getId()."_content'>";
		echo "<div class='alttext_option'>";
		echo "<span class='btn_edit_txt btn_option_txt' data-src='".$txt->getId()."'>edit</span> - <span class='btn_delete_txt btn_option_txt' data-src='".$txt->getId()."'>delete</span> - <span class='btn_refresh_txt btn_option_txt' data-src='".$txt->getId()."'>refresh</span> - <span class='btn_copy_txt btn_option_txt' data-src='".$txt->getId()."'>copy</span>";
		echo "</div>";
		echo "<div class='memo_content'>".str_replace("\n","<br>",$txt->get("text"))."</div></div>";
	}	
	
	echo "</div></div>";
	
	// Regarde s'il y a des médias connectés à ce document, et les liste
	$medias=$doc->getMedias();
	if (count($medias)>0) {
		echo "<h3>Medias associés:</h3><div >";
		foreach ($medias as $media) {
			

			// Sons
			if ($media->get("IDtype")==1) {
				echo "<div class='media-div' style='background-image:url(/img/icon_sound.png);'>";
				echo '<audio controls style="float:right; width:150px;">';
				echo '  <source src="/shared/getfile.php?id='.$media->getId().'" type="audio/ogg">';
				echo '</audio>';
				echo "<div class='vertical' style='margin-right:150px;'>".$media->get("title")."</div>";
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
	echo "</div>";
	echo "</td>";
	echo "<td id='tools' style='padding-top:77px; vertical-align:top'>";
	

		//<!-- bouton pour le zoom -->
		echo "<img src='/img/expand.png' class='imgbutton' id='btn_zoom' data-toggle='tooltip' data-placement='left' title='".T_('Plein écran',true)."'>";

		//<!-- bouton pour éditer -->
		if ($connected && $doc->canEdit())
		echo "<img src='/img/icon_edit.png' class='imgbutton' id='btn_edit' data-toggle='tooltip' data-placement='left' title='".T_('Editer le document',true)."'>";

		//<!-- bouton pour imprimer -->
		echo "<img src='/img/printing.png' onclick='window.print();' class='imgbutton' id='btn_print' data-toggle='tooltip' data-placement='left' title='".T_('Imprimer',true)."'>";

		//<!-- bouton pour partager -->
		if ($connected)
		echo "<img src='/img/share.png' class='imgbutton' id='btn_share' data-toggle='tooltip' data-placement='left' title='".T_('Partager',true)."'>";

		//<!-- bouton pour télécharger -->
		if ($connected)
		echo "<img src='/img/download.png' class='imgbutton' id='btn_download' data-toggle='tooltip' data-placement='left' title='".T_('Télécharger',true)."'>";

		//<!-- bouton pour l'aide -->
		echo "<img src='/img/question.png' class='imgbutton' id='btn_help' data-toggle='tooltip' data-placement='left' title='".T_('Afficher l\'aide',true)."'>";

		//<!-- bouton pour les paramẗres -->
		if ($connected)
		echo "<img src='/img/settings.png' class='imgbutton' id='btn_parameters' data-toggle='tooltip' data-placement='left' title='".T_('Paramètres',true)."'>";
		
	
	
	echo "</td>";
	echo "</tr></table>";
	echo "<div id='popupbackground'></div>";
	echo "<div id='popup'><div id='popup_content'></div><div id='popup_close'><button><img src='/img/icon_close.png'>".T_("Fermer")."</button></div></div>";
	echo "</body></html>";

	}
?>
<script>
$(function() {
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
					showPopup("/popup/memo/help.php", "<?=T_("Aide",true)?>");
				});			
				$("#login").click(function () {
					showPopup("/popup/login.php", "<?=T_("Se connecter",true)?>");
				});			
				$("#popup_close").click(function () {
					closePopup(); 
				});
				$("#btn_zoom").click(function () {
					enterFullscreen(document.documentElement);  
				});
				$("#btn_parameters").click(function () {
					showPopup("/popup/memo/parameters.php", "<?=T_("Paramètres",true)?>");
				});			
				$("#btn_support").click(function () {
					showPopup("/popup/support.php", "<?=T_("Soutenez-nous !",true)?>");
				});	
			});

</script>
