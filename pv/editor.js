
			
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
				
				// Edition sur double click
				$("body").delegate(".note-editor","dblclick", function(e) {
					// évite que le double-click dans l'éditeur soit mal interprété
					e.stopPropagation();
				});
				$("body").delegate(".page","dblclick",function () {
					if ($(".note-editor").length>0) {
						//alert ("")
						//$(".note-editor").focus();
					} 
					
					$(this).find(".content").summernote({focus: true, toolbar: mytoolbar, styleTags: mystyles, fontSizes:myfontsize, buttons: mybuttons, lang:"fr-FR"});
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
					if (confirm(("" + window.pvEditorPageConfig.text2 + " \"")+(txt!=""?txt:"sans titre")+("\" ?\n\n" + window.pvEditorPageConfig.text3 + ""))) {
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
						//alert ("")
						//$(".note-editor").focus();
					} 

						$(this).parent().next().summernote({ focus: true, toolbar: mytoolbar, styleTags: mystyles, fontSizes:myfontsize, buttons: mybuttons, lang: "fr-FR"});
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
					['color', ['omoPvHighlight']],
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

				var mybuttons = {
					omoPvHighlight: function (context) {
						return $.summernote.ui.button({
							contents: '<img src="/omo/images/tools/surligneur.png" alt="" style="display:block;width:18px;height:18px;object-fit:contain;">',
							tooltip: 'Modifier le surlignage',
							click: function (event) {
								if (!window.omoHighlightPalette) {
									return;
								}

								context.invoke('editor.saveRange');
								window.omoHighlightPalette.open({
									anchor: event && event.currentTarget,
									onSelect: function (color) {
										context.invoke('editor.restoreRange');
										context.invoke('editor.backColor', color || 'transparent');
									}
								});
							}
						}).render();
					}
				};


					
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
					showPopup("/popup/pv_load.php", ("" + window.pvEditorPageConfig.text4 + ""));
				});
				$("#btn_help").click(function () {
					showPopup("/popup/help.php", ("" + window.pvEditorPageConfig.text5 + ""));
				});			

				$("#btn_download").click(function () {
					showPopup("/popup/download.php", ("" + window.pvEditorPageConfig.text6 + ""));
				});	
				$("#btn_share").click(function () {
					showPopup("/popup/pv_share.php", ("" + window.pvEditorPageConfig.text7 + ""));
				});	
				$("#btn_parameters").click(function () {
					showPopup("/popup/parameters.php", ("" + window.pvEditorPageConfig.text8 + ""));
				});			
				$("#btn_support").click(function () {
					showPopup("/popup/support.php", ("" + window.pvEditorPageConfig.text9 + ""));
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
				if ($("#cb_"+id).is(":checked")) newtitle+="<img src='/img/check.png' style='width:20px;vertical-align:bottom'> ";
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
					
					$("li.list-group-item.active input.type").attr("value",event.key);
				}
				if (event.altKey && event.key == "0" ) {
					// Empêche l'action par défaut si nécessaire
					event.preventDefault();
					
					$("li.list-group-item.active input.type").attr("value","");
				}			});

				function addSection(name=null) {
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
				}
				
			function addTension(type="", title = "", who = "", duration = "", realduration = "", content ="", checked=false) {

				cpt=$(".list-group-item").length+1;
				if ($(".tension-sortable:has(li.active)").length>0)
					$("<li id='menu_"+cpt+"' class='list-group-item' data='"+cpt+"'><table cellspacing=0 cellpadding=0 style='width:100%;'><tr><td><input type='checkbox' tabindex='-1' id='cb_"+cpt+"' class='cb' "+(checked?"checked":"")+"></td><td style='width:100%;padding-right:20px;'><input id='type_"+cpt+"' class='type' value='"+type+"'><input  id='tension_"+cpt+("' class='liketext tension' style='width:100%' placeholder='" + window.pvEditorPageConfig.text10 + "' value='")+title.replace("'","&apos;")+"'></td></tr><tr><td></td><td><input id='qui_"+cpt+("' class='liketext' style='width:60%;font-size:70%' placeholder='" + window.pvEditorPageConfig.text11 + "' value='")+who.replace("'","&apos;")+"'><input type='hidden' id='realduree_"+cpt+"' value='"+realduration.replace("'","&apos;")+"'><input autocomplete='off' id='duree_"+cpt+("' class='liketext duration' style='width:30%;font-size:70%;text-align:right' placeholder='" + window.pvEditorPageConfig.text12 + "' value='")+duration.replace("'","&apos;")+"'></td></tr></table></li>").appendTo(".tension-sortable:has(li.active)");
				else
					$("<li id='menu_"+cpt+"' class='list-group-item' data='"+cpt+"'><table cellspacing=0 cellpadding=0 style='width:100%;'><tr><td><input type='checkbox' tabindex='-1' id='cb_"+cpt+"' class='cb' "+(checked?"checked":"")+"></td><td style='width:100%;padding-right:20px;'><input id='type_"+cpt+"' class='type' value='"+type+"'><input  id='tension_"+cpt+("' class='liketext tension' style='width:100%' placeholder='" + window.pvEditorPageConfig.text10 + "' value='")+title.replace("'","&apos;")+"'></td></tr><tr><td></td><td><input id='qui_"+cpt+("' class='liketext' style='width:60%;font-size:70%' placeholder='" + window.pvEditorPageConfig.text11 + "' value='")+who.replace("'","&apos;")+"'><input type='hidden' id='realduree_"+cpt+"' value='"+realduration.replace("'","&apos;")+"'><input autocomplete='off' id='duree_"+cpt+("' class='liketext duration' style='width:30%;font-size:70%;text-align:right' placeholder='" + window.pvEditorPageConfig.text12 + "' value='")+duration.replace("'","&apos;")+"'></td></tr></table></li>").appendTo(".tension-sortable");
			
				// De la même manière, ajoute une zone d'édition
				$("<div class='page' id='page_"+cpt+"' data='"+cpt+"'><input type='hidden' class='id' value=''><div class='title'></div><div class='buttons'><button class='edit' data='"+cpt+("'>" + window.pvEditorPageConfig.text13 + "</button><button class='delete' data='")+cpt+("'>" + window.pvEditorPageConfig.text14 + "</button><button class='save' style='display:none' data='")+cpt+("'>" + window.pvEditorPageConfig.text15 + "</button><button class='cancel' style='display:none' data='")+cpt+("'>" + window.pvEditorPageConfig.text16 + "</button></div><div class='content'>")+content+"</div></div>").appendTo(".contentRight");	
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
					alert (("" + window.pvEditorPageConfig.text17 + ""));
				else {
					console.log(saveArray);
					// Efface les informations existantes
					$(".list-group-item").remove();
					$(".page:not(:first-child)").remove();
					
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
				/*	data.section.forEach(function(sec) {
						// Ajoute la section
						addSection();
						sec.oj.forEach(function(obj) {
							addTension (obj.type,obj.title, obj.who, obj.duration, obj.realduration, obj.content,obj.checked);
							
						});
						
					});*/
					
					
					
					// adapte les timer
					calcDurees();
					updateTimer();
					// Click sur le premier élément
					$(".list-group-item").first().click();
				}
				

				
			}
			
			function newDoc() {
				if (confirm(("" + window.pvEditorPageConfig.text18 + ""))) { 
					// Efface le cookie
					//eraseCookie("savedata");
					localStorage.removeItem("savedata");
					
					// Efface les informations existantes
					//$("#meetingSlices").remove();
					$(".list-group-item").remove();
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
			

		
		
