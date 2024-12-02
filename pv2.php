<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	
	// Initialise le login
	$connected=checklogin();
?>
<html>
	<head>
		<?=writeHeadContent("Facilitez-vous la prise de PV !","EasyPV");?>
		<style>
		  .sector-1 { fill: #FFFFFF; }
		  .sector-2 { fill: #22AA22; }
		  .sector-3 { fill: #CC0000; }
		  .sector-4 { fill: #4BC0C0; }

		</style>		
		<!-- Script spécifique à la page -->
		<script>
			
window.addEventListener('beforeprint', () => {
  const userLang = navigator.language || 'fr-CH'; // Utilise la langue du navigateur ou 'fr-CH' par défaut

  document.querySelectorAll('input[type="date"]').forEach(input => {
    const date = new Date(input.value);
    const formattedDate = date.toLocaleDateString(userLang); // Utilise le format de date du navigateur
    input.setAttribute('data-original-type', input.type);
    input.setAttribute('data-original-value', input.value);
    input.type = 'text';
    input.value = formattedDate;
  });

  document.querySelectorAll('input[type="time"]').forEach(input => {
    const time = input.value;
    input.setAttribute('data-original-type', input.type);
    input.setAttribute('data-original-value', input.value);
    input.type = 'text';
    input.value = time;
  });
});

window.addEventListener('afterprint', () => {
  document.querySelectorAll('input[data-original-type]').forEach(input => {
    input.type = input.getAttribute('data-original-type');
    input.value = input.getAttribute('data-original-value');
    input.removeAttribute('data-original-type');
    input.removeAttribute('data-original-value');
  });
});

// Fonction pour convertir des angles polaires en coordonnées cartésiennes
function polarToCartesian(cx, cy, radius, angleInDegrees) {
  let angleInRadians = (angleInDegrees - 90) * Math.PI / 180.0;
  return {
    x: cx + (radius * Math.cos(angleInRadians)),
    y: cy + (radius * Math.sin(angleInRadians))
  };
}

// Fonction qui génère le chemin d'un arc de cercle pour un secteur
function describeArc(x, y, radius, startAngle, endAngle) {
  let start = polarToCartesian(x, y, radius, startAngle);
  let end = polarToCartesian(x, y, radius, endAngle);

  let largeArcFlag = (endAngle - startAngle) <= 180 ? "0" : "1";

  return [
    "M", x, y, // Déplacement au centre du cercle
    "L", start.x, start.y, // Ligne vers le début de l'arc
    "A", radius, radius, 0, largeArcFlag, 1, end.x, end.y, // Arc de cercle
    "Z" // Fermeture du chemin (retour au centre)
  ].join(" ");
}

// Fonction pour dessiner le camembert
function drawPieChart(data) {
  const svg = document.getElementById("pieChart");

  // Effacer les anciens secteurs
  svg.innerHTML = "";

  const cx = 25, cy = 25, radius = 25;
  let total = data.reduce((sum, val) => sum + val, 0);
  let currentAngle = 0;

  // Vérifier si une seule valeur est non nulle pour dessiner un cercle complet
  if (data.filter(value => value > 0).length === 1) {
    let index = data.findIndex(value => value > 0);
    let path = document.createElementNS("http://www.w3.org/2000/svg", "circle");
    path.setAttribute("cx", cx);
    path.setAttribute("cy", cy);
    path.setAttribute("r", radius);
    path.setAttribute("class", `sector-${index + 1}`);
    svg.appendChild(path);
    
  } else {

  data.forEach((value, index) => {
    if (value > 0) { // Ignorer les valeurs nulles
      let sliceAngle = (value / total) * 360;
      let pathData = describeArc(cx, cy, radius, currentAngle, currentAngle + sliceAngle);

      // Créer un élément <path> pour chaque secteur
      let path = document.createElementNS("http://www.w3.org/2000/svg", "path");
      path.setAttribute("d", pathData);
      path.setAttribute("class", `sector-${index + 1}`);
      svg.appendChild(path);

      currentAngle += sliceAngle;
    }
  });
}
  let text = document.createElementNS("http://www.w3.org/2000/svg", "text");
 
    // Récupérer l'heure actuelle
  let now = new Date();
  let timeString = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

  text.setAttribute("x", cx); // Centre horizontal
  text.setAttribute("y", cy); // Centre vertical
  text.setAttribute("class", "center-text");
  text.setAttribute("text-anchor", "middle"); // Centrer horizontalement le texte
  text.setAttribute("dominant-baseline", "middle"); // Centrer verticalement le texte
  text.textContent = timeString; // Contenu du texte (heure actuelle)

  svg.appendChild(text); // Ajouter l'heure au SVG
}


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
				$("body").delegate(".section h3 input","click",function(e) {
					e.stopPropagation();
					});
				
				// Edition sur double click
				$("body").delegate(".note-editor","dblclick", function(e) {
					// évite que le double-click dans l'éditeur soit mal interprété
					e.stopPropagation();
				});
				$("body").delegate(".page","dblclick",function () {
					if ($(".note-editor").length>0) {
						//alert ("<?=T_("Veuillez préalablement fermer l'éditeur déjà ouvert.",true);?>")
						//$(".note-editor").focus();
					} 
					
					$(this).find(".content").summernote({focus: true, toolbar: mytoolbar, styleTags: mystyles, fontSizes:myfontsize, lang:"fr-FR"});
					// Cache le bouton edit et affiche le bouton sauver
					$(this).find("button.save").css("display","");
					$(this).find("button.cancel").css("display","");
					$(this).find("button.edit").css("display","none");
					$(this).find("button.delete").css("display","none");
			
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
						$("#saved").val(0);
						save();
					}
				});	
				
				// Editer une page, ouvre l'éditeur
				$("body").delegate(".edit","click",function () {
					// S'assure qu'aucun autre éditeur est déjà ouvert
					if ($(".note-editor").length>0) {
						//alert ("<?=T_("Veuillez préalablement fermer l'éditeur déjà ouvert.")?>")
						//$(".note-editor").focus();
					} 

						$(this).parent().next().summernote({ focus: true, toolbar: mytoolbar, styleTags: mystyles, fontSizes:myfontsize, lang: "fr-FR"});
						// Cache le bouton edit et affiche le bouton sauver
						$(this).parent().find("button.save").css("display","");
						$(this).parent().find("button.cancel").css("display","");
						$(this).css("display","none");
						$(this).parent().find("button.delete").css("display","none");
					
					
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
					$("#saved").val(0);
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
					pos=$(".interface-left").width()+ui.position.left+4;
					if (pos<250) pos=250;
					$(".interface-left").css("width",pos);
					$( "#resizeelem" ).css("left",0);
				  }
				});
				
				// Elements de la colonne de gauche ordonnable, avec effet miroir sur la colonne de droite
				$("#sortable1").sortable({axis: "y", containment: ".screenOJ", connectWith: ".tension-sortable", placeholder: "sortable-placeholder", tolerance: "pointer",
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
						$("#saved").val(0);
					save();
				});	
				// Update et sauve automatiquement lorsque les paramètres des tensions sont définis
				$("body").delegate(".list-group-item input:not([type=checkbox])","focusout",function (e) {
					updateTitles($(this));
					
					// Sauve les infos en local, et si nécessaire à distance
					$("#saved").val(0);
					save();
				});	
				// Sauve automatiquement lorsque on quitte les champs d'entête
				$("body").delegate(".interface-top input","focusout",function (e) {
					$("#saved").val(0);
					save();
				});	
				// Sauve automatiquement lorsque on quitte les champs d'entête
				$("body").delegate(".divedit","focusout",function (e) {
					$("#saved").val(0);
					save();
				});	
				$("body").delegate(".cb","click",function (e) {
					calcDurees();
					updateTimer();
				});	
				
				$("body").delegate(".duration","keydown", function (e) {
					if(e.key === "Tab" || e.keyCode === 9) {
						// Si c'est le dernier élément de la liste, en ajoute un nouveau
						//$(this).parent().parent().parent().parent().parent().nextAll("li").css( "background", "#FFFF00" );
						if ($(this).parent().parent().parent().parent().parent().nextAll("li").length == 0)
							{addTension(); e.preventDefault();}
						
					}
					
				});

				// *************** Boutons ****************
				
				// Ajouter une section
				$("body").delegate("#btn_menuTension","click",function () {
					addSection(); setTimeout (save,50);
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
					{ title: 'Paragraphe', tag: 'p', className: '', value: 'p' },
					{ title: 'Titre 1', tag: 'h1', className: '', value: 'h1' },
					{ title: 'Titre 2', tag: 'h2', className: '', value: 'h2' },
					{ title: 'Titre 3', tag: 'h3', className: '', value: 'h3' },
					{ title: 'Decision', tag: 'div', className: 'monstyledemenu', value: 'h4' },
					{ title: 'Tâche/action', tag: 'div', className: 'monstyledemenu', value: 'h5' },
					   
				];
				
				var myfontsize = ['8', '9', '10', '11', '12', '13', '14', '15', '16', '18', '20', '22' , '24', '28', '32', '36', '40', '48'];


					
				// *******************************************************
				// Menu d'option à droite
				// ******************************************************
				
				// Difflrents boutons de la page
				$("#btn_save").click(function () {
					saveSQL();
				});
				$("#btn_new").click(function () {
					newDoc();
				});
				$("#btn_load").click(function () {
					//loadSQL();
					showPopup("popup/pv_load.php", "<?=T_("Charger un document",true)?>");
				});
				$("#btn_help").click(function () {
					showPopup("popup/help.php", "<?=T_("Aide",true)?>");
				});			

				$("#btn_download").click(function () {
					showPopup("popup/download.php", "<?=T_("Télécharger",true)?>");
				});	
				$("#btn_share").click(function () {
					showPopup("popup/pv_share.php", "<?=T_("Partager",true)?>");
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
				setInterval(updateTimer, 1000);
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
				newtitle+="&nbsp;<span style='float:right'>"+($("#realduree_"+id).val()!=""?Math.floor(parseInt($("#realduree_"+id).val())/60) + "' / ":"")+$("#duree_"+id).val()+($("#duree_"+id).val()!=""?"'":"")+"</span>";
				
				$("#page_"+id+" .title").html(newtitle);
				
				// Mise à jour des timer
				calcDurees();
				//updateTimer();
			}
			
			$(document).keydown(function(event) {
				// Vérifiez si Alt (keyCode 18) et la touche 1 (keyCode 49) sont pressés
				if (event.altKey && event.key >= "1" && event.key <= "5") {
					// Empêche l'action par défaut si nécessaire
					event.preventDefault();
					
					elem=$(':focus').closest('li.list-group-item');
					if (elem.length>0)
						elem.find("input.type").attr("value",event.key);
					else
						$("li.list-group-item.active input.type").attr("value",event.key);
				}
				if (event.altKey && event.key == "0" ) {
					// Empêche l'action par défaut si nécessaire
					event.preventDefault();
					
					$("li.list-group-item.active input.type").attr("value","");
				}			});

				function addSection(name=null) {
					console.log("Name "+name);
					if ($("#meetingSlices").length>0 || $(".tension-sortable").length==0 ) {
						cpt=($("#meetingSlices h3").length+1);
						$("#meetingSlices").append($("<div class='section'><h3><input type='text' value='"+(name!==null?name:"Section "+cpt)+"'  class='liketext'></h3><div class='sectionContent'><ul id='sortable"+cpt+"' class='tension-sortable list-group ui-sortable'></ul></div></div>"));
						$("#sortable"+cpt).sortable({axis: "y", containment: ".screenOJ", connectWith: ".tension-sortable", placeholder: "sortable-placeholder", tolerance: "pointer",
								stop: function( event, ui ) {
									// Réordre la seconde liste en fonction 
									$.each($(".list-group-item"),function(index, value) {
										$("#page_"+$(value).attr("data")).appendTo("#contentright");
									});
								}
							});

					} else {
						$(".screenOJ").append($("<div id='meetingSlices'><div class='section'><h3><input type='text' value='"+(name!==null?name:"Section 1")+"' class='liketext'></h3><div class='sectionContent'></div></div></div>"));
						$("#meetingSlices div.sectionContent").append($("#sortable1"));
						$("#sortable1").sortable({axis: "y", containment: ".screenOJ", connectWith: ".tension-sortable", placeholder: "sortable-placeholder", tolerance: "pointer",
									stop: function( event, ui ) {
										// Réordre la seconde liste en fonction 
										$.each($(".list-group-item"),function(index, value) {
											$("#page_"+$(value).attr("data")).appendTo("#contentright");
										});
									}
								});

						}
				}
				
			function addTension(type="", title = "", who = "", duration = "", realduration = "", content ="", checked=false) {

				cpt=$(".list-group-item").length+1;
				if ($(".tension-sortable:has(li.active)").length>0)
					$("<li id='menu_"+cpt+"' class='list-group-item' data='"+cpt+"'><table cellspacing=0 cellpadding=0 style='width:100%;'><tr><td><input type='checkbox' tabindex='-1' id='cb_"+cpt+"' class='cb' "+(checked?"checked":"")+"></td><td style='width:100%;padding-right:20px;'><input id='type_"+cpt+"' class='type' value='"+type+"'><input  id='tension_"+cpt+"' class='liketext tension' style='width:100%' placeholder='<?=T_("Description brève",true);?>' value='"+title.replace("'","&apos;")+"'></td></tr><tr><td></td><td><input id='qui_"+cpt+"' class='liketext' style='width:60%;font-size:70%' placeholder='<?=T_("Qui",true);?>' value='"+who.replace("'","&apos;")+"'><input type='hidden' id='realduree_"+cpt+"' value='"+realduration.replace("'","&apos;")+"'><input autocomplete='off' id='duree_"+cpt+"' class='liketext duration' style='width:30%;font-size:70%;text-align:right' placeholder='<?=T_("Durée",true);?>' value='"+duration.replace("'","&apos;")+"'></td></tr></table></li>").appendTo(".tension-sortable:has(li.active)");
				else
					$("<li id='menu_"+cpt+"' class='list-group-item' data='"+cpt+"'><table cellspacing=0 cellpadding=0 style='width:100%;'><tr><td><input type='checkbox' tabindex='-1' id='cb_"+cpt+"' class='cb' "+(checked?"checked":"")+"></td><td style='width:100%;padding-right:20px;'><input id='type_"+cpt+"' class='type' value='"+type+"'><input  id='tension_"+cpt+"' class='liketext tension' style='width:100%' placeholder='<?=T_("Description brève",true);?>' value='"+title.replace("'","&apos;")+"'></td></tr><tr><td></td><td><input id='qui_"+cpt+"' class='liketext' style='width:60%;font-size:70%' placeholder='<?=T_("Qui",true);?>' value='"+who.replace("'","&apos;")+"'><input type='hidden' id='realduree_"+cpt+"' value='"+realduration.replace("'","&apos;")+"'><input autocomplete='off' id='duree_"+cpt+"' class='liketext duration' style='width:30%;font-size:70%;text-align:right' placeholder='<?=T_("Durée",true);?>' value='"+duration.replace("'","&apos;")+"'></td></tr></table></li>").appendTo(".tension-sortable:last");
			
				// De la même manière, ajoute une zone d'édition
				$("<div class='page' id='page_"+cpt+"' data='"+cpt+"'><input type='hidden' class='id' value=''><div class='title'></div><div class='buttons'><button class='edit' data='"+cpt+"'><?=T_("editer");?></button><button class='delete' data='"+cpt+"'><?=T_("supprimer");?></button><button class='save' style='display:none' data='"+cpt+"'><?=T_("sauver");?></button><button class='cancel' style='display:none' data='"+cpt+"'><?=T_("annuler");?></button></div><div class='content'>"+content+"</div></div>").appendTo(".contentRight");	
				updateTitles($("#tension_"+cpt));
				// Focus directement sur le champ avec la description du point
				if (title=="") $("#tension_"+cpt).focus();
			
				}
			// Mise à jour de l'heure de fin toutes les minutes
			function updateTimer() {
				// Ajoute à l'heure courante le temps en minute du champ restant
				newDateObj = new Date(Date.now() + parseInt("0"+$("#restTime").html())*60000);
				$("#finalTime").html(newDateObj.toLocaleTimeString(navigator.language, {hour: '2-digit',  minute:'2-digit'}));
				$("#time_svg").text(newDateObj.toLocaleTimeString(navigator.language, {hour: '2-digit',  minute:'2-digit'}));
		
				// Ajoute si nécessaire une seconde au point actuellement édité
				let current = $("div.page.selected:has(div.note-editable)");
				if (current) {
					$("#realduree_"+current.attr("data")).val(parseInt("0"+$("#realduree_"+current.attr("data")).val())+1);
					// Nise à jour du titre
					updateTitles($("#tension_"+current.attr("data")));
					
				}
			}
					
			function load() {
				//saveArray=readCookie("savedata");
				saveArray=localStorage.getItem("savedata");
				if (saveArray=="")
					alert ("<?=T_("Aucunes données sauvegardée trouvées",true);?>");
				else {
					console.log(saveArray);
					// Efface les informations existantes
					newDoc(false);
					
					// Parse le document pour ajouter les infos
					data=JSON.parse(saveArray);
					$("#id").val(data.id);
					$("#saved").val(data.saved);
					$("#title").val(data.title);
					$("#location").val(data.location);
					$("#dateevent").val(data.dateevent);
					$("#starttime").val(data.starttime);
					$("#endtime").val(data.endtime);
					$("#participants").html(data.people);

					$("#excuses").html(data.excused); // Excusés
					$("#absents").html(data.nothere); // 

					$("#facilitation").html(data.facilitator);
					$("#memoire").html(data.secretary);				
					
					// Ajoute les tensions
					data.oj.forEach(function(obj) {
						addTension (obj.type,obj.title, obj.who, obj.duration, obj.realduration, obj.content,obj.checked);
						
					});
					// Ajoute les sections
					data.section.forEach(function(sec) {
						// Ajoute la section
						console.log (sec.title);
						addSection(sec.title);
						sec.oj.forEach(function(obj) {
							addTension (obj.type,obj.title, obj.who, obj.duration, obj.realduration, obj.content,obj.checked);
							
						});
						
					});
					
					
					
					// adapte les timer
					calcDurees();
					updateTimer();
					// Click sur le premier élément
					$(".list-group-item").first().click();
				}
				

				
			}
			
			function newDoc(check=true) {
				if (!check || confirm("<?=T_("Avez-vous imprimé le PV en cours?\n\nLe contenu actuel sera effacé définitivement. Êtes-vous sûr de vouloir continuer ?",true);?>")) { 
					// Efface le cookie
					//eraseCookie("savedata");
					localStorage.removeItem("savedata");
					
					// Efface les informations existantes
					$(".list-group-item").remove();
					// Efface les sections
					if ($("#meetingSlices").length>0) {
						$("#meetingSlices").remove();
						$('<ul id="sortable1" class="tension-sortable list-group ui-sortable"></ul>').appendTo($(".screenOJ"));
					}
					
					
					$(".page:not(:first-child)").remove();

					$("#id").val("");
					$("#saved").val("");
					$("#title").val("");
					$("#location").val("");
					$("#starttime").val("");
					$("#dateevent").val("");
					$("#endtime").val("");
					$(".divedit").html("");
									
					calcDurees();
					updateTimer();
				}			
				
			}
				
			function saveSQL() {
				save();
				$.ajax({
					url: '/ajax/savepv.php', // Remplacez par l'URL de votre serveur PHP
					type: 'POST',
					contentType: 'application/json',
					dataType: 'json',
					data: JSON.stringify(JSON.parse(localStorage.getItem("savedata"))),
					success: function(response) {
						if (response.status === 'ok') { // Vérifiez si le serveur a renvoyé un statut "ok"
							// Inscrivez l'ID dans le champ de formulaire caché
							$('#id').val(response.id);
							$("#saved").val("");
							save();
							alert('Sauvegarde effectuée !');
							
						} else {
							alert('Erreur: ' + response.message);
						}
					},
					error: function(xhr, status, error) {
						console.log('Erreur de requête : ', error);
						alert('Une erreur est survenue. Veuillez réessayer.');
					}
				});			
			}	
						
			function save() {
				saveArray = {};
				saveArray.id = $("#id").val();
				saveArray.saved = $("#saved").val();
				saveArray.title = $("#title").val();
				saveArray.location = $("#location").val();
				saveArray.dateevent = $("#dateevent").val();
				saveArray.starttime = $("#starttime").val();
				saveArray.endtime = $("#endtime").val();
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
						tension.type=$("#type_"+index).val();
						tension.who=$("#qui_"+index).val();
						tension.duration=$("#duree_"+index).val();
						tension.realduration=$("#realduree_"+index).val();
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
					tension.type=$("#type_"+index).val();
					tension.who=$("#qui_"+index).val();
					tension.duration=$("#duree_"+index).val();
					tension.realduration=$("#realduree_"+index).val();
					tension.content=$("#page_"+index+" .content").html();
					saveArray.oj.push(tension);
				})
				
				localStorage.setItem("savedata", JSON.stringify(saveArray));
			
			}
			

			
			function calcDurees() {
				bigTotal=0;
				progress=0;
				let data=[1];
				$.each($(".list-group-item"),function(index, value) {
					// Parcours tous les item, pour faire la somme des heures
					bigTotal+=parseInt("0"+$(value).find(".duration").val());
					if (!$(value).find(".cb").is(":checked")) progress+=parseInt("0"+$(value).find(".duration").val());
				});
				
				$("#totalTime").html(parseInt(bigTotal));
				$("#restTime").html(parseInt(progress));
		
				// Heure de fin
				endTime = new Date(Date.now() + parseInt("0"+$("#restTime").html())*60000);
				// Heure actuelle
				currentTime = new Date(Date.now());
				
				const [hours, minutes] = $("#endtime").val().split(':').map(Number);




				planifiedTime = new Date();
				planifiedTime.setHours(hours, minutes, 0, 0);
				// Calcul du temps disponible restant avant la fin
				restTime=(planifiedTime-endTime)/60000;
				// Calcul du dépassement
				toMuch=(restTime<0?restTime:"");
				$("#toMuchTime2").html(toMuch!=""?" ("+parseInt(toMuch)+"')":"");
	
				
				// Construit le camenbert

				// Est-ce qu'une heure de fin est définie?
				if ($("#endtime").val()!="") {
					// Heure programmée
					let timeString = $("#endtime").val(); // Récupérer la valeur du champ input
					let programmedTime = new Date(); 
					programmedTime.setHours(...timeString.split(':').map(Number), 0, 0);
					
					// Est-ce qu'on fini dans les temps? Si oui, camenbert tout vert
					if (endTime<=programmedTime) {
						
						data = [parseInt(bigTotal)-parseInt(progress),parseInt(progress),0];						
					} else
					
					// Sinon, est-ce qu'on est déjà aux fraises (déjà dépassé)? Si oui, camenbert tout rouge
					if (currentTime>=programmedTime) {
						data = [parseInt(bigTotal)-parseInt(progress),0,parseInt(progress)];						
					} else 
					// Sinon, besoin de calculer le ratio vert/rouge
					{
						// Convertir la différence en minutes (1 minute = 60 000 millisecondes)
						let differenceInMinutes = (endTime-programmedTime) / (1000 * 60);
						
						data = [parseInt(bigTotal)-parseInt(progress),parseInt(progress)-differenceInMinutes,differenceInMinutes];						
					}
					
				} else {
						data = [parseInt(bigTotal)-parseInt(progress),parseInt(progress)];						

					
				}
				drawPieChart(data);	

			}
			

		
		</script>
	
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
		.resize {width:5px;position:relative;}
		#resizeelem {width:10px;height:100%; cursor:e-resize;z-index:2; background-image: url(/img/dots.png);

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
	
	.section H3 {border:1px solid black; background-color:#EEE; padding:5px 5px 5px 35px; margin:2px;background-image:url(/img/section_arrow2.png);			background-size:contain;
			background-repeat:no-repeat;
			background-position:left;}
		.section:has(> div[style*="display: none"]) H3 {
			background-image:url(/img/section_arrow1.png);

		}
	
	
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
		.tension-sortable {}
		.tension-sortable:not(:has(.list-group-item)) {border:2px dotted #DDD;min-height:50px;}
		
		
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
    background-image: url(img/thumb-up.png) !important;
    background-size: 21px !important;
    background-repeat: no-repeat !important;
    background-position: 8px !important;}
	.content h5, .note-editable h5 {font-size:inherit; background:rgba(255,255,0,0.3) !important; padding:5px;padding-left:20px;    padding-left: 35px;
    background-image: url(img/clipboard.png) !important;
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
		echo "<form name='logoutform' id='logoutform' action='ajax/login.php' class='ajax' style='margin:0px;display:inline-block'><button id='logoutbtn' name='logoutbtn' value='1' type='button'>".T_("Se déconnecter")."</button></form>";		
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
			<div><?=T_("Ordre du jour");?><span class='noPrint' style='float:right; background:#FFF; border-radius:5px 5px 0px 0px'><img src='img/addentry.png' class='imgbutton' style='margin:0px;' id='btn_add'  data-toggle='tooltip' data-placement='bottom' title='<?=T_('Ajouter une tension',true)?>'>  
<?
		if ($connected)
			echo "<img id='btn_menuTension' src='img/addfolder.png' class='imgbutton'  data-toggle='tooltip' data-placement='bottom' title='".T_('Ajouter une section',true)."' style='margin:0px;'>";
?>
			
			
			</span></div>
			</td></tr><tr><td style='height:100%; position: relative;vertical-align:top'><div class='screenOJ'>
			<ul id="sortable1" class="tension-sortable list-group">
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


				
<?=T_("<h2><b>Bienvenue sur l'éditeur spécial procès verbal de OpenMyOrganization</b></h2><p></p><h5>Pour démarrer un nouveau PV, effacez le texte de ce bloc ou cliquez sur Nouveau (en haut à droite)</h5><p></p><p>Voici un petit outil vous permettant facilement de prendre en main une réunion, en tenant un procès verbal sur un écran que vous pouvez partager. L'avantage, au regard d'un traitement de texte classique, est que l'ordre du jour reste constamment accessible, et qu'il est facile de naviguer entre les points.</p><p>Actuellement, il n'est pas possible de sauver les documents autrement qu'en les imprimant en PDF. Prochainement, il sera possible de sauvegarder les PV ou de les télécharger dans un format Word, vous permettant de finaliser la mise en page à l'issue de la réunion si vous le souhaitez.</p><h3>Vous pouvez utiliser la barre ci-dessus pour ajouter du formatage, comme:</h3><ul><li>Des listes à puces</li><li>Des textes <b>en gras</b> ou en <i>italique</i></li><li>Des couleurs de <font color='#000000' style='background-color: rgb(255, 255, 0);'>surlignage</font></li><li>Des <a href='https://www.linkedin.com/in/daviddraeyer/' target='_blank'>liens</a></li><li>Et même des images.</li></ul><h4>Dans les options de formatage, il existe des options particulières pour faire ressortir les décision.</h4>");?>


								</div>
			
			</div>
		
	
		
		</div></td><td rowspan="2" id='tools' style='width:50px; vertical-align:top;'>
<?	
		//<!-- bouton pour le zoom -->
		echo "<img src='img/expand.png' class='imgbutton' id='btn_zoom' data-toggle='tooltip' data-placement='left' title='".T_('Plein écran',true)."'>";

		//<!-- bouton pour un nouveau fichier -->
		echo "<img src='img/newfile.png' class='imgbutton' id='btn_new' data-toggle='tooltip' data-placement='left' title='".T_('Nouveau document',true)."'>";

		//<!-- bouton pour sauver -->
		if ($connected)
		echo "<img src='img/save-file.png' class='imgbutton' id='btn_save' data-toggle='tooltip' data-placement='left' title='".T_('Enregistrer le document',true)."'>";

		//<!-- bouton pour charger -->
		if ($connected)
		echo "<img src='img/up-arrow.png' class='imgbutton' id='btn_load' data-toggle='tooltip' data-placement='left' title='".T_('Charger un document',true)."'>";

		//<!-- bouton pour imprimer -->
		echo "<img src='img/printing.png' onclick='window.print();' class='imgbutton' id='btn_print' data-toggle='tooltip' data-placement='left' title='".T_('Imprimer',true)."'>";

		//<!-- bouton pour partager -->
		if ($connected)
		echo "<img src='img/share.png' class='imgbutton' id='btn_share' data-toggle='tooltip' data-placement='left' title='".T_('Partager',true)."'>";

		//<!-- bouton pour télécharger -->
		if ($connected)
		echo "<img src='img/download.png' class='imgbutton' id='btn_download' data-toggle='tooltip' data-placement='right' left='".T_('Télécharger',true)."'>";

		//<!-- bouton pour l'aide -->
		echo "<img src='img/question.png' class='imgbutton' id='btn_help' data-toggle='tooltip' data-placement='left' title='".T_('Afficher l\'aide',true)."'>";

		//<!-- bouton pour les paramẗres -->
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
<script>
	
	
function updatePrintValue(input) { let value = input.val(); if (input.attr('type') === 'date') { value = formatDate(value); } input.next('.print-value').text(value); } function formatDate(date) { const options = { year: 'numeric', month: 'long', day: 'numeric' }; return new Date(date).toLocaleDateString('fr-FR', options); } // Fonction pour mettre à jour les champs par script function updateInputValue(selector, value) { $(selector).val(value).trigger('input'); }	
	
$(function() {
	
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
