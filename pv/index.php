<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	
	// Initialise le login
	$connected=checklogin();
?>
<html>
	<head>
		<?=writeHeadContent("Facilitez-vous la prise de PV !");?>
		<script src="/common/choice/highlight-palette.js?v=20260904-highlight-clear"></script>
<style>
  .sector-1 { fill: #FFFFFF; }
  .sector-2 { fill: #22AA22; }
  .sector-3 { fill: #CC0000; }
  .sector-4 { fill: #4BC0C0; }
  .interface-top:has(> input#id:valid) {padding-left: 30px; background-image:url(/img/diskette.png); background-size:20px 20px; background-position: 5px 5px; background-repeat:no-repeat}
  .interface-top:has(> input#id:valid):has(> input#saved:valid) {background-image:url(/img/diskette_warning.png);}
  input#id, input#saved {display:none}

</style>		
		<!-- Script spécifique à la page -->
		<?= commonPageScriptTags('/pv/editor.js', [
    'text' => T_("Veuillez préalablement fermer l'éditeur déjà ouvert."),
    'text2' => T_("Êtes-vous sûr de vouloir supprimer le point"),
    'text3' => T_("Le contenu sera définitivement perdu."),
    'text4' => T_("Charger un document"),
    'text5' => T_("Aide"),
    'text6' => T_("Télécharger"),
    'text7' => T_("Partager"),
    'text8' => T_("Paramètres"),
    'text9' => T_("Soutenez-nous !"),
    'text10' => T_("Description brève"),
    'text11' => T_("Qui"),
    'text12' => T_("Durée"),
    'text13' => T_("editer"),
    'text14' => T_("supprimer"),
    'text15' => T_("sauver"),
    'text16' => T_("annuler"),
    'text17' => T_("Aucunes données sauvegardée trouvées"),
    'text18' => T_("Avez-vous imprimé le PV en cours?\n\nLe contenu actuel sera effacé définitivement. Êtes-vous sûr de vouloir continuer ?"),
], 'pvEditorPageConfig') ?>
	
	<style>
		

	@media screen {
		.panel {margin-bottom:0px;}
		#backColorPalette.note-holder-custom, #foreColorPalette.note-holder-custom, .note-color-select {display:none}
		
		.page:has(.note-editor) .title {background:#FFD}
		.page:has(.note-editor)  {border-color:#AA0}

		body {overflow:hidden;}
		.interface-left { background:var(--light-bg-color)}
		.contentleft {  background:var(--white-bg-color)}
		.contentright {background:var(--white-bg-color) }
		.interface-right {  background:var(--light-bg-color)}
		#resizeelem { background:var(--midlow-bg-color);}

		.list-group-item.active {
			color:var(--dark-txt-color);
			background-color: var(--light-bg-color);
			border-color: var(--midlow-bg-color);
		}
		.list-group-item:not(.active):hover {background:var(--verylight-bg-color)}
		.list-group-item.active:hover {background:var(--midlow-bg-color); border-color:var(--midlow-bg-color)}
		
		
		
		input.type {display:none}
		 .list-group-item:has(input.type:not([value=""])):before {

			content: ""; /* Nécessaire pour afficher l'élément pseudo */
			position: absolute;
			top: -2px; /* Ajustez selon votre besoin */
			right: -2px; /* Ajustez selon votre besoin */
			width: 30px; /* Largeur du cercle */
			height: 30px; /* Hauteur du cercle */
			background-color:#FFFFFF;
			
			background-size: cover; /* Ajuste l'image pour couvrir le cercle */
			background-position: center; /* Centre l'image */
			background-repeat:no-repeat;
			border-radius: 5px; /* Fait un cercle */
			border: 2px solid #DDD; /* Bordure optionnelle */
		 }
		 .list-group-item:has(input.type[value="1"]):before {
			 background-image: url("/img/tension_1.jpg"); /* Image de fond */
			}
		 .list-group-item:has(input.type[value="2"]):before {
			 background-image: url("/img/tension_2.jpg"); /* Image de fond */
			}
		 .list-group-item:has(input.type[value="3"]):before {
			 background-image: url("/img/tension_3.jpg"); /* Image de fond */
			}
		 .list-group-item:has(input.type[value="4"]):before {
			 background-image: url("/img/tension_4.jpg"); /* Image de fond */
			}
		 .list-group-item:has(input.type[value="5"]):before {
			 background-image: url("/img/tension_5.jpg"); /* Image de fond */
			}

		  /* Autre couleur pour quand sélectionné */
		   .list-group-item.active:has(input.type[value]):before {
			  border-color: var(--midlow-bg-color);
		  }
		 
		.sortable-placeholder {height:60px}		
		.screenOJ {width:100%; padding-right:3px ; height:100%; overflow:auto;position: absolute;}
		.odj {background: var(--light-bg-color);}
	}
	
	.screenOJ H3 {border:1px solid black; background-color:#EEE; padding:5px; margin:2px;}
	
	
	.displayTab {height:100%; width:100%}
	.leftTab {height:100%; width:100%}
	.top {height:50px;}
	.interface-left {height:calc(100% - 100px);width:300px; padding:2px;}
	.contentleft {height:100%; border-radius:5px;}
	.contentright {height:calc(100% - 4px); width:calc(100% - 4px); border-radius:5px;  overflow:auto;position:absolute; left:2px; top:2px;}
	.interface-right {height:calc(100% - 100px); padding:2px; position:relative;}
	.bottom {height:50px;}
	.resize {width:5px;position:relative;}
	#resizeelem {width:10px;height:100%; cursor:e-resize;z-index:2; background-image: url(/img/dots.png);
  background-size: 14px;
  background-repeat: no-repeat;
  background-position: center;}
	
	.odj {font-weight:bold; font-size:110%}
	
	.list-group-item {border:2px solid #DDDD; margin:2px; cursor:pointer; padding:5px 5px 5px 5px;}
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
    background-image: url(/img/thumb-up.png);
    background-size: 21px;
    background-repeat: no-repeat;
    background-position: 8px;}
	.content h5, .note-editable h5 {font-size:inherit; background:rgba(255,255,0,0.3); padding:5px;padding-left:20px;    padding-left: 35px;
    background-image: url(/img/clipboard.png);
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

.print-value { display: none; }

	  /* All your print styles go here */
	  @media print { 

#locationandtime input { display: none; } #locationandtime .print-value { display: inline-block; }

		  
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
	  	.interface-left, .resize {display:none;}
		.buttons {display:none !important;}
		.interface-right, .interface-left {height:inherit; vertical-align:top; }
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
		#menu {display:none}
		  	.content h4, .note-editable h4 {font-size:inherit; background:rgba(0,255,0,0.3) !important; padding:5px;padding-left:20px;    padding-left: 35px;
    background-image: url(/img/thumb-up.png) !important;
    background-size: 21px !important;
    background-repeat: no-repeat !important;
    background-position: 8px !important;}
	.content h5, .note-editable h5 {font-size:inherit; background:rgba(255,255,0,0.3) !important; padding:5px;padding-left:20px;    padding-left: 35px;
    background-image: url(/img/clipboard.png) !important;
    background-size: 21px !important;
    background-repeat: no-repeat !important;
    background-position: 8px !important;}		
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
			<input autocomplete="off" id='title' class='mainTitle liketext' placeholder='<?=T_("Titre de la réunion",true);?>'></input><br>
			<div id='locationandtime'>
			<input autocomplete="off" id='location' class=' liketext' placeholder='<?=T_("Lieu",true);?>'></input>, <input type='date' autocomplete="off" id='dateevent' class=' liketext' placeholder='<?=T_("date",true);?>'></input>, <input type='time' autocomplete="off" id='starttime' class=' liketext' placeholder='<?=T_("heure de début",true);?>'></input>
			à <input type='time' autocomplete="off" id='endtime' class=' liketext' placeholder='<?=T_("heure de fin",true);?>'></input>
			</div>
		</td></tr>
		<tr><td class='interface-left'><div class='contentleft'>
			<table class='leftTab' cellspacing=0 cellpadding=0><tr><td class='odj'>
			<div><?=T_("Ordre du jour");?><span class='noPrint' style='float:right; background:#FFF; border-radius:5px 5px 0px 0px'><img src='/img/addentry.png' class='imgbutton' style='margin:0px;' id='btn_add'  data-toggle='tooltip' data-placement='bottom' title='<?=T_('Ajouter une tension',true)?>'>  
<?
		if ($connected)
			echo "<img id='btn_menuTension' src='/img/addfolder.png' class='imgbutton'  data-toggle='tooltip' data-placement='bottom' title='".T_('Ajouter une section',true)."' style='margin:0px;'>";
?>
			
			
			</span></div>
			</td></tr><tr><td style='height:100%; position: relative;vertical-align:top'><div class='screenOJ'>
			<ul id="sortable" class="tension-sortable list-group">
			  <li id='menu_1' class="list-group-item active ui-icon ui-icon-arrowthick-2-n-s" data="1"><table cellspacing=0 cellpadding=0 style='width:100%;'><tr><td><input type='checkbox' tabindex='-1' id='cb_1' class='cb'></td><td style='width:100%; padding-right:20px;'><input id='type_1' class='type' value=''><input autocomplete="off" id='tension_1' class='liketext tension' style='width:100%' placeholder='Description brève'></td></tr><tr><td></td><td><input autocomplete="off" id='qui_1' class='liketext' style='width:60%;font-size:70%' placeholder='Qui'><input type='hidden' id='realduree_1'><input autocomplete="off" id='duree_1' class='liketext duration' style='width:30%;font-size:70%;text-align:right' placeholder='Durée'></td></tr></table></li>


			</ul>
		
			</div>
			</td></tr><tr><td style='background:#eee; padding:10px; font-size:90%' class='noPrint'>
			<div id='time_graph'><svg id="pieChart" style="float:right;" height="55" width="55" viewBox="0 0 55 55">
			  <circle r="27" cx="28" cy="28" fill="white" />
			</svg></div>
			<?=T_("Durée totale");?> : <span id='totalTime'></span>'<br>
			<?=T_("Durée restante");?> : <span id='restTime'></span>'<br>
			<?=T_("Heure de fin");?> : <span id='finalTime'></span><span id='toMuchTime2' style='font-weight:bold; color:red'></span><br>


			</td></tr></table>
		</div></td><td class='resize'><div id='resizeelem'></div></td><td class='interface-right'><div id='contentright' class='contentright'>
			<div class='page' style='background:#f4f4f4; padding:10px;'>
				<div id='listepresence'>
				<div class='odj'><?=T_("Liste des participant-e-s");?>:</div>
				<table  style='width:100%'>
					<tr><td><?=T_("Présents");?>&nbsp;:&nbsp;</td><td style='width:100%'><span contenteditable=true id='participants' class='horaires liketext divedit' style='position:relative' placeholder='<?=T_("Liste des personnes présentes",true);?>'></span></td></tr>
					<tr><td><?=T_("Excusés");?>&nbsp;:&nbsp;</td><td style='width:100%'><span contenteditable=true id='excuses' class='horaires liketext divedit' style='position:relative' placeholder='<?=T_("Liste des personnes excusées",true);?>'></span></td></tr>
					<tr><td><?=T_("Absents");?>&nbsp;:&nbsp;</td><td style='width:100%'><span contenteditable=true id='absents' class='horaires liketext divedit' style='position:relative' placeholder='<?=T_("Liste des personnes absentes",true);?>'></span></td></tr>
					</table>
					<hr></div>
					<table  style='width:100%'><tr><td><?=T_("Secrétaire");?>&nbsp;:&nbsp;</td><td style='width:50%'><span contenteditable=true id='memoire' class='liketext divedit' style='position:relative' placeholder='<?=T_("Indéfini",true);?>'></span></td><td><?=T_("Facilitation");?>&nbsp;:&nbsp;</td><td style='width:50%'><span contenteditable=true id='facilitation' class='liketext divedit' style='position:relative' placeholder='<?=T_("Indéfini",true);?>'></span></td></tr></table>
			</div>
			
			
			<div class='page' id='page_1' data="1">
				<div class='title'><?=T_("Bienvenue");?></div>

				<div class='buttons'>
				<button class='edit' data='1'><?=T_("Editer");?></button>
				<button class='delete' data='1'><?=T_("Supprimer");?></button>
				<button class='save' style='display:none' data='1'><?=T_("Sauver");?></button>
				<button class='cancel' style='display:none' data='1'><?=T_("Annuler");?></button>
				</div>
				<div class='content'>


					
<!-- Texte d'intro si PV vide-->
<?=T_("<h2><b>Bienvenue sur l'éditeur spécial procès verbal de OpenMyOrganization</b></h2><p></p><h5>Pour démarrer un nouveau PV, effacez le texte de ce bloc ou cliquez sur Nouveau (en haut à droite)</h5><p></p><p>Voici un petit outil vous permettant facilement de prendre en main une réunion, en tenant un procès verbal sur un écran que vous pouvez partager. L'avantage, au regard d'un traitement de texte classique, est que l'ordre du jour reste constamment accessible, et qu'il est facile de naviguer entre les points.</p><p>Actuellement, il n'est pas possible de sauver les documents autrement qu'en les imprimant en PDF. Prochainement, il sera possible de sauvegarder les PV ou de les télécharger dans un format Word, vous permettant de finaliser la mise en page à l'issue de la réunion si vous le souhaitez.</p><h3>Vous pouvez utiliser la barre ci-dessus pour ajouter du formatage, comme:</h3><ul><li>Des listes à puces</li><li>Des textes <b>en gras</b> ou en <i>italique</i></li><li>Des couleurs de <font color='#000000' style='background-color: rgb(255, 255, 0);'>surlignage</font></li><li>Des <a href='https://www.linkedin.com/in/daviddraeyer/' target='_blank'>liens</a></li><li>Et même des images.</li></ul><h4>Dans les options de formatage, il existe des options particulières pour faire ressortir les décision.</h4>");?>


								</div>
			
			</div>
		
	
		
		</div></td><td rowspan="2" id='tools' style='width:50px; vertical-align:top;'>
<?	
		//<!-- bouton pour le zoom -->
		echo "<img src='/img/expand.png' class='imgbutton' id='btn_zoom' data-toggle='tooltip' data-placement='left' title='".T_('Plein écran',true)."'>";

		//<!-- bouton pour un nouveau fichier -->
		echo "<img src='/img/newfile.png' class='imgbutton' id='btn_new' data-toggle='tooltip' data-placement='left' title='".T_('Nouveau document',true)."'>";

		//<!-- bouton pour sauver -->
		if ($connected)
		echo "<img src='/img/save-file.png' class='imgbutton' id='btn_save' data-toggle='tooltip' data-placement='left' title='".T_('Enregistrer le document',true)."'>";

		//<!-- bouton pour charger -->
		if ($connected)
		echo "<img src='/img/up-arrow.png' class='imgbutton' id='btn_load' data-toggle='tooltip' data-placement='left' title='".T_('Charger un document',true)."'>";

		//<!-- bouton pour imprimer -->
		echo "<img src='/img/printing.png' onclick='window.print();' class='imgbutton' id='btn_print' data-toggle='tooltip' data-placement='left' title='".T_('Imprimer',true)."'>";

		//<!-- bouton pour partager -->
		if ($connected)
		echo "<img src='/img/share.png' class='imgbutton' id='btn_share' data-toggle='tooltip' data-placement='left' title='".T_('Partager',true)."'>";

		//<!-- bouton pour télécharger -->
		if ($connected)
		echo "<img src='/img/download.png' class='imgbutton' id='btn_download' data-toggle='tooltip' data-placement='right' left='".T_('Télécharger',true)."'>";

		//<!-- bouton pour l'aide -->
		echo "<img src='/img/question.png' class='imgbutton' id='btn_help' data-toggle='tooltip' data-placement='left' title='".T_('Afficher l\'aide',true)."'>";

		//<!-- bouton pour les paramẗres -->
		if ($connected)
		echo "<img src='/img/settings.png' class='imgbutton' id='btn_parameters' data-toggle='tooltip' data-placement='left' title='".T_('Paramètres',true)."'>";
?>		
		</td></tr>
		<tr><td class='interface-bottom' colspan=3><span style='float:right;'><img src='/img/support.png' style='height:40px;' id='btn_support'></span></td></tr>
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
  border-radius: 5px;
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

	</body>
</html>
<script>
	
	
function updatePrintValue(input) { let value = input.val(); if (input.attr('type') === 'date') { value = formatDate(value); } input.next('.print-value').text(value); } function formatDate(date) { const options = { year: 'numeric', month: 'long', day: 'numeric' }; return new Date(date).toLocaleDateString('fr-FR', options); } // Fonction pour mettre à jour les champs par script function updateInputValue(selector, value) { $(selector).val(value).trigger('input'); }	
	
$(function() {

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
	
$('input').each(function() 
{ $(this).after('<span class="print-value"></span>'); updatePrintValue($(this)); }).on('input change', function() { updatePrintValue($(this)); });	
	
	
	
// Champ de saisie autoresize
$.fn.textWidth = function(_text, _font){//get width of text with font.  usage: $("div").textWidth();
        var fakeEl = $('<span>').hide().appendTo(document.body).text(_text || this.val() || this.attr("placeholder") || this.text()).css('font', _font || this.css('font')),
            width = fakeEl.width();
        fakeEl.remove();
        return width;
    };

$.fn.autoresize = function(options){//resizes elements based on content size.  usage: $('input').autoresize({padding:10,minWidth:0,maxWidth:100});
  options = $.extend({padding:10,minWidth:0,maxWidth:10000}, options||{});
  $(this).on('input', function() {
    $(this).css('width', Math.min(options.maxWidth,Math.max(options.minWidth,$(this).textWidth() + options.padding)));
  }).trigger('input');
  return this;
}

$("#location").autoresize({padding:5,minWidth:0,maxWidth:600});
});

 

</script>
