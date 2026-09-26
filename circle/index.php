<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	
	// Initialise le login
	$connected=checklogin();
?>
<html>
	<head>

		<!-- D3.js -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js" charset="utf-8"></script>
		<script src="https://d3js.org/queue.v1.min.js"></script>

		<!-- stats -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/stats.js/r14/Stats.js"></script>
		
		<?writeHeadContent(T_("Dessinez votre organization !"),"EasyCIRCLE");?>
	
		<!-- Script spécifique à la page -->
		<?= commonPageScriptTags('/circle/editor.js', [
    'view' => !isset($_GET["view"]),
    'text' => T_("Nouvelle structure"),
    'text2' => T_("Charger un schéma"),
    'text3' => T_("Soutenez-nous !"),
    'text4' => T_("Ajouter un noeud"),
    'text5' => T_("Editer un noeud"),
    'text6' => T_("Déplacer un noeud"),
], 'circleEditorConfig') ?>


<script>
	// Script pour le glisser-déplacer
	     // Cibler le canvas
        let $canvas;
        let wasDragging = isDragging = false;
        $(document).ready(function() {
            let offsetX, offsetY;

			$('div#showPanel').click(function () {
				
				const $div = $('td.left');
				
				const isVisible = ($div.css('left') === '0px');
				if (isVisible) {
					// Calculer dynamiquement la position de fermeture
					const closeLeft = `-${$div.outerWidth()}px`;
					$div.animate({ left: closeLeft }, 500); // Cache la div
					
				} else {
					$div.animate({ left: '0' }, 500); // Montre la div
					
				}			
			});

            // Commencer le déplacement
            $("body").delegate("#canvas",'mousedown', function(event) {
				$canvas = $('#canvas');
                isDragging = true;

                // Calculer l'offset initial entre la souris et le coin du canvas
                offsetX = event.pageX;
                offsetY = event.pageY;
            });

            // Déplacer le canvas
            $(document).on('mousemove', function(event) {
                if (isDragging) {
					wasDragging=true;
					// Essaie de bouger les coordonnées du centre pour dessiner
					var v = [vOld[0]+(-event.pageX+offsetX)*2/(diameter / vOld[2]), vOld[1]+(-event.pageY+offsetY)*2/(diameter / vOld[2]), vOld[2]]; //The center and width of the new "viewport"
					offsetX = event.pageX;
					offsetY = event.pageY;
					zoomInfo.centerX = v[0];
					zoomInfo.centerY = v[1];
					zoomInfo.scale = diameter / v[2];
					console.log(diameter / v[2]);
					
					vOld=v;
                } else {
					wasDragging	=false;
				}
            });

            // Arrêter le déplacement
            $("body").delegate("#canvas",'mouseout', function(event) {
				if (isDragging) {
					isDragging = false;
					drawCanvas(context);
					drawCanvas(hiddenContext, true);
				}
				$('.popoverWrapper').remove(); 
				$('.popover').each(function() {
						$('.popover').remove(); 	
				}); 

			});

            $(document).on('mouseup', function() {
				if (isDragging) {
					isDragging = false;
					drawCanvas(context);
					drawCanvas(hiddenContext, true);
				}

            });
        });
    </script>

		<?php
$circleSharedOrganizationId = 0;
if (isset($_GET["view"])) {
    $org = new \dbObject\holon();
    $org->load(["accesskey", $_GET["view"]]);
    $circleSharedOrganizationId = (int)$org->getId();
}
?>
<?= commonPageScriptTags('/circle/renderer.js', [
    'view' => isset($_GET["view"]),
    'circleSharedOrganizationId' => $circleSharedOrganizationId,
    'view2' => (string)($_GET["view"] ?? ""),
], 'circleRendererConfig') ?>
	
	<style>
<?
	if (isset($_GET["view"])) {
		echo ".menuNode {display:none !important}";
	}
?>		

	@media screen {
		
		.filter_zone:has(.highlight) li {display:none}
		li:has(.highlight) {display:list-item !important;}
		.highlight {background:#FFFF00} 
		
		#role_list {padding:15px;}
		#role_list .data {display:none}
		#role_list .data:has(.highlight) {display:block}
		
		
.data_field	 {background:rgba(0,0,0,0.1); border-radius:var(--radius-md); padding:5px;margin-bottom:10px;}
.data_field::before {
		content: attr(title) " :"; /* Texte à afficher avant la liste */
		display: block; /* Pour que le texte soit affiché sur une nouvelle ligne */
		font-weight: bold; /* Exemple de style */
	}

		/* Conteneur de la switch */
.switch {
  position: relative;
  display: inline-block;
  width: 50px; /* Largeur du switch */
  height: 35px; /* Hauteur du switch */
}

/* Case à cocher (invisible) */
.switch input {
  display: none; /* Cache la case à cocher */
}

/* Le slider (apparence du switch) */
.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc; /* Couleur de fond du switch (désactivé) */
  border-radius: var(--radius-md); /* Pour arrondir les bords */
  transition: 0.2s; /* Animation fluide */
font-size: 18px;
    padding: 2px 1px;
}

/* Le "cercle" à l'intérieur du switch */
.slider::before {
  content: "";
  position: absolute;
  height: 30px;
  width: 30px;
  top:0px;
  left: -1px;
  bottom: 4px;
  background-color: var(--light-bg-color); /* Couleur du cercle */
  border-radius: 50%; /* Cercle parfait */
  transition: 0.2s; /* Animation fluide */
}

input:checked + .slider::before {
  content: "☰"; /* Icône lorsque activé */
  font-size: 20px;
  text-align: center;
}
input:not(:checked) + .slider::before {
  content: "🔘"; /* Icône lorsque désactivé */
  font-size: 20px;
  color: #fff;
  text-align: center;
}



/* État actif (case cochée) */
input:checked + .slider {
  /* background-color: #4caf50; /* Couleur du fond activé */
}

/* Déplacement du cercle en mode activé */
input:checked + .slider::before {
  transform: translateX(22px); /* Distance parcourue par le cercle */
  transform: translateX(22px); /* Distance parcourue par le cercle */
}

/* Ajout d'ombre pour un effet esthétique */
.slider {
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.2);
}

/* Affichage de la liste */
div#contentright:has(input#toggleSwitch:checked) div#chart {
	 display:none;
}
div#contentright:not(:has(input#toggleSwitch:checked)) div#role_list {
	 display:none;
}
		
		

		#tools {background:var(--midlow-bg-color)}
		#tools_scroll {
			overflow-y: auto; /* Activer le scroll */
			scrollbar-width: thin; /* Rendre la scrollbar fine (Firefox) */
			scrollbar-color: rgba(0,0,0,0.2) white; /* Couleurs invisibles (Firefox) */
		}

		/* Pour les navigateurs basés sur WebKit (Chrome, Safari, Edge) */
		#tools_scroll::-webkit-scrollbar {
			width: 4px; /* Rendre la scrollbar aussi fine que possible */			height: 4px; /* Même chose pour la scrollbar horizontale */
		}


		
		.left { background:var(--light-bg-color)}
		.contentleft {  background:var(--white-bg-color)}
		.contentright {background:var(--white-bg-color) }
		.right {  background:var(--light-bg-color)}
		.resize {width:10px;position:relative;}
		#resizeelem {width: 10px;
			height: 100%;
			cursor: e-resize;
			z-index: 2;
			background-image: url(/img/dots.png);
			background-size: 14px;
			background-repeat: no-repeat;
			background-position: center;
			background-color:var(--midlow-bg-color);
		};

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

	.left {height:calc(100% - 100px);width:400px; padding:2px;}
	.contentleft {height:calc(100% - 30px); border-radius:var(--radius-md);}
	.contentright {height:calc(100% - 4px); width:calc(100% - 4px); border-radius:var(--radius-md);  overflow:hidden;position:absolute; left:2px; top:2px;}
	.right {height:calc(100% - 100px); padding:2px; position:relative;}

	
	.odj {font-weight:bold; font-size:110%}
	
	
	div.menu {display:none;}
	div.menu.selected {display:inherit}
	
	.mainTitle {font-size:200%;width:100%}
	.horaires {font-color:#ccc;width:100%}

	div#showpanel {display:none}


	#menu {position:fixed; top:0px; right:20px; border-radius: 0px 0px var(--radius-md) var(--radius-md); padding:10px;background-color:#FFFFFF;box-shadow: 5px 5px 10px rgba(0,0,0,0.5)}


/* Adaptation graphique pour téléphone portable */

/* Supprime le menu du bas si l'écran n'est pas assez haut */
	@media screen and (max-height: 500px) {
		.interface-bottom {display:none; height:0px;overflow:hidden}
		
	}
	@media screen and (max-width: 700px) {
		td.left div#showPanel {display:block;width:40px; height:50px; position:absolute; right:-38px; bottom:30px; background: white; border-radius:0px var(--radius-md) var(--radius-md) 0px;overflow:hidden; border:2px solid var(--light-bg-color);border-width: 2px 2px 2px 0px; cursor:pointer; background-image:url(/img/loupe.png); background-size: contain;
        background-position: center;
        background-repeat: no-repeat;}
		td.resize {display:none}
		td.left {
			display: block;
			position: fixed;
			width: calc(100% - 50px);
			z-index: 2;
			left: calc(-100% + 50px);
			top: 40px;

			height: calc(100% - 90px);
		}
	}
		
	


	  /* All your print styles go here */
	  @media print { 
		  td.left {display:none;}
		  td.right {width:100% !important; left:0px;}
		  #canvas {width:100%}
		  input:autofill {
			  -webkit-box-shadow: 0 0 0px 1000px white inset;
			}

			input:-webkit-autofill {
			  -webkit-box-shadow: 0 0 0px 1000px white inset;
			}
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
		
		.switch {
display:none;
}
		
	}
	</style>
	</head>
	<body style='overflow:hidden;'>
		<div id="menu"><select id='lang'>
			
			<option value=''>Français</option>
			<option value='DE' <?=(isset($_COOKIE["lang"]) && $_COOKIE["lang"]=="DE"?" selected":"");?>>Deutch</option>
			<option value='EN' <?=(isset($_COOKIE["lang"]) && $_COOKIE["lang"]=="EN"?" selected":"");?>>English</option>
			<option value='ES' <?=(isset($_COOKIE["lang"]) && $_COOKIE["lang"]=="ES"?" selected":"");?>>Español</option>
			
			</select> 
<? 
	if ($connected) {
		echo "<button id='profilbtn'>".T_("Profil")."</button>";
		echo "<form name='logoutform' id='logoutform' action='/common/logout.php' class='ajax' style='margin:0px;display:inline-block'><button id='logoutbtn' name='logoutbtn' value='1' type='button'>".T_("Se déconnecter")."</button></form>";		
	} else {
			echo "<button id='login'>".T_("Se connecter")."</button>";
		}
?>	
			</div>
		<table class='displayTab' cellspacing=0 cellpadding=0><tr><td  class='interface-top' colspan=4>
			<!-- <button id="save" style='float:right'>Sauver</button>
			<button id="load" style='float:right'>Charger</button> -->
			<input type='text' id='id' value='' required pattern="[0-9]{1,}">
			<input type='text' id='saved' value='' required pattern="[0-9]{1,}">
			<input autocomplete="off" id='title2' class='mainTitle liketext' autocomplete="off" placeholder='<?=T_("Nom de votre organisation",true);?>'></input><br>
		
		</td></tr>
		<tr><td class='left'><div id='showPanel'></div><div class='contentleft'>
			<table class='leftTab' cellspacing=0 cellpadding=0><tr><td class='odj'>
			<div>
			</div>
			</td></tr><tr><td style='height:100%; position: relative;vertical-align:top'><div class='screenOJ'>

		
			</div>
			</td></tr></table>
		</div>
		<div style='height:30px; padding:4px;' class='noPrint'>
			
			<input type='text' id='quickfilter' placeholder='Filtre rapide'>
		</div>
		
		
		</td><td class='resize'><div id='resizeelem'></div></td><td class='right'><div id='contentright' class='contentright'>
			
		<div id="chart"></div>
		<div id="role_list" class='filter_zone' style='height:100%; overflow-y:auto'></div>
		<div class="switch" style='position:absolute; bottom:5px; right:25px;'>
		  <input type="checkbox" id="toggleSwitch" />
		  <label for="toggleSwitch" class="slider">🔘 ☰</label>
		</div>
		
		</div></td><td rowspan="2" id='tools' style='width:50px; vertical-align:top;'><div id='tools_scroll' style='height:100%; width:100%; overflow-y:auto'>
<?	
		//<!-- bouton pour le zoom -->
		echo "<img src='/img/expand.png' class='imgbutton' id='btn_zoom' data-toggle='tooltip' data-placement='right' title='".T_('Plein écran',true)."'>";

if (!isset($_GET["view"])) {

		//<!-- bouton pour un nouveau fichier -->
		echo "<img src='/img/newfile.png' class='imgbutton' id='btn_new' data-toggle='tooltip' data-placement='right' title='".T_('Nouveau document',true)."'>";

		//<!-- bouton pour sauver -->
		if ($connected)
		echo "<img src='/img/save-file.png' class='imgbutton' id='btn_save' data-toggle='tooltip' data-placement='left' title='".T_('Enregistrer le schéma',true)."'>";

		//<!-- bouton pour charger -->
		if ($connected)
		echo "<img src='/img/up-arrow.png' class='imgbutton' id='btn_load' data-toggle='tooltip' data-placement='left' title='".T_('Charger un schéma',true)."'>";
}

		//<!-- bouton pour imprimer -->
		echo "<img src='/img/printing.png' onclick='window.print();' class='imgbutton' id='btn_print' data-toggle='tooltip' data-placement='right' title='".T_('Imprimer',true)."'>";


		//<!-- bouton pour l'aide -->
		echo "<img src='/img/question.png' class='imgbutton' id='btn_help' data-toggle='tooltip' data-placement='right' title='".T_('Afficher l\'aide',true)."'>";

		//<!-- bouton pour les parameẗres -->
		if ($connected)
		echo "<img src='/img/settings.png' class='imgbutton' id='btn_parameters' data-toggle='tooltip' data-placement='right' title='".T_('Paramètres',true)."'>";
?>		</div>
		</td></tr>
		<tr><td class='interface-bottom' colspan=3><span style='float:right;'><img src='/img/support.png' style='height:40px;' id='btn_support'></span><a href='/' target='_blank' style='display:inline-block; cursor:pointer; height:70%; width:115px;'></a></span></td></tr>
		</table>
		<div id='popupbackground'></div>
		<div id='popup'><div id='popup_content'></div><div id='popup_close'><button><img src='/img/icon_close.png'><?=T_("Fermer");?></button></div></div>

		<style>
.support-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;

  background: #0a1e3c;
  color: white;

  overflow: hidden;
  height: 46px;

  transition: height 0.3s ease;
  z-index: 1000;
}

.support-inner {
  display: flex;
  align-items: center;
  gap: 1rem;

  max-width: 1100px;
  margin: 0 auto;
  padding: 0.6rem;
}

.support-logo {
  width: 32px;
  height: 32px;
}

.support-text {
  flex: 1;
}

.support-text .short {
	font-size: 120%;
}
.support-text .long {
  display: block;
  opacity: 0;
  max-height: 0;
  transition: all 0.3s ease;
}

/* Expansion differee */
.support-bar.is-expanded {
  height: 140px;
}

.support-bar.is-expanded .support-text .long {
  opacity: 1;
  max-height: 100px;
  margin-top: 0.5rem;
}

.support-btn {
  background: #ff424d;
  color: white;
  padding: 0.4rem 1rem;
  border-radius: var(--radius-md);
  text-decoration: none;
  font-weight: bold;
  white-space: nowrap;
}
</style>
<div class="support-bar">
  <div class="support-inner">
    <img src="https://opengov.tools/img/logo-OGC.png" alt="OGC" class="support-logo">

    <div class="support-text">
      <span class="short">
        Soutenez la prochaine génération d'outils coopératifs.
      </span>

      <div class="long">
        Vous utiliser une version en développement de l'un des modules du future OpenMyOrganization. Pour contribuer au développement d'un outils libre et accessible soutenant l'être et le faire ensemble, soutenez le projet et rejoignez la communauté OpenGovernance.
		<p style='padding-top: 10px;'><a href="https://opengov.tools" target="_blank" class="support-btn">
		En savoir plus sur le projet
		</a></p>
	  </div>
	  
    </div>

    <a href="https://www.patreon.com/cw/OpenGovernance" target="_blank" class="support-btn">
      Contribuer
    </a>
  </div>
</div>

<script>
const supportBar = document.querySelector('.support-bar');
if (supportBar) {
  let supportBarHoverTimeout = null;

  supportBar.addEventListener('mouseenter', function () {
    supportBarHoverTimeout = window.setTimeout(function () {
      supportBar.classList.add('is-expanded');
    }, 1000);
  });

  supportBar.addEventListener('mouseleave', function () {
    if (supportBarHoverTimeout !== null) {
      window.clearTimeout(supportBarHoverTimeout);
      supportBarHoverTimeout = null;
    }
    supportBar.classList.remove('is-expanded');
  });
}
</script>

	</body>
</html>
