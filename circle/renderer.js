
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
if (window.circleRendererConfig.view) {
if (window.circleRendererConfig.circleSharedOrganizationId > 0) {
		localStorageName="tmpcirclestructure";
		// Charge l'organisation avec l'access key, pour bypasser le "canView()"
		// Attention, mauvaise gestion des erreurs ici...
		$.getJSON(("/ajax/loadorga.php?id=" + window.circleRendererConfig.circleSharedOrganizationId + "&accesskey=" + window.circleRendererConfig.view2 + ""), function( my_var ) {		

		localStorage.setItem(localStorageName, JSON.stringify(my_var));
		$("canvas").remove();
		queue().defer(loadImage, "/img/rayures.png").await(drawAll);
		});	


} else { alert('Error!'); }
} else {
	
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
	
}
	
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

	/*nodeByName = {};
	nodes.forEach(function(d,i) {
		nodeByName[d.name] = d;
	});*/

	


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
	
	/* //Function to call once the search box is filled in
	searchEvent = function(occupation) { 
		//If the occupation is not equal to the default
		if (occupation !== "" & typeof occupation !== "undefined") {
			zoomToCanvas(nodeByName[occupation]);
		}//if 
	}//searchEvent
		*/
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


	
