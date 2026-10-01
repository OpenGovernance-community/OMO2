


//fonctions génériques de validation
const SHARED_THEME_STORAGE_KEY = 'omo-theme-preference';
const SHARED_THEME_MEDIA_QUERY = '(prefers-color-scheme: dark)';
const SHARED_COLOR_STYLE_STORAGE_KEY = 'omo-color-style-preference';
let sharedThemeMessageListenerBound = false;

function sharedGetThemePreference(storageKey = SHARED_THEME_STORAGE_KEY) {
	try {
		const storedPreference = window.localStorage.getItem(storageKey);

		if (storedPreference === 'light' || storedPreference === 'dark' || storedPreference === 'system') {
			return storedPreference;
		}
	} catch (error) {
	}

	return 'system';
}

function sharedResolveTheme(preference, mediaQuery = SHARED_THEME_MEDIA_QUERY) {
	if (preference === 'light' || preference === 'dark') {
		return preference;
	}

	if (typeof window.matchMedia !== 'function') {
		return 'light';
	}

	return window.matchMedia(mediaQuery).matches ? 'dark' : 'light';
}

function sharedGetColorStylePreference(storageKey = SHARED_COLOR_STYLE_STORAGE_KEY) {
	try {
		const storedPreference = window.localStorage.getItem(storageKey);

		if (storedPreference === 'mono' || storedPreference === 'turquoise' || storedPreference === 'ocean-blue') {
			return storedPreference;
		}
	} catch (error) {
	}

	return 'mono';
}

function sharedGetParentFrameThemeState() {
	if (typeof window === 'undefined' || window.parent === window) {
		return null;
	}

	try {
		const parentRoot = window.parent && window.parent.document
			? window.parent.document.documentElement
			: null;

		if (!parentRoot || !parentRoot.dataset) {
			return null;
		}

		const preference = parentRoot.dataset.themePreference;
		const theme = parentRoot.dataset.theme;
		const colorStyle = parentRoot.dataset.colorStyle;

		if (!preference && !theme && !colorStyle) {
			return null;
		}

		return {
			preference: preference === 'light' || preference === 'dark' || preference === 'system'
				? preference
				: null,
			theme: theme === 'light' || theme === 'dark'
				? theme
				: null,
			colorStyle: colorStyle === 'mono' || colorStyle === 'turquoise' || colorStyle === 'ocean-blue'
				? colorStyle
				: null
		};
	} catch (error) {
		return null;
	}
}

function sharedEnsureThemeMessageListener() {
	if (sharedThemeMessageListenerBound || typeof window === 'undefined') {
		return;
	}

	sharedThemeMessageListenerBound = true;
	window.addEventListener('message', function (event) {
		const data = event && event.data && typeof event.data === 'object'
			? event.data
			: null;

		if (!data || data.type !== 'omo-theme-sync') {
			return;
		}

		sharedApplyDocumentTheme({
			preference: data.preference,
			colorStyle: data.colorStyle
		});
	});
}

function sharedBroadcastThemeToChildFrames(detail = {}) {
	if (typeof document === 'undefined' || typeof window === 'undefined') {
		return;
	}

	const payload = {
		type: 'omo-theme-sync',
		preference: detail.preference === 'light' || detail.preference === 'dark' || detail.preference === 'system'
			? detail.preference
			: sharedGetThemePreference(),
		colorStyle: detail.colorStyle === 'mono' || detail.colorStyle === 'turquoise' || detail.colorStyle === 'ocean-blue'
			? detail.colorStyle
			: sharedGetColorStylePreference()
	};

	document.querySelectorAll('iframe').forEach(function (frame) {
		if (!frame || !frame.contentWindow) {
			return;
		}

		try {
			const configuredOrigin = String(frame.getAttribute('data-omo-theme-message-origin') || '').trim();
			const targetOrigin = /^https?:\/\/[^/?#]+$/i.test(configuredOrigin)
				? configuredOrigin
				: window.location.origin;
			frame.contentWindow.postMessage(payload, targetOrigin);
		} catch (error) {
		}
	});
}

function sharedEnsureChildFrameThemeLoadListener() {
	if (typeof document === 'undefined' || typeof window === 'undefined' || window.sharedChildFrameThemeLoadListenerBound) {
		return;
	}

	window.sharedChildFrameThemeLoadListenerBound = true;
	document.addEventListener('load', function (event) {
		const frame = event && event.target;
		if (!frame || frame.tagName !== 'IFRAME' || !frame.contentWindow) {
			return;
		}

		const configuredOrigin = String(frame.getAttribute('data-omo-theme-message-origin') || '').trim();
		if (!/^https?:\/\/[^/?#]+$/i.test(configuredOrigin)) {
			return;
		}

		try {
			frame.contentWindow.postMessage({
				type: 'omo-theme-sync',
				preference: sharedGetThemePreference(),
				colorStyle: sharedGetColorStylePreference()
			}, configuredOrigin);
		} catch (error) {
		}
	}, true);
}

function sharedApplyDocumentTheme(options = {}) {
	const settings = options && typeof options === 'object' ? options : {};
	const storageKey = settings.storageKey || SHARED_THEME_STORAGE_KEY;
	const colorStyleStorageKey = settings.colorStyleStorageKey || SHARED_COLOR_STYLE_STORAGE_KEY;
	const mediaQuery = settings.mediaQuery || SHARED_THEME_MEDIA_QUERY;
	const root = settings.root || settings.documentElement || document.documentElement;
	const parentThemeState = sharedGetParentFrameThemeState();
	const preference = settings.preference === 'light' || settings.preference === 'dark' || settings.preference === 'system'
		? settings.preference
		: (parentThemeState && parentThemeState.preference
			? parentThemeState.preference
			: sharedGetThemePreference(storageKey));
	const colorStyle = settings.colorStyle === 'mono' || settings.colorStyle === 'turquoise' || settings.colorStyle === 'ocean-blue'
		? settings.colorStyle
		: (parentThemeState && parentThemeState.colorStyle
			? parentThemeState.colorStyle
			: sharedGetColorStylePreference(colorStyleStorageKey));
	const resolvedTheme = parentThemeState && parentThemeState.theme && settings.preference !== 'light' && settings.preference !== 'dark'
		? parentThemeState.theme
		: sharedResolveTheme(preference, mediaQuery);

	sharedEnsureThemeMessageListener();
	sharedEnsureChildFrameThemeLoadListener();

	root.dataset.themePreference = preference;
	root.dataset.theme = resolvedTheme;
	root.dataset.colorStyle = colorStyle;
	root.style.colorScheme = resolvedTheme;

	if (typeof window !== 'undefined' && typeof window.setTimeout === 'function') {
		window.setTimeout(function () {
			sharedBroadcastThemeToChildFrames({
				preference: preference,
				colorStyle: colorStyle
			});
		}, 0);
	}

	return {
		preference: preference,
		theme: resolvedTheme,
		colorStyle: colorStyle
	};
}

function countChar(objet, limit) {
	if (objet.val().length>limit) {
		objet.val(objet.val().substr(0,limit));
	}
	objet.nextAll(".char_count").html(objet.val().length+" sur "+limit+" caractères");
} 

// Ouvre et ferme la fenêtre popup
function showPopup(target, title=null, close=true) {
	$("#popup").data("popup-target", target);
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
	var popupTarget = String($("#popup").data("popup-target") || "");
	if (/(?:^|\/)popup\/profil\.php(?:[?#]|$)/i.test(popupTarget) && typeof window.commonTopbarModalCanClose === "function" && window.commonTopbarModalCanClose() === false) {
		return;
	}
	if (/(?:^|\/)popup\/profil\.php(?:[?#]|$)/i.test(popupTarget) && typeof window.commonTopbarRefreshUserProfile === "function") {
		window.commonTopbarRefreshUserProfile("close");
	}
	if (/(?:^|\/)popup\/profil\.php(?:[?#]|$)/i.test(popupTarget) && typeof window.commonTopbarModalCanClose === "function") {
		window.commonTopbarModalCanClose = null;
	}
	$("#popup").removeData("popup-target");
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


// Fonction pour convertir un json en HTML en utilisant du XSLT
// Usage: transformJSONtoHTML(jsondata, xsltFilePath, 'output');
async function transformJSONtoHTML(jsonData, xsltFilePath, targetElementId=null,  paramName=null, paramValue=null) {
    // Convert JSON to XML
    
    
  
    
    
   function jsonToXml(json, root = 'root') {
        let xml = `<${root}>`;
        for (let key in json) {
			// Fonction particulière pour éviter les boucles
			if (key!='parent') {
				if (Array.isArray(json[key])) {
					json[key].forEach(item => {
						xml += jsonToXml(item, key);
					});
				} else if (typeof json[key] === 'object') {
					xml += jsonToXml(json[key], key);
				} else {
					xml += `<${key}>${json[key]}</${key}>`;
				}
			}
        }
        xml += `</${root}>`;
        console.log(xml);
        return xml;
    }

    // Fetch and parse the XSLT file
    async function fetchXSLT(xsltFilePath) {
        const response = await fetch(xsltFilePath);
        if (!response.ok) {
            throw new Error(`Failed to load XSLT file: ${response.statusText}`);
        }
        const xsltText = await response.text();
        const parser = new DOMParser();
        return parser.parseFromString(xsltText, "application/xml");
    }

    try {
        // Convert JSON to XML string
        const xmlString = `<?xml version="1.0" encoding="UTF-8"?>${jsonToXml(jsonData)}`;
        
        // Parse XML string to a DOM Document
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(xmlString, "application/xml");

        // Load the XSLT file
        const xsltDoc = await fetchXSLT(xsltFilePath);

        // Apply the XSLT transformation
        const xsltProcessor = new XSLTProcessor();
        xsltProcessor.importStylesheet(xsltDoc);
        
        // Définir le paramètre
        if (paramName)
			xsltProcessor.setParameter(null, paramName, paramValue);

        const resultDocument = xsltProcessor.transformToFragment(xmlDoc, document);

        // Insert the transformed HTML into the target element
        if (targetElementId) {
		
        const targetElement = document.getElementById(targetElementId);
        if (targetElement) {
            targetElement.innerHTML = '';
            targetElement.appendChild(resultDocument);
        } else {
            console.warn(`Element with ID "${targetElementId}" not found.`);
        }
        } else {
			return resultDocument;
		}
    } catch (error) {
        console.error('Error in transformJSONtoHTML:', error);
        const targetElement = document.getElementById(targetElementId);
        if (targetElement) {
            targetElement.innerHTML = `<p>Error: ${error.message}</p>`;
        }
    }
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

let sharedJqueryBindingsInitialized = false;

function initializeSharedJqueryBindings() {
	if (sharedJqueryBindingsInitialized || typeof window.jQuery !== 'function') {
		return false;
	}

	sharedJqueryBindingsInitialized = true;

	window.jQuery(function () {
		function sharedGetCurrentReturnTo() {
			return window.location.pathname + window.location.search + window.location.hash;
		}

		// *******************************************************
		// Menu utilisateur en haut
		// ******************************************************
		$("body").delegate("#profilbtn","click", function (e) {
			showPopup("/popup/profil.php", "Profil");
		});

		$("body").delegate("#logoutbtn","click", function (e) {
			window.location.href = "/common/logout.php?return_to=" + encodeURIComponent(sharedGetCurrentReturnTo());
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
			showPopup("/popup/login.php?return_to=" + encodeURIComponent(sharedGetCurrentReturnTo()), "Se connecter");
		});			

		$("#popup_close").click(function () {
			closePopup(); 
		});
	});

	return true;
}

if (!initializeSharedJqueryBindings()) {
	document.addEventListener('DOMContentLoaded', function () {
		initializeSharedJqueryBindings();
	}, { once: true });
}
