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
			// ***********************************
			// Script pour l'interface
			// ***********************************
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
			$(window).resize(function() {
				init ();
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


function init() {
	// Efface tous les canvas
	$("canvas").remove();
queue() 
	.defer(d3.csv, "data/occupations by age.csv")
	.defer(d3.csv, "data/ID of parent levels.csv")
	.defer(d3.json, "data/occupation.json")
	.defer(loadImage, "img/rayures.png")

	.await(drawAll);
}
init();
	
//Initiates practically everything
function drawAll(error, ageCSV, idCSV, occupations,img) {
	
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
	var mobileSize = window.mobileAndTabletcheck();
	
	var padding = 0, // Default: 20
		width = Math.max($("#chart").innerWidth(),350);
		height = Math.max($("html").innerHeight()-100,350);
	//	height = (mobileSize | $("#chart").innerWidth() < 768 ? width : $("#chart").innerHeight() ); // -90?

	var centerX = width/2,
		centerY = height/2;

	////////////////////////////////////////////////////////////// 
	/////////////////////// Create SVG  /////////////////////// 
	////////////////////////////////////////////////////////////// 
	
	//Create the visible canvas and context
	var canvas  = d3.select("#chart").append("canvas")
		.attr("id", "canvas")
		.attr("width", width)
		.attr("height", height);
		
	var context = canvas.node().getContext("2d");
		context.clearRect(0, 0, width, height);
	
	//Create a hidden canvas in which each circle will have a different color
	//We can use this to capture the clicked/hovered over on circle
	var hiddenCanvas  = d3.select("#chart").append("canvas")
		.attr("id", "hiddenCanvas")
		.attr("width", width)
		.attr("height", height)
		.style("display","none");
		
	var hiddenContext = hiddenCanvas.node().getContext("2d");
		hiddenContext.clearRect(0, 0, width, height);

	////////////////////////////////////////////////////////////// 
	/////////////////////// Create Scales  /////////////////////// 
	////////////////////////////////////////////////////////////// 

	var mainTextColor = [74,74,74],//"#4A4A4A",
		titleFont = "Oswald",
		bodyFont = "Merriweather Sans";
	
	var colorCircle = d3.scale.ordinal()
			.domain([0,1,2,3,4,5,6])
			.range(['rgb(61, 168, 169)','rgba(255, 255, 255,0.4)','rgba(255, 255, 255,0.4)','rgba(255, 255, 255,0.4)','rgba(255, 255, 255,0.4)','rgba(255, 255, 255,0.4)']);
			
	var colorBar = d3.scale.ordinal()
		.domain(["16 to 19","20 to 24","25 to 34","35 to 44","45 to 54","55 to 64","65+"])
		.range(["#EFB605", "#E3690B", "#CF003E", "#991C71", "#4F54A8", "#07997E", "#7EB852"]);	

	var diameter = Math.min(width*0.9, height*0.9),
		radius = diameter / 2;
		
	var commaFormat = d3.format(',');
	
	var zoomInfo = {
		centerX: centerX,
		centerY: centerY,
		scale: 1
	};
	
	//Dataset to swtich between color of a circle (in the hidden canvas) and the node data	
	var colToCircle = {};
	
	var pack = d3.layout.pack()
		.padding(1)
		.size([diameter, diameter]) //[diameter, diameter]
		.value(function(d) { return d.size; })
		.sort(function(d) { return d.ID; });

	////////////////////////////////////////////////////////////// 
	////////////// Create Circle Packing Data ////////////////////
	////////////////////////////////////////////////////////////// 

	var nodes = pack.nodes(occupations),
		root = occupations,
		focus = root,
		nodeCount = nodes.length;

	var nodeByName = {};
	nodes.forEach(function(d,i) {
		nodeByName[d.name] = d;
	});

	////////////////////////////////////////////////////////////// 
	///////////////// Create Bar Chart Data //////////////////////
	////////////////////////////////////////////////////////////// 
	
	//Turn the value into an actual numeric value
	ageCSV.forEach(function(d) { d.value = +d.value; });
 
	//Create new dataset grouped by ID
	data = d3.nest()
		.key(function(d) { return d.ID; })
		.entries(ageCSV);
		
	//Find the max value per ID - needed for the bar scale setting per mini bar chart
	dataMax = d3.nest()
		.key(function(d) { return d.ID; })
		.rollup(function(d) { return d3.max(d, function(g) {return g.value;}); })
		.entries(ageCSV);

	//Array to keep track of which ID belongs to which index in the array
	var dataById = {};
	data.forEach(function (d, i) { 
		dataById[d.key] = i; 
	});	
	
	var IDbyName = {};
	//Small file to get the IDs of the non leaf circles
	idCSV.forEach(function (d, i) { 
		IDbyName[d.name] = d.ID; 
	});	
	
	////////////////////////////////////////////////////////////// 
	///////////////// Canvas draw function ///////////////////////
	////////////////////////////////////////////////////////////// 
		
	var elementsPerBar = 7,
		barChartHeight = 0.7,
		barChartHeightOffset = 0.15;
	
	//The draw function of the canvas that gets called on each frame
	function drawCanvas(chosenContext, hidden) {

		//Clear canvas
		chosenContext.fillStyle = "#eee";
		chosenContext.rect(0,0,width,height);
		chosenContext.fill();
	  
		//Select our dummy nodes and draw the data to canvas.
		var node = null;
		// It's slightly faster than nodes.forEach()
		for (var i = 0; i < nodeCount; i++) {
			node = nodes[i];

			var nodeX = ((node.x - zoomInfo.centerX) * zoomInfo.scale) + centerX,
				nodeY = ((node.y - zoomInfo.centerY) * zoomInfo.scale) + centerY,
				nodeR = node.r * zoomInfo.scale;
				
			//Use one node to reset the scale factor for the legend
			if(i === 4) scaleFactor = node.value/(nodeR * nodeR); 
						
			//Draw each circle
			chosenContext.beginPath();
			chosenContext.arc(nodeX, nodeY, nodeR, 0,  2 * Math.PI, true);	

			//If the hidden canvas was send into this function and it does not yet have a color, generate a unique one
			if(hidden) {
				if(node.color == null) {
					// If we have never drawn the node to the hidden canvas get a new color for it and put it in the dictionary.
					node.color = genColor();
					colToCircle[node.color] = node;
				}//if
				// On the hidden canvas each rectangle gets a unique color.
				chosenContext.fillStyle = node.color;
				chosenContext.fill();
				
			} else {
				chosenContext.fillStyle = node.children ? colorCircle(node.depth) : (node.mycolor?node.mycolor:"rgb(255, 204, 0)"); // Couleur des noeuds
				if (node.type && node.type=="group") {chosenContext.fillStyle="rgba(0,0,0,0)";}
				
							if (node.type=="group") {
				chosenContext.lineWidth = 2;
				chosenContext.setLineDash([10, 10]);
				chosenContext.strokeStyle= "rgba(255,255,255,0.5)"
				chosenContext.stroke();
				chosenContext.fill();

			} else {
				chosenContext.fill();
				if (node.type=="template") {
					var pattern = chosenContext.createPattern(img,'repeat');
					chosenContext.fillStyle=pattern;
					chosenContext.fill();
				}
			}

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
			if(!node.children) {
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
						chosenContext.font = (fontSizeTitle*0.5 <= 5 ? 0 : Math.round(fontSizeTitle*0.5)) + "px " + bodyFont;
						chosenContext.fillStyle = "rgba(0,0,0," + (0.5*textAlpha) +")" //"#BFBFBF";
						chosenContext.textAlign = "center";
						chosenContext.textBaseline = "middle"; 
						chosenContext.fillText("Total "+commaFormat(node.size)+" (in thousands)", nodeX, nodeY + -0.75 * nodeR);
						
						//Get the text back in pieces that will fit inside the node
						var titleText = getLines(chosenContext, node.name, nodeR*2*0.7, fontSizeTitle, titleFont);
						//Loop over all the pieces and draw each line
						titleText.forEach(function(txt, iterator) { 
							chosenContext.font = fontSizeTitle + "px " + titleFont;
							chosenContext.fillStyle = "rgba(" + mainTextColor[0] + "," + mainTextColor[1] + ","+ mainTextColor[2] + "," + textAlpha +")";
							chosenContext.textAlign = "center";
							chosenContext.textBaseline = "middle"; 
							chosenContext.fillText(txt, nodeX, nodeY + (-0.65 + iterator*0.125) * nodeR);
						})//forEach
						
					}//if

					//The barscale differs per node
/*					var barScale = d3.scale.linear()
						.domain([0, dataMax[dataById[node.ID]].values]) //max value of bar charts in circle
						.range([0, nodeR]);
			
					//Variables for the bar chart
					var bars = data[dataById[node.ID]].values,
						totalOffset = nodeX + -nodeR*0.3, 
						eachBarHeight = ((1 - barChartHeightOffset) * 2 * nodeR * barChartHeight)/elementsPerBar,
						barHeight = eachBarHeight*0.8;
					
					//Variables for the labels on the bars: Age
					var drawLabelText = true;
					var fontSizeLabels = Math.round(nodeR / 18);
					if (fontSizeLabels < 6) drawLabelText = false;
					
					//Variables for the value labels on the end of each bar
					var drawValueText = true;
					var fontSizeValues = Math.round(nodeR / 22);
					if (fontSizeValues < 6) drawValueText = false;
					
					//Only draw the bars and all labels of each bar has a height of at least 1 pixel
					if (Math.round(barHeight) > 1) {
						//Loop over each bar
						for (var j = 0; j < bars.length; j++) {
							var bar = bars[j];
							
							bar.width = (isNaN(bar.value) ? 0 : barScale(bar.value)); 
							bar.barPiecePosition = nodeY + barChartHeightOffset*2*nodeR + j*eachBarHeight - barChartHeight*nodeR;
							
							//Draw the bar
							chosenContext.beginPath();
							chosenContext.fillStyle = colorBar(bar.age);
							chosenContext.fillRect(nodeX + -nodeR*0.3, bar.barPiecePosition, bar.width, barHeight);
							chosenContext.fill();
							
							//Only draw the age labels if the font size is big enough
							if(drawLabelText & showText) {
								chosenContext.font = fontSizeLabels + "px " + bodyFont;
								chosenContext.fillStyle = "rgba(" + mainTextColor[0] + "," + mainTextColor[1] + ","+ mainTextColor[2] + "," + textAlpha +")";
								chosenContext.textAlign = "right";
								chosenContext.textBaseline = "middle"; 
								chosenContext.fillText(bar.age, nodeX + -nodeR*0.35, bar.barPiecePosition+0.5*barHeight);
							}//if
							
							//Only draw the value labels if the font size is big enough
							if(drawValueText & showText) {
								chosenContext.font = fontSizeValues + "px " + bodyFont;
								var txt = commaFormat(bar.value);
								//Check to see if the bar is big enough to place the text inside it
								//If not, place the text outside the bar
								var textWidth = chosenContext.measureText(txt).width;
								var valuePos = (textWidth*1.1 > (bar.width - nodeR * 0.03) ? "left" : "right");
								
								//Calculate the x position of the bar value label
								bar.valueLoc = nodeX + -nodeR*0.3 + bar.width + (valuePos === "left" ? (nodeR * 0.03) : (-nodeR * 0.03));
								
								//Draw the text
								chosenContext.fillStyle = (valuePos === "left" ? "rgba(51,51,51," + textAlpha +")" : "rgba(255,255,255," + textAlpha +")"); //#333333 or white
								chosenContext.textAlign = valuePos;
								chosenContext.textBaseline = "middle"; 
								chosenContext.fillText(txt, bar.valueLoc, bar.barPiecePosition+0.5*barHeight);
							}//if
				
						}//for j
					}//if -> Math.round(barHeight) > 1*/
					
				}//if -> node.ID.lastIndexOf(currentID, 0) === 0 & !hidden
			}//if -> node.ID in dataById 
			
		}//for i
		
		var counter = 0; //Needed for the rotation of the arc titles
		
		//Do a second loop because the arc titles always have to be drawn on top
		for (var i = 0; i < nodeCount; i++) {
			node = nodes[i];
		
			var nodeX = ((node.x - zoomInfo.centerX) * zoomInfo.scale) + centerX,
				nodeY = ((node.y - zoomInfo.centerY) * zoomInfo.scale) + centerY,
				nodeR = node.r * zoomInfo.scale;
			
			//Don't draw for leaf-nodes
			//And don't draw the arced label for the largest outer circle
			//And don't draw these things for the hidden layer
			//And only draw these while showText = true (so not during a zoom)
			//And hide those not close the the parent
			//if(typeof node.parent !== "undefined" ) {  // & typeof node.children !== "undefined"
				if(!hidden & showText & (node.ID==currentnode || $.inArray(node.ID, kids) >= 0)) {  
					//Calculate the best font size for the non-leaf nodes
					var fontSizeTitle = Math.round(nodeR / 5);
					if (typeof node.children == "undefined" || $.inArray(node.ID, kids) >= 0) {
						drawText(chosenContext, node.name.replace(/,? and /g, ' & '), Math.min(fontSizeTitle,18), titleFont, nodeX, nodeY, nodeR);  // rotationText[counter] pour le 1er 0
					} else {
						if (fontSizeTitle > 4) drawCircularText(chosenContext, node.name.replace(/,? and /g, ' & '), fontSizeTitle, titleFont, nodeX, nodeY, nodeR, 0, 0);  // rotationText[counter] pour le 1er 0
					}
				}//if
				counter = counter + 1;
			//}//if

		}//for i
		
	}//function drawCanvas

	////////////////////////////////////////////////////////////// 
	/////////////////// Click functionality ////////////////////// 
	////////////////////////////////////////////////////////////// 
	
	//Default values for variables - set to root
	var currentID = "",
		oldID = "",
		kids = []; //needed to check which arced titles to show - only those close to the parent node
		currentnode=root.ID;
		hoverNode=null;
	//Setup the kids variable for the top (root) level			
	for(var i = 0; i < root.children.length; i++) { kids.push(root.children[i].ID) };	
	
	var mouseoverFunction = function(e){
		//Figure out where the mouse click occurred.
		var mouseX = e.offsetX; //e.layerX;
		var mouseY = e.offsetY; //e.layerY;

		// Get the corresponding pixel color on the hidden canvas and look up the node in our map.
		// This will return that pixel's color
		var col = hiddenContext.getImageData(mouseX, mouseY, 1, 1).data;
		//Our map uses these rgb strings as keys to nodes.
		var colString = "rgb(" + col[0] + "," + col[1] + ","+ col[2] + ")";
		var node = colToCircle[colString];

		//If there was an actual node clicked on, zoom into this
		if(node) {
			hoverNode=node.ID;
		}
		drawCanvas(context);
	}
	
	
	//Function to run oif a user clicks on the canvas
	var clickFunction = function(e){
		//Figure out where the mouse click occurred.
		var mouseX = e.offsetX; //e.layerX;
		var mouseY = e.offsetY; //e.layerY;

		// Get the corresponding pixel color on the hidden canvas and look up the node in our map.
		// This will return that pixel's color
		var col = hiddenContext.getImageData(mouseX, mouseY, 1, 1).data;
		//Our map uses these rgb strings as keys to nodes.
		var colString = "rgb(" + col[0] + "," + col[1] + ","+ col[2] + ")";
		var node = colToCircle[colString];

		//If there was an actual node clicked on, zoom into this
		if(node) {
			//If the same node is clicked twice, set it to the top (root) level
			if (focus === node) node = root;
			console.log(node);			
			//Save the names of the circle itself and first children
			//Needed to check which arc titles to show
			currentnode=node.ID;
			kids = [];
			if(typeof node.children !== "undefined") {
				for(var i = 0; i < node.children.length; i++) {
					kids.push(node.children[i].ID)
				}//for i
			}//if
 
			//Perform the zoom
			zoomToCanvas(node);			
		}//if -> node
		
	}//function clickFunction

	//Listen for clicks on the main canvas
	//document.getElementById("canvas").addEventListener("click", clickFunction);
	$("#canvas").on("click", clickFunction);
	$("#canvas").on("mousemove",mouseoverFunction);
	
	////////////////////////////////////////////////////////////// 
	//////////////// Mousemove functionality ///////////////////// 
	////////////////////////////////////////////////////////////// 
	
	//Only run this if the user actually has a mouse
	if (!mobileSize) {
		var nodeOld = root;
		
		//Listen for mouse moves on the main canvas
		var mousemoveFunction = function(e){
			//Figure out where the mouse click occurred.
			var mouseX = e.offsetX; //e.layerX;
			var mouseY = e.offsetY; //e.layerY;
			
			// Get the corresponding pixel color on the hidden canvas and look up the node in our map.
			// This will return that pixel's color
			var col = hiddenContext.getImageData(mouseX, mouseY, 1, 1).data;
			//Our map uses these rgb strings as keys to nodes.
			var colString = "rgb(" + col[0] + "," + col[1] + ","+ col[2] + ")";
			var node = colToCircle[colString];

			//Only change the popover if the user mouses over something new
			if(node !== nodeOld) {
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
						var div = document.createElement('div');
						div.setAttribute('class', 'popoverWrapper');
						document.getElementById('chart').appendChild(div);

						//Position the wrapper right above the circle
						$(".popoverWrapper").css({
							'position':'absolute',
							'top':nodeY, // -nodeR
							'left':nodeX //+padding*5/4
						});
						
						//Show the tooltip
						$(".popoverWrapper").popover({
							placement: 'auto top',
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
		$("#canvas").on("mousemove", mousemoveFunction);
	
	}//if !mobileSize

	////////////////////////////////////////////////////////////// 
	///////////////////// Zoom Function //////////////////////////
	////////////////////////////////////////////////////////////// 
	
	//Based on the generous help by Stephan Smola
	//http://bl.ocks.org/smoli/d7e4f9199c15d71258b5
	
	var ease = d3.ease("cubic-in-out"),
		timeElapsed = 0,
		interpolator = null,
		duration = 500, //Starting duration (deafault:1500)
		vOld = [focus.x, focus.y, focus.r * 2.05];
	
	//Create the interpolation function between current view and the clicked on node
	function zoomToCanvas(focusNode) {
		
		//Temporarily disable click & mouseover events
		$("#canvas").css("pointer-events", "none");
	
		//Remove all previous popovers - if present
		$('.popoverWrapper').remove(); 
		$('.popover').each(function() {
				$('.popover').remove(); 	
		}); 
					
		//Save the ID of the clicked on node (or its parent, if it is a leaf node)
		//Only the nodes close to the currentID will have bar charts drawn
		if (focusNode === focus) currentID = ""; 
		else currentID = (typeof focusNode.ID === "undefined" ? IDbyName[focusNode.name] : focusNode.ID.replace(/\.([^\.]*)$/, ""));
		
		//Set the new focus
		focus = focusNode;
		var v = [focus.x, focus.y, focus.r * 2.05]; //The center and width of the new "viewport"

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
		
	}//function zoomToCanvas
	
	//Create the interpolation function between current view and the clicked on node
	function quickZoomToCanvas(focusNode) {
			
		//Remove all previous popovers - if present
		$('.popoverWrapper').remove(); 
		$('.popover').each(function() {
				$('.popover').remove(); 	
		}); 
					
		//Save the ID of the clicked on node (or its parent, if it is a leaf node)
		//Only the nodes close to the currentID will have bar charts drawn
		if (focusNode === focus) currentID = ""; 
		else currentID = (typeof focusNode.ID === "undefined" ? IDbyName[focusNode.name] : focusNode.ID.replace(/\.([^\.]*)$/, ""));
		
		//Set the new focus
		focus = focusNode;
		var v = [focus.x, focus.y, focus.r * 2.05]; //The center and width of the new "viewport"

		//Create interpolation between current and new "viewport"
		interpolator = d3.interpolateZoom(vOld, v);
			
		//Set the needed "zoom" variables
		duration = 	5; //Interpolation gives back a suggested duration	 		
		timeElapsed = 0; //Set the time elapsed for the interpolateZoom function to 0	
		showText = true; //Don't show text during the zoom
		alwaysDisplayText=true;
		vOld = v; //Save the "viewport" of the next state as the next "old" state
		
		//Start animation
		stopTimer = false;
		animate();
		
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
	var	showText = true, //Only show the text while you're not zooming
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

	////////////////////////////////////////////////////////////// 
	//////////////////// Other Functions /////////////////////////
	////////////////////////////////////////////////////////////// 
	
	//The start angle in degrees for each of the non-node leaf titles
	var rotationText = [-14,4,23,-18,-10.5,-20,20,20,46,-30,-25,-20,20,15,-30,-15,-45,12,-15,-16,15,15,5,18,5,15,20,-20,-25]; //The rotation of each arc text //[-14,4,23,-18,-10.5,-20,20,20,46,-30,-25,-20,20,15,-30,-15,-45,12,-15,-16,15,15,5,18,5,15,20,-20,-25]
	
	function drawText(ctx, text, fontSize, titleFont, centerX, centerY, radius) {
		// startAngle:   In degrees, Where the text will be shown. 0 degrees if the top of the circle
		// kearning:     0 for normal gap between letters. Positive or negative number to expand/compact gap in pixels
		if (fontSize<4) return;	
		if (fontSize<8) fontSize=8;	
		//Setup letters and positioning
		ctx.textBaseline = 'alphabetic';
		ctx.textAlign = 'center'; // Ensure we draw in exact center
		ctx.font = fontSize+"px Arial";
		ctx.fillStyle = "rgba(0,0,0," + textAlpha +")";
		ctx.strokeStyle = 'white';
		ctx.lineWidth = 5;
		ctx.setLineDash([]);
		ctx.lineJoin = 'round';
		
		//Get the text back in pieces that will fit inside the node
		var titleText = getLines(ctx, text, radius*2*0.7, fontSize, "Tahoma");
		//Loop over all the pieces and draw each line
		titleText.forEach(function(txt, iterator) { 
			ctx.textBaseline = "middle"; 
			ctx.strokeText(txt, centerX, centerY + ((-titleText.length/2)+iterator+0.5)*fontSize);
			ctx.fillText(txt, centerX, centerY + ((-titleText.length/2)+iterator+0.5)*fontSize );
		})//forEach		
		
		
		
		//ctx.strokeText(text, centerX, centerY);
		
		//ctx.fillText(text, centerX, centerY);
		
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
			ctx.rotate(-(charWid + kerning) / radius); 
		}//for j
		
		ctx.restore(); //Restore to state as it was before transformations
	}//function drawCircularText

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
	zoomToCanvas(root);
	//Draw the hZdden canvas at least once
	drawCanvas(hiddenContext, true);
	//Draw the legend
	var scaleFactor = 1; //dummy value
	
	//Start the drawing loop. It will jump out of the loop once stopTimer becomes true
	var stopTimer = false;
	//animate();
	
	//This function runs during changes in the visual - during a zoom
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

	  nextCol += 100; // This is exagerated for this example and would ordinarily be 1.
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
		ctx.font = fontSize + "px " + titleFont;
		var width = ctx.measureText(currentLine + " " + word).width;
		if (width < maxWidth) {
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
			<input autocomplete="off" id='title' class='mainTitle liketext' placeholder='<?=T_("Nom de votre organisation",true);?>'></input><br>
		
		</td></tr>
		<tr><td class='left'><div class='contentleft'>
			<table class='leftTab' cellspacing=0 cellpadding=0><tr><td class='odj'>
			<div><?=T_("Ordre du jour");?><span class='noPrint' style='float:right; background:#FFF; border-radius:5px 5px 0px 0px'><img src='img/addentry.png' class='imgbutton' style='margin:0px;' id='btn_add'  data-toggle='tooltip' data-placement='bottom' title='<?=T_('Ajouter une tension',true)?>'>  

			haut
			
			</span></div>
			</td></tr><tr><td style='height:100%; position: relative;vertical-align:top'><div class='screenOJ'>
			milieu
		
			</div>
			</td></tr><tr><td style='background:#eee' class='noPrint'>
			
			bas
			</td></tr></table>
		</div></td><td class='resize'><div id='resizeelem'></div></td><td class='right'><div id='contentright' class='contentright'>
			
		<div id="chart"></div>
		
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
		<tr><td class='interface-bottom' colspan=3><span style='float:right;'><img src='img/support.png' style='height:40px;' id='btn_support'></span></td></tr>
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
