
//fonctions génériques de validation
function countChar(objet, limit) {
	if (objet.val().length>limit) {
		objet.val(objet.val().substr(0,limit));
	}
	objet.nextAll(".char_count").html(objet.val().length+" sur "+limit+" caractères");
} 

// Ouvre et ferme la fenêtre popup
function showPopup(target, title=null, close=true) {
	$("#popup_content").load(target);
	$("#popupbackground").show();
	$("#popupbackground").animate({
		opacity:1,
	  }, 500);
	$("#popup").animate({
		opacity:1,
		right: "0"
	  }, 500, function() {
		// Animation complete.
	  });
}
	
function closePopup() {
	$("#popupbackground").animate({
		opacity:0,
	  }, 500, function() {
		$("#popupbackground").hide();
	  });
	$("#popup").animate({
		opacity:0,
		right: 0-$(this).width()
	  }, 500, function() {
		$("#popup_content").html("");
	  });
}
	
function showError(msg) {
	alert (msg);
}
	
function showInfo(msg) {
	alert (msg);
}
	
function enterFullscreen(element) {
				 
	if (!window.screenTop && !window.screenY) {
		if(element.requestFullscreen) {
			element.requestFullscreen();
		  } else if(element.msRequestFullscreen) {      // for IE11 (remove June 15, 2022)
			element.msRequestFullscreen();
		  } else if(element.webkitRequestFullscreen) {  // iOS Safari
			element.webkitRequestFullscreen();
		  }
	 } else {
		if (document.exitFullscreen) {
			document.exitFullscreen();
		  } else if (document.webkitExitFullscreen) { /* Safari */
			document.webkitExitFullscreen();
		  } else if (document.msExitFullscreen) { /* IE11 */
			document.msExitFullscreen();
		  }
	  }
}

	function ajouterParametreRefresh(url) {
	  const sep = url.includes('?') ? '&' : '?';
	  return url+sep+"refresh=1";
	}
	
	function refresh(elementID, sourceDocument) {
	  // Si la source du document est null, utilisez l'URL courante
	  sourceDocument = ajouterParametreRefresh(sourceDocument || window.location.href);


	  // Chargez le contenu complet de la page via AJAX
	  $.ajax({
		url: sourceDocument,
		method: "GET",
		dataType: "html",
		success: function(data) {
		  // Trouvez l'élément correspondant à l'ID

		  var parsedHtml = $.parseHTML(data);

		// Est-ce que elementID est une string ou un tableau?
		if (Array.isArray(elementID)) {
			// Parcours chaque élément 
			elementID.forEach(function(element) {

			  var $targetElement = $(parsedHtml).filter(element);
			  if (!$targetElement.length) $targetElement = $(parsedHtml).find(element);

			  // Vérifiez si l'élément avec l'ID donné a été trouvé
			  if ($targetElement.length) {
				// Copiez le contenu de l'élément trouvé
				var newContent = $targetElement.html();

				// Collez le contenu dans la page actuelle
				$(element).html(newContent);
			  } else {
				//console.error("E1 : L'élément avec l'ID " + element + " n'a pas été trouvé dans le document source.");
				// Changement de comportement, utilise tout le contenu trouvé
				$(element).html(data);
			  }
			  				
			});
			
			
		} else {

			  var $targetElement = $(parsedHtml).filter(elementID);
			  if (!$targetElement.length) $targetElement = $(parsedHtml).find(elementID);

			  // Vérifiez si l'élément avec l'ID donné a été trouvé
			  if ($targetElement.length) {
				// Copiez le contenu de l'élément trouvé
				var newContent = $targetElement.html();

				// Collez le contenu dans la page actuelle
				$(elementID).html(newContent);
			  } else {
				//console.error("E2 : L'élément avec l'ID " + elementID + " n'a pas été trouvé dans le document source.");
				// Changement de comportement, utilise tout le contenu trouvé
				$(elementID).html(data);
			  }
			  // Exécute tous les scripts
			  // Recherchez tous les éléments script dans la chaîne HTML
		  }
		},
		error: function(xhr, status, error) {
		  console.error("Erreur lors du chargement du document source: " + error);
		}
	  });
	}

// Fonction pour définir un cookie
function setCookie(cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  let expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

// Fonction générique pour envoi de formulaire
/* Exemple
 * 	$("#loginbtn").click(function (e) {
 *		sendForm($("#loginform"),success);
 *	});
 */
function sendForm(formulaire, successfunction=null, failfunction=null) {
	// Désactive le formualaire
	formulaire.addClass("disabled");
	
	if (successfunction===null) sucessfunction = function() {alert("success");}
	if (failfunction===null) failfunction = function() {alert ("Echec lors de l'envoi de données.\n\nVeuillez réessayer après avoir vérifié votre connexion Internet.");
}
	// Sérialize le formulaire pour l'envoyer en ajax
	$.ajax({
		type : 'POST',
		url : formulaire.attr("action"),
		data : formulaire.serialize(),
		context:formulaire
	})
	 .done(successfunction)
	 .fail(failfunction)
	 .always(function() {
		 // Réactive le formulaire
		$( this ).removeClass("disabled");
	 });
}

// Fonction de base pour envoi de formulaire réussie
/* Exemple
 * 	$("#loginbtn").click(function (e) {
 *		sendForm($("#loginform"),success);
 *	});
 */
function success(data) {
	console.log("Success!");
	console.log(data);
	data=jQuery.parseJSON(data);
	if (data.status===false) {
		if (data.script) eval(data.script);
		alert (data.message);
	} else {
		if (data.script) eval(data.script);
		if (data.message) alert (data.message);
	}
}

$(function () {
	// *******************************************************
	// Menu utilisateur en haut
	// ******************************************************
	$("body").delegate("#profilbtn","click", function (e) {
		showPopup("/popup/profil.php", "Profil");
	});

	$("body").delegate("#logoutbtn","click", function (e) {
		sendForm($("#logoutform"),success);
	});
	$("body").delegate("#loginbtn","click", function (e) {
		sendForm($("#loginform"),success);
	});	
	
	// *******************************************************
	// Menu tools commun
	// ******************************************************
	
	$("#btn_zoom").click(function () {
		enterFullscreen(document.documentElement);  
	});
	
	// *******************************************************
	// Popup window
	// ******************************************************
	$("#login").click(function () {
		showPopup("/popup/login.php", "Se connecter");
	});			

	$("#popup_close").click(function () {
		closePopup(); 
	});

});
