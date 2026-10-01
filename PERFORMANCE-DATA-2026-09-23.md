# Acces aux donnees - comparaison du 23 septembre 2026

## Resultat

| Reponse PHP | Contenu visible | Requetes avant | Requetes apres | Reduction |
| --- | --- | ---: | ---: | ---: |
| Documents / Descendants | 37 documents | 1383 | 180 | 87,0 % |
| Calendrier / Local | 23 evenements en septembre | 701 | 200 | 71,5 % |

Ces nombres concernent chaque reponse de liste, pas tous les appels d un chargement complet. Les memes volumes sont affiches avant et apres. Le calendrier du 23 septembre contient 23 evenements, contre 19 lors de la reference du 22 septembre : utiliser la nouvelle capture avant/apres pour comparer ce lot.

## Conditions

- Docker local, PHP 8.5, meme navigateur et session, organisation 1 et holon racine 674.
- Revision de depart : `a3a80de6f1e2ce2e938e6d3a6d993e363aa1c01c`, puis modifications locales de ce lot.
- Capture detaillee avec `DB_QUERY_LOG_MIN_MS=0`, uniquement le temps du diagnostic et des premieres mesures.
- Caches navigateur et OPcache non purges. Les maintenances habituelles continuent ; aucune edition de contenu declenchee dans le navigateur.
- Les tests d integration de lecture ne sauvegardent rien. Le test existant de disponibilite utilise des fixtures isolees dans une transaction annulee a la fin.

## Causes identifiees et changements

### Documents

La meme arborescence de permissions et les memes affiliations etaient reconstruites des dizaines de fois dans une reponse. La recherche du holon racine apparaissait 216 fois. Des regles de visibilite deja chargees en lot etaient aussi relues individuellement.

Les calculs de permissions sont maintenant reutilises dans la reponse, avec des cles distinctes pour la classe, la connexion/base, l utilisateur, l organisation, les permissions demandees et le mode administrateur. La racine structurelle, le caractere contextuel des permissions et la revision de l historique beneficient de la meme reutilisation. Les regles de visibilite chargees en lot alimentent les verifications individuelles, y compris lorsqu aucune regle explicite n existe.

### Calendrier

- Chargement complet des evenements en une collection, au lieu de completer chaque objet partiellement charge lors du rendu.
- Hydratation directe des evenements externes depuis les lignes de leur requete de collection.
- Les evenements externes virtuels sont construits comme objets de rendu charges : ils ne cherchent plus une ligne inexistante dans `event` et ne restent pas dans le cache des objets persistants.
- Reutilisation des calculs de permissions, comme pour Documents.

La capture initiale contenait 105 lectures individuelles de `event`, dont 90 sans resultat, ainsi que 90 lectures individuelles de `external_calendar_event`.

### Garde-fous

La memoisation est desactivee par defaut et activee explicitement dans les deux ecrans de consultation, apres la preparation des jetons de session. Ce n est ni un cache SQL general ni un cache persistant : seules les lectures explicitement selectionnees y participent. Les endpoints d edition conservent leur comportement normal.

Toute ecriture passant par DbObject, ou lecture verrouillante, vide et desactive la memoisation. Une erreur SQL pendant un calcul empeche de retenir son resultat. Les controles de visibilite et les regles d autorisation restent appliques ; aucune condition d acces n a ete supprimee.

Ne pas activer ce mode dans un traitement qui ecrit directement via PDO hors des chemins dbObject, ni dans un worker de longue duree sans delimitation explicite. Les donnees sont reutilisees pendant une seule reponse, pas actualisees entre chaque ligne du rendu.

## Mesures detaillees, seuil 0 ms

| Reponse | request_id | Requetes | PHP ms | SQL ms |
| --- | --- | ---: | ---: | ---: |
| Documents avant | `b8c07d0a3e82cdee` | 1383 | 15589.191 | 1110.292 |
| Documents apres | `16de58e8ff9d200f` | 180 | 2712.653 | 130.655 |
| Calendrier avant | `64476b2a04df6a3d` | 701 | 7974.999 | 534.366 |
| Calendrier apres | `440eac535dacf96c` | 200 | 2631.036 | 162.170 |

Le seuil 0 ecrit une ligne par requete sur le montage Docker/Windows et ralentit fortement les reponses. Ces temps servent au diagnostic ; ils ne representent pas un gain de vitesse garanti en production. Le nombre de requetes est le principal indicateur comparable.

## Controle final au seuil habituel de 50 ms

Le `.env` Docker a ete remis a sa configuration initiale : journalisation active, seuil 50 ms, destination `tmp/sql-performance/live.jsonl`.

| Reponse | request_id | Requetes | PHP ms | SQL ms |
| --- | --- | ---: | ---: | ---: |
| Calendrier, rechargement | `9f7c11e5b899c3b7` | 200 | 337.795 | 114.099 |
| Calendrier, seconde reponse du rechargement | `b8b4210dce32ff40` | 200 | 147.974 | 76.020 |
| Documents / Local intermediaire | `b47fa0cee74384fb` | 128 | 976.621 | 53.204 |
| Documents / Descendants | `7b3e493c96486f71` | 180 | 178.005 | 75.163 |

Zero erreur SQL dans ces reponses et aucune erreur console lors du controle final des listes. Documents a ete remis sur Local, puis le navigateur sur le tableau de pilotage. Le lecteur video Vimeo presentait deja une erreur reseau avant le travail ; il ne fait pas partie de cette optimisation.

## Tests

20 tests PHP passes sous PHP 8.5, dont :

- `read_only_memoization_test.php` : activation explicite, resultats refuses/absents, isolation des cles, invalidation par le chemin reel de sauvegarde dbObject, reutilisation des regles chargees en lot et absence de lecture SQL des apercus virtuels.
- `data_access_readonly_integration_test.php 1` : egalite des permissions, actions sur les documents et visibilite des evenements avec/sans memoisation, dans sept scenarios utilisateur/mode administrateur ; verification d une autre portee d organisation invalide.
- `arraydbobject_hydration_test.php` : hydratation des collections. Les assertions textuelles ont ete actualisees pour le chargement complet du calendrier et deux attentes deja depassees par le code existant (quatre collections de documents et controle specifique de suppression).
- Tests existants des permissions CRUD, activites, processus, budget, PV, visibilite des documents, sessions, Team, disponibilite du calendrier, documents lies aux evenements, statuts, modeles, partage, ICS et decouverte des calendriers externes.

Syntaxe de tous les fichiers PHP modifies/nouveaux verifiee, `git diff --check` passe, fichiers UTF-8 sans BOM et fins de ligne LF. Aucune migration SQL requise.

Les sept scenarios ne constituent pas une preuve exhaustive de toutes les combinaisons de droits ou de partage public. Aucun parcours d enregistrement utilisateur n a ete declenche dans le navigateur.

## Archives locales

Les fichiers restent dans `tmp/sql-performance/`, ignore par Git et non accessible par HTTP :

- `data-before-2026-09-23.jsonl`, SHA-256 `24DC72C12368D24378BA836A027BB301C194FBF0C0140F2492572FB55D581F0A`.
- `data-after-2026-09-23.jsonl`, SHA-256 `7AF2BCC994ED2A8CBE5CC9A3906C8F344EC108CFF6A470656BE1572E8D7C44C1`.
- `data-final-2026-09-23.jsonl`, SHA-256 `7B5D83A86DE80DC1EE4542A2FD3BC1C403661E3865BF8601670FFEC44A49D016`.

La capture apres contient aussi les iterations intermediaires et certains tests CLI ; la copie finale reprend l historique du journal live. Filtrer par `request_id` ci-dessus pour reproduire la comparaison.

## Suite possible

- Doubles chargements de restauration traites dans le complement ci-dessous pour Documents et Calendrier.
- Regrouper le parcours de l arborescence visible, qui conserve de nombreuses petites requetes sur les enfants des holons.
- Mesurer sur une organisation plus volumineuse et avec plusieurs sessions simultanees avant de generaliser la memoisation a d autres ecrans.

## Complement : restauration directe des vues

Le navigateur transmet maintenant ses preferences de presentation avec la premiere requete de Documents ou Calendrier. Le serveur garde la resolution de la priorite des vues et les controles d acces. Aucun nouveau stockage, cache de donnees ou changement SQL. Les URL explicites et les onglets PV restent sur leur chemin existant.

| Scenario | Avant | Apres | Verification |
| --- | --- | --- | --- |
| Documents / Descendants apres rechargement | 2 reponses, 128 + 180 = 308 requetes SQL | 1 reponse, 179 requetes SQL | 37 documents, 30 fiches initiales |
| Calendrier / mois courant apres rechargement | 2 reponses de 200 requetes dans la capture precedente | 1 reponse, 200 requetes SQL | Septembre 2026, 23 evenements |
| Calendrier / position memorisee en aout | Non remesure avant ce lot | 1 reponse, 208 requetes SQL | Aout restaure directement |

Documents avant : `9ba945b1062116ec` et `0a4fc8b998778625` (11:03:45). Apres : `45ebd5b44195267c` (11:08:31). Calendrier courant apres : `aa674540db23eb45` (11:10:14). Position aout : `a6cb5a05026f1404` (11:06:25). Horaires locaux Europe/Zurich, 23 septembre 2026 ; traces dans `tmp/sql-performance/live.jsonl`, seuil SQL conserve a 50 ms. La baisse Documents est de 41,9 % pour la restauration complete, sans promettre un gain temporel equivalent.

Tests ajoutes : `application_view_restore_test.js` et `.php` (contexte, priorites, champs limites, liens directs, exclusion PV, stockage indisponible). Tests du rendu progressif, des routes Documents et Calendrier, des vues de tableau de pilotage et des sessions passes. Le test existant `pv_application_tabs_test.php` echoue sur une assertion textuelle `hideHeader: true` absente du calendrier egalement dans HEAD ; ce point preexistant n a pas ete modifie.

## Complement : interfaces du calendrier a la demande

- Les quatre presentations et les trois portees ne produisent plus douze panneaux HTML a chaque reponse. PHP transmet leurs donnees autorisees, avec deduplication des objets identiques ; le navigateur construit une seule presentation au depart.
- Verification navigateur : 1 panneau au depart, puis 2 apres ouverture de Semaine, 3 apres Jour et 4 apres Liste/Descendants. Une nouvelle periode repart sur 1 panneau. La recherche, le detail d evenement et son rechargement par route ont ete verifies sans edition de donnees.
- Le fichier `omo/api/calendar/calendar.js` est charge par le chargeur partage et initialise apres son chargement. Le parametre `v` est derive de SHA-256 du fichier : toute modification renouvelle son URL, sans compteur de version a maintenir. Les traductions et les donnees restent dans le JSON propre a chaque instance.
- Les donnees des vues sont toujours calculees cote serveur ; ce lot reduit surtout le HTML repete, les interfaces cachees et le JavaScript retransmis. Il ne revendique pas de reduction du nombre de requetes SQL.
- `calendar_lazy_views_test.js` couvre les quatre rendus, leur reutilisation, les actions documentaires, les attributs d evenements externes et l echappement. `calendar_availability_test.php --render` verifie le JSON reel et l absence de fuite des details d autres organisations. Les tests textuels concernes par l extraction pointent maintenant vers le fichier JavaScript.
- Le test transversal `pv_application_subdrawers_test.js` passe les assertions Calendrier puis echoue sur une ancienne attente de classe CSS du panneau Projets, dont les fichiers sont inchanges. Ce point et le test d en-tete PV deja signale restent hors de ce lot.
