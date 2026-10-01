# Optimisations 1, 2 et 3 - 2026-09-22

## Changements

1. La maintenance ne fait plus partie de la reponse PHP de `/omo/`. Le navigateur lance le secours deux secondes apres `load`, sans retenir la session PHP. Un verrou non bloquant par base protege les appels CLI, HTTP, navigateur et import. Le navigateur respecte un delai de 60 secondes apres une execution terminee. Le CLI existant reste le chemin recommande pour une planification reguliere ; aucun cron systeme n a ete installe. Voir `DEPLOY.md`, section 8, pour les droits du fichier de verrou et la limite multi-serveur.
2. Team charge les utilisateurs et affiliations manquants par lots dans `UserOrganization::loadTeamMemberContext()`, reutilise les objets deja charges et conserve les controles de visibilite. La collection des affiliations visibles hydrate directement ses objets. Les autres chargements propres aux droits et aux affectations ne sont pas tous regroupes par cette modification.
3. Fermeture explicite des sessions apres les ecritures indispensables sur Team, Calendrier, Documents, Projets, tableau de pilotage, panneau organisation, menu, notifications et maintenance. Les jetons CSRF et le cache de permissions sont enregistres avant fermeture. Le bootstrap commun et les endpoints d edition ne ferment pas globalement la session.

Aucun changement de schema SQL. Aucun changement CSS ou regroupement d ecrans dans ces trois optimisations.

## Conditions de comparaison

- Meme Docker, PHP 8.5.10, compte, organisation et holon racine que `PERFORMANCE-BASELINE-2026-09-22.md`.
- Meme seuil de journalisation SQL : 50 ms. Tous les appels SQL contribuent aux bilans, meme sous ce seuil.
- Base de revision : `3a6d3452dd778f5f343135a883780123b67d0dff`, avec les modifications locales de cette intervention, non commitees.
- Plage mesuree : 18:53:07 a 18:57:23, Europe/Zurich. Les essais de mise au point de 18:51 a 18:52 sont exclus.
- Caches non purges ; base non figee. Navigation de consultation, sans edition de contenu. Les maintenances habituelles peuvent modifier leurs donnees de travail.
- Volumes visibles conserves : 3 membres dont une invitation en attente, 37 documents descendants, 19 evenements, 3 projets, 4 decisions actives.
- Les filtres Documents ont ete remis sur Local et le navigateur sur le tableau de pilotage.

## Premiers passages

Temps en millisecondes issus des bilans PHP, pas du rendu final dans le navigateur. Ne pas additionner les temps des reponses paralleles pour en deduire un temps de chargement global.

| Parcours | SQL avant / apres | PHP avant / apres | SQL ms avant / apres | request_id apres |
| --- | ---: | ---: | ---: | --- |
| Page `/omo/` | 40 / 16 | 1128.224 / 842.402 | 27.732 / 12.396 | `1212bfa35d904a84` |
| Team / Local | 46 / 43 | 1148.565 / 863.885 | 30.619 / 23.507 | `400a6a4094e6e345` |
| Documents / Local | 130 / 129 | 1236.893 / 968.435 | 62.740 / 50.813 | `1840501e2dcbc243` |
| Documents / Descendants | 1384 / 1383 | 1912.511 / 1606.667 | 687.035 / 543.300 | `b36b791024213be9` |
| Panneau Structure | 12 / 12 | 902.954 / 871.134 | 10.880 / 7.729 | `e1d13962c2707256` |
| Calendrier / Local | 694 / 693 | 1293.822 / 1232.593 | 261.382 / 253.788 | `52856552193f788b` |
| Projets / Local | 43 / 42 | 987.065 / 1016.684 | 26.466 / 23.160 | `0e61d0921d522ff9` |
| Decisions / Descendants | 289 / 287 | 1116.066 / 1081.471 | 126.270 / 120.262 | `838027822b11698e` |

La baisse de 60 % des requetes de `/omo/` concerne uniquement cette reponse : la maintenance est deplacee, pas supprimee. Son premier appel differe compte 33 requetes (`48532e5bcd957cfb`). Son journal confirme une execution a 18:53:10, apres les premieres reponses du chargement, puis des appels ignores pendant le delai de protection. Aucun nouvel appel de source `omo_index` n est observe.

## Passages repetes

| Parcours / mode | SQL avant / apres | PHP avant / apres | SQL ms apres | request_id apres |
| --- | ---: | ---: | ---: | --- |
| Documents / Descendants, rechargement 2 | 1384 / 1383 | 697.872 / 686.906 | 513.674 | `209cd5036ba65cef` |
| Documents / Descendants, rechargement 3 | 1384 / 1383 | 936.997 / 703.830 | 512.142 | `0adec20ddc440106` |
| Team, ouverture 2 | 46 / 43 | 913.184 / 876.849 | 24.858 | `4746948fc8092dfe` |
| Team, rechargement complet 3 | 46 / 43 | 307.383 / 81.587 | 22.999 | `660646856e1fb785` |
| Calendrier, ouverture 2 | 694 / 693 | 922.227 / 907.779 | 257.943 | `c45a407b2070621b` |
| Calendrier, rechargement complet 3 | 694 / 693 | 462.997 / 472.133 | 266.602 | `580b15af6a384160` |

L ouverture 2 de Team provient ici de Decisions, contre Documents dans la reference : ne pas la traiter comme un essai strictement identique. Les rechargements complets utilisent les memes routes et filtres.

Documents conserve son double chargement Local puis Descendants : 129 + 1383 = 1512 requetes, contre 1514 avant. Les appels locaux intermediaires sont `7b04c72ea54d2f19` et `8e4dda48229f2b4b`. Ce comportement n est pas corrige dans ce lot.

## Interpretation et limites

- Gain structurel confirme sur le chemin critique de la page et sur les chargements des membres. Le petit jeu de 3 membres ne mesure pas le gain d une grande equipe.
- Le test isole du chargement des membres verifie exactement deux requetes pour 1, 3 et 100 membres, y compris la lecture des champs, puis aucune requete supplementaire si utilisateurs et affiliations sont reutilisables. Ce chiffre ne represente pas toutes les requetes de l ecran Team.
- Le test de session prouve la liberation immediate du verrou fichier et la persistance des jetons et du cache de permissions. Les differences de temps navigateur/PHP ne constituent pas, a elles seules, une mesure isolee du gain de concurrence.
- Calendrier reste globalement stable, et le premier passage Projets est legerement plus lent. Les temps varient avec les caches, les appels paralleles et la charge Docker : aucun gain global garanti n est deduit de quelques passages.
- Les volumes Documents et Calendrier restent les principaux sujets SQL a traiter separement. Les petites differences d une ou deux requetes sur les autres ecrans ne prouvent pas une optimisation de leurs boucles metier.

## Verification

- Syntaxe de tous les fichiers PHP modifies et nouveaux validee avec PHP 8.5 dans Docker ; `node --check omo/assets/js/app.js` et `git diff --check` passes.
- Tests ajoutes : `omo_maintenance_lock_test.php`, `readonly_session_test.php`, `team_member_batch_test.php`.
- Tests existants passes : `omo_cron_log_test.php`, `team_budget_application_visibility_test.php`, `user_holon_dashboard_layout_test.php`, `password_login_permission_test.php`.
- 60 bilans HTTP dans la plage finale, zero erreur SQL, zero requete individuelle atteignant 50 ms. Aucune erreur ou alerte console lors du controle navigateur. Aucun nouvel incident PHP dans le journal consulte.
- Les tests de verrou ne lancent aucune maintenance metier ; ils utilisent des callbacks et fichiers temporaires isoles. Pas de test exhaustif des editeurs, des imports ou des appels multi-serveur.

## Archive locale

- `tmp/sql-performance/after-optimizations-1-2-3-2026-09-22.jsonl` : 53 599 octets, SHA-256 `D8DEFB717AFD1073688CA1283140C9673C4A21F968B94B0A2A711B306BD8FC8D`.
- La copie contient 125 bilans, dont la reference initiale et les essais intermediaires : filtrer la plage finale ci-dessus pour les 60 bilans compares.
- L archive de reference est intacte : SHA-256 `3C0CB65276074EE644B26E7AAD6BC6D206A628E8C1E5F3E2FA5EF8ABF18251FE`.
- Les journaux bruts restent locaux et ignores par Git. La journalisation SQL du `.env` Docker reste active ; penser a archiver ou desactiver la collecte apres usage.
