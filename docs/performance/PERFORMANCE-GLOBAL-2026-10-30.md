# Performance globale - 30 octobre 2026

## Resultat

Optimisations locales a partir de la revision `d9b44153`, sans changement de schema SQL.
Docker Desktop et les services Compose ont ete demarres avec les volumes existants.

| Reponse HTTP | Requetes avant | Requetes apres | Mediane avant | Mediane apres | Gain temporel |
| --- | ---: | ---: | ---: | ---: | ---: |
| Calendrier | 370 | 129 | 293,69 ms | 163,96 ms | 44 % |
| Scrutins | 142 | 78 | 131,07 ms | 98,07 ms | 25 % |
| Panneau de structure | 79 | 71 | 92,14 ms | 72,87 ms | 21 % |
| Documents | 59 | 51 | 108,21 ms | 103,06 ms | 5 % |
| Projets | 45 | 37 | 196,87 ms | 193,53 ms | 2 % |
| Reglement | 178 | 170 | 155,24 ms | 145,73 ms | 6 % |

Les autres ecrans controles sont les badges d attention, la navigation laterale,
le formulaire et le detail du calendrier. Leurs nombres de requetes sont inchanges.
Le temps median de la navigation laterale passe de 40,86 a 43,01 ms : les petits
ecarts de quelques millisecondes ne constituent pas un gain ou une regression etabli.

Les dix reponses ont le meme HTML/JSON apres normalisation des jetons de session
et des identifiants aleatoires de controles. Toutes repondent HTTP 200, avec zero
erreur SQL dans les deux series.

## Methode et limites

- PHP 8.5.10, MariaDB 11.4, Apache, montage Docker/Windows existant.
- Organisation locale 828, utilisateur 2150, holon 8440 ; detail de l evenement 15553.
- Deux sessions de mesure separees, meme utilisateur et memes donnees.
- Code initial retabli temporairement pour la reference, puis modifications restaurees.
- Redemarrage gracieux d Apache avant chaque serie pour repartir dans les memes conditions.
- Neuf appels HTTPS sequentiels par ecran et par version ; les deux premiers sont
  exclus de la mediane pour laisser chauffer les caches. Soit 180 reponses HTTP.
- Journal SQL habituel conserve : seuil 50 ms. Les captures exhaustives utilisent
  temporairement un fichier dans `/tmp` du conteneur, sans modifier le `.env`.
- Les nombres du tableau comprennent le bootstrap HTTP. Les premiers diagnostics
  CLI du rendu seul donnaient 369 puis 128 requetes pour le calendrier.

Le montage local presente encore des valeurs extremes proches d une seconde,
y compris sur des ecrans tres courts. Sept observations retenues par ecran ne
suffisent pas pour conclure sur les percentiles eleves. Les gains temporels sont
locaux ; la baisse du nombre de lectures constitue le resultat le plus reproductible.
Ces mesures ne couvrent pas le rendu navigateur, une charge simultanee ni le serveur
de production.

## Analyse des donnees existantes

Le journal `tmp/sql-performance/live.jsonl` contenait 36 658 evenements JSON valides
du 22 septembre au 29 octobre, plus une ligne invalide ignoree par l analyse.
Il melange navigation et tests ; ses volumes ne doivent pas etre lus comme une
mesure de frequentation reelle. Les traces detaillees a seuil 0 des anciens audits
ne sont pas directement comparables aux temps avec le seuil habituel.

La table de details fournissait 17 386 mesures de pages, cron et taches depuis le
11 octobre, dont 9 745 appels web sur des chemins applicatifs, jusqu au 29 octobre
a 19:37 UTC. Ces appels totalisent environ 3 970 secondes, 845 000 requetes SQL
et 412 secondes de SQL. Le SQL ne represente donc qu environ 10 % du temps ecoule
historique ; optimiser seulement les requetes individuellement lentes serait
insuffisant. Le reste n est pas attribuable avec certitude aux seules mesures
existantes : initialisation PHP, E/S, attentes de session et appels externes doivent
etre distingues par un profilage supplementaire.

Les routes les plus couteuses en temps cumule dans cet historique sont la maintenance
de fond, les badges d attention, l accueil OMO, les actions PV et le panneau de
structure. Le calendrier, les scrutins et les documents comportent des lectures
repetitives directement reproductibles dans la version actuelle.

## Changements

### Calendrier

- Les occurrences suivantes sont recherchees en une requete groupee par lot de
  series, au lieu d une recherche par evenement et par portee. Les conditions
  existantes sur la position, l activite et la fin future sont conservees ; une
  serie absente ne donne pas de faux resultat positif.
- Les temps personnels sont charges par lots pour le seul utilisateur courant,
  puis reutilises dans la reponse, y compris lorsque la ligne est absente.
- Le calcul des invitations reutilise le lecteur par lots existant
  `Event::filterInvitedForUser()`. Les controles de visibilite et d edition restent
  appliques a chaque evenement.
- Le test de presence de documents reutilise les associations deja chargees pour
  les liens du calendrier, y compris les documents partages.
- Les evenements externes virtuels conservent leurs propres donnees et restent
  exclus des nouveaux chargements de relations OMO.

### Lectures partagees et suivi SQL

- `Holon::getChildren()` hydrate directement les enfants de la collection. Les
  parcours de membres et de structure evitent ainsi une lecture supplementaire
  par enfant, avec les memes filtres et le meme ordre.
- Le suivi SQL conserve l origine de l appel, mais reporte l anonymisation, le
  calcul d empreinte et la resolution des chemins jusqu a la decision de journaliser
  la requete. Les requetes rapides restent comptees sans payer ce formatage.
- Les erreurs restent journalisees quel que soit le seuil, sans valeurs de
  parametres, litteraux SQL ni commentaires sensibles.
- Le cache des temps personnels utilise la memoisation de lecture existante,
  limitee a la reponse. Une ecriture dbObject le vide et le desactive. Aucun cache
  de donnees supplementaire n est conserve en session.

Toutes les nouvelles requetes restent dans `class/dbobject/`. Aucune migration
ni modification du seed Docker n est necessaire.

## Validation

Tests passes sous PHP 8.5 :

- `calendar_read_batch_test.php` : equivalence des recurrences et invitations,
  exclusions des occurrences inactives/passees, temps absents, isolation des
  utilisateurs, copies modifiables et invalidation apres sauvegarde.
- `dbobject_sql_performance_threshold_test.php` : seuil, erreurs, compteurs,
  conservation de la ligne d appel et absence de secrets dans les journaux.
- `dbobject_sql_performance_log_test.php`, destinations explicite et par defaut.
- `performance_monitoring_test.php`, `read_only_memoization_test.php`,
  `arraydbobject_hydration_test.php`, `application_catalog_cache_test.php`.
- `data_access_readonly_integration_test.php 828` : onze contextes de droits.
- `structure_batch_readonly_integration_test.php 828 2150` et `1 1`,
  `holon_bulk_structure_representation_test.php`.
- `calendar_availability_test.php`, `calendar_time_buffers_test.php`,
  `calendar_personal_time_buffers_test.php`, `calendar_recurrence_test.php`,
  `calendar_recurrence_endpoint_test.php`.
- `group_membership_permissions_integration_test.php`,
  `circle_invitation_membership_test.php`.

Le test JavaScript `calendar_lazy_views_test.js` passe aussi. Syntaxe PHP des fichiers
modifies, UTF-8 sans BOM, fins de ligne LF et `git diff --check` controles.

Le mode `calendar_availability_test.php --render` echoue sur l assertion
`No other-organization block in month`. Le meme echec a ete reproduit avec les
cinq fichiers applicatifs remis a leur version initiale : ce n est pas une
regression de ce lot. L assertion ancienne n a pas ete modifiee. Le test de mise
en page Playwright n a pas pu demarrer, car ce module n est pas installe dans le
projet ; aucun fichier JavaScript ou CSS n a ete modifie.

## Pistes differees

- La synchronisation d un agenda externe perime peut encore attendre le reseau
  pendant l affichage (budget global actuel de 12 secondes). La deplacer hors de
  la reponse demanderait une decision sur la fraicheur et le retour d erreur.
- Les imports d organisations atteignent plusieurs dizaines de milliers de
  requetes. Leur reduction toucherait aux sauvegardes, historiques et droits ;
  ce parcours rare est laisse pour un lot dedie.
- Les recherches documentaires par expressions regulieres ont quelques traces
  lentes. Un autre moteur ou index demande de valider la pertinence des resultats
  et le comportement multilingue.
- Le calendrier conserve des lectures individuelles de l etat de verrouillage PV.
  Elles pourraient etre groupees a leur tour, en verifiant les transitions de
  statut et les modifications concurrentes.
- Mesurer ensuite sur un serveur proche de la production et avec plusieurs sessions
  pour distinguer cout applicatif, attentes et pression sur les workers.

Les artefacts locaux sont conserves sous `tmp/performance-global-*`,
`tmp/performance-http-*` et `tmp/performance-*-full.jsonl`, notamment les exports
anonymises de mesures, les reponses comparees et le detail des deux series.
