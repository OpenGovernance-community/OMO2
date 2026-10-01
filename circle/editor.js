
			
			let root;
			var canvas, hiddenCanvas, context, hiddenContext;
			var node = null;
			var centerX = centerY = null;
			var zoomInfo = null;
			var pack;
			var nodes;
			//var nodeByName;	
			var mobileSize;
			var diameter;
			var mainTextColor
			var colorCircle;
			var colorBarmeter;
				
			var commaFormat; 
			var elementsPerBar;
			var	showText;

			var padding;
			var chartwidth;
			var chartheight;
			
			var colToCircle;
			var currentnode = null;
			var hoverNode=null;
			
			var localStorageName="circlestructure";
			
			var nodeOld;
			
				//Default values for variables - set to root
			var currentID = "",
				oldID = "";
			

	var ease;
	var	timeElapsed = 0;
	var	interpolator = null;
	var	duration = 500;
	var	vOld;

	// Supprime les références circulaires dans un objet, pour pouvoir le convertir en XML
	function removeCircularReferences(obj, seen = new WeakSet()) { if (obj && typeof obj === 'object') { if (seen.has(obj)) { return; } seen.add(obj); for (const key in obj) { if (obj.hasOwnProperty(key)) { obj[key] = removeCircularReferences(obj[key], seen); } } } return obj; }			

	
	function drawText(ctx, text, fontSize, titleFont, centerX, centerY, radius, fillcolor="#000", strockcolor="#FFF",style="",font="Tahoma") {
		
		// startAngle:   In degrees, Where the text will be shown. 0 degrees if the top of the circle
		// kearning:     0 for normal gap between letters. Positive or negative number to expand/compact gap in pixels
		if (fontSize<6) return;			// Inutile d'afficher
		if (fontSize<12) fontSize=12;	// Taille min (même si ça dépasse)
		//Setup letters and positioning
		ctx.textBaseline = 'alphabetic';
		ctx.textAlign = 'center'; // Ensure we draw in exact center
		ctx.fillStyle = fillcolor;
		ctx.strokeStyle = strockcolor;
		ctx.lineWidth = 5;
		ctx.setLineDash([]);
		ctx.lineJoin = 'round';
		ctx.font = style+" "+fontSize+"pt '"+font+"'";
		
		//Get the text back in pieces that will fit inside the node
		var titleText = getLines(ctx, text, radius*2*0.7, fontSize, font);
		// Décortique l'objet retourné
		fontSize=titleText.fontSize;
		titleText=titleText.lines;
		
		if (fontSize<6) return;	// Si après adaptation, c'est trop petit...
		if (fontSize<12) fontSize=12;	// Taille min (même si ça dépasse)
		
		ctx.font = style+" "+fontSize+"pt '"+font+"'";

		//Loop over all the pieces and draw each line
		cpt=0;
		titleText.forEach(function(txt, iterator) { 
			if (cpt<4) {
				if (cpt==3) txt="...";
				ctx.textBaseline = "middle"; 
				ctx.strokeText(txt, centerX, centerY + ((-Math.min(titleText.length,4)/2)+iterator+0.5)*fontSize*1.1);
				ctx.fillText(txt, centerX, centerY + ((-Math.min(titleText.length,4)/2)+iterator+0.5)*fontSize*1.1 );
			}
			cpt+=1;
		})//forEach		
		
	}

	//Adjusted from: http://blog.graphicsgen.com/2015/03/html5-canvas-rounded-text.html
	function drawCircularText(ctx, text, fontSize, fontBold, titleFont, centerX, centerY, radius, startAngle, kerning) {
		// startAngle:   In degrees, Where the text will be shown. 0 degrees if the top of the circle
		// kearning:     0 for normal gap between letters. Positive or negative number to expand/compact gap in pixels
				
		//Setup letters and positioning
		ctx.textBaseline = 'alphabetic';
		ctx.textAlign = 'center'; // Ensure we draw in exact center
		ctx.font = fontBold+" "+fontSize + "pt " + titleFont;
		ctx.fillStyle = "rgba(255,255,255," + textAlpha +")";

		startAngle = startAngle * (Math.PI / 180); // convert to radians
		text = text.split("").reverse().join(""); // Reverse letters
		
		//Rotate 50% of total angle for center alignment
		for (var j = 0; j < text.length; j++) {
			var charWid = ctx.measureText(text[j]).width;
			startAngle += ((charWid + (j == text.length-1 ? 0 : kerning)) / radius) / 2;
		}//for j

		ctx.save(); //Save the default state before doing any transformations
		ctx.translate(centerX, centerY); // Move to center
		ctx.rotate(startAngle); //Rotate into final start position
			
		//Now for the fun bit: draw, rotate, and repeat
		for (var j = 0; j < text.length; j++) {
			var charWid = ctx.measureText(text[j]).width/2; // half letter
			//Rotate half letter
			ctx.rotate(-charWid/radius); 
			//Draw the character at "top" or "bottom" depending on inward or outward facing
			ctx.fillText(text[j], 0, -radius);
			//Rotate half letter
			//ctx.rotate(-0.1);
			ctx.rotate(-(charWid + kerning ) / radius); 
		}//for j
		
		ctx.restore(); //Restore to state as it was before transformations
	}//function drawCircularText
	
		
	//The draw function of the canvas that gets called on each frame
	function drawCanvas(chosenContext, hidden) {

		function drawPolygon(ctx, x, y, radius, sides) {
			if (sides < 3) return; // Un polygone a au moins 3 côtés

			ctx.beginPath();

			// Tracer chaque sommet du polygone
			for (let i = 0; i <= sides; i++) {
				const angle = (2 * Math.PI / sides) * i; // Diviser le cercle en "sides" parties
				const px = x + radius * Math.cos(angle);
				const py = y + radius * Math.sin(angle);

				if (i === 0) {
					ctx.moveTo(px, py); // Début du polygone
				} else {
					ctx.lineTo(px, py); // Ligne vers le sommet suivant
				}
			}

			ctx.closePath(); // Fermer le chemin pour relier le dernier sommet au premier
			ctx.stroke(); // Tracer le contour
		}

		//Clear canvas
		chosenContext.fillStyle = "#eee";
		chosenContext.rect(0,0,chartwidth,chartheight);
		chosenContext.fill();
		let nodeCpt=0;

		// It's slightly faster than nodes.forEach()
		for (var i = 0; i < nodeCount; i++) {
			node = nodes[i];

			var nodeX = ((node.x - zoomInfo.centerX) * zoomInfo.scale) + centerX,
				nodeY = ((node.y - zoomInfo.centerY) * zoomInfo.scale) + centerY,
				nodeR = node.r * zoomInfo.scale * (node.type=="1"?0.9:(node.type=="4"?1.05:1));
				
			//Use one node to reset the scale factor for the legend
			if(i === 0) scaleFactor = node.value/(nodeR * nodeR); 
						
			//Draw each circle
			if (node.mod=="hierarchy")
				drawPolygon(chosenContext, nodeX, nodeY, nodeR, 8);
			else {
				chosenContext.beginPath();
				chosenContext.arc(nodeX, nodeY, nodeR, 0,  2 * Math.PI, true);	
			}
			//If the hidden canvas was send into this function and it does not yet have a color, generate a unique one
			if(hidden) {
				if(node.color == null) {
					// If we have never drawn the node to the hidden canvas get a new color for it and put it in the dictionary.
					node.color = genColor();
					colToCircle[node.color] = node;
				} else {
					colToCircle[node.color] = node;
				}//if
				// On the hidden canvas each rectangle gets a unique color.
				chosenContext.fillStyle = node.color;
				chosenContext.fill();
				
			} else {
				// anciennement node.children
				chosenContext.fillStyle =  node.type=="3" ||  node.type=="2" ? colorCircle(node.depth) : (node.mycolor?node.mycolor:"rgb(255, 204, 0)"); // Couleur des noeuds
				if (node.type && node.type=="3") {chosenContext.fillStyle="rgba(0,0,0,0)";}
				
				if (node.type=="4") {
					chosenContext.lineWidth = 1;
					//chosenContext.setLineDash([10, 10]);
					chosenContext.strokeStyle= "rgba(255,255,255,0.5)"
					chosenContext.stroke();
					chosenContext.fillStyle="rgb(61, 168, 169)";
					chosenContext.fill();
				

				} else							
				if (node.type=="3") {
					chosenContext.lineWidth = 2;
					chosenContext.setLineDash([10, 10]);
					chosenContext.strokeStyle= "rgba(255,255,255,0.5)"
					chosenContext.stroke();
					chosenContext.fill();

				} else {
					chosenContext.fill();
					if (node.mod=="template") {
						var pattern = chosenContext.createPattern(pattern_img,'repeat');
						chosenContext.fillStyle=pattern;
						chosenContext.fill();
					}
				}

			// Current node layout
			if (node.ID==currentnode.ID) {
				chosenContext.lineWidth = 6;
				chosenContext.setLineDash([]);
				chosenContext.strokeStyle= "rgba(255,255,255,1)";
				chosenContext.stroke();
				
			} else
			// Hover layout
			if (node.ID==hoverNode) {
				chosenContext.lineWidth = 3;
				chosenContext.setLineDash([]);
				chosenContext.strokeStyle= "rgba(255,255,255,1)";
				chosenContext.stroke();
				
			} 
			
			
			}//else
	

		
			//Draw the bars inside the circles (only in the visible canvas)
			//Only draw bars in leaf nodes
			// Not used for now...
			/* if(!node.children && 1!=1) {
				//Only draw the bars that are in the same parent ID as the clicked on node
				if(node.ID.lastIndexOf(currentID, 0) === 0  & !hidden) {
					//if(node.ID === "1.1.1.30") console.log(currentID);
														
					//Variables for the bar title
					var drawTitle = true;
					var fontSizeTitle = Math.round(nodeR / 10);
					if (fontSizeTitle < 8) drawTitle = false;

					//Only draw the title if the font size is big enough
					if(drawTitle & showText) {	
						//First the light grey total text
						chosenContext.font = (fontSizeTitle*0.5 <= 5 ? 0 : Math.round(fontSizeTitle*0.5)) + "pt " + bodyFont;
						chosenContext.fillStyle = "rgba(0,0,0," + (0.5*textAlpha) +")" //"#BFBFBF";
						chosenContext.textAlign = "center";
						chosenContext.textBaseline = "middle"; 
						chosenContext.fillText("Total "+commaFormat(node.size)+" (in thousands)", nodeX, nodeY + -0.75 * nodeR);
						
						//Get the text back in pieces that will fit inside the node
						var titleText = getLines(chosenContext, node.name, nodeR*2*0.7, fontSizeTitle, titleFont);
						//Loop over all the pieces and draw each line
						titleText.forEach(function(txt, iterator) { 
							chosenContext.font = fontSizeTitle + "pt " + titleFont;
							chosenContext.fillStyle = "rgba(" + mainTextColor[0] + "," + mainTextColor[1] + ","+ mainTextColor[2] + "," + textAlpha +")";
							chosenContext.textAlign = "center";
							chosenContext.textBaseline = "middle"; 
							chosenContext.fillText(txt, nodeX, nodeY + (-0.65 + iterator*0.125) * nodeR);
						})//forEach
						
					}//if
					
				}//if -> node.ID.lastIndexOf(currentID, 0) === 0 & !hidden
			} */ //if -> node.ID in dataById 
			
		}//for i
		
	
		
		//Do a second loop because the arc titles always have to be drawn on top
		for (var i = nodeCount-1; i >=0; i--) {
			node = nodes[i];
		
			var nodeX = ((node.x - zoomInfo.centerX) * zoomInfo.scale) + centerX,
				nodeY = ((node.y - zoomInfo.centerY) * zoomInfo.scale) + centerY,
				nodeR = node.r * zoomInfo.scale * (node.type=="1"?0.9:(node.type=="4"?1.05:1));
				
				
			
				titleFont="Arial";
				if(!hidden & showText & (node.ID==currentnode.ID || node.parent==currentnode || (node.parent && node.parent.parent==currentnode) || ((currentnode.parent && (currentnode.type!="2" || currentnode.parent.children.length>1)) && (node.ID==currentnode.parent.ID || (node.parent && node.parent.ID==currentnode.parent.ID))  ))) {  
					//Calculate the best font size for the non-leaf nodes

					thename=node.name;
					
					if (node.type != "1" && node==currentnode || currentnode.parent==node) {
						var fontSizeTitle = Math.round(nodeR / 6);
						if (fontSizeTitle > 4) drawCircularText(chosenContext, thename.replace(/,? and /g, ' & '), fontSizeTitle, "bold", titleFont, nodeX, nodeY, nodeR, 0, 0);  // rotationText[counter] pour le 1er 0
					} else {	
						
						var fontSizeTitle = Math.round(nodeR / 3);

						if (node.type == "1") {
							
							// Limite la taille max pour les rôles, et écrit en noir
							if (fontSizeTitle>36) fontSizeTitle=36;
							drawText(chosenContext, thename.replace(/,? and /g, ' & '), fontSizeTitle, titleFont, nodeX, nodeY, nodeR,"#000000","#FFFFFF");  // rotationText[counter] pour le 1er 0
						}
						else
						{
							drawText(chosenContext, thename.replace(/,? and /g, ' & '), fontSizeTitle, titleFont, nodeX, nodeY, nodeR,"#FFFFFF","#000000","bold");  // rotationText[counter] pour le 1er 0
						}
					}
				}//if
		


		}//for i
		
	}//function drawCanvas
					
	//Jump to the destination
	function quickZoomToCanvas(focusNode) {
			
		//Remove all previous popovers - if present
		$('.popoverWrapper').remove(); 
		$('.popover').each(function() {
				$('.popover').remove(); 	
		}); 
					
		//Save the ID of the clicked on node (or its parent, if it is a leaf node)
		//Only the nodes close to the currentID will have bar charts drawn
		if (focusNode === focus) currentID = ""; 
		else currentID = focusNode.ID;
		
		$(".contentleft").load("/circle/detail.php?id="+focusNode.ID);
		
		//Set the new focus
		focus = focusNode;
		if (focusNode.type=="1" || focusNode.children && focusNode.children.length<2)
			var v = [focus.x, focus.y, focus.r * 4.05]; //The center and width of the new "viewport"
		else 
			var v = [focus.x, focus.y, focus.r * 2.05];

		zoomInfo.centerX = v[0];
		zoomInfo.centerY = v[1];
		zoomInfo.scale = diameter / v[2];

		drawCanvas(context);
		drawCanvas(hiddenContext, true);
		vOld = v; //Save the "viewport" of the next state as the next "old" state

		
	}//function zoomToCanvas					
					
	// ***********************************
	// Script pour l'interface
	// ***********************************
	// Fonctions appelées après le chargement complet de la page
	$(function() {

	var mouseoverFunction = function(e){
		//Figure out where the mouse click occurred.
		var mouseX = e.offsetX*2; //e.layerX;
		var mouseY = e.offsetY*2; //e.layerY;
		

		// Get the corresponding pixel color on the hidden canvas and look up the node in our map.
		// This will return that pixel's color
		var col = hiddenContext.getImageData(mouseX, mouseY, 1, 1).data;
		//Our map uses these rgb strings as keys to nodes.
		var colString = "rgb(" + col[0] + "," + col[1] + ","+ col[2] + ")";
		//console.log (colString);
		//console.log(colToCircle);
		var node = colToCircle[colString];

		//If there was an actual node clicked on, zoom into this
		if(node) {
			hoverNode=node.ID;
			//console.log (hoverNode);
		}
		drawCanvas(context);
	}
	
	
	//Function to run oif a user clicks on the canvas
	var clickFunction = function(e){
		if (!wasDragging) {
			//Figure out where the mouse click occurred.
			var mouseX = e.offsetX*2; //e.layerX;
			var mouseY = e.offsetY*2; //e.layerY;

			// Get the corresponding pixel color on the hidden canvas and look up the node in our map.
			// This will return that pixel's color
			var col = hiddenContext.getImageData(mouseX, mouseY, 1, 1).data;
			//Our map uses these rgb strings as keys to nodes.
			var colString = "rgb(" + col[0] + "," + col[1] + ","+ col[2] + ")";
			var node = colToCircle[colString];

			//If there was an actual node clicked on, zoom into this
			if(node) {
				//Perform the zoom
				zoomToCanvas(node);			
			} else {zoomToCanvas(root)}//if -> node
		}
	}//function clickFunction

	//Listen for clicks on the main canvas
	//document.getElementById("canvas").addEventListener("click", clickFunction);
	$("body").delegate("#canvas","click", clickFunction);
	$("body").delegate("#canvas","mousemove",mouseoverFunction);
	
	////////////////////////////////////////////////////////////// 
	//////////////// Mousemove functionality ///////////////////// 
	////////////////////////////////////////////////////////////// 
	
	//Only run this if the user actually has a mouse

		
		//Listen for mouse moves on the main canvas
		var mousemoveFunction = function(e){
			//Figure out where the mouse click occurred.
			var mouseX = e.offsetX*2; //e.layerX;
			var mouseY = e.offsetY*2; //e.layerY;
			
			// Get the corresponding pixel color on the hidden canvas and look up the node in our map.
			// This will return that pixel's color
			var col = hiddenContext.getImageData(mouseX, mouseY, 1, 1).data;
			//Our map uses these rgb strings as keys to nodes.
			var colString = "rgb(" + col[0] + "," + col[1] + ","+ col[2] + ")";
			var node = colToCircle[colString];

			//Only change the popover if the user mouses over something new
			if(node !== nodeOld || 1) {
				//Remove all previous popovers
				$('.popoverWrapper').remove(); 
				$('.popover').each(function() {
						$('.popover').remove(); 	
				 }); 
				//Only continue when the user mouses over an actual node
				if(node) {
					//Only show a popover for the leaf nodes
					//if(typeof node.ID !== "undefined") {
						//Needed for placement
						var nodeX = ((node.x - zoomInfo.centerX) * zoomInfo.scale) + centerX,
							nodeY = ((node.y - zoomInfo.centerY) * zoomInfo.scale) + centerY,
							nodeR = node.r * zoomInfo.scale;
						
						//Create the wrapper div for the popover
						// Anciennement "document"
						var div = document.createElement('div');
						div.setAttribute('class', 'popoverWrapper');
						//document.getElementById('chart').appendChild(div);
						$("td.right").eq(0).append(div);
						
						//Position the wrapper right above the circle
						$(".popoverWrapper").css({
							'position':'absolute',
							'top':mouseY/2+20, // -nodeR
							'left':mouseX/2+10 //nodeX/2 //+padding*5/4
						});
						
						//Show the tooltip
						$(".popoverWrapper").popover({
							placement: 'auto bottom',
							container: 'body',
							trigger: 'manual',
							html : true,
							animation:false,
							content: function() { 
								return "<span class='nodeTooltip'>" + node.name + "</span>"; }
							});
						$(".popoverWrapper").popover('show');
					//}//if -> typeof node.ID !== "undefined"
				}//if -> node
			}//if -> node !== nodeOld
			
			nodeOld = node;
		}//function mousemoveFunction
		
		//document.getElementById("canvas").addEventListener("mousemove", mousemoveFunction);
		$("body").delegate("#canvas","mousemove", mousemoveFunction);
	

				// **************************************
				// Colonne de gauche
				// **************************************
				
				// Adaptation en largeur de la colonne de gauche
				$( "#resizeelem" ).draggable({ axis: "x" ,

				  stop: function(event, ui) {
					pos=$(".left").width()+ui.position.left+4;
					if (pos<250) pos=250;
					  console.log("resize:"+pos);
					$(".left").css("width",pos);
					$( "#resizeelem" ).css("left",0);
					refreshCircle(false);
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
					

				// Chargement des données si sauvegardée localement
				if (localStorage.savecircle)
					load();

if (window.circleEditorConfig.view) {
			function saveSQL() {
				save();
				$.ajax({
					url: '/ajax/saveorga.php', // Remplacez par l'URL de votre serveur PHP
					type: 'POST',
					contentType: 'application/json',
					dataType: 'json',
					data: JSON.stringify(JSON.parse(localStorage.getItem("circlestructure"))),
					success: function(response) {
						if (response.status === 'ok') { // Vérifiez si le serveur a renvoyé un statut "ok"
							// Récupère la nouvelle structure, avec les ID mis à jour
							
							localStorage.setItem(localStorageName, JSON.stringify(response.json));
							root=response.json;
						
							refreshCircle(false);refreshCircle(false);
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
			
				$("#btn_new").click(function () {
					showPopup("/popup/circle/new.php", ("" + window.circleEditorConfig.text + ""));
/*					// Crée une nouvelle organisation avec un seul cercle
					if (confirm("Voulez-vous réellement créer une nouvelle organisation?\nL'organisation en cours sera remplacée. Créez un compte pour la sauvegarder.") ) {
						root=JSON.parse('{"name": "Mon organisation", "ID": "TMP_1", "type": "4", "children": [{"name": "Ancrage", "ID": "TMP_2", "type": "2", "children": [ {"name": "Facilitation", "mycolor":"#FF6600", "ID": "TMP_3","type":"1", "mod":"template",  "size":10}, {"name": "Pilotage",  "mycolor":"#FF2200", "type":"1", "mod":"template", "ID": "TMP_4", "size":10}, {"name": "Mémoire", "mycolor":"#FF9900", "type":"1", "mod":"template", "ID": "TMP_8", "size":10}, {"name": "Role opérationnel", "type":"1", "ID": "TMP_9", "size":10}]},{"name": "CA", "ID": "TMP_5", "type": "2", "children": [ {"name": "Trésorier", "ID": "TMP_6","type":"1",  "size":10}, {"name": "Président", "type":"1", "ID": "TMP_7", "size":10}]}]}');
						
						// Stock sur le disque
						localStorage.setItem(localStorageName, JSON.stringify(root));
						currentnode=focusNode=root;
						
						// Raffraichi l'affichage
						refreshCircle();
					}*/
				});
				

				$("#btn_save").click(function () {
					saveSQL();
				});
				
				$("#btn_load").click(function () {
					//loadSQL();
					showPopup("/popup/circle/load.php", ("" + window.circleEditorConfig.text2 + ""));
				});
				
				$("#btn_support").click(function () {
					showPopup("/popup/support.php", ("" + window.circleEditorConfig.text3 + ""));
				});	
				
				// Boutons pour manipuler l'holarchie
				$("body").delegate("#btn_add_role","click", function () {
					showPopup("/popup/circle/addrole.php", ("" + window.circleEditorConfig.text4 + ""));					
				});
				$("body").delegate("#btn_edit_role","click", function () {
					showPopup("/popup/circle/editrole.php", ("" + window.circleEditorConfig.text5 + ""));					
				});
				$("body").delegate("#btn_move_role","click", function () {
					showPopup("/popup/circle/moverole.php", ("" + window.circleEditorConfig.text6 + ""));					
				});
}

				function supprimerNoeud(noeud) {
					console.log(noeud);
				  const parent = noeud.parent;

				  if (parent && Array.isArray(parent.children)) {
					// Rechercher l'index du nœud à supprimer dans le tableau Children
					const index = parent.children.findIndex(child => child === noeud);

					if (index !== -1) {
					  // Supprimer l'enfant du tableau
					  parent.children.splice(index, 1);
					  console.log ("Nouvelle structure");

					  // Supprimer la référence Parent dans l'enfant pour éviter des cycles
					  //delete noeud.parent;

					  return true; // Nœud supprimé avec succès
					} else {console.log("pas trouvé");}
				  }  else {console.log("pas de parent");}

				  return false; // Nœud non trouvé ou parent invalide
				}
				
				$("body").delegate("#btn_delete_role","click", function () {
					
						// Se déplace sur le parent
						gotoNode=currentnode.parent;

						
						if (confirm("Voulez-vous réellement supprimer ce noeud ?"))	{
								supprimerNoeud(currentnode);
								save();
								nodes = pack.nodes(root),

										focus = root,
										nodeCount = nodes.length;
			
								zoomToCanvas(gotoNode);
							}		
				});

				$("body").delegate("#btn_add","click", function () {
					alert ("Add");
						// Ajoute un élément au noeud courant
					console.log("btn_add - CurrentNode");
					console.log(currentnode);
					if (currentnode) {
						currentnode.children.push(JSON.parse('{"name": "Role ajouté", "ID": "50", "size":10}'));
					}
					;
					refreshCircle();
					
				});
				// Sauve automatiquement lorsque on quitte les champs d'entête
				$("body").delegate(".interface-top input#title2","focusout",function (e) {
					root.name=$(this).val();
					save();
					refreshCircle();
				});	
				
				function highlightText(text) {
					if (text.length<3) {
							// Efface tous les éléments
							$(".filter_zone").find(".highlight").each(function() {
								$(this).replaceWith($(this).text()); // Remplace les balises <span> par leur contenu
							});
					} else {
						var allzone = $(".filter_zone");
						allzone.each(function() {
							content=$(this);
							
							var regex = new RegExp('(>[^<]*?)(' + text + ')([^<]*?<)', 'gi');

							// Efface tous les éléments
							content.find(".highlight").each(function() {
								$(this).replaceWith($(this).text()); // Remplace les balises <span> par leur contenu
							});

							var contentHTML = content.html();
							content.html(contentHTML.replace(regex, function(match, p1, p2, p3) {
								return p1 + '<span class="highlight">' + p2 + '</span>' + p3;
							}));
						});
					}
				}
    
				$("body").delegate("#quickfilter","keyup", function () {
					// Cache toute les lignes
					$(".memo_item").hide();
					// Affiche toute les lignes qui contiennent le texte
					$('.memo_item:icontains('+$(this).val()+')').show();
					highlightText($(this).val())
				});					
	
			
			});
			
			// *********************************************************
			// Définition des fonction appelées par les boutons
			// *********************************************************



					
			function load() {

				
			}
			
			function newDoc() {

			}
						
			function save() {
				localStorage.setItem(localStorageName, JSON.stringify(removeCircularReferences(root)));
			}
			$(window).resize(function() {
				refreshCircle();
			});
				
				

		
		
