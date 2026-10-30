# Performance de pv/action.php - 30 octobre 2026

## Diagnostic

Le total affiche pour `/omo/api/documents/pv/action.php` regroupe plusieurs actions.
Les mesures historiques ne contiennent pas leur nom ; elles ne permettent donc pas
de repartir les 16 417 appels fournis entre lecture, sauvegarde et verrouillage.

L editeur appelle `poll_updates` toutes les cinq secondes en reunion ou pendant
une edition, et toutes les quinze secondes sinon. Ces appels sont suspendus quand
l editeur est masque. Les verrous ouverts
ont un heartbeat distinct toutes les trente secondes. Une reponse de polling
peut etre courte (`unchanged`) ou reconstruire le document, les presences et les
points. La revision inclut l historique de l organisation : une autre modification
dans la meme organisation peut aussi provoquer une reconstruction complete.

Le chemin complet repetait les parcours de hierarchie et les calculs de droits
pour les differents controles. Une premiere capture CLI sur un PV local de cinq
points comptait 1 352 requetes, dont 1 112 lectures des enfants des holons et 83
lectures de la derniere entree d historique de l organisation. Le chemin HTTP
avec session chaude restait a 1 338 requetes.

## Modifications

- Activation explicite de la memoisation dbObject existante pour `poll_updates`,
  qui est une lecture transportee en POST.
- Reutilisation de cette memoisation dans les trois constructeurs de reponses,
  y compris apres une sauvegarde. Les phases de validation des mutations restent
  hors de cette activation ; une ecriture dbObject ulterieure vide et desactive
  automatiquement le cache.
- Chargement du catalogue de traductions seulement lorsqu un texte doit etre
  produit. Une reponse courte ou un heartbeat reussi ne le charge plus inutilement.
- En relecture, suppression du calcul de droits de gestion qui etait execute
  pour le polling alors que son resultat n etait pas utilise pour cette action.
  Les controles d ouverture du PV restent appliques avant toute comparaison de revision.
- Persistance des caches de session puis liberation du verrou de session avant
  de construire la reponse complete, afin de laisser passer les autres requetes
  du meme navigateur.

Pas de modification des intervalles de synchronisation, des requetes SQL, du
schema, des revisions transmises ni du contenu JSON. Aucun cache de donnees
supplementaire entre deux requetes.

## Mesures HTTP locales

PHP 8.5.10, MariaDB 11.4, Docker/Windows. Document 25051, organisation 146,
utilisateur 1, cinq points. Comparaison du fichier initial puis du fichier modifie,
avec redemarrage gracieux d Apache avant chaque serie. Neuf appels par cas et
par version ; les deux premiers sont exclus des medianes. Journal SQL au seuil
habituel de 50 ms. Aucune edition de ce document pendant le benchmark.

| Reponse | Requetes avant | Requetes apres | Mediane HTTP avant | Mediane HTTP apres | Mediane SQL avant | Mediane SQL apres |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Aucun changement | 12 | 10 | 45,10 ms | 39,60 ms | 6,04 ms | 4,47 ms |
| Etat complet | 1 338 | 213 | 1 338,85 ms | 184,65 ms | 565,92 ms | 95,28 ms |

La reponse complete perd environ 84 % de ses requetes et 86 % de son temps median
dans ce cas local. Les JSON avant/apres sont identiques hors `serverTime` :
155 octets sans changement, 33 979 octets pour les cinq points. Toutes les reponses
sont HTTP 200. Une autre lecture locale, sur un PV de dix points et un autre
utilisateur, fonctionne aussi sans erreur SQL.

Ces chiffres ne prejugent pas du nouveau temps moyen en production : il depend
du melange des actions, du nombre de points, des structures et des utilisateurs.
Le gain principal porte sur les reponses completes, pas sur tous les appels.

## Validation

- Nouveau `pv_action_polling_test.php` : vraies requetes HTTPS, reponse complete
  puis stable, points confidentiels de l editeur, sauvegarde et nouvelle revision,
  verrous, isolation des organisations, acces en relecture et refus apres validation.
  Une structure de seize roles supplementaires et un budget de moins de 250
  requetes evitent de laisser revenir les parcours repetes lorsque le suivi est actif.
- `pv_participation_editor_permissions_test.php` : liens publics, operations sur
  l ordre du jour, changement de secretaire, refus des actions non autorisees et
  perte des droits apres desactivation d une affiliation.
- `pv_lock_heartbeat_batch_test.php`, `pv_point_lock_cache_test.php`,
  `pv_point_lock_takeover_test.php`, `pv_point_readonly_messages_test.php`.
- `pv_editor_attendance_sync_test.php`, `pv_editor_manual_save_test.php`,
  `pv_editor_event_extension_sync_test.php`, `pv_review_controls_test.php`,
  `pv_replacement_editor_permissions_test.php`, `read_only_memoization_test.php`.
- Syntaxe PHP, UTF-8 sans BOM, fins de ligne LF et `git diff --check` verifies.

Les captures et le comparatif restent dans les fichiers locaux ignores
`tmp/pv-action-*` et `tmp/pv-http-*`.
