<?php

	$routes = [
		'admin'   => '/admin/index.php',
		'demo'    => '/omo/index.php',
	];

	$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
	$host = preg_replace('/:\d+$/', '', $host);
	$parts = array_values(array_filter(explode('.', $host)));
	$subdomain = $parts[0] ?? '';
	$isLocalhostSubdomain = count($parts) === 2 && ($parts[1] ?? '') === 'localhost';
	$reservedEnvironmentSubdomains = ['dev', 'beta'];
	$isEnvironmentRootHost = count($parts) >= 3
		&& in_array((string)($parts[count($parts) - 3] ?? ''), $reservedEnvironmentSubdomains, true);
	$rootPartCount = $isEnvironmentRootHost ? 3 : 2;
	$hasOrganizationSubdomain = $isLocalhostSubdomain || count($parts) > $rootPartCount;

	if (isset($routes[$subdomain])) {
		require __DIR__ . $routes[$subdomain];
		exit;
	} else {
		if ($hasOrganizationSubdomain) {
			require __DIR__ . "/lms/index.php";
			exit;
		}
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>OpenGov.tools - Des outils pour soutenir une collaboration efficace et humaniste</title>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
		<!-- JQuery et jquery UI -->
		<script src="https://code.jquery.com/jquery-3.6.0.js"></script>
		<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
		<script>
			
		// Change JQueryUI plugin names to fix name collision with Bootstrap.
		$.widget.bridge('uitooltip', $.ui.tooltip);
		$.widget.bridge('uibutton', $.ui.button);
		</script>
		
		<!-- Bootstrap (for html editor) Summernote-->
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.bundle.min.js"></script>
<script>
	$(window).on("scroll", function () {
		$("#bkg_illustration").css('top', -$(window).scrollTop()/2);
	});
	$(function() {
		var $root = $('html, body');

		$('a[href^="#"]').click(function() {
			var href = $.attr(this, 'href');

			$root.animate({
				scrollTop: $(href).offset().top
			}, 500, function () {
				//window.location.hash = href;
			});

			return false;
		});
});
</script>
<style>
* {box-sizing: border-box;}

.tools {text-align:right; left:0px; scrollbar-width: thin;width:100%; height:300px; overflow-x:auto; overflow-y: hidden; padding:10px;white-space:nowrap;}
.tools .tool {text-align:left; font-weight:bold;position:relative;overflow:hidden; display:inline-block; margin-right:10px;white-space:normal; vertical-align:top; background:rgba(255,255,255,0.8); border-radius:10px; width:250px; height:100%;box-shadow: 5px 5px 10px rgba(0,0,0,0.5);}
.tools .tool h1 {padding:15px; margin:0px;}
.tools .tool p {margin:5px 10px;}
.box_title {
	background-color:#0B6E7A
}
.easypv_title {background:#FFC600 url(/img/bkg_pat_easypv.png); background-size:cover;}

.easycircle_title {background-color:#FFC600; background-image:url(/img/bkg_pat_easycircle.png);background-size:cover;}
.easymemo_title {background-image:url(/img/bkg_pat_easymemo.png);background-size:cover;}
.on_dev:after {
    content: "En développement";
    position: absolute;
    transform: rotate(-45deg);
    background: #F00;
    left: -75px;
    top: 55px;
    white-space: nowrap;
    padding: 4px 4px;
    font-size: 20px;
    opacity: 0.7;
    width: 300px;
    text-align: center;
}
.on_project:after {
    content: "En projet";
    position: absolute;
    transform: rotate(-45deg);
    background: #F00;
    left: -75px;
    top: 55px;
    white-space: nowrap;
    padding: 4px 4px;
    font-size: 20px;
    opacity: 0.7;
    width: 300px;
    text-align: center;
}


.contentPres {
  display: flex;
  flex-direction: column;
  gap: 3rem;
  max-width: 1200px;
  margin: auto;
  padding: 20px;
}

.bloc {
  display: flex;
  align-items: center;
  gap: 2rem;
}

/* alternance automatique */
.bloc:nth-child(even) {
  flex-direction: row-reverse;
}

.bloc img {
  border-radius: 10px;
  height: 300px;
  width: auto;
  object-fit: cover;
}

.bloc h1 {
  margin-top: 0;
}

.intro_txt {
position:relative; 
margin-left:auto; 
width:50%;
color:#FFF; 
font-size: 1.5vmax;
overflow:hidden;
min-height: calc(100dvh - 360px); 
padding:15px;
}

.vertical {
	  /* 🔥 fade transparent réel */
  -webkit-mask-image: linear-gradient(to bottom, black 70%, transparent);
  mask-image: linear-gradient(to bottom, black 70%, transparent);
  height:calc(100dvh - 440px); 
}
.intro_txt.expanded .vertical {
 height:inherit;
}
.intro_txt.expanded .vertical{
  -webkit-mask-image: none;
  mask-image: none;
}

.read-more-wrapper {
  text-align: center;
  margin-top: 10px;
}

.read-more {
  background: none;
  border: none;
  padding: 6px 12px;

  font-size: 0.9rem;
  color: rgba(255,255,255,0.8);

  cursor: pointer;
  position: relative;

   outline: none;
  -webkit-tap-highlight-color: transparent; /* 🔥 supprime le flash bleu sur mobile */

}

/* petite ligne discrète */
.read-more::after {
  content: "";
  display: block;
  width: 40px;
  height: 1px;
  margin: 6px auto 0;
  background: rgba(255,255,255,0.5);
  transition: width 0.2s ease;
}

.read-more:hover::after {
  width: 70px;
}

.read-more:hover {
  color: white;
}


/* enlève le contour seulement si ce n'est pas du focus clavier */
.read-more:focus:not(:focus-visible) {
  outline: none;
}

.cta-more {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;

  text-align: center;
  font-size: 1.4rem;
  color: #000;
  text-decoration: none;

  max-width: 100%;
}

/* texte flexible sur 2 lignes max */
.cta-text {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;

  line-height: 1.2;
}

/* flèches */
.arrow {
  width: 32px;
  flex-shrink: 0; /* 🔥 empêche de rétrécir */
}

/* 📱 Mobile */
@media (max-width: 768px) {
  .bloc {
    flex-direction: column !important;
    text-align: left;
  }

  .bloc img {
    width: 100%;
    height: auto;
	max-width: 50dvh;
  }
  .intro_txt {
    right:0px;
	top:0px;
    width:100%;
	color:#FFF;
    font-size: inherit;
  }
  .vertical {
	overflow-y: hidden;
  }
  .cta-text {font-size: 80%;}
}


</style>
<!-- Bootstrap (for html editor) Summernote-->
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style='margin:0px; padding:0px; background-color: #1A82A3;'>
<div id='bkg_illustration' style='background:url(/img/home.jpg) #11668B; background-size:cover; background-position: 30% center; height:100vh; width:100%; padding:0px; margin:0px;position:fixed;'></div>
<div style='background: linear-gradient(to top right, rgba(0,0,0,0), rgba(0,0,0,0.7));height:100vh; width:100%; padding:0px; margin:0px;position:fixed;'></div>
<div style='margin:0px;position:relative;'>
	<!-- Affichage du tableau avec les différents éléments -->
	<div class='intro_txt' id="intro">
	<div class='vertical'><h1 style='font-size:150%;'>Nous développons une nouvelle version de OpenMyOrganization!</h1>
	<p>10 ans après la première version, nous lançons un chantier d'envergure: repenser le logiciel en intégrant nos dix années d'expériences, autant d'un point de vue des fonctionnalités que de l'ergonomie ou de la prise en main.</p>
	<p>Accompagnez-nous dans cette grande aventure, en testant les nouvelles fonctionnalités, en soutenant financièrement son développement ou en amenant des propositions d'amélioration.</p>
    <p style='text-align:center'><a href="https://www.patreon.com/cw/OpenGovernance" target="_blank" class="btn">
        Rejoindre la communauté de soutien
      </a></p>
</div>
<div class="read-more-wrapper">
      <button id="toggleText" class="read-more">
        Afficher la suite
      </button>
    </div>
</div>

	<!-- Affichage des outils -->
	<div class='tools'>
	<div class='tool'><h1 class='box_title easypv_title'>EasyPV</h1><p>Facilitez vos prises de notes en réunion grâche à cette application permettant de gérer un ordre du jour dynamique!</p><p><a href='/pv'>&gt;Découvrez ce module</a></p></div>
	<div class='tool'><h1 class='box_title easycircle_title'>EasyCIRCLE</h1><p>Améliorez la lisibilité de la structure de votre organisation grâche à l'affichage en cercles et rôles.</p><p><a href='/circle'>&gt;Découvrez ce module</a></p></div>
	<div class='tool'><h1 class='box_title easymemo_title on_dev'>EasyMEMO</h1><p>Générez facilement des mémos depuis votre téléphone portable, en utilisant l'IA pour retranscrire et formater vos propos.</p><p><a target='_blank' href='https://t.me/SD2_MemoBot'>&gt;Connectez le BOT Telegram</a><br><a target='_blank' href='/memo'>&gt;Gérez vos memos</a></p></div>
	<div class='tool'><h1 class='box_title easymgov_title on_dev'>EasyGOV</h1><p>Définissez des règles de fonctionnement sous la forme d'une constitution claire et accessibles à tous et toutes.</p><p><a target='_blank' href='https://jm.instantz.org/constitution.php'>&gt;Visitez le chantier</a></p></div>
	<div class='tool'><h1 class='box_title easytask_title on_project'>EasyTASK</h1><p>Augmentez votre productivité grâce à notre application de gestion de tâches pour mobile et PC.</p></div>
	<div class='tool'><h1 class='box_title easypilot_title on_project'>EasyPILOT</h1><p>Pilotez votre organisation en vous appuyant sur des faits, grâche à un cockpit d'indicateurs visuels de qualité.</p></div>
	<div class='tool'><h1 class='box_title easychoose_title on_project'>EasyCHOICE</h1><p>Décidez collectivement de façon asynchrone: vote, jugement majoritaire, consentement, sondage,... </p></div>
	<div class='tool'><h1 class='box_title easydate_title on_project'>EasyDATE</h1><p>Organisez et partagez vos agenda, facilitez la prise de rendez-vous en équipe et planifiez des événements.</p></div></div>
	

	
</div>
<div style='min-height:calc(100vh); padding:10px; margin:0px;position:relative; background:#FFF'>
<a href="#content" class="cta-more">
  <img src="/img/down-arrow.png" alt="" class="arrow">
  <span class="cta-text">
    Découvrez-en plus sur la nouvelle version
  </span>
  <img src="/img/down-arrow.png" alt="" class="arrow">
</a>
<a name='content' id='content'></a>
<div class="contentPres">

  <section class="bloc">
    <img src="/img/ilu_opensource.jpg" alt="Illustration open source">
    <div>
      <h2>Open Source</h2>
      <p>Une avancée majeure qui incarne notre engagement envers la transparence, la communauté et la durabilité: Cette nouvelle version est maintenant entièrement Open Source, grâce à un code fiable qui offre la liberté à chacun d'installer sa propre instance du projet.</p>
      <p>L'ouverture de notre code source marque une étape cruciale dans notre mission d'autonomisation des utilisateurs et de promotion de la collaboration au sein de la communauté. En permettant à chacun d'accéder, de modifier et de distribuer le code, nous croyons fermement en la création d'un écosystème plus robuste, où la pérennité du logiciel est garantie par la diversité des contributeurs.</p>
      <p>Que vous soyez un développeur chevronné cherchant à personnaliser le logiciel selon vos besoins spécifiques, ou un utilisateur souhaitant simplement comprendre le fonctionnement interne de l'application, notre engagement envers l'Open Source vise à favoriser l'innovation, l'échange de connaissances et la confiance au sein de notre communauté.</p>
      <p><a href="https://github.com/DavidDrayer/OMO2" target="_blank">&gt; Voir sur GitHub</a></p>
    </div>
  </section>

  <section class="bloc">
    <img src="/img/ilu_multilingual.jpg" alt="Illustration multilingue">
    <div>
      <h2>Multilingue</h2>
      <p>Désormais, notre application est non seulement multilingue, offrant la possibilité de traduire l'interface dans différentes langues, mais elle s'adapte également aux subtilités des langages propres à la gouvernance partagée.</p>
      <p>Vous avez désormais la possibilité de personnaliser le vocabulaire spécifique utilisé dans le logiciel, alignant ainsi les termes tels que les cercles, les redevabilités ou les liens de pilotage avec la culture et les pratiques propres à votre organisation.</p>
      <p>Cette flexibilité linguistique vise à créer une expérience utilisateur plus fluide, où chaque utilisateur peut interagir avec le logiciel de manière naturelle et conforme à ses préférences linguistiques et culturelles. Nous croyons que cette approche renforce la pertinence de notre logiciel dans des contextes divers, encourageant l'adoption au sein d'organisations aux structures et aux terminologies spécifiques.</p>
    </div>
  </section>

  <section class="bloc">
    <img src="/img/ilu_modular2.jpg" alt="Illustration modulaire">
    <div>
      <h2>Modulaire</h2>
      <p>Avec enthousiasme, nous vous dévoilons la dernière évolution de notre logiciel, désormais conçu avec une approche modulaire révolutionnaire. Cette nouvelle fonctionnalité vous offre une flexibilité inégalée, vous permettant de personnaliser votre expérience en activant uniquement les modules nécessaires à vos besoins spécifiques.</p>
      <p>La modularité de notre logiciel simplifie grandement la prise en main, offrant une interface épurée et une navigation intuitive. Dès le départ, vous avez la liberté de sélectionner les fonctionnalités qui correspondent à vos priorités immédiates, vous permettant ainsi de vous concentrer sur ce qui compte le plus pour votre équipe.</p>
      <p>Cependant, l'aspect révolutionnaire de notre approche modulaire ne s'arrête pas là. Vous avez également la possibilité d'ajouter des modules supplémentaires au fil du temps, élargissant ainsi progressivement les fonctionnalités de votre logiciel. Cette évolutivité vous donne la possibilité de transformer notre logiciel en une solution complète, permettant la gestion intégrale de l'information au sein de votre équipe.</p>
      <p>Cette approche modulaire représente une nouvelle ère dans la personnalisation des outils logiciels, où l'adaptabilité devient la clé de l'efficacité.</p>
    </div>
  </section>

  <section class="bloc">
    <img src="/img/ilu_accessible.jpg" alt="Illustration accessible">
    <div>
      <h2>Accessible</h2>
      <p>Nous croyons en un avenir où chaque organisation, en particulier celles qui œuvrent à l'émergence d'un monde enviable, doit avoir accès aux outils nécessaires pour prospérer. C'est dans cet esprit que nous avons rendu notre logiciel non seulement puissant, mais également accessible, en particulier pour les petites organisations partageant des missions sociales ou écologiques importantes.</p>
      <p>Pour favoriser l'inclusion, les fonctionnalités de base de notre logiciel sont mises à disposition gratuitement. Nous croyons que chaque initiative mérite un accès équitable aux outils nécessaires pour réussir, et cela commence par la fourniture des fonctionnalités essentielles sans coût initial.</p>
      <p>Comprenant les défis financiers auxquels sont confrontées les petites organisations, nous proposons également différents plans de financement flexibles. Ces plans sont adaptés non seulement à la taille de votre organisation, mais aussi à la mission sociale ou écologique que vous poursuivez. Notre objectif est de soutenir activement ceux qui cherchent à apporter un changement positif dans le monde, en offrant des solutions financières qui correspondent à leurs besoins spécifiques.</p>
    </div>
  </section>

  <section class="bloc">
    <img src="/img/ilu_peda.jpg" alt="Illustration pédagogique">
    <div>
      <h2>Pedagogique</h2>
      <p>Nous comprenons les défis auxquels sont confrontés les utilisateurs lorsqu'il s'agit de maîtriser la complexité des informations, c'est pourquoi notre logiciel a été conçu pour rendre cette expérience d'apprentissage aussi accessible et progressive que possible.</p>
      <p>S'appuyant sur les enseignements tirés au cours de ces dix dernières années d'implémentation chez nos clients, notre logiciel intègre une approche pédagogique unique. Nous avons identifié et compris les obstacles que peuvent rencontrer les utilisateurs lors de la prise en main d'un outil complexe, et nous avons mis en place des solutions pour les surmonter.</p>
      <p>Pour faciliter votre apprentissage, nous mettons à votre disposition une documentation complète, claire et concise. Chaque fonctionnalité est expliquée en détail, accompagnée d'exemples concrets pour une compréhension approfondie. De plus, notre approche progressive vous permet d'apprendre pas à pas, sans être submergé par la complexité, vous laissant ainsi la liberté de maîtriser les différentes fonctions à votre rythme.</p>
    </div>
  </section>

</div>

<!-- Footer d'appel aux dons -->

<div >

</div>
	</div>
<style>
	.footer-cta {
		background: linear-gradient(rgba(10, 30, 60, 0.9), rgba(10, 30, 60, 0.5)), url('/img/OGC-background.png') center/cover no-repeat;
  color: white;
  padding: 3rem 2rem;
  z-index: 9;
  position: relative;
}


.footer-content {
  display: flex;
  align-items: center;
  gap: 2rem;
  max-width: 1000px;
  margin: 0 auto;
}

.logo {
  width: 80px;
  height: 80px;
  object-fit: contain;
}

.footer-text {
  flex: 1;
}

.footer-text p {
  margin-bottom: 1rem;
  line-height: 1.5;
}

.btn {
  display: inline-block;
  background: #e2b100;
  color: white;
  padding: 0.6rem 1.2rem;
  border-radius: 6px;
  text-decoration: none;
  font-weight: bold;
  transition: background 0.3s;
}

.btn:hover {
  background: #e8c500;
  color: #003b6b
}
@media (max-width: 768px) {
  .footer-content {
    flex-direction: column;
    text-align: center; /* optionnel mais souvent plus joli */
  }

  .logo {
    margin-bottom: 1rem; /* espace entre logo et texte */
  }
}
</style>

<footer class="footer-cta">
  <div class="footer-content">
    <img src="/img/logo-OGC.png" alt="OpenGovernance.community" class="logo">

    <div class="footer-text">
      <p>
        Soutenez le projet OpenGovernance.community et contribuez à construire
        des outils ouverts et accessibles pour tous.
      </p>
      <a href="https://www.patreon.com/cw/OpenGovernance" target="_blank" class="btn">
        Nous soutenir sur Patreon
      </a>
    </div>
  </div>
</footer>

</body>
<script>
const intro = document.getElementById('intro');
const textBlock = intro.querySelector('.vertical');
const btn = document.getElementById('toggleText');

let isExpanded = false;

// toggle
btn.addEventListener('click', () => {
  isExpanded = !isExpanded;

  intro.classList.toggle('expanded', isExpanded);

  btn.textContent = isExpanded
    ? "Réduire"
    : "Afficher la suite";
});

// check overflow sur le BON élément
function checkOverflow() {
  // force état fermé pour mesurer
  intro.classList.remove('expanded');

  const hasOverflow = textBlock.scrollHeight > textBlock.clientHeight + 2;

  btn.style.display = hasOverflow ? "inline-block" : "none";

  // restaure état
  if (isExpanded || !hasOverflow) {
    intro.classList.add('expanded');
  }
}

// debounce
function debounce(fn, delay) {
  let t;
  return () => {
    clearTimeout(t);
    t = setTimeout(fn, delay);
  };
}

const debouncedCheck = debounce(checkOverflow, 150);

// events
window.addEventListener('load', checkOverflow);
window.addEventListener('resize', debouncedCheck);

if (document.fonts) {
  document.fonts.ready.then(checkOverflow);
}
</script>
</html>
