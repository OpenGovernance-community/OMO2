<?php
require_once __DIR__ . '/../config.php';

$siteTitle = trim((string)($GLOBALS['siteTitle'] ?? 'Le site'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Conditions générales - <?= htmlspecialchars($siteTitle) ?></title>
	<style>
		:root {
			--cg-bg: #f8fafc;
			--cg-card: #ffffff;
			--cg-text: #0f172a;
			--cg-muted: #475569;
			--cg-accent: #2563eb;
			--cg-border: #dbe4ee;
		}

		* {
			box-sizing: border-box;
		}

		body {
			margin: 0;
			font-family: Arial, Helvetica, sans-serif;
			background: linear-gradient(180deg, #eff6ff 0%, var(--cg-bg) 220px);
			color: var(--cg-text);
		}

		.cg-shell {
			max-width: 920px;
			margin: 0 auto;
			padding: 32px 20px 48px;
		}

		.cg-card {
			background: var(--cg-card);
			border: 1px solid var(--cg-border);
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
			color: var(--cg-muted);
		}

		.cg-badge {
			display: inline-block;
			margin-bottom: 14px;
			padding: 6px 10px;
			border-radius: 999px;
			background: #dbeafe;
			color: var(--cg-accent);
			font-size: 13px;
			font-weight: 700;
			letter-spacing: .02em;
		}

		.cg-note {
			margin-top: 22px;
			padding: 14px 16px;
			border-left: 4px solid var(--cg-accent);
			background: #f8fbff;
			border-radius: 12px;
		}

		a {
			color: var(--cg-accent);
		}
	</style>
</head>
<body>
	<div class="cg-shell">
		<div class="cg-card">
			<div class="cg-badge">Version provisoire</div>
			<h1>Conditions générales d'utilisation</h1>
			<p>
				Cette page constitue une version temporaire des conditions générales d'utilisation de
				<strong><?= htmlspecialchars($siteTitle) ?></strong>.
				Elle est publiée afin de permettre l'activation technique de certaines intégrations
				et sera complétée, relue et validée ultérieurement.
			</p>

			<h2>1. Objet</h2>
			<p>
				Le présent site propose des services numériques, contenus, fonctionnalités et espaces
				d'interaction destinés à ses utilisateurs. Les présentes conditions ont pour objet de
				définir, à titre provisoire, le cadre général d'utilisation du service.
			</p>

			<h2>2. Acceptation</h2>
			<p>
				L'utilisation du site implique l'acceptation des présentes conditions générales, dans
				leur version en vigueur au moment de la consultation ou de l'utilisation du service.
			</p>

			<h2>3. Accès au service</h2>
			<p>
				L'éditeur s'efforce d'assurer un accès raisonnable au service, sans garantie
				d'accessibilité permanente, de disponibilité continue ou d'absence d'erreur.
			</p>

			<h2>4. Compte utilisateur</h2>
			<p>
				Certaines fonctionnalités peuvent nécessiter la création ou l'utilisation d'un compte.
				L'utilisateur s'engage à fournir des informations exactes et à ne pas détourner le
				service de son usage normal.
			</p>

			<h2>5. Services tiers</h2>
			<p>
				Le site peut s'interfacer avec des services externes, notamment des plateformes
				tierces d'authentification, de paiement, de soutien ou d'abonnement. L'utilisation de
				ces services reste également soumise aux conditions propres de leurs éditeurs.
			</p>

			<h2>6. Limitation de responsabilité</h2>
			<p>
				Cette version provisoire est fournie à des fins de préfiguration. Tant que la version
				définitive n'a pas été publiée, aucun élément de cette page ne doit être interprété
				comme une rédaction juridique finale ou comme un engagement exhaustif.
			</p>

			<h2>7. Évolution du document</h2>
			<p>
				Ces conditions générales pourront être modifiées, complétées ou remplacées à tout
				moment par une version définitive plus complète.
			</p>

			<div class="cg-note">
				<p>
					Document de travail à compléter. Prévoir ensuite l'ajout des mentions légales,
					des dispositions sur les données personnelles, des modalités d'abonnement, des
					conditions de résiliation et du droit applicable.
				</p>
			</div>
		</div>
	</div>
</body>
</html>
