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
		<script>
			
			let root;
			var canvas, hiddenCanvas, context, hiddenContext;
			var node = null;
			var centerX = centerY = null;
			var zoomInfo = null;
			var pack;
			var nodes;
			var nodeByName;	
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
					
					if (node.type != "1" && node==currentnode || currentnode.parent==node) {
						var fontSizeTitle = Math.round(nodeR / 6);
						if (fontSizeTitle > 4) drawCircularText(chosenContext, node.name.replace(/,? and /g, ' & '), fontSizeTitle, "bold", titleFont, nodeX, nodeY, nodeR, 0, 0);  // rotationText[counter] pour le 1er 0
					} else {	
						
						var fontSizeTitle = Math.round(nodeR / 3);
						if (node.type == "1") {
							
							// Limite la taille max pour les rôles, et écrit en noir
							if (fontSizeTitle>36) fontSizeTitle=36;
							drawText(chosenContext, node.name.replace(/,? and /g, ' & '), fontSizeTitle, titleFont, nodeX, nodeY, nodeR,"#000000","#FFFFFF");  // rotationText[counter] pour le 1er 0
						}
						else
							drawText(chosenContext, node.name.replace(/,? and /g, ' & '), fontSizeTitle, titleFont, nodeX, nodeY, nodeR,"#FFFFFF","#000000","bold");  // rotationText[counter] pour le 1er 0
					
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

<?
	// N'intègre pas le code d'édition s'il s'agit d'un lien de partage
	if (!isset($_GET["view"])) {
?>
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
					// Crée une nouvelle organisation avec un seul cercle
					
					if (confirm("Voulez-vous réellement créer une nouvelle organisation?\nL'organisation en cours sera remplacée. Créez un compte pour la sauvegarder.") ) {
						root=JSON.parse('{"name": "Mon organisation", "ID": "TMP_1", "type": "4", "children": [{"name": "Ancrage", "ID": "TMP_2", "type": "2", "children": [ {"name": "Facilitation", "mycolor":"#FF6600", "ID": "TMP_3","type":"1", "mod":"template",  "size":10}, {"name": "Pilotage",  "mycolor":"#FF2200", "type":"1", "mod":"template", "ID": "TMP_4", "size":10}, {"name": "Mémoire", "mycolor":"#FF9900", "type":"1", "mod":"template", "ID": "TMP_8", "size":10}, {"name": "Role opérationnel", "type":"1", "ID": "TMP_9", "size":10}]},{"name": "CA", "ID": "TMP_5", "type": "2", "children": [ {"name": "Trésorier", "ID": "TMP_6","type":"1",  "size":10}, {"name": "Président", "type":"1", "ID": "TMP_7", "size":10}]}]}');
						
						// Stock sur le disque
						localStorage.setItem(localStorageName, JSON.stringify(root));
						currentnode=focusNode=root;
						
						// Raffraichi l'affichage
						refreshCircle();
					}
				});
				

				$("#btn_save").click(function () {
					saveSQL();
				});
				
				$("#btn_load").click(function () {
					//loadSQL();
					showPopup("/popup/circle/load.php", "<?=T_("Charger un schéma",true)?>");
				});
				
				$("#btn_support").click(function () {
					showPopup("/popup/support.php", "<?=T_("Soutenez-nous !",true)?>");
				});	
				
				// Boutons pour manipuler l'holarchie
				$("body").delegate("#btn_add_role","click", function () {
					showPopup("/popup/circle/addrole.php", "<?=T_("Ajouter un noeud",true)?>");					
				});
				$("body").delegate("#btn_edit_role","click", function () {
					showPopup("/popup/circle/editrole.php", "<?=T_("Editer un noeud",true)?>");					
				});
				$("body").delegate("#btn_move_role","click", function () {
					showPopup("/popup/circle/moverole.php", "<?=T_("Déplacer un noeud",true)?>");					
				});
<?
				} 
?>

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
				
				

		
		</script>


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

		<script>
			// ***********************************
			// Script pour les cercles
			// ***********************************
var loadImage = function(src, cb) {
    var img = new Image();
    img.src = src;
    img.onload  = function(){ cb(null, img); };
    img.onerror = function(){ cb('IMAGE ERROR', null); };
};

function isJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
let pattern_img;

function removeColorNodes(json, size=10) {
    if (Array.isArray(json)) {
        // Si c'est un tableau, appliquer récursivement sur chaque élément
        console.log("zone 1");
        return json.map(item => removeColorNodes(item, size));
    } else if (typeof json === 'object' && json !== null) {
        // Si c'est un objet, vérifier chaque clé
        for (let key in json) {
            if (key === 'color') {
                // Supprimer la clé "color"
                delete json[key];
            } else if (key === 'size') {
                // Adapte la clé
                json[key]=size;
            } else if (key === 'children') {
                // Appliquer récursivement sur les propriétés imbriquées, sauf pour le noeud parent
                json[key] = removeColorNodes(json[key], (json["type"]==2?(size>2?size-2:2):size));
            }
        }
    }
    return json; // Retourner l'objet ou la valeur inchangée
}

function refreshCircle(zoom = true) {
		// Init fields
	$("canvas").remove();
	
	// Raffraichi les couleurs
	removeColorNodes(root);
	console.log (root);
	
	var currentID=currentnode.ID;
	drawAll();
	// Find the current node again after drawing
	if (zoom) quickZoomToCanvas(currentnode);

	
	for (const [key, value] of Object.entries(colToCircle)) {
	  if (value.ID==currentID) currentnode=value;
	}

}

function init() {
<?
		// Comportement différent si appelé avec un paramètre de visualisation ($_GET["view"])
		if (isset($_GET["view"])) {
			// Récupère l'organisation
			$org=new \dbObject\holon();
			$org->load(['accesskey',$_GET["view"]]);
			if ($org->getId()>0) {
			
?>





		console.log("Yop");
		localStorageName="tmpcirclestructure";
		// Charge l'organisation avec l'access key, pour bypasser le "canView()"
		// Attention, mauvaise gestion des erreurs ici...
		$.getJSON("/ajax/loadorga.php?id=<?=$org->getId()?>&accesskey=<?=$_GET["view"]?>", function( my_var ) {		
		//my_var=JSON.parse('{"name": "<?=$org->get("name")?>", "ID": "TMP_1", "type": "4", "children": [{"name": "Ancrage", "ID": "TMP_2", "type": "2", "children": [ {"name": "Facilitation", "mycolor":"#FF6600", "ID": "TMP_3","type":"1", "mod":"template",  "size":10}, {"name": "Pilotage",  "mycolor":"#FF2200", "type":"1", "mod":"template", "ID": "TMP_4", "size":10}, {"name": "Mémoire", "mycolor":"#FF9900", "type":"1", "mod":"template", "ID": "TMP_8", "size":10}, {"name": "Role opérationnel", "type":"1", "ID": "TMP_9", "size":10}]},{"name": "CA", "ID": "TMP_5", "type": "2", "children": [ {"name": "Trésorier", "ID": "TMP_6","type":"1",  "size":10}, {"name": "Président", "type":"1", "ID": "TMP_7", "size":10}]}]}');
		localStorage.setItem(localStorageName, JSON.stringify(my_var));
		$("canvas").remove();
		queue().defer(loadImage, "/img/rayures.png").await(drawAll);
		});	


<? 
	} else {
		echo "alert('Error!')";
	}

} else { ?>
	
	// Efface tous les canvas
	if (localStorage.getItem(localStorageName)==null || !isJsonString(localStorage.getItem(localStorageName))) {
		$.getJSON("/data/occupation.json", function( my_var ) {		
			localStorage.setItem(localStorageName, JSON.stringify(my_var));
			$("canvas").remove();
			queue().defer(loadImage, "/img/rayures.png").await(drawAll);
		});		
	} else {
		// 
		$("canvas").remove();
		queue().defer(loadImage, "/img/rayures.png").await(drawAll);
	}
	
<? } ?>
	
}
$(function() {
	init();

		
		
		$("body").delegate(".navTo","click",function() {
			zoomToCanvas(idIndexedMap[$(this).attr("data-src")]);
		});
	});
	
	function animate() {
		var dt = 0;
		d3.timer(function(elapsed) {
			interpolateZoom(elapsed - dt);
			if (!alwaysDisplayText) interpolateFadeText(elapsed - dt);
			dt = elapsed;
			drawCanvas(context);

			return stopTimer;
		});
	}//function animate
	
	function arraysAreEqual(arr1, arr2) {
	  // Vérifier si les longueurs sont différentes
	  if (arr1.length !== arr2.length) {
		return false;
	  }
	  
	  // Vérifier chaque élément
	  for (let i = 0; i < arr1.length; i++) {
		if (arr1[i] !== arr2[i]) {
		  return false;
		}
	  }
	  
	  return true;
	}	
	
	//Create the interpolation function between current view and the clicked on node
	function zoomToCanvas(focusNode) {
		
		// A comparer avec la variable globale "focus"
		
		
		currentnode=focusNode;
		
 
			// Load the content description
			if (focus!==focusNode)
				$(".contentleft").load("/circle/detail.php?id="+currentnode.ID);		
		
		
		//Temporarily disable click & mouseover events
		$("#canvas").css("pointer-events", "none");
	
		//Remove all previous popovers - if present
		$('.popoverWrapper').remove(); 
		$('.popover').each(function() {
				$('.popover').remove(); 	
		}); 
					
		currentID = focusNode.ID;
		
		//Set the new focus
		if (focus===focusNode)
			focus=root;
		else
			focus = focusNode;
		if (focusNode.type=="1" || focusNode.children && focusNode.children.length<2)
			var v = [focus.x, focus.y, focus.r * 4.05]; //The center and width of the new "viewport"
		else 
			var v = [focus.x, focus.y, focus.r * 2.05];
		if (arraysAreEqual(vOld,v)) {
			// Rafraîchi simplement l'affichage
			refreshCircle();
		} else {
			

		//Create interpolation between current and new "viewport"
		interpolator = d3.interpolateZoom(vOld, v);
			
		//Set the needed "zoom" variables
		duration = 	Math.max(500, interpolator.duration); //Interpolation gives back a suggested duration	 		
		timeElapsed = 0; //Set the time elapsed for the interpolateZoom function to 0	
		showText = false; //Don't show text during the zoom
		alwaysDisplayText=false;
		
		vOld = v; //Save the "viewport" of the next state as the next "old" state
		
		//Start animation
		stopTimer = false;
		animate();
	}
		
	}//function zoomToCanvas
	

	
	//Perform the interpolation and continuously change the zoomInfo while the "transition" occurs
	function interpolateZoom(dt) {
		if (interpolator) {
			timeElapsed += dt;
			var t = ease(timeElapsed / duration); //mini interpolator that puts 0 - duration into 0 - 1 in a cubic-in-out fashion
			
			//Set the new zoom variables
			zoomInfo.centerX = interpolator(t)[0];
			zoomInfo.centerY = interpolator(t)[1];
			zoomInfo.scale = diameter / interpolator(t)[2];
		
			//After iteration is done remove the interpolater and set the fade text back into motion
			if (timeElapsed >= duration) {
				interpolator = null;
				showText = true;
				fadeText = true;
				timeElapsed = 0;
				
				//Draw the hidden canvas again, now that everything is settled in 
				//to make sure it is in the same state as the visible canvas
				//This way the tooltip and click work correctly
				drawCanvas(hiddenContext, true);
				
				//Update the texts in the legend
				d3.select(".legendWrapper").selectAll(".legendText")
					.text(function(d) { return commaFormat(Math.round(scaleFactor * d * d / 10)*10); });
				
			}//if -> timeElapsed >= duration
		}//if -> interpolator
	}//function zoomToCanvas

	//Text fading variables
		showText = true, //Only show the text while you're not zooming
		textAlpha = 1, //After a zoom is finished fade in the text;
		fadeText = false,
		fadeTextDuration = 250; //750
	//Function that fades in the text - Otherwise the text will be jittery during the zooming	
	function interpolateFadeText(dt) {
		if(fadeText) {
			timeElapsed += dt;
			textAlpha = ease(timeElapsed / fadeTextDuration);				
			if (timeElapsed >= fadeTextDuration) {
				//Enable click & mouseover events again
				$("#canvas").css("pointer-events", "auto");
				
				fadeText = false; //Jump from loop after fade in is done
				stopTimer = true; //After the fade is done, stop with the redraws / animation
			}//if
		}//if
	}//function interpolateFadeText

	
//Initiates practically everything
function drawAll(error, img) {

	if (img != null) pattern_img=img;
	
	// Récupère le json sur le storage interne
	if (typeof(root) == "undefined") {
		root=localStorage.getItem(localStorageName);
		if (root==null) {
			root=JSON.parse('{"name": "Demo", "ID": "0", "type": "4", "children": [ {"name": "Trésorerie", "ID": "3", "size":10}]}');
		} else
			root=JSON.parse(root);
	}
	removeColorNodes(root);
	// Create the list
	transformJSONtoHTML(root, "/xslt/list_role.xml", 'role_list');
	
	
	////////////////////////////////////////////////////////////// 
	////////////////// Create Set-up variables  ////////////////// 
	////////////////////////////////////////////////////////////// 

	//Trying to figure out how to detect touch devices (exept for laptops with touch screens)
	//Since there's no need to have a mouseover function for touch
	//There has to be a more foolproof way than this...
	//var mobileSize = true;
	//if (!("ontouchstart" in document.documentElement) | window.innerWidth > 900) mobileSize = false;
	window.mobileAndTabletcheck = function() {
		var check = false;
		(function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4)))check = true})
			(navigator.userAgent||navigator.vendor||window.opera);
		return check;
	}//function mobileAndTabletcheck
	mobileSize = window.mobileAndTabletcheck();
	
	padding = 0; // Default: 20
	chartwidth = $("#chart").innerWidth()*2;
	chartheight = ($("html").height()-40-$(".interface-bottom").innerHeight())*2;
	console.log ($(".interface-bottom").height()+" - "+chartheight);
	//	height = (mobileSize | $("#chart").innerWidth() < 768 ? width : $("#chart").innerHeight() ); // -90?



	centerX = chartwidth/2;
	centerY = chartheight/2;


	////////////////////////////////////////////////////////////// 
	/////////////////////// Create SVG  /////////////////////// 
	////////////////////////////////////////////////////////////// 
	
	//Create the visible canvas and context
	canvas  = d3.select("#chart").append("canvas")
		.attr("id", "canvas")
		.attr("width", chartwidth)
		.attr("height", chartheight)
		.style("zoom", "50%");
		
	context = canvas.node().getContext("2d",{willReadFrequently:true});
		context.clearRect(0, 0, chartwidth, chartheight);
	
	//Create a hidden canvas in which each circle will have a different color
	//We can use this to capture the clicked/hovered over on circle
	hiddenCanvas  = d3.select("#chart").append("canvas")
		.attr("id", "hiddenCanvas")
		.attr("width", chartwidth)
		.attr("height", chartheight)
		.style("display","none")
		.style("zoom", "50%");
		
	hiddenContext = hiddenCanvas.node().getContext("2d",{willReadFrequently:true});
		hiddenContext.clearRect(0, 0, chartwidth, chartheight);

	////////////////////////////////////////////////////////////// 
	/////////////////////// Create Scales  /////////////////////// 
	////////////////////////////////////////////////////////////// 

	mainTextColor = [74,74,74],//"#4A4A4A",
		titleFont = "Oswald",
		bodyFont = "Merriweather Sans";
	
	colorCircle = d3.scale.ordinal()
			.domain([0,1,2,3,4,5,6])
			.range(['rgb(61, 168, 169)','rgba(255, 255, 255,0.4)','rgba(255, 255, 255,0.4)','rgba(255, 255, 255,0.4)','rgba(255, 255, 255,0.4)','rgba(255, 255, 255,0.4)']);
			
	colorBar = d3.scale.ordinal()
		.domain(["16 to 19","20 to 24","25 to 34","35 to 44","45 to 54","55 to 64","65+"])
		.range(["#EFB605", "#E3690B", "#CF003E", "#991C71", "#4F54A8", "#07997E", "#7EB852"]);	

		diameter = Math.min(chartwidth*0.9, chartheight*0.9),
		radius = diameter / 2;
		
	commaFormat = d3.format(',');

	if (currentnode) {
		zoomInfo = {
			centerX: currentnode.x,
			centerY: currentnode.y,
			scale: diameter/currentnode.r*2.05
		};
		
		
	} else {
		currentnode=root;
		zoomInfo = {
			centerX: centerX,
			centerY: centerY,
			scale: 1
		};
	}
	
	//Dataset to swtich between color of a circle (in the hidden canvas) and the node data	
	colToCircle = {};
	
	pack = d3.layout.pack()
		.padding(1)
		.size([diameter, diameter]) //[diameter, diameter]
		.value(function(d) { return d.size; })
		.sort(function(d) { return d.ID; });

	////////////////////////////////////////////////////////////// 
	////////////// Create Circle Packing Data ////////////////////
	////////////////////////////////////////////////////////////// 

	nodes = pack.nodes(root),
		focus = root,
		nodeCount = nodes.length;

	nodeByName = {};
	nodes.forEach(function(d,i) {
		nodeByName[d.name] = d;
	});

	


	////////////////////////////////////////////////////////////// 
	/////////////////// Click functionality ////////////////////// 
	////////////////////////////////////////////////////////////// 
	
	
	if (!mobileSize) {
		nodeOld = root;
	}
	
//if !mobileSize

	////////////////////////////////////////////////////////////// 
	///////////////////// Zoom Function //////////////////////////
	////////////////////////////////////////////////////////////// 
	
	//Based on the generous help by Stephan Smola
	//http://bl.ocks.org/smoli/d7e4f9199c15d71258b5
	
	ease = d3.ease("cubic-in-out"),
		timeElapsed = 0,
		interpolator = null,
		duration = 500, //Starting duration (deafault:1500)
		vOld = [focus.x, focus.y, focus.r * 2.05];
	

	////////////////////////////////////////////////////////////// 
	//////////////////// Other Functions /////////////////////////
	////////////////////////////////////////////////////////////// 
	
	//The start angle in degrees for each of the non-node leaf titles
	var rotationText = [-14,4,23,-18,-10.5,-20,20,20,46,-30,-25,-20,20,15,-30,-15,-45,12,-15,-16,15,15,5,18,5,15,20,-20,-25]; //The rotation of each arc text //[-14,4,23,-18,-10.5,-20,20,20,46,-30,-25,-20,20,15,-30,-15,-45,12,-15,-16,15,15,5,18,5,15,20,-20,-25]

	


	////////////////////////////////////////////////////////////// 
	///////////////////// Create Search Box ////////////////////// 
	////////////////////////////////////////////////////////////// 

	//Create options - all the root
	var options = nodes.map(function(d) { return d.name; });
	
	//Function to call once the search box is filled in
	searchEvent = function(occupation) { 
		//If the occupation is not equal to the default
		if (occupation !== "" & typeof occupation !== "undefined") {
			zoomToCanvas(nodeByName[occupation]);
		}//if 
	}//searchEvent
		
	////////////////////////////////////////////////////////////// 
	/////////////////////// FPS Stats box //////////////////////// 
	////////////////////////////////////////////////////////////// 
	
	/*
	var stats = new Stats();
	stats.setMode(0); // 0: fps, 1: ms, 2: mb

	// align top-left
	stats.domElement.style.position = 'absolute';
	stats.domElement.style.left = '0px';
	stats.domElement.style.top = '0px';

	document.body.appendChild( stats.domElement );

	d3.timer(function(elapsed) {
		stats.begin();
		stats.end();
	});
	*/
	
	////////////////////////////////////////////////////////////// 
	/////////////////////// Initiate ///////////////////////////// 
	////////////////////////////////////////////////////////////// 
			
	//First zoom to get the circles to the right location
	if (currentnode) {
		//alert (currentnode.ID);
			// Raffraichi les couleurs
		removeColorNodes(root);
		console.log (root);
	
		var currentID=currentnode.ID;
	
		quickZoomToCanvas(currentnode);
	} else {
		currentnode=root;
		removeColorNodes(root);
		console.log (root);
	
		var currentID=currentnode.ID;
		
		quickZoomToCanvas(root);
	}
	//Draw the hZdden canvas at least once
	//drawCanvas(hiddenContext, true);
	//Draw the legend
	var scaleFactor = 1; //dummy value
	
	//Start the drawing loop. It will jump out of the loop once stopTimer becomes true
	var stopTimer = false;
	//animate();
	
	//This function runs during changes in the visual - during a zoom
	$("input#title2").val(root.name);
	$("input#id").val(root.IDdb);
		
}//drawAll

	
////////////////////////////////////////////////////////////// 
//////////////////// Other Functions /////////////////////////
////////////////////////////////////////////////////////////// 

//Needed in the global scope
var searchEvent = function(occupation) { };
	


//Generates the next color in the sequence, going from 0,0,0 to 255,255,255.
//From: https://bocoup.com/weblog/2d-picking-in-canvas
var nextCol = 1;
function genColor(){
	var ret = [];
	// via http://stackoverflow.com/a/15804183
	if(nextCol < 16777215){
	  ret.push(nextCol & 0xff); // R
	  ret.push((nextCol & 0xff00) >> 8); // G 
	  ret.push((nextCol & 0xff0000) >> 16); // B

	  nextCol += 1; // This is exagerated for this example and would ordinarily be 1.
	}
	var col = "rgb(" + ret.join(',') + ")";
	return col;
}//function genColor

//From http://stackoverflow.com/questions/2936112/text-wrap-in-a-canvas-element
function getLines(ctx, text, maxWidth, fontSize, titleFont) {
	var words = text.split(" ");
	var lines = [];
	var currentLine = words[0];
	var maxi="";
	for (var i = 1; i < words.length; i++) {
		var word = words[i];
		ctx.font = fontSize + "pt " + titleFont;
		ctxwidth = ctx.measureText(currentLine + " " + word).width;
		if (ctxwidth < maxWidth) {
			currentLine += " " + word;
		} else {
			lines.push(currentLine);
			if (currentLine.length>maxi.length) maxi=currentLine;
			currentLine = word;
		}
	}
	lines.push(currentLine);
	if (currentLine.length>maxi.length) maxi=currentLine;
	
	// Adapte la taille de la fonte si le mot le plus long dépasse de la taille du cercle
	if (ctx.measureText(maxi).width>maxWidth) {
		// Règle de 3 pour redéfinir la taille (passée en référence
		fontSize=fontSize/ctx.measureText(maxi).width*maxWidth;
	}
	
	// Retourne le tableau adapté, et la taille de police conseillée
	return {lines:lines,fontSize:fontSize};
}//function getLines


	</script>
	
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
		
		
.data_field	 {background:rgba(0,0,0,0.1); border-radius:5px; padding:5px;margin-bottom:10px;}	
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
  border-radius: 13px; /* Pour arrondir les bords */
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
	.contentleft {height:calc(100% - 30px); border-radius:5px;}
	.contentright {height:calc(100% - 4px); width:calc(100% - 4px); border-radius:5px;  overflow:hidden;position:absolute; left:2px; top:2px;}
	.right {height:calc(100% - 100px); padding:2px; position:relative;}

	
	.odj {font-weight:bold; font-size:110%}
	
	
	div.menu {display:none;}
	div.menu.selected {display:inherit}
	
	.mainTitle {font-size:200%;width:100%}
	.horaires {font-color:#ccc;width:100%}

	div#showpanel {display:none}


	#menu {position:fixed; top:0px; right:20px; border-radius: 0px 0px 10px 10px; padding:10px;background-color:#FFFFFF;box-shadow: 5px 5px 10px rgba(0,0,0,0.5)}


/* Adaptation graphique pour téléphone portable */

/* Supprime le menu du bas si l'écran n'est pas assez haut */
	@media screen and (max-height: 500px) {
		.interface-bottom {display:none; height:0px;overflow:hidden}
		
	}
	@media screen and (max-width: 700px) {
		td.left div#showPanel {display:block;width:40px; height:50px; position:absolute; right:-38px; bottom:30px; background: white; border-radius:0px 15px 15px 0px;overflow:hidden; border:2px solid var(--light-bg-color);border-width: 2px 2px 2px 0px; cursor:pointer; background-image:url(/img/loupe.png); background-size: contain;
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

	</body>
</html>
