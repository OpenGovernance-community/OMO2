<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	
	// Initialise le login
	$connected=checklogin();
?>
<html>
	<head>

		<!-- Google fonts -->
		<link href='https://fonts.googleapis.com/css?family=Oswald:300,400' rel='stylesheet' type='text/css'>
		<link href='https://fonts.googleapis.com/css?family=Merriweather+Sans:400,700' rel='stylesheet' type='text/css'>

		<!-- D3.js -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js" charset="utf-8"></script>
		<script src="https://d3js.org/queue.v1.min.js"></script>

		<!-- stats -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/stats.js/r14/Stats.js"></script>
		
		<?writeHeadContent(T_("Dessinez votre organization !"));?>
	
		<!-- Script spécifique à la page -->
		<script>
			
			let occupations;
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
			
			var nodeOld;
			
				//Default values for variables - set to root
			var currentID = "",
				oldID = "",
				kids = []; //needed to check which arced titles to show - only those close to the parent node

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
		ctx.font = style+" "+fontSize+"px '"+font+"'";
		ctx.fillStyle = fillcolor;
		ctx.strokeStyle = strockcolor;
		ctx.lineWidth = 5;
		ctx.setLineDash([]);
		ctx.lineJoin = 'round';
		
		//Get the text back in pieces that will fit inside the node
		var titleText = getLines(ctx, text, radius*2*0.7, fontSize, font);
		//Loop over all the pieces and draw each line
		titleText.forEach(function(txt, iterator) { 
			ctx.textBaseline = "middle"; 
			ctx.strokeText(txt, centerX, centerY + ((-titleText.length/2)+iterator+0.5)*fontSize);
			ctx.fillText(txt, centerX, centerY + ((-titleText.length/2)+iterator+0.5)*fontSize );
		})//forEach		
		
	}

	//Adjusted from: http://blog.graphicsgen.com/2015/03/html5-canvas-rounded-text.html
	function drawCircularText(ctx, text, fontSize, titleFont, centerX, centerY, radius, startAngle, kerning) {
		// startAngle:   In degrees, Where the text will be shown. 0 degrees if the top of the circle
		// kearning:     0 for normal gap between letters. Positive or negative number to expand/compact gap in pixels
				
		//Setup letters and positioning
		ctx.textBaseline = 'alphabetic';
		ctx.textAlign = 'center'; // Ensure we draw in exact center
		ctx.font = fontSize + "px " + titleFont;
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
				nodeR = node.r * zoomInfo.scale * (node.type=="role"?0.9:(node.type=="organization"?1.05:1));
				
			//Use one node to reset the scale factor for the legend
			if(i === 0) scaleFactor = node.value/(nodeR * nodeR); 
						
			//Draw each circle
			chosenContext.beginPath();
			chosenContext.arc(nodeX, nodeY, nodeR, 0,  2 * Math.PI, true);	

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
				chosenContext.fillStyle =  node.type=="group" ||  node.type=="circle" ? colorCircle(node.depth) : (node.mycolor?node.mycolor:"rgb(255, 204, 0)"); // Couleur des noeuds
				if (node.type && node.type=="group") {chosenContext.fillStyle="rgba(0,0,0,0)";}
				
				if (node.type=="organization") {
				chosenContext.lineWidth = 1;
				//chosenContext.setLineDash([10, 10]);
				chosenContext.strokeStyle= "rgba(255,255,255,0.5)"
				chosenContext.stroke();
				chosenContext.fillStyle="rgb(61, 168, 169)";
				chosenContext.fill();
				

				} else							
				if (node.type=="group") {
				chosenContext.lineWidth = 2;
				chosenContext.setLineDash([10, 10]);
				chosenContext.strokeStyle= "rgba(255,255,255,0.5)"
				chosenContext.stroke();
				chosenContext.fill();

				} else {
					chosenContext.fill();
					if (node.type=="template") {
						var pattern = chosenContext.createPattern(pattern_img,'repeat');
						chosenContext.fillStyle=pattern;
						chosenContext.fill();
					}
				}

			// Current node layout
			if (node.ID==currentID) {
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
		
		var counter = 0; //Needed for the rotation of the arc titles
		
		//Do a second loop because the arc titles always have to be drawn on top
		for (var i = 0; i < nodeCount; i++) {
			node = nodes[i];
		
			var nodeX = ((node.x - zoomInfo.centerX) * zoomInfo.scale) + centerX,
				nodeY = ((node.y - zoomInfo.centerY) * zoomInfo.scale) + centerY,
				nodeR = node.r * zoomInfo.scale * (node.type=="role"?0.9:(node.type=="organization"?1.05:1));
			
				titleFont="Arial black";
				if(!hidden & showText & (node.ID==currentnode.ID || $.inArray(node.ID, kids) >= 0)) {  
					//Calculate the best font size for the non-leaf nodes
					
					if (typeof node.children == "undefined" || $.inArray(node.ID, kids) >= 0) {
						var fontSizeTitle = Math.round(nodeR / 3);
						if (typeof node.children == "undefined") {
							if (fontSizeTitle>36) fontSizeTitle=36;
							drawText(chosenContext, node.name.replace(/,? and /g, ' & '), fontSizeTitle, titleFont, nodeX, nodeY, nodeR,"#000000","#FFFFFF");  // rotationText[counter] pour le 1er 0
						}
						else
							drawText(chosenContext, node.name.replace(/,? and /g, ' & '), fontSizeTitle, titleFont, nodeX, nodeY, nodeR,"#FFFFFF","#000000","bold");  // rotationText[counter] pour le 1er 0
					} else {
						var fontSizeTitle = Math.round(nodeR / 6);
						if (fontSizeTitle > 4) drawCircularText(chosenContext, node.name.replace(/,? and /g, ' & '), fontSizeTitle, titleFont, nodeX, nodeY, nodeR, 0, 0);  // rotationText[counter] pour le 1er 0
					}
				}//if
				counter = counter + 1;


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
		var v = [focus.x, focus.y, focus.r * 2.05]; //The center and width of the new "viewport"

		zoomInfo.centerX = v[0];
		zoomInfo.centerY = v[1];
		zoomInfo.scale = diameter / v[2];

			node=currentnode=focusNode;
			kids = [];
			if(typeof node.children !== "undefined") {
				for(var i = 0; i < node.children.length; i++) {
					kids.push(node.children[i].ID)
				}//for i
			}//if

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
					$(".left").css("width",$(".left").width()+ui.position.left+4);
					$( "#resizeelem" ).css("left",0);
					refresh();
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


			
				$("#btn_new").click(function () {
					// Crée une nouvelle organisation avec un seul cercle
					
					if (confirm("Voulez-vous réellement créer une nouvelle organisation?\nL'organisation en cours sera remplacée. Créez un compte pour la sauvegarder.") ) {
						occupations=JSON.parse('{"name": "Mon organisation", "ID": "1", "type": "organization", "children": [{"name": "Ancrage", "ID": "2", "type": "circle", "children": [ {"name": "Trésorerie", "ID": "3","type":"role",  "size":10}, {"name": "Test", "type":"role", "ID": "4", "size":10}]}]}');
						
						// Stock sur le disque
						localStorage.setItem('circlestructure', JSON.stringify(occupations));
						
						// Raffraichi l'affichage
						refresh();
					}
				});


				
				$("body").delegate("#btn_add_role","click", function () {
					showPopup("/popup/addrole.php", "<?=T_("Ajouter un rôle",true)?>");					
				});
				
				
				
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
					  console.log(parent);

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
						console.log ("Goto Node");
						console.log(gotoNode);
						
						if (confirm("Voulez-vous réellement supprimer ce noeud ?"))	{
								console.log(supprimerNoeud(currentnode));
							
								occupations=root;
	
								save();
								nodes = pack.nodes(occupations),
										root = occupations,
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
					occupations=root;
					refresh();
					
				});
				// Sauve automatiquement lorsque on quitte les champs d'entête
				$("body").delegate(".interface-top input#title2","focusout",function (e) {
					root.name=$(this).val();
					occupations=root;
					save();
					refresh();
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
				localStorage.setItem('circlestructure', JSON.stringify(removeCircularReferences(root)));
			}
			$(window).resize(function() {
				refresh();
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

function removeColorNodes(json) {
    if (Array.isArray(json)) {
        // Si c'est un tableau, appliquer récursivement sur chaque élément
        return json.map(removeColorNodes);
    } else if (typeof json === 'object' && json !== null) {
        // Si c'est un objet, vérifier chaque clé
        for (let key in json) {
            if (key === 'color') {
                // Supprimer la clé "color"
                delete json[key];
            } else {
                // Appliquer récursivement sur les propriétés imbriquées, sauf pour le noeud parent
               if (key!=='parent')
                json[key] = removeColorNodes(json[key]);
            }
        }
    }
    return json; // Retourner l'objet ou la valeur inchangée
}

function refresh() {
		// Init fields
	$("canvas").remove();
	
	// Raffraichi les couleurs
	removeColorNodes(root);
	
	var currentID=currentnode.ID;
	drawAll();
	// Find the current node again after drawing
	quickZoomToCanvas(currentnode);

	
	for (const [key, value] of Object.entries(colToCircle)) {
	  if (value.ID==currentID) currentnode=value;
	}
	quickZoomToCanvas(currentnode);

}

function init() {
	// Efface tous les canvas
	if (localStorage.getItem('circlestructure')==null || !isJsonString(localStorage.getItem('circlestructure'))) {
		$.getJSON("/data/occupation.json", function( my_var ) {		
			localStorage.setItem('circlestructure', JSON.stringify(my_var));
			$("canvas").remove();
			queue().defer(loadImage, "/img/rayures.png").await(drawAll);
		});		
	} else {
		$("canvas").remove();
		queue().defer(loadImage, "/img/rayures.png").await(drawAll);
	}
	
}
$(function() {
	init();
	$("#chart").on('mouseout', function() {
		console.log("Out!");
		$('.popoverWrapper').remove(); 
				$('.popover').each(function() {
						$('.popover').remove(); 	
				}); 

		});
		
		
		
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
			kids = [];
			if(typeof focusNode.children !== "undefined") {
				for(var i = 0; i < focusNode.children.length; i++) {
					kids.push(focusNode.children[i].ID)
				}//for i
			}//if
 
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
		var v = [focus.x, focus.y, focus.r * 2.05]; //The center and width of the new "viewport"
		console.log (vOld);
		console.log (v);
		if (arraysAreEqual(vOld,v)) {
			// Rafraîchi simplement l'affichage
			refresh();
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
	if (typeof(occupations) == "undefined") {
		occupations=localStorage.getItem('circlestructure');
		if (occupations==null) {
			occupations=JSON.parse('{"name": "Demo", "ID": "0", "type": "circle", "children": [ {"name": "Trésorerie", "ID": "3", "size":10}]}');
		} else
			occupations=JSON.parse(occupations);
	}
	
	// Create the list
	transformJSONtoHTML(occupations, "/xslt/list_role.xml", 'role_list');
	
	
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
	chartheight = ($("html").innerHeight()-100)*2;
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
		currentnode=occupations;
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

	nodes = pack.nodes(occupations),
		root = occupations,
		focus = root,
		nodeCount = nodes.length;

	nodeByName = {};
	nodes.forEach(function(d,i) {
		nodeByName[d.name] = d;
	});

	


	////////////////////////////////////////////////////////////// 
	/////////////////// Click functionality ////////////////////// 
	////////////////////////////////////////////////////////////// 
	

	//Setup the kids variable for the top (root) level	
	kids = [];		
	for(var i = 0; i < root.children.length; i++) { kids.push(root.children[i].ID) };	
	
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

	//Create options - all the occupations
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
		quickZoomToCanvas(currentnode);
	} else {
		currentnode=root;
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
	console.log("Il est passé par là : "+root.name);
		
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

	for (var i = 1; i < words.length; i++) {
		var word = words[i];
		ctx.font = fontSize + "pt " + titleFont;
		ctxwidth = ctx.measureText(currentLine + " " + word).width;
		if (ctxwidth < maxWidth) {
			currentLine += " " + word;
		} else {
			lines.push(currentLine);
			currentLine = word;
		}
	}
	lines.push(currentLine);
	return lines;
}//function getLines


	</script>
	
	<style>
		

	@media screen {
		
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




#menu {position:fixed; top:0px; right:20px; border-radius: 0px 0px 10px 10px; padding:10px;background-color:#FFFFFF;box-shadow: 5px 5px 10px rgba(0,0,0,0.5)}
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
			<input autocomplete="off" id='title2' class='mainTitle liketext' autocomplete="off" placeholder='<?=T_("Nom de votre organisation",true);?>'></input><br>
		
		</td></tr>
		<tr><td class='left'><div class='contentleft'>
			<table class='leftTab' cellspacing=0 cellpadding=0><tr><td class='odj'>
			<div><?=T_("Holarchie");?>
			<span class='noPrint' style='float:right; background:#FFF; border-radius:5px 5px 0px 0px'><img src='/img/addentry.png' class='imgbutton' style='margin:0px;' id='btn_add_role'  data-toggle='tooltip' data-placement='bottom' title='<?=T_('Ajouter un rôle',true)?>'></span>
			<span class='noPrint' style='float:right; background:#FFF; border-radius:5px 5px 0px 0px'><img src='/img/addentry.png' class='imgbutton' style='margin:0px;' id='btn_add_circle'  data-toggle='tooltip' data-placement='bottom' title='<?=T_('Ajouter un cercle',true)?>'></span>
			</div>
			</td></tr><tr><td style='height:100%; position: relative;vertical-align:top'><div class='screenOJ'>

		
			</div>
			</td></tr><tr><td style='background:#eee' class='noPrint'>
			

			</td></tr></table>
		</div></td><td class='resize'><div id='resizeelem'></div></td><td class='right'><div id='contentright' class='contentright'>
			
		<div id="chart"></div>
		<div id="role_list" style='height:100%; overflow-y:auto'>Test pour voir où ça s'affiche</div>
		<div class="switch" style='position:absolute; bottom:5px; right:25px;'>
		  <input type="checkbox" id="toggleSwitch" />
		  <label for="toggleSwitch" class="slider">🔘 ☰</label>
		</div>
		
		</div></td><td rowspan="2" id='tools' style='width:50px; vertical-align:top;'>
<?	
		//<!-- bouton pour le zoom -->
		echo "<img src='/img/expand.png' class='imgbutton' id='btn_zoom' data-toggle='tooltip' data-placement='right' title='".T_('Plein écran',true)."'>";

		//<!-- bouton pour un nouveau fichier -->
		echo "<img src='/img/newfile.png' class='imgbutton' id='btn_new' data-toggle='tooltip' data-placement='right' title='".T_('Nouveau document',true)."'>";

		//<!-- bouton pour imprimer -->
		echo "<img src='/img/printing.png' onclick='window.print();' class='imgbutton' id='btn_print' data-toggle='tooltip' data-placement='right' title='".T_('Imprimer',true)."'>";


		//<!-- bouton pour l'aide -->
		echo "<img src='/img/question.png' class='imgbutton' id='btn_help' data-toggle='tooltip' data-placement='right' title='".T_('Afficher l\'aide',true)."'>";

		//<!-- bouton pour les parameẗres -->
		if ($connected)
		echo "<img src='/img/settings.png' class='imgbutton' id='btn_parameters' data-toggle='tooltip' data-placement='right' title='".T_('Paramètres',true)."'>";
?>		
		</td></tr>
		<tr><td class='interface-bottom' colspan=3><span style='float:right;'><img src='/img/support.png' style='height:40px;' id='btn_support'></span></td></tr>
		</table>
		<div id='popupbackground'></div>
		<div id='popup'><div id='popup_content'></div><div id='popup_close'><button><img src='/img/icon_close.png'><?=T_("Fermer");?></button></div></div>

	</body>
</html>
