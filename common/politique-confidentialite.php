<?php
require_once __DIR__ . '/../config.php';

$siteTitle = trim((string)($GLOBALS['siteTitle'] ?? 'Le site'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Politique de confidentialité - <?= htmlspecialchars($siteTitle) ?></title>
	<style>
		:root {
			--pc-bg: #f8fafc;
			--pc-card: #ffffff;
			--pc-text: #0f172a;
			--pc-muted: #475569;
			--pc-accent: #0f766e;
			--pc-border: #dbe4ee;
		}

		* {
			box-sizing: border-box;
		}

		body {
			margin: 0;
			font-family: Arial, Helvetica, sans-serif;
			background: linear-gradient(180deg, #ecfeff 0%, var(--pc-bg) 220px);
			color: var(--pc-text);
		}

		.pc-shell {
			max-width: 920px;
			margin: 0 auto;
			padding: 32px 20px 48px;
		}

		.pc-card {
			background: var(--pc-card);
			border: 1px solid var(--pc-border);
			border-radius: 24px;
			padding: 28px;
			box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
		}

		h1 {
			margin: 0 0 10px;
			font-size: 32px;
			line-height: 1.15;
		}

		h2 {
			margin-top: 28px;
			font-size: 20px;
		}

		p, li {
			line-height: 1.7;
			color: var(--pc-muted);
		}

		.pc-badge {
			display: inline-block;
			margin-bottom: 14px;
			padding: 6px 10px;
			border-radius: 999px;
			background: #ccfbf1;
			color: var(--pc-accent);
			font-size: 13px;
			font-weight: 700;
			letter-spacing: .02em;
		}

		.pc-note {
			margin-top: 22px;
			padding: 14px 16px;
			border-left: 4px solid var(--pc-accent);
			background: #f0fdfa;
			border-radius: 12px;
		}

		a {
			color: var(--pc-accent);
		}
	</style>
</head>
<body>
	<div class="pc-shell">
		<div class="pc-card">
			<div class="pc-badge">Version provisoire</div>
			<h1>Politique de confidentialité</h1>
			<p>
				Cette page constitue une version temporaire de la politique de confidentialité de
				<strong><?= htmlspecialchars($siteTitle) ?></strong>.
				Elle est publiée afin de permettre l'activation technique de certaines intégrations
				et sera complétée, relue et validée ultérieurement.
			</p>

			<h2>1. Données concernées</h2>
			<p>
				Le site peut être amené à traiter certaines données nécessaires à son fonctionnement,
				à la gestion des comptes utilisateurs, à la sécurité des accès, ainsi qu'à certaines
				intégrations techniques avec des services tiers.
			</p>

			<h2>2. Finalités du traitement</h2>
			<p>
				Les données peuvent être utilisées, à titre provisoire et non exhaustif, pour :
			</p>
			<ul>
				<li>permettre l'accès au service et l'authentification des utilisateurs ;</li>
				<li>gérer les préférences et paramètres liés au compte ;</li>
				<li>assurer la sécurité technique, la maintenance et le suivi du service ;</li>
				<li>vérifier l'état d'une connexion ou d'un abonnement via un service tiers autorisé.</li>
			</ul>

			<h2>3. Services tiers</h2>
			<p>
				Certaines fonctionnalités peuvent impliquer des échanges avec des plateformes tierces.
				Dans ce cadre, les données strictement nécessaires à l'intégration concernée peuvent
				être reçues, stockées ou mises à jour selon les autorisations accordées par l'utilisateur.
			</p>

			<h2>4. Conservation</h2>
			<p>
				Les données sont conservées pendant la durée nécessaire au fonctionnement du service,
				sous réserve des obligations légales, techniques ou contractuelles applicables.
			</p>

			<h2>5. Sécurité</h2>
			<p>
				L'éditeur met en œuvre des mesures raisonnables pour limiter les accès non autorisés,
				les usages abusifs et les pertes de données, sans pouvoir garantir une sécurité absolue.
			</p>

			<h2>6. Droits des personnes</h2>
			<p>
				Une version définitive de cette politique précisera les modalités d'exercice des droits
				d'accès, de rectification, d'effacement, d'opposition et, le cas échéant, de portabilité.
			</p>

			<h2>7. Caractère provisoire</h2>
			<p>
				Ce document est fourni à titre transitoire. Il sera remplacé par une version complète
				intégrant les mentions légales, les bases juridiques, les coordonnées de contact et les
				détails opérationnels du traitement.
			</p>

			<div class="pc-note">
				<p>
					Document de travail à compléter. Prévoir ensuite l'ajout des catégories exactes de
					données, des durées de conservation, des sous-traitants, des transferts éventuels
					et du contact de référence pour les demandes liées à la confidentialité.
				</p>
			</div>
		</div>
	</div>
</body>
</html>
