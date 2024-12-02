<?
	// Inclus les éléments partagés entre plusieurs pages: base de donnée, instanciation de classe, etc...
	include_once("../include.php");

	if (isset($_POST["id"]) ) {
		
		// Lorsqu'un ID est passé, considère que c'est une clé privée et en affiche les options d'édition, dont le chargement en PDF	
			// Recherche la license avec la clé
			$license=new \dbObject\license ();
			$license->load(["privatekey",$_POST["id"]]);
			if ($license->getID()!="") {
				
				$_SESSION["IDlicense"]=$license->getId();
				// Ajoute (ou met à jour) un cookie, sans tenir compte du statut du cookie
				setcookie("IDlicense", $license->getId(), time()+3600*24*30,'/'); // 30 jours

				header('Location: index.php');
				exit;

			} 
	} 
	
	// Sinon, est-ce qu'un cookie est enregistré?
	if (isset($_COOKIE["IDlicense"])) {
		$_SESSION["IDlicense"]=$_COOKIE["IDlicense"];
	}
	
	if (isset($_SESSION["IDlicense"])) {
		$license=new \dbObject\license ();
		$license->load($_SESSION["IDlicense"]);
	}

?>
<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>	
  <title>Potty License - Keep in touch with the ABDL nursery</title>
  <link rel="stylesheet" href="/css/std.css">
  <script type='text/javascript' src='/script/std.js'></script>

<!-- Google tag (gtag.js) -->
	<?
	// Désactive le suivi google pour iPhone 12
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone 12') === false) {
	?>
<script async src="https://www.googletagmanager.com/gtag/js?id=G-HZYWQ6LP97"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-HZYWQ6LP97');
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCdxm1y7As7elrzATAeQ1WYIP8rIcajs4I&libraries=places"></script>
<?
}
?>

</head>
<body>
	
<?
	
	echo "<div id='popupScreen' class='popup' style='display:none'><div class='popupWindow' style=''><div class='title'></div><div class='scroll'><div class='content'></div></div><div id='dialog_buttons' style='position:absolute; bottom:10px; right:70px;'><input type='button' onclick='$(\"#popupScreen\").find(\".content\").html(\"\"); $(\"#popupScreen\").hide()' value='Close'></div></div></div>";
	// Est-ce que la session a défini un compte?
	if (!isset($license) || !$license->get("id")>0) {
	
	// Sinon, demande un login avant toute chose
		pageBackground("Application - Login");
		echo "<div class='centerzone'>";
		echo "<h1>Login</h1>";
	echo "<div class='bloc'>";
	echo "<p>Please connect to the App with your private key.</p>";
	echo "<form method ='POST'> <input type='text' name='id' placeholder='Your private key'><input type='submit' value='Login'></form>";
	echo "</div>";
		echo  "</div></html>";
			exit;
	}
	
?>
  <style>
	  .nav {display:inline-block;cursor:pointer;}
    body, html {
      margin: 0;
      padding: 0;
      overflow: hidden;
      height: 100%;
      position: relative;
	}
    .screens-container {
	  margin-top:80px;
      display: flex;
      height: calc(100% - 80px);
    }

.tbl_family th {background:#EEEEEE; border-radius:5px;padding:5px;}

body::before {
  content: "";
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(to bottom right, #4a8cff, #bd37ff);
  /*mix-blend-mode: lighten;*/
  z-index: -1;
}

body {
  /*background-image: url("/images/bkg_potty.jpg");*/
  background-position: center;
  background-size:25%;
  background-color: lightblue; /* Couleur de fond de secours si l'image n'est pas visible */
}

    .screen {
      flex: 0 0 100%;
      height: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      padding:10px;
      box-sizing: border-box;
    }
    #screen1 { }
    #screen2 { }
    #screen3 {  }
    .container {box-sizing: border-box;background-color: #FFF; box-shadow:5px 5px 10px rgba(0,0,0,0.5); border-radius:10px;height:100%; width:100%;max-width:800px; margin:auto; padding:10px; overflow:auto;scrollbar-width: none !important;}
	 .container::-webkit-scrollbar {
	  display: none;
	}

	/* Hide scrollbar for IE, Edge and Firefox */
	.container {
	  
	  
		::-webkit-scrollbar {
		  width: 5px;
		}

		::-webkit-scrollbar-thumb {
		  background-color: #888; /* Couleur de la poignée de l'ascenseur */
		  border-radius: 5px; /* Pour arrondir les coins de la poignée */
		}

		::-webkit-scrollbar-track {
		  background-color: #f2f2f2; /* Couleur de l'arrière-plan de l'ascenseur */
		}
	  
	  
	  
	  
	}
	.menu {
		position:absolute; left:0px; top:0px; bottom:0px; width:200px; background:rgba(255,255,255,0.5);
	}
	.bottommenu {
		position:fixed; left:0px; right:0px; top:0px; height:80px; overflow:auto; white-space:nowrap;
	   -ms-overflow-style: none;  /* IE and Edge */
	   scrollbar-width: none;  /* Firefox */
	}
	.bottommenu::-webkit-scrollbar {
	  display: none;
	}

.scene {
  width: 100%;
  border: 0px solid #CCC;
  margin: 0px;
  perspective: 1200px;
  position:relative;
}

.card {
  width: 100%;
  height: 100%;
  transition: transform 1s;
  transform-style: preserve-3d;
  cursor: pointer;
  position: absolute;
  top:0px;
}

.card.is-flipped {
  transform: rotateY(180deg);
}

.card__face {
  position: absolute;
  width: 100%;
  height: 100%;
  line-height: 260px;
  color: white;
  text-align: center;
  font-weight: bold;
  font-size: 40px;
  -webkit-backface-visibility: hidden;
  backface-visibility: hidden;
    -webkit-filter: drop-shadow(2px 2px 5px rgba(0,0,0,0.2));
  filter: drop-shadow(2px 2px 5px rgba(0,0,0,0.2));
}

.card__face--front {
  background: url(/lib/getImg.php?x=1200&ext=jpg&url=<?=urlencode("https://pottylicense.fun/images/license.php?id=".$license->get("number"))?>);
  background-position:center;
  background-size:cover;
  background-repeat:no-repeat;
  border-radius:10px; border:1px solid black; box-shadow: 5px 5px 5px rgba(0,0,0,0.3);

}

.card__face--back {
background: url(/lib/getImg.php?x=1200&ext=jpg&url=<?=urlencode("https://pottylicense.fun/images/license_verso.php?id=".$license->get("number"))?>);
  background-position:center;
  background-size:cover;
  background-repeat:no-repeat;
  transform: rotateY(180deg);
  border-radius:10px; border:1px solid black; box-shadow: 5px 5px 5px rgba(0,0,0,0.3);

}	
table.RS img {height:30px;}
	
	
	
/* Adaptation pour petit écran, on cache les menus mais on reste avec les marges */
@media only screen and (max-width: 1200px) {

	.menu {
		right:90%;
	}
	.menu:after {
		position:absolute;
		left:250px;
		top:20px;
		height:50px;
		width:50px;
		background:#FFFFFF;
	}


}

/* Adaptation pour téléphone, on enlève les marges */

@media only screen and (max-width: 700px) {
	.screen {padding:0px;}
	.container {
		margin:0px;
	}
	.menu {display:none}
 
}	
	img.star_new {display:none;}
	
	
  </style>
  	<style>
	.friend img.avatar {width:50px; float:left; border-radius:50%; margin-right:5px;border:2px solid black;}
	.friend {margin-bottom:10px;}
	.friend .name {font-weight:bold;}
	.friend.unconfirmed {opacity:0.5;}
	.buttons {float:right; position:relative; z-index:5}
	.buttons img {width:40px;}
	</style>
<div class='bottommenu'>
<?
		echo "<div style='margin-top:8px; text-align:right'>";
		echo "<div class='nav' data='0' ><img src='/images/img_config.png' style='margin:4px; width:60px; border-radius:50%; border:2px solid black;vertical-align:middle;'></div>";
		echo "<div class='nav' data='1' ><img src='/images/img_list_like.png' style='margin:4px; width:60px; border-radius:50%; border:2px solid black;vertical-align:middle;'></div>";
		echo "<div class='nav' data='2' ><img src='".$license->get("photo")."' style='margin:4px; width:60px; border-radius:50%; border:2px solid black;vertical-align:middle;'></div>";


		$friends=$license->getFriends(null,1);
		if (count($friends)>0) {
			$count=3;
			foreach ($friends as $friend) {
				// Est-ce que c'est validé ?
				if ($friend->get("validateCaregiver")!=null && $friend->get("validateLittle")!=null ) {
					// Affiche chaque personne     
					if ($friend->get("IDlicense_caregiver")!=$license->get("id"))
						echo "<div  data='".($count)."' style='position:relative;' class='nav check_usr_".$friend->get("license_caregiver")->get("id")."'><img src='https://".$_SERVER['HTTP_HOST']."/lib/getImg.php?x=200&url=".$friend->get("license_caregiver")->get("photo")."'  style='margin:4px; width:60px; border-radius:50%; border:2px solid black;vertical-align:middle;'><img src='/images/star.png' class='star_new' style='position:absolute; top:40px; right:5px; width:20px'></div>";
					else
						echo "<div data='".($count)."' style='position:relative;' class='nav check_usr_".$friend->get("license_little")->get("id")."'><img src='https://".$_SERVER['HTTP_HOST']."/lib/getImg.php?x=200&url=".$friend->get("license_little")->get("photo")."' style='margin:4px; width:60px; border-radius:50%; border:2px solid black;vertical-align:middle;'><img src='/images/star.png' class='star_new' style='position:absolute; top:40px; right:5px; width:20px'></div>";
					$count++;
				}
			}
		}

		echo "<div class='nav' data='".$count."' ><img src='/images/img_add_friend.png' style='margin:4px; width:60px; border-radius:50%; border:2px solid black;vertical-align:middle;'></div>";
			echo "</div>";
		?>

</div>

  <div class="screens-container"> <!-- Utilisation d'un conteneur pour les écrans -->
	  
	    <div class="screen" id="screen0"><div class='container' >
			
<?					

					// Affiche les credits disponibles
					echo "Credits: ".$license->get("credit");
					// Affichage graphique
					echo "<div style='border:2px solid black; border-radius:5px;overflow:hidden;'>";
					echo "<div style='display:inline-block; padding:3px; font-size:70%; vertical-align:bottom;width:".(365/(365+$license->get("credit"))*100)."%; background:#FFFF00; overflow:hidden'>365 days (default)</div>";
					echo "<div style='display:inline-block; padding:3px; font-size:70%; vertical-align:bottom;width:".($license->get("credit")/(365+$license->get("credit"))*100)."%; background:#00FF00; overflow:hidden'>".$license->get("credit")." days (credits)</div>";
					
					echo "</div>";
					echo "<div style='font-size:70%; padding:5px; margin:0px 10px;background:#EEEEEE; border-radius:10px; '>Your images will be kept for ".(365+$license->get("credit"))." days. To increase the length of time images remain available, you can earn credits by becoming a Patreon: each day you become a Patreon will increase the number of days your images are kept. If you stop contributing, credits will slowly decrease by 1 per day.</div>";

					// Bouton pour rafraichir la page
					echo "<input type='button' value='Refresh display' onclick='location.reload()'>";
					
					// Ajoute un bouton pour l'édition du contenu de la carte
					echo "<form id='info_form' method='POST' action='/license.php'><p>Click here to edit the informations on your card:</p><input type='hidden' name='action' value='edit'><input type='submit' value='Basic card informations'></form>";

					// Ajoute un bouton pour l'édition avancée
					echo "<form id='advanced_form' method='POST' action='/license.php'><p>Click here to configure <b>advanced features</b>, like color, short URL, Telegram diaper check,...</p><input type='hidden' name='action' value='advanced'><input type='submit' value='Advanced parameters'></form>";
?>	


<script>
	// Pour les systèmes à onglet
	// Si click d'un onglet 
	$(function () {

	});

</script>
	
		</div></div>
	    <div class="screen" id="screen1"><div class='container' >
			
			<div class='tab'>
			<div class='tab_header'>
				<div class='tab_title selected' id='tab_ll'>Last likes</div>
				<div class='tab_title' id='tab_lc'>Last checks</div>
			</div>
			<div class='tab_content selected' id='tab_ll_content'>
			
<?
			$likes=$license->getLikes(2);
			$olddate="";
			$oldimg="";
			$cpt=0;
			echo "<table style='width:100%'><tr><td>";
			foreach ($likes as $like) {
				$cpt+=1;
				if ($olddate!=$like->get("date")->format("d.m.Y")) {
					echo "</td></tr><tr><td colspan=2>";
					echo "<div style='border-radius:10px; color:#ffffff; font-weight:bold;padding:10px;background: linear-gradient(to bottom right, #4a8cff, #bd37ff);'>".$like->get("date")->format("d.m.Y")."</div>";
					echo "</td></tr><tr><td>";
					$oldimg="";
					$olddate=$like->get("date")->format("d.m.Y");
				}
				if ($oldimg!=$like->get("IDmeteo")) {
					echo "</td></tr><tr><td style='width:0px;position:relative;'>";
					if (is_null($like->get("meteo")->get("image"))) {
						echo "<img src='/images/stat_".$like->get("meteo")->get("IDstatus").".png' style='width:50px;'> ";
					} else {
						echo "<img src='/images/stat_".$like->get("meteo")->get("IDstatus").".png' style='position:absolute;width:30px;left:27px; top:27px;'> ";
						echo "<img class='openPopup' href='/app/viewPicture.php?id=".$like->get("IDmeteo")."' src='https://".$_SERVER['HTTP_HOST']."/lib/getImg.php?x=50&url=".$like->get("meteo")->get("image")."' style='width:50px;'>";
					}
					echo "</td><td>";
					$oldimg=$like->get("IDmeteo");
					$oldapp="";
				}
				if ($oldapp!=$like->get("IDeval")) {
					echo "<span style='padding-left:10px;'><img src='/images/eval_".$like->get("IDeval").".png' style='vertical-align:middle;height:25px;'> ".(is_string($like->get("license"))?"visitor":$like->get("license")->get("name"))."</span>";
					$oldapp=$like->get("IDeval");
				} else {
					echo "<span>, ".(is_string($like->get("license"))?"visitor":$like->get("license")->get("name"))."</span>";
				}
				if ($cpt>50) break;
			}
			echo "</td></tr></table>";
	?>		
	
	
			</div>
			<div class='tab_content' id='tab_lc_content'>
	
<?
			$checks=$license->getChecks();
			$olddate="";
			$oldimg="";
			$cpt=0;
			echo "<table style='width:100%'><tr><td>";
			foreach ($checks as $meteo) {
				$cpt+=1;
				if ($olddate!=$meteo->get("date")->format("d.m.Y")) {
					echo "</td></tr><tr><td colspan=2>";
					echo "<div style='border-radius:10px; color:#ffffff; font-weight:bold;padding:10px;background: linear-gradient(to bottom right, #4a8cff, #bd37ff);'>".$meteo->get("date")->format("d.m.Y")."</div>";
					echo "</td></tr><tr><td style='position:relative;'>";
					$oldimg="";
					$olddate=$meteo->get("date")->format("d.m.Y");
				}
					echo "<span style='padding-left:10px;' class='openPopup' href='/popup/pop_followrequest.php?id=".$meteo->get("id")."'><img src='/images/stat_".$meteo->get("IDstatus").".png' style='position:absolute;width:30px;left:35px; top:20px;'>";
					echo "<img src='/lib/getImg.php?x=200&url=https://pottylicense.fun".$meteo->get("license")->get("photo")."' style='height:50px; border-radius:50%; border:2px solid black;'> ";
					
					// Affiche mon évaluation
					$eval=$meteo->getMyEval();
					//if (!is_null($eval)) {
					//		echo "<img src='/images/evaluation_".$eval->get("IDeval").".png' style='vertical-align:middle;height:25px;'>";
					//}
					
					echo "</span><span style='position:relative;'>";
					if (is_null($meteo->get("image"))) {
						if (!is_null($eval))
						echo "<img src='/images/evaluation_".$eval->get("IDeval").".png' style='width:50px;'> ";
					} else {
						if (!is_null($eval))
							echo "<img src='/images/evaluation_".$eval->get("IDeval").".png' style='position:absolute;width:30px;left:27px; bottom:0px;'> ";
						echo "<img class='openPopup' href='/app/viewPicture.php?id=".$meteo->get("id")."' src='https://".$_SERVER['HTTP_HOST']."/lib/getImg.php?x=50&url=".$meteo->get("image")."' style='width:50px;'>";
					}

					
					echo "</span></td></tr><tr><td  style='position:relative;'>";

				if ($cpt>25) break;
			}
			echo "</td></tr></table>";
	?>		
			</div>
			</div>
	
		</div></div>	
			
    <div class="screen" id="screen2"><div class='container' >
<div class="scene scene--card">
	  <img  src="/images/size_license.jpg" alt="Potty License for <?=$license->get("name")?>" style="max-width:100%;  opacity:0">

  <div class="card">
    <div class="card__face card__face--front" title="Click to flip <?=$license->get("name")?>'s card and see the back side."></div>
    <div class="card__face card__face--back" title="Click to flip <?=$license->get("name")?>'s card and see the front side."></div>
  </div>

</div>

<p align='center' style='font-size:small'>Click on the image to see the back.</p>
<?
	// Affiche les liens sur les réseaux
	// Affichage des réseaux sociaux en petit

	
	$license->display("rs.php");

	// Affichage du bouton pour ajouter
	echo "<div style='text-align:center' id='main_button_zone'>";

	echo "<div style='text-align:center;'>";
	echo "<input type='button' value='Diaper Check !' class='openPopup' href='/app/check.php' title='Record a diaper check status'>";
	if ($license->get("telegrambot")!="")
		echo "<button id='btn_startstop'  title='Record a diaper check status'>".($license->get("diapercheck")?"Stop":"Start")." Telegram notifications</button>";

	echo "</div>";
	echo "</div>";
	
	$license->display("meteo.php");
	



	
	//echo  "</div></html>";

?>
<script>

	$(".nav").click(function () {
		changeScreen($(this).attr("data"));
	});

	$("body").delegate("#btn_startstop","click",function () {
		$("#btn_startstop").load("toggle.php");
	});

	// Crée un tableau vide
	let arrayLicense=["#main_button_zone","#meteo_refresh_<?=$license->get("number")?>","#tab_ll_content","#tab_lc_content"];

</script>
	
		
		
		</div></div>
		
<?

	$cglicense=$license;
	// Ajoute un onglet par ami/caregiver
		$count=3;
		if (count($friends)>0) {

$friendtoconfirmStr="";		
			
			foreach ($friends as $friend) {
				
				
				if ($friend->get("IDlicense_caregiver")!=$cglicense->get("id"))
					$license=$friend->get("license_caregiver");
				else
					$license=$friend->get("license_little");
			
				if ($friend->get("validateCaregiver")!=null && $friend->get("validateLittle")!=null ) {		
					
				
?>
   <div class="screen" id="screen<?=$count?>"><div class='container' >
		
<div class="scene scene--card">
	  <img  src="/images/size_license.jpg" alt="Potty License for <?=$license->get("name")?>" style="max-width:100%;  opacity:0">

  <div class="card">
    <div class="card__face card__face--front" style="background-image: url(/lib/getImg.php?x=1200&ext=jpg&url=<?=urlencode("https://pottylicense.fun/images/license.php?id=".$license->get("number"))?>);" title="Click to flip <?=$license->get("name")?>'s card and see the back side."></div>
    <div class="card__face card__face--back" style="background-image: url(/lib/getImg.php?x=1200&ext=jpg&url=<?=urlencode("https://pottylicense.fun/images/license_verso.php?id=".$license->get("number"))?>);" title="Click to flip <?=$license->get("name")?>'s card and see the front side."></div>
  </div>

</div>

<p align='center' style='font-size:small'>Click on the image to see the back.</p>
	
<?
	$license->display("rs.php");
	
	$canCheck=json_decode($license->canCheck());
	echo "<div style='text-align:center' id='caregiver_button_zone_".$license->get("number")."'>";
	if ($canCheck->button==1) {
		// Affiche le bouton, défini après le contenu et si c'est grisé
		if ($cglicense->get("id")>0) {
			// Bouton check avec image profil
			echo "<img src='".$cglicense->get("photo")."' style='vertical-align:bottom;border-radius:50%; height:35px; border:2px solid black;margin-right:-14px; z-index:2; position:relative;'><button class='btn_caregiver_check tooltip' data-src='".$license->get("number")."'";
		} else {
			// Bouton check anonyme
			echo "<button class='btn_caregiver_check tooltip' data-src='".$license->get("number")."'";
		} 

		
		
		if ($canCheck->success==1) {
			echo ">Diaper Check NOW !";
			
			echo "<style>.check_usr_".$license->get("id")." img {border-color:#00FF00 !important}</style>";
			
		} else {
			// Bouton grisé
			if ($canCheck->delay=="-1") {
				// Don't know when...
				echo " disabled title='".str_replace("'","&apos;",$canCheck->message)."'>Please wait for diaper check<span class='tooltiptext' id='myTooltip'>".$canCheck->message."</span>";
			} else {
				// With a delay
				echo " disabled title='".str_replace("'","&apos;",$canCheck->message)."'>Please wait for diaper check<span class='tooltiptext' id='myTooltip'>".$canCheck->message."</span>";
			}
		}
		echo "</button>";
		
		
		// Ajoute un bouton pour éditer si je suis Caregiver
		if ($friend->get("IDlicense_caregiver")==$cglicense->get("id") && !$friend->get("onlyFriend")) {
			echo "<button id='btn_caregiver_edit' data-src='".$license->get("number")."'>Edit</button>";
		}
	

?>
<script>
	// Ajoute l'ID de l'élément à rafraîchir
	arrayLicense.push("#caregiver_button_zone_<?=$license->get("number")?>");
	arrayLicense.push("#meteo_refresh_<?=$license->get("number")?>");
</script>

<?
	} 

	echo "</div>";

	$license->display("meteo.php");
	


	
?>	
	
	
		
		</div></div>
<?	
				$count++;
			} else {
				// Ici c'est pas validé
				if ($friend->get("IDlicense_caregiver")==$license->get("id")) {
					$friendtoconfirmStr.="<div class='buttons' >";
					if (!$friend->get("validateCaregiver")) $friendtoconfirmStr.="<img src='/images/hourglass.png' title='since ".$friend->get("validateLittle")->format("d.m.Y")."'> <img class='decline_friendship' data-src='".$friend->get("id")."' src='/images/ico_nocheck.png' title='cancel invitation'>";
					else if (!$friend->get("validateLittle")) $friendtoconfirmStr.="<img class='validate_friendship' src='/images/ico_check.png' title='validate friendship'  data-src='".$friend->get("id")."'> <img class='decline_friendship' data-src='".$friend->get("id")."' src='/images/ico_nocheck.png' title='decline friendship'>";
					else $friendtoconfirmStr.="<img class='decline_friendship askbefore' data-src='".$friend->get("id")."' src='/images/ico_nocheck.png' title='remove friendship'>";
					$friendtoconfirmStr.="</div>";
					$friendtoconfirmStr.=$friend->get("validateCaregiver") && $friend->get("validateLittle")?"<div class='friend'>":"<div class='friend unconfirmed'>";
					// Si ce n'est pas validé pour l'autre
					$friendtoconfirmStr.="<img src='".$friend->get("license_caregiver")->get("photo")."' class='avatar'>";
					$friendtoconfirmStr.="<div class='name'><a href='/id/".$friend->get("license_caregiver")->get("number")."' target='_blank'>".($friend->get("license_caregiver")->get("name"))."</a></div>";
					// Détermine si besoin d'ajouter des éléments pour validation (ou rejet)
					$friendtoconfirmStr.="<div class='type'>".($friend->get("onlyFriend")?"Friend":"Caregiver")."</div>";
				} else {
					$friendtoconfirmStr.="<div class='buttons' >";
					if (!$friend->get("validateLittle"))$friendtoconfirmStr.="<img src='/images/hourglass.png' title='since ".$friend->get("validateCaregiver")->format("d.m.Y")."'> <img class='decline_friendship' data-src='".$friend->get("id")."' src='/images/ico_nocheck.png' title='cancel invitation'>";
					else if (!$friend->get("validateCaregiver")) $friendtoconfirmStr.="<img src='/images/ico_check.png' class='validate_friendship' title='validate friendship'  data-src='".$friend->get("id")."'> <img class='decline_friendship' data-src='".$friend->get("id")."' src='/images/ico_nocheck.png' title='decline friendship'>";
					else $friendtoconfirmStr.="<img class='decline_friendship askbefore' data-src='".$friend->get("id")."' src='/images/ico_nocheck.png' title='remove friendship'>";
					$friendtoconfirmStr.="</div>";
					$friendtoconfirmStr.=$friend->get("validateCaregiver") && $friend->get("validateLittle")?"<div class='friend'>":"<div class='friend unconfirmed'>";
					$friendtoconfirmStr.="<img src='".$friend->get("license_little")->get("photo")."' class='avatar'>";
					$friendtoconfirmStr.="<div class='name'><a href='/id/".$friend->get("license_little")->get("number")."' target='_blank'>".($friend->get("license_little")->get("name"))."</a></div>";
					$friendtoconfirmStr.="<div class='type'>".($friend->get("onlyFriend")?"Friend":"Little")."</div>";
				}
				$friendtoconfirmStr.="<div style='clear:both;'></div></div>";
				//$friendtoconfirmStr.=$license->get("name")." ";
				
			}
		}
			
			
?>
<script>
	$("html").delegate("#btn_caregiver_edit","click",function () {
		showPopup("Configure little license", "/popup/pop_editlicense.php?id="+$(this).attr("data-src"));
	});
	// Appel la fonction de rafraichissement avec le tableau
	$(function() {
		setInterval(function() {
			refresh(arrayLicense);
		}, 60 * 1000); // 60 * 1000 milsec
	});
</script>
<?

 echo '<div class="screen" id="screen<?=$count?>"><div class="container" >';
		echo "<h1>Friend request</h1>";


			echo "<div id='friendlist'>";
			echo $friendtoconfirmStr;
			echo "</div>";

		echo "<div style='text-align:center'><button class='openPopup' href='/popup/pop_friend.php' title='Connect to a friend, a little or a caregiver'>Add friends...</button></div>";
	
			echo "<h1>Random check</h1>";
	
			
// Affichage d'une license à checker
	$tocheck=new \dbObject\arrayLicense();	
	$params["filter"] = "IDvisibility_check<3 and diapercheck=1";
	$params["order"] = "rand()";
	$tocheck->load($params);
	foreach ($tocheck as $lil) {
		if (json_decode($lil->canCheck())->success==1) {
			echo "<div style='position:relative; max-width:215px; display:inline-block; width:100%;padding:10px;vertical-align: top;'>";
			echo "<div><a href='/id/".$lil->get("number")."' target='_blank'><img src='/images/minilicense.php?id=".$lil->get("number")."' style='border:1px solid black; width:100%; max-width:250px;'></a>";
			echo "</div></div>";
			
			break;
		}
	}


		echo "</div></div>";
			echo "</div>";
		}
	
	
?>

 
  </div>

  <script>
	  
	  
	$("#friendlist").delegate(".validate_friendship","click", function() {
		// Appel la fonction de validation, si ça retourn 1, c'est bon, sinon, erreur
		$.get("/popup/pop_friend.php?vid="+$(this).attr("data-src"), function( retvalue ) {
			if (retvalue=="1") refresh("#friendlist");
			else
			alert ("Validation error. Try again.");
		}, 'html'); 
	});
	$("#friendlist").delegate(".decline_friendship","click", function() {
		// Confirmation si nécessaire
		if (!$(this).hasClass('askbefore') || confirm("Are you sure you want to remove this relationship ?")) {
			
			// Appel la fonction de validation, si ça retourn 1, c'est bon, sinon, erreur
			
			$.get("/popup/pop_friend.php?did="+$(this).attr("data-src"), function( retvalue ) {
				if (retvalue=="1") refresh("#friendlist");
				else
				alert ("Decline error. Try again.");
			}, 'html'); 
		}
	});	  
	  
	  
	// Bouton check, même routine pour tout le monde
	$("html").delegate(".btn_caregiver_check","click",function () {
		// Appel la page pour traiter la demande (page sécurisée)
		$.get("/ajax/friend_dc.php?id="+$(this).attr("data-src"), function( retvalue ) {
			if (retvalue!="0") {
				// Ouvre la popup avec le suivi de la demande
				showPopup("Request sent !", "/popup/pop_followrequest.php?id="+retvalue);
				
				//alert ("The request has been sent.");
				refresh("#caregiver_button_zone_"+$(this).attr("data-src"));
				
			} else
				alert ("Diaper Check error. Try again.");
		}, 'html'); 

		
	});
	
$('html').delegate('.card','click', function() {
  $(this).toggleClass('is-flipped');
});

	  
  let startX = 0;
let currentScreen = 0;
const screensContainer = document.querySelector('.screens-container');
const screens = document.querySelectorAll('.screen');

function changeScreen(index) {
  console.log ("index:"+index);

  const screenWidth = window.innerWidth;
  const offset = -index * screenWidth;
  console.log ("offest:"+offset);
  // Version sans translate
 	<?
	// Désactive les transition animée pour iPhone 12
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone 12') === false) {
	?>
  screensContainer.style.transform = `translateX(${offset}px)`; // Applique la translation sur le conteneur
	
	<?
} else {
	?>
	screensContainer.style.position = 'relative'; // Assurez-vous que l'élément a une position relative ou absolue
    screensContainer.style.left = offset + 'px'; // Utilisez la propriété left pour déplacer l'élément
  <?
	}
  ?>
  currentScreen = index;
}
changeScreen(2);
<?	// Désactive le swip pour iPhone 12
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone 12') === false) {
?>
screensContainer.addEventListener('touchstart', (event) => {
  startX = event.touches[0].clientX;
});

screensContainer.addEventListener('touchend', (event) => {
  const endX = event.changedTouches[0].clientX;
  const screenWidth = window.innerWidth;
  const sensitivity = 100;

  if (startX - endX > sensitivity && currentScreen < screens.length - 1) {
    changeScreen(parseInt(currentScreen) + 1);
  } else if (endX - startX > sensitivity && currentScreen > 0) {
    changeScreen(parseInt(currentScreen) - 1);
  }
});
<?
	}
?>

</script>
</body>
</html>

