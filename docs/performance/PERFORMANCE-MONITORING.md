# Suivi des performances

Le tableau de bord `/common/admin/stats.php` est reserve au role super administrateur du site. Le mode super admin n a pas besoin d etre active. Les membres ordinaires et visiteurs recoivent HTTP 403 avant les lectures de statistiques.

## Collecte

La migration `2026-10-11-01-performance-monitoring.sql` cree `performance_request` et `performance_hourly`. Elle passe par le flux de migration habituel. La collecte est active par defaut ; `PERFORMANCE_MONITOR_ENABLED=false` permet de la suspendre sans effacer l historique.

Le bootstrap commun enregistre une ligne a la fin de chaque requete HTTP, y compris sans SQL et en cas d erreur PHP fatale lorsque le shutdown peut encore fonctionner. Les scripts qui n utilisent pas ce bootstrap, les fichiers statiques, les arrets forces et la file d attente du serveur web ne sont pas couverts. Les appels CLI ordinaires sont exclus ; le cron CLI utilise les memes mesures explicites que le cron HTTP.

Champs : script et methode (sans query string ni parametres), heure UTC, organisation et utilisateur de session, statut HTTP, duree ecoulee, CPU PHP si disponible, duree/nombre/erreurs SQL, pic memoire et participation du cron. Les identifiants de requete et de parent permettent de relier pages, cron et taches dans les details. Le CPU est mesure par difference de `getrusage` depuis le bootstrap ; il ne comprend pas celui de MySQL. Les compteurs SQL fonctionnent aussi lorsque les logs SQL detailles sont desactives. Les acces PDO qui contournent dbObject ne sont pas comptes.

L insertion dediee reste dans dbObject : une ecriture sans historique d objet et sans auto-profilage. Une erreur de collecte n interrompt pas la reponse. Une transaction applicative encore ouverte a la fin du traitement n est ni validee ni annulee par le collecteur : son echantillon est ignore.

## Cron et attribution

Le cron habituel, y compris son declenchement CLI, HTTP et de secours par le navigateur, agrege les donnees et applique la retention. Aucun nouveau planificateur n est necessaire.

Trois vues sont exclusives dans le tableau de bord : pages HTTP, invocations du cron et taches du cron. Les invocations ignorees par le verrou ou la temporisation sont identifiees. Une exception de tache marque la tache en erreur et remonte dans le statut mesure de l invocation, meme lorsque le cron poursuit les autres taches.

Une page est marquee sans appel, cron ignore ou cron execute. Si elle appelle plusieurs fois le cron, le statut execute prend le dessus ; nombre et temps des appels restent cumules. Sa duree et son CPU incluent deja le travail du cron : ne pas additionner les trois vues. Le cron global et ses taches ont une organisation/utilisateur nuls, car ils peuvent traiter toutes les organisations. La requete HTTP declencheuse reste rattachee a son contexte de session.

La maintenance du navigateur passe par une requete HTTP separee a `runtime_maintenance.php`. Elle est mesuree sur cette route, pas comme du temps supplementaire dans la reponse d un ecran qui a deja ete charge. Le filtre cron permet aussi de comparer les appels executes et ignores de cette route.

## Agregation et retention

- Details : 30 jours, uniquement apres agregation reussie.
- Agregats horaires : 13 mois, par script, methode, type, organisation, utilisateur et statut cron.
- Verrou SQL par base, lots de 5 000 lignes, au maximum 10 lots par passage.
- Selection des identifiants, ajout des compteurs et marquage des details dans une meme transaction. Une relance ne double pas les compteurs ; une arrivee tardive complete le bon intervalle.
- Purges bornees a 10 000 lignes par table et par passage. Un retard de cron conserve les details non traites.

Le tableau lit les agregats et les details non encore traites via une union dans le meme snapshot SQL. Les utilisateurs actifs sont comptes distinctement sur chaque periode, sans additionner des comptes horaires. Les utilisateurs anonymes sont inclus dans les volumes mais exclus du nombre de comptes actifs. Les comparaisons par utilisateur incluent le cout total, y compris anonyme, divise par les comptes actifs ; ce n est pas un cout individuel.

Les medianes et p95 sont des estimations par histogramme additif : bornes 50, 100, 250, 500, 1 000, 2 000, 5 000 et 10 000 ms, puis maximum observe. La borne haute est affichee ; les percentiles horaires ne sont jamais moyennes pour obtenir un percentile journalier.

Ce suivi mesure le travail applicatif, pas le pourcentage de saturation du serveur. CPU global, files de workers et pression memoire systeme demandent une collecte d infrastructure distincte.

## Validation locale

`tests/performance_monitoring_test.php` verifie les compteurs SQL sans log detaille, les donnees live, l equivalence apres agregation, les relances, les arrivees tardives, la retention, les utilisateurs distincts, les valeurs CPU absentes, l isolation des organisations et les statuts cron/taches.

`tests/stats_dashboard_access_test.php` verifie les refus visiteur/membre et l acces du super administrateur sans mode eleve. Ces tests utilisent la base locale ; la migration doit deja etre appliquee.
