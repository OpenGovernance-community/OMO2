<?php

require_once dirname(__DIR__) . '/common/patreon.php';

function patreonSyncHelp()
{
	echo "Usage : php scripts/patreon-sync.php [--user=ID]\n";
	echo "Synchronise les comptes Patreon connectés et rafraîchit les jetons si nécessaire.\n";
	echo "Exemple cron quotidien : 15 3 * * * php /chemin/projet/scripts/patreon-sync.php\n";
}

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "Ce script doit être exécuté en ligne de commande.\n");
	exit(1);
}

$userId = 0;
foreach (array_slice($argv, 1) as $argument) {
	if ($argument === '--help' || $argument === '-h') {
		patreonSyncHelp();
		exit(0);
	}

	if (strpos($argument, '--user=') === 0) {
		$userId = (int)substr($argument, strlen('--user='));
		continue;
	}

	fwrite(STDERR, "Option inconnue : {$argument}\n");
	exit(1);
}

if (!patreonIsConfigured('sync')) {
	fwrite(STDERR, "La configuration Patreon est incomplète : " . patreonGetConfigurationMessage('sync') . "\n");
	exit(1);
}

$connections = \dbObject\UserPatreon::loadActiveConnections($userId);
if ($connections === []) {
	echo "Aucun compte Patreon à synchroniser.\n";
	exit(0);
}

$hasError = false;
foreach ($connections as $connection) {
	$label = 'Utilisateur #' . (int)$connection->get('IDuser');
	try {
		$profile = patreonSyncConnection($connection);
		$status = trim((string)($profile['patron_status'] ?? ''));
		$tierTitles = trim((string)($profile['tier_titles'] ?? ''));
		echo "[OK] {$label}";
		if ($status !== '') {
			echo " - {$status}";
		}
		if ($tierTitles !== '') {
			echo " - {$tierTitles}";
		}
		echo PHP_EOL;
	} catch (Throwable $exception) {
		$hasError = true;
		fwrite(STDERR, "[ERREUR] {$label} - " . $exception->getMessage() . PHP_EOL);
	}
}

exit($hasError ? 1 : 0);
?>
