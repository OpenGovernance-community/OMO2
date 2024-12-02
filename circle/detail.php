<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	
	// Initialise le login
	$connected=checklogin();
	
	// En principe, les données sont chargées
	
?>
<style>
	.navTo {cursor:pointer;padding:2px; display:inline-block; border-radius:2px;}
	.roleNav .navTo::before {
		content: ">";
	}
	.navTo:hover {background:rgba(0,0,0,0.1)}
	
	/* Afficher ou cacher les titres des rubriques selon divers critères */
	ul.detail_list_role, ul.detail_list_circle {padding-left:20px;}
	ul.detail_list_role:has(li)::before {
		content: "Rôles :"; /* Texte à afficher avant la liste */
		display: block; /* Pour que le texte soit affiché sur une nouvelle ligne */
		font-weight: bold; /* Exemple de style */
		margin-left:-20px;
	}
	.filter_zone:has(.highlight) ul.detail_list_role::before {display:none}
	ul.detail_list_role:has(.highlight)::before {display:block !important}

	ul.detail_list_circle:has(li)::before {
		content: "Cercles :"; /* Texte à afficher avant la liste */
		display: block; /* Pour que le texte soit affiché sur une nouvelle ligne */
		font-weight: bold; /* Exemple de style */
		margin-left:-20px;
	}
	.filter_zone:has(.highlight) ul.detail_list_circle::before {display:none}
	ul.detail_list_circle:has(.highlight)::before {display:block !important}
	
	span.detail_list_group:has(li)::before {
		content: "Groupe " attr(title) ; /* Texte à afficher avant la liste */
		display: block; /* Pour que le texte soit affiché sur une nouvelle ligne */
		font-weight: bold; /* Exemple de style */
	}
	.filter_zone:has(.highlight) span.detail_list_group::before {display:none}
	span.detail_list_group:has(.highlight)::before {display:block !important}

	}
	
</style>
<script>
	// Fonction récursive pour créer un tableau indexé par ID
	function createIdIndexedMap(node, map) {
		// Ajouter le nœud actuel au tableau indexé par ID
		map[node.ID] = node;
		
		// Si le nœud a des enfants, parcourir récursivement les enfants
		if (node.children) {
			for (var i = 0; i < node.children.length; i++) {
				createIdIndexedMap(node.children[i], map);
			}
		}
	}

		
			// Créer un tableau indexé par ID
	var idIndexedMap = {};
	createIdIndexedMap(root, idIndexedMap);
	var displayNode=idIndexedMap['<?=$_GET["id"]?>'];

	
	$(function () {
		
		var txt="";
		if (displayNode.type=="4") txt+="<style>#btn_delete_role, #btn_move_role  {display:none}</style>";
		if (displayNode.type=="1") txt+="<style>#btn_add_role {display:none}</style>";
		// Nom du noeud (et son groupe)
		txt+="<h1>"+(displayNode.type=="4"?"Organisation ":(displayNode.type=="2"?"Cercle ":(displayNode.type=="3"?"Groupe ":"Rôle ")))+(displayNode.name?displayNode.name.replace(/</g, '&lt;').replace(/>/g, '&gt;'):"<i>Indéfini</i>")+"</h1>";
		
		// Chemin d'accès
		let path=displayNode;
		let pathStr='';
		while (path.parent) {

			path=path.parent;

			pathStr="<span class='navTo' data-src='"+path.ID+"'>"+path.name+"</span> "+pathStr;
		}
		txt="<div style='font-size:60%' class='roleNav'>"+pathStr+"</div>"+txt;
		
		
		
		// Ensuite les cercles spécifiques
		
		txt+="<style>.node_"+displayNode.ID+" {background:rgba(255, 204, 0,0.5) !important}</style>";
		txt+="<div id='detail_node'></div>";
		$(".screenOJ").html(txt);
		transformJSONtoHTML(displayNode, "/xslt/list_role_short.xml","detail_node");

        const $div = $('#role_list');
        const $para = $(".node_"+displayNode.ID);
        if ($para.position()) {
        const wasHidden = !$div.is(':visible');

        // Afficher temporairement la div si elle est cachée
        if (wasHidden) $div.show();

        // Calculer la position pour centrer le paragraphe
        const scrollPosition = $div.scrollTop() + $para.position().top - ($div.height() / 2) + ($para.outerHeight() / 2);

        // Si elle était cachée, la cacher à nouveau après le calcul
        if (wasHidden) $div.hide();

        // Afficher et animer le scroll si nécessaire
        $div.fadeIn(300, function () {
            $div.animate({ scrollTop: scrollPosition }, 500);
        });
       }
			
		
		


	}); 

		
</script>
<table class='leftTab' cellspacing=0 cellpadding=0><tr><td class='odj'>
			<div><?=T_("Holarchie");?>

			<span class='noPrint menuNode' style='float:right; background:#FFF; border-radius:5px 5px 0px 0px'>
			<img src='/img/addentry.png' class='imgbutton' style='margin:0px;' id='btn_add_role'  data-toggle='tooltip' data-placement='bottom' title='<?=T_('Ajouter un noeud',true)?>'>
			<img src='/img/icon_edit.png' class='imgbutton' style='margin:0px;' id='btn_edit_role'  data-toggle='tooltip' data-placement='bottom' title='<?=T_('Editer le noeud',true)?>'>
			<img src='/img/expand-arrows.png' class='imgbutton' style='margin:0px;' id='btn_move_role'  data-toggle='tooltip' data-placement='bottom' title='<?=T_('Déplacer le noeud',true)?>'>
			<img src='/img/icon_delete.png' class='imgbutton' style='margin:0px;' id='btn_delete_role'  data-toggle='tooltip' data-placement='bottom' title='<?=T_('Supprimer le noeud',true)?>'>

			</span>
			</div>
			</td></tr><tr><td style='height:100%; position: relative;vertical-align:top'><div class='screenOJ filter_zone'>

		
			</div>
			</td></tr>
</table>
