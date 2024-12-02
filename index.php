<?
	require_once("config.php");
	require_once("shared_functions.php");
	
	// Initialise le login
	$connected=checklogin();
?>
<html>
	<head>
		<title>EasyPV - <?=T_("Facilitez-vous la prise de notes !");?>?></title>
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
				
				// ***********************************
				// Page de droite
				// ***********************************
				
				// Sélection des pages
				$("body").delegate(".page:not(.selected)","click", function() {
					$(".page").removeClass("selected");
					$(".list-group-item").removeClass("active");
					$("#menu_"+$(this).attr("data")).click();
				});
				
				// Edition sur double click
				$("body").delegate(".note-editor","dblclick", function(e) {
					// évite que le double-click dans l'éditeur soit mal interprété
					e.stopPropagation();
				});
				$("body").delegate(".page","dblclick",function () {
					if ($(".note-editor").length>0) {
						alert ("<?=T_("Veuillez préalablement fermer l'éditeur déjà ouvert.",true);?>")
						$(".note-editor").focus();
					} else {
						$(this).find(".content").summernote({focus: true, toolbar: mytoolbar, styleTags: mystyles});
						// Cache le bouton edit et affiche le bouton sauver
						$(this).find("button.save").css("display","");
						$(this).find("button.cancel").css("display","");
						$(this).find("button.edit").css("display","none");
						$(this).find("button.delete").css("display","none");
					}
				});
				
				// ************ Boutons *******************
				
				// Supprimer une page
				$("body").delegate(".delete","click",function () {
					index=$(this).parents(".page").attr("data");
					txt=$("#tension_"+index).val();
					if (confirm("<?=T_("Êtes-vous sûr de vouloir supprimer le point",true);?> \""+(txt!=""?txt:"sans titre")+"\" ?\n\n<?=T_("Le contenu sera définitivement perdu.",true)?>")) {
						// Supprime les blocs (sortable et pv)
						$("#page_"+index).remove();
						$("#menu_"+index).remove();
						save();
					}
				});	
				
				// Editer une page, ouvre l'éditeur
				$("body").delegate(".edit","click",function () {
					// S'assure qu'aucun autre éditeur est déjà ouvert
					if ($(".note-editor").length>0) {
						alert ("<?=T_("Veuillez préalablement fermer l'éditeur déjà ouvert.")?>")
						$(".note-editor").focus();
					} else {

						$(this).parent().next().summernote({focus: true, toolbar: mytoolbar, styleTags: mystyles});
						// Cache le bouton edit et affiche le bouton sauver
						$(this).parent().find("button.save").css("display","");
						$(this).parent().find("button.cancel").css("display","");
						$(this).css("display","none");
						$(this).parent().find("button.delete").css("display","none");
					}
					
				});
				
				// Sauve le contenu de la page
				$("body").delegate(".save","click",function (){
					// Récupère le code HTML
					  var markup = $(this).parent().next().summernote('code');
					  // L'envoie en ajax pour le sauver
						$.post(window.location, { id: $(this).attr("data"), value: markup })
						  .done(function( data ) {
							//alert( "Sauvé!");
						  });				  
					  // Change la zone éditable en texte
					  $(this).parent().next().summernote('destroy');			
					// Cache le bouton edit et affiche le bouton sauver
					$(this).parent().find("button.edit").css("display","");
					$(this).parent().find("button.delete").css("display","");
					$(this).parent().find("button.cancel").css("display","none");
					$(this).css("display","none");
					// Sauve à chaque fois
					save();
					
				});
				
				// Annule l'édition de la page
				$("body").delegate(".cancel","click",function () {
							  
					// Change la zone éditable en texte
					$(this).parent().next().summernote('reset');			
					$(this).parent().next().summernote('destroy');			
					// Cache le bouton edit et affiche le bouton sauver
					$(this).parent().find("button.delete").css("display","");
					$(this).parent().find("button.edit").css("display","");
					$(this).parent().find("button.save").css("display","none");
					$(this).css("display","none");
				});	
						
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
				
				// Elements de la colonne de gauche ordonnable, avec effet miroir sur la colonne de droite
				$("#sortable").sortable({axis: "y", containment: ".screenOJ", connectWith: ".tension-sortable", placeholder: "sortable-placeholder", tolerance: "pointer",
					stop: function( event, ui ) {
						// Réordre la seconde liste en fonction 
						$.each($(".list-group-item"),function(index, value) {
							$("#page_"+$(value).attr("data")).appendTo("#contentright");
						});
					}
				});
				
				// *************** Elements actifs *********************
				
				// Click sur un élément de la colonne de gauche
				$("body").delegate(".list-group-item","click",function () {
					$(".list-group-item").removeClass("active");
					$(this).addClass("active");
					$(".page").removeClass("selected");
					$("#page_"+$(this).attr("data")).addClass("selected");
					nb=$("#page_"+$(this).attr("data")).position().top+$(".contentright").scrollTop()-30;
					$(".contentright").animate({ scrollTop:nb});
				});
			
				// Click sur une section
				$("body").delegate("div.section h3","click",function () {
					$(this).next().toggle();
				});
				
				// *************** Changements de valeurs *******************
				
				// Update et sauve automatiquement lorsque une case à cocher est cliquées
				$("body").delegate(".list-group-item input[type=checkbox]","click",function (e) {
					updateTitles($(this));
					
					// Sauve les infos en local, et si nécessaire à distance
					save();
				});	
				// Update et sauve automatiquement lorsque les paramètres des tensions sont définis
				$("body").delegate(".list-group-item input:not([type=checkbox])","focusout",function (e) {
					updateTitles($(this));
					
					// Sauve les infos en local, et si nécessaire à distance
					save();
				});	
				// Sauve automatiquement lorsque on quitte les champs d'entête
				$("body").delegate(".top input","focusout",function (e) {
					save();
				});	
				// Sauve automatiquement lorsque on quitte les champs d'entête
				$("body").delegate(".divedit","focusout",function (e) {
					save();
				});	
				$("body").delegate(".cb","click",function (e) {
					calcDurees();
					updateTimer();
				});	

				
				// *************** Boutons ****************
				
				// Ajouter une section
				$("body").delegate("#btn_menuTension","click",function () {
					if ($("#meetingSlices").length>0) {
						cpt=($("#meetingSlices h3").length+1);
						$("#meetingSlices").append($("<div class='section'><h3><input type='text' value='Section "+cpt+"'  class='liketext'></h3><div><ul id='sortable"+cpt+"' class='tension-sortable list-group ui-sortable'></ul></div></div>"));
						$("#sortable"+cpt).sortable({axis: "y", containment: ".screenOJ", connectWith: ".tension-sortable", placeholder: "sortable-placeholder", tolerance: "pointer",
								stop: function( event, ui ) {
									// Réordre la seconde liste en fonction 
									$.each($(".list-group-item"),function(index, value) {
										$("#page_"+$(value).attr("data")).appendTo("#contentright");
									});
								}
							});

					} else {
						$(".screenOJ").append($("<div id='meetingSlices'><div class='section'><h3><input type='text' value='Section 1' class='liketext'></h3><div class='sectionContent'></div></div></div>"));
						$("#meetingSlices div.sectionContent").append($("#sortable"));
					}
				});
				
				// Ajouter une tension
				$("#btn_add").click(function () {addTension(); setTimeout(save, 50);});
				$("body").delegate (".list-group-item input",'keypress',function(e) {
					if(e.which == 13) {
						$(this).blur();
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
				if (localStorage.savedata)
					load();

				// Boucle de mise à jour des infos de timer
				setInterval(updateTimer, 6000);
			});
			
			// *********************************************************
			// Définition des fonction appelées par les boutons
			// *********************************************************
			
			// Mise à jour des titres des pages de droite
			function updateTitles(elem) {
				// Mise à jour du titre
				var id = elem.parents("li").attr("data");
				var newtitle="";
				if ($("#cb_"+id).is(":checked")) newtitle+="<img src='img/check.png' style='width:20px;vertical-align:bottom'> ";
				newtitle+=$("#qui_"+id).val();
				newtitle+=($("#qui_"+id).val()!=""?" - ":"");
				newtitle+="<b>"+$("#tension_"+id).val()+"</b>";
				newtitle+="&nbsp;<span style='float:right'>"+$("#duree_"+id).val()+($("#duree_"+id).val()!=""?"'":"")+"</span>";
				
				$("#page_"+id+" .title").html(newtitle);
				
				// Mise à jour des timer
				calcDurees();
				updateTimer();
			}

			function addTension(title = "", who = "", duration = "", content ="", checked=false) {

				cpt=$(".list-group-item").length+1;
				$("<li id='menu_"+cpt+"' class='list-group-item' data='"+cpt+"'><table cellspacing=0 cellpadding=0 style='width:100%;'><tr><td><input type='checkbox' tabindex='-1' id='cb_"+cpt+"' class='cb' "+(checked?"checked":"")+"></td><td style='width:100%'><input  id='tension_"+cpt+"' class='liketext tension' style='width:100%' placeholder='<?=T_("Description brève",true);?>' value='"+title.replace("'","&apos;")+"'></td></tr><tr><td></td><td><input id='qui_"+cpt+"' class='liketext' style='width:60%;font-size:70%' placeholder='<?=T_("Qui",true);?>' value='"+who.replace("'","&apos;")+"'><input id='duree_"+cpt+"' class='liketext duration' style='width:30%;font-size:70%;text-align:right' placeholder='<?=T_("Durée",true);?>' value='"+duration.replace("'","&apos;")+"'></td></tr></table></li>").appendTo("#sortable");
				// De la même manière, ajoute une zone d'édition
				$("<div class='page' id='page_"+cpt+"' data='"+cpt+"'><div class='title'></div><div class='buttons'><button class='edit' data='"+cpt+"'><?=T_("editer");?></button><button class='delete' data='"+cpt+"'><?=T_("supprimer");?></button><button class='save' style='display:none' data='"+cpt+"'><?=T_("sauver");?></button><button class='cancel' style='display:none' data='"+cpt+"'><?=T_("annuler");?></button></div><div class='content'>"+content+"</div></div>").appendTo(".contentRight");	
				updateTitles($("#tension_"+cpt));
				// Focus directement sur le champ avec la description du point
				if (title=="") $("#tension_"+cpt).focus();
			
				}
			// Mise à jour de l'heure de fin toutes les minutes
			function updateTimer() {
				// Ajoute à l'heure courante le temps en minute du champ restant
				newDateObj = new Date(Date.now() + parseInt("0"+$("#restTime").html())*60000);
				$("#finalTime").html(newDateObj.toLocaleTimeString(navigator.language, {hour: '2-digit',  minute:'2-digit'}));
			}
					
			function load() {
				//saveArray=readCookie("savedata");
				saveArray=localStorage.getItem("savedata");
				if (saveArray=="")
					alert ("<?=T_("Aucunes données sauvegardée trouvées",true);?>");
				else {
					// Efface les informations existantes
					$(".list-group-item").remove();
					$(".page:not(:first-child)").remove();
					
					// Parse le document pour ajouter les infos
					data=JSON.parse(saveArray);
					$("#title").val(data.title);
					$("#location").val(data.location);
					$("#participants").html(data.people);

					$("#excuses").html(data.excused); // Excusés
					$("#absents").html(data.nothere); // 

					$("#facilitation").html(data.facilitator);
					$("#memoire").html(data.secretary);				
					
					data.oj.forEach(function(obj) {
						addTension (obj.title, obj.who, obj.duration, obj.content,obj.checked);
						
					});
					// adapte les timer
					calcDurees();
					updateTimer();
					// Click sur le premier élément
					$(".list-group-item").first().click();
				}
				

				
			}
			
			function newDoc() {
				if (confirm("<?=T_("Avez-vous imprimé le PV en cours?\n\nLe contenu actuel sera effacé définitivement. Êtes-vous sûr de vouloir continuer ?",true);?>")) { 
					// Efface le cookie
					//eraseCookie("savedata");
					localStorage.removeItem("savedata");
					
					// Efface les informations existantes
					$(".list-group-item").remove();
					$(".page:not(:first-child)").remove();

					$("#title").val("");
					$("#location").val("");
					$(".divedit").html("");
									
					calcDurees();
					updateTimer();
				}			
				
			}
						
			function save() {
				saveArray = {};
				saveArray.title = $("#title").val();
				saveArray.location = $("#location").val();
				saveArray.people = $("#participants").html();
				saveArray.excused = $("#excuses").html(); // Excusés
				saveArray.nothere = $("#absents").html(); // 

				saveArray.facilitator = $("#facilitation").html();
				saveArray.secretary = $("#memoire").html();
			

				saveArray.oj = [];
				saveArray.section = [];
				
				// Enregistre les sections s'il y en a
				$.each($(".section"),function (index,value) {
					section ={};
					section.title=$(value).find("h3 input").first().val();
					section.oj = [];
					
					// Enregistre les points à l'ordre du jour non hiérarchisé
					$.each($(value).find(".list-group-item"),function (index,value) {
						tension = {};
						index=$(value).attr("data");
						tension.checked=$("#cb_"+index).is(":checked");
						tension.title=$("#tension_"+index).val();
						tension.who=$("#qui_"+index).val();
						tension.duration=$("#duree_"+index).val();
						tension.content=$("#page_"+index+" .content").html();
						section.oj.push(tension);
					});	
					saveArray.section.push(section);			
				});
			
				
				// Enregistre les points à l'ordre du jour non hiérarchisé
				$.each($("div.screenOJ>ul>li.list-group-item"),function (index,value) {
					tension = {};
					index=$(value).attr("data");
					tension.checked=$("#cb_"+index).is(":checked");
					tension.title=$("#tension_"+index).val();
					tension.who=$("#qui_"+index).val();
					tension.duration=$("#duree_"+index).val();
					tension.content=$("#page_"+index+" .content").html();
					saveArray.oj.push(tension);
				})
				console.log (saveArray);
				localStorage.setItem("savedata", JSON.stringify(saveArray));
				//createCookie("savedata", JSON.stringify(saveArray),365);
			}
			

			
			function calcDurees() {
				bigTotal=0;
				progress=0;
				$.each($(".list-group-item"),function(index, value) {
					// Parcours tous les item, pour faire la somme des heures
					bigTotal+=parseInt("0"+$(value).find(".duration").val());
					if (!$(value).find(".cb").is(":checked")) progress+=parseInt("0"+$(value).find(".duration").val());
				});
				$("#totalTime").html(parseInt(bigTotal));
				$("#restTime").html(parseInt(progress));
			}

		
		</script>
	
	<style>
		

	@media screen {

		#tools {background:var(--midlow-bg-color)}
		.top {background:var(--midlow-bg-color)}
		.left { background:var(--light-bg-color)}
		.contentleft {  background:var(--white-bg-color)}
		.contentright {background:var(--white-bg-color) }
		.right {  background:var(--light-bg-color)}
		.bottom { background:var(--midlow-bg-color)}
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
			<input autocomplete="off" id='title' class='mainTitle liketext' placeholder='<?=T_("Titre de la réunion",true);?>'></input><br>
			<input autocomplete="off" id='location' class='horaires liketext' placeholder='<?=T_("Lieu, date et horaires",true);?>'></input>
		
		</td></tr>
		<tr><td class='left'><div class='contentleft'>
			<table class='leftTab' cellspacing=0 cellpadding=0><tr><td class='odj'>
			<div><?=T_("Ordre du jour");?><span class='noPrint' style='float:right; background:#FFF; border-radius:5px 5px 0px 0px'><img src='img/addentry.png' class='imgbutton' style='margin:0px;' id='btn_add'  data-toggle='tooltip' data-placement='bottom' title='<?=T_('Ajouter une tension',true)?>'>  
<?
		if ($connected)
			echo "<img id='btn_menuTension' src='img/addfolder.png' class='imgbutton'  data-toggle='tooltip' data-placement='bottom' title='".T_('Ajouter une section',true)."' style='margin:0px;'>";
?>
			
			
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

		//<!-- bouton pour les paramẗres -->
		if ($connected)
		echo "<img src='img/settings.png' class='imgbutton' id='btn_parameters' data-toggle='tooltip' data-placement='right' title='".T_('Paramètres',true)."'>";
?>		
		</td></tr>
		<tr><td class='bottom' colspan=3>&nbsp; <img src='img/systemeD.png' style='height:30px;'><span style='float:right;'><img src='img/support.png' style='height:40px;' id='btn_support'></span></td></tr>
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
