# Reference de performances - 2026-09-22

## Conditions

- Capture : 22 septembre 2026, 18:31:06 a 18:36:40, Europe/Zurich.
- Revision : `3a6d3452dd778f5f343135a883780123b67d0dff` ; aucun changement de code applicatif pour cette capture.
- Environnement : Docker local, PHP 8.5.10, OPcache actif, sessions PHP sur fichiers, compression gzip active.
- Navigateur : onglet Codex existant, `https://org1.localtest.me/omo/`, session utilisateur existante, holon racine #674.
- Navigation par les commandes visibles. Aucune creation, edition ou suppression de contenu declenchee manuellement ; la maintenance habituelle de l'application peut s'executer au chargement.
- Cache navigateur et OPcache non purges. Base existante non figee : garder le meme jeu de donnees et les memes droits pour comparer.

## Configuration locale

Variables ajoutees dans `docker/app/.env` (fichier ignore par Git) :

```dotenv
DB_QUERY_LOG_ENABLED=true
DB_QUERY_LOG_MIN_MS=50
DB_QUERY_LOG_PATH=/var/www/html/tmp/sql-performance/live.jsonl
```

Le fichier est monte comme `/var/www/html/.env` et relu par PHP : aucun redemarrage Docker n'a ete necessaire.
Le journal est accessible sous Windows dans `tmp/sql-performance/live.jsonl`, grace au montage existant du projet.
Le repertoire `tmp/` est ignore par Git et son acces HTTP a ete verifie : reponse 403.
La journalisation reste active. Le fichier `live.jsonl` utilise un chemin fixe et grossit par ajout ; le copier/archiver regulierement ou desactiver la collecte apres usage.

## Archive de reference

- Fichier brut local : `tmp/sql-performance/baseline-2026-09-22.jsonl`.
- Taille : 23 538 octets.
- SHA-256 : `3C0CB65276074EE644B26E7AAD6BC6D206A628E8C1E5F3E2FA5EF8ABF18251FE`.
- 55 bilans HTTP, zero erreur SQL enregistree, zero requete individuelle atteignant le seuil de 50 ms.
- Le nombre et le temps cumule de toutes les requetes SQL sont comptes, meme sous le seuil.
- Les fichiers bruts restent locaux ; ce rapport ne contient ni identifiants de connexion ni contenu metier.

## Premiers passages

Les durees sont celles du bilan PHP (`request_duration_ms`) et du cumul SQL (`database_duration_ms`), en millisecondes. Elles ne mesurent pas la fin du rendu navigateur. La duree PHP peut inclure une attente de session et le cout de la journalisation. Les temps de requetes HTTP paralleles ne doivent pas etre additionnes comme un temps de chargement global.

| Parcours / contexte | Route | Requetes SQL | PHP ms | SQL ms | request_id |
| --- | --- | ---: | ---: | ---: | --- |
| Chargement initial | `/omo/` | 40 | 1128.224 | 27.732 | `398a193579be4cd4` |
| Equipe, Local, 3 fiches | `/omo/api/team/index.php` | 46 | 1148.565 | 30.619 | `3a485b3e468b674d` |
| Documents, Local, 0 document | `/omo/api/documents/index.php` | 130 | 1236.893 | 62.740 | `6dda39b9744667ac` |
| Documents, Descendants, 37 documents, Modification / Detail | `/omo/api/documents/index.php` | 1384 | 1912.511 | 687.035 | `27c0798f353da584` |
| Ouverture du panneau Structure | `/omo/api/getStructure.php` | 12 | 902.954 | 10.880 | `418d33ec2b396ce1` |
| Calendrier, Local, septembre 2026, 19 evenements | `/omo/api/calendar/index.php` | 694 | 1293.822 | 261.382 | `3d1543e36aca383e` |
| Projets, Local, Kanban, 3 projets | `/omo/api/projects/index.php` | 43 | 987.065 | 26.466 | `04df1537cbd2ecd4` |
| Decisions, Descendants, Actif, 4 cartes | `/omo/api/decision/index.php` | 289 | 1116.066 | 126.270 | `e04c338eda6bd8c6` |

Structure : les donnees avaient deja ete chargees par la mini-structure au demarrage (`getStructureData.php`, 13 requetes, 68.906 ms PHP, 10.154 ms SQL). La ligne du panneau ne represente donc pas un premier chargement complet de toutes les donnees.

## Passages repetes

| Parcours | Mode | Requetes SQL | PHP ms | SQL ms | request_id |
| --- | --- | ---: | ---: | ---: | --- |
| Documents / Descendants, passage 2 | Rechargement complet de `#documents` | 1384 | 697.872 | 504.230 | `0c964df51e96d278` |
| Documents / Descendants, passage 3 | Rechargement complet de `#documents` | 1384 | 936.997 | 505.966 | `e8eaa214d53d1ed0` |
| Equipe / Local, passage 2 | Ouverture depuis Documents | 46 | 913.184 | 24.062 | `e63ca5a02dcc836e` |
| Equipe / Local, passage 3 | Rechargement complet de `#team` | 46 | 307.383 | 27.851 | `9c8e8cc0e5ff4255` |
| Calendrier, passage 2 | Ouverture depuis Equipe | 694 | 922.227 | 251.589 | `ef30de39f09ebffe` |
| Calendrier, passage 3 | Rechargement complet de `#calendar` | 694 | 462.997 | 269.022 | `2462e58cc8f1f3ae` |

Lors des deux rechargements Documents, une requete pour la vue locale precede celle des descendants : 130 + 1384 = 1514 requetes SQL pour ces deux reponses Documents, hors autres appels du demarrage. Les reponses locales sont identifiees par `7f4bbf4c6c5c45c4` et `6fc491809d858c23`. Conserver ce double appel dans la comparaison du parcours complet.

Les passages supplementaires de 18:36:26 et 18:36:39 servent a relever le nombre de documents puis a restaurer la portee Local. Ils sont presents dans l'archive mais exclus des passages repetes ci-dessus.

## Rejouer apres optimisation

1. Conserver PHP, la configuration SQL ci-dessus, le meme navigateur, compte, organisation, holon et jeu de donnees. Ne pas purger les caches pour comparer a cette session ; mesurer un demarrage a froid separement si necessaire.
2. Noter l'heure de debut et la revision. Garder l'archive actuelle intacte ; isoler la nouvelle plage de bilans HTTP par heure et `request_id`.
3. Recharger `/omo/`, puis ouvrir Team, Documents/Local, Documents/Descendants, Structure, Calendrier, Projets et Decisions. Attendre le contenu final entre chaque action.
4. Recharger deux fois la page sur `#documents`, avec la portee Descendants temporairement appliquee. Verifier les 37 documents et compter aussi l'eventuel chargement Local intermediaire.
5. Ouvrir Team puis recharger `#team`. Ouvrir Calendrier puis recharger `#calendar`, sur septembre 2026.
6. Comparer les memes types de passages : premiers affichages, ouvertures de panneaux et rechargements complets separes. Comparer le nombre de requetes, le cumul SQL, la duree PHP, les erreurs et la memoire, sans attribuer automatiquement tout gain a SQL.
7. Restaurer Documents/Local et revenir au tableau de pilotage. Copier la nouvelle collecte sous un autre nom date.

La petite equipe locale ne constitue pas un test de montee en charge. Aucun editeur PV, enregistrement, export ou traitement intensif n'a ete teste ici. Les mesures donnent une reference de navigation, pas un benchmark de production.

## Pistes confirmees ou a approfondir

- Documents/Descendants : 1384 requetes pour 37 documents affiches, reproductible ; priorite a l'analyse des chargements repetes et des droits.
- Calendrier : 694 requetes pour 19 evenements affiches, reproductible.
- Documents : double chargement Local puis Descendants lors de la restauration de la vue.
- Aucune requete individuelle lente au seuil choisi : le volume de petites requetes est une piste plus pertinente que la seule recherche de requetes de plus de 50 ms.
- Les ecarts PHP/SQL ne prouvent pas a eux seuls un verrou de session : ajouter une mesure ciblee avant d'en attribuer la cause.
- Pour identifier toutes les requetes repetees, une future capture courte avec `DB_QUERY_LOG_MIN_MS=0` est possible, mais ses temps devront etre compares avec une capture utilisant le meme seuil.
