# CalDAV

## Lectures et frequence de synchronisation

Le cache de reponses couvre les routes `scoped/...` et
`calendars/{utilisateur}/organization-{organisation}/`, y compris leurs
evenements. Les droits sont controles avant chaque reutilisation. Une lecture
de calendrier charge uniquement l organisation demandee. Les modifications
invalident les reponses via les revisions deja utilisees par CalDAV.

La decouverte `PROPFIND /calendars/{utilisateur}/` utilise aussi le cache.
La liste des adhesions actives et des applications autorisees est rechargee
avant chaque lecture du cache, sans charger les evenements. La cle inclut
l utilisateur, Depth, le XML demande et les revisions de toutes les organisations
visibles ; une modification dans n importe laquelle invalide la liste.
Cette requete multi-organisation reste classee sans organisation dans les statistiques.
Les traces donnent `home_revisions` pour suivre les organisations impliquees.
Test HTTP local avec 12 evenements : 70 requetes SQL a froid, 7 sur un HIT.
Les routes principal restent dynamiques (3 requetes SQL dans la trace analysee).

Les abonnements ICS lies a une organisation utilisent le meme stockage de
cache : un ETag inchange peut produire une reponse 304 sans reconstruire les
evenements. Le jeton de partage, son expiration et son proprietaire restent
verifies a chaque appel. Les flux personnels regroupant plusieurs organisations
et des calendriers externes conservent leur construction dynamique.

Les entetes `X-OMO-CalDAV-Cache` et `X-OMO-ICS-Cache` indiquent `MISS`, `HIT`
ou `BYPASS`. Le repertoire `CALDAV_CACHE_PATH` (ou son emplacement temporaire
par defaut) doit etre accessible en ecriture au processus PHP. Le cache reduit
le cout des appels, pas leur nombre. Les nouvelles statistiques rattachent les
lectures d une organisation validee a celle-ci ; l historique reste inchange.

Pour reduire les appels, regler le compte CalDAV sur une actualisation toutes
les 15 ou 30 minutes et les abonnements ICS sur une heure, selon le delai
acceptable. Verifier aussi les abonnements doubles ICS/CalDAV et les appareils
qui synchronisent le meme compte. Sur Mac : Calendrier > Reglages > Comptes
pour CalDAV ; Informations du calendrier > Actualisation automatique pour ICS
([documentation Apple](https://support.apple.com/en-mt/guide/calendar/icl1024/mac)).
Les flux ICS annoncent deja un intervalle d une heure ; le client decide de le
respecter. La limitation des lectures de decouverte CalDAV est decrite ci-dessous.

Test local : `docker compose exec -u www-data -T app php tests/calendar_cache_http_test.php`.

Diagnostic du 13 octobre 2026, 07:08:02-07:59:04 Europe/Zurich : 483 appels
du compte 18 avec la signature `macOS/26.4 (25E246) dataaccessd/1.0`.
44 lectures de la liste des calendriers consommaient chacune 1457 requetes SQL
et 967 ms en moyenne, hors du cache avant le correctif de decouverte.
Les 439 autres appels au principal coutaient chacun 3 requetes SQL ;
l authentification representait 95,3 s sur 143,4 s de duree cumulee.
Aucun PROPPATCH dans cette plage. La signature ne permet pas de compter les
appareils physiques ; la cause exacte de la frequence reste a verifier cote client.

Le point d entree est `/omo/api/caldav/`. L authentification et les droits de
consultation habituels restent requis pour acceder a chaque calendrier.

## Preferences personnelles avec PROPPATCH

Les ressources calendrier acceptent `PROPPATCH` pour ces proprietes :

| Propriete | Valeur acceptee |
| --- | --- |
| `{DAV:}displayname` | Texte simple, 255 caracteres maximum |
| `{http://apple.com/ns/ical/}calendar-color` | `#RRGGBB` ou `#RRGGBBAA` |
| `{http://apple.com/ns/ical/}calendar-order` | Entier signe sur 32 bits |

Ces preferences sont propres au membre, a l organisation et au calendrier.
Elles sont stockees dans `user_organization.parameters.caldavCalendars`, sans
modifier le nom de l organisation ni les evenements. Les calendriers dont les
evenements sont en lecture seule acceptent aussi ces preferences personnelles.

Les instructions `set` et `remove` sont appliquees dans l ordre du document.
Retirer une preference retablit la valeur generee du nom ou de la couleur ;
l ordre devient absent. Une reponse `207 Multi-Status` donne le resultat par
propriete : `200` en cas de succes, `403` pour une propriete refusee, `409` pour
une valeur invalide et `424` pour les autres proprietes si la demande echoue.
Les proprietes techniques comme `sync-token` restent protegees. Les corps XML
invalides ou contenant une DTD sont refuses avec `400`.

Aucune modification partielle n est conservee. Le merge des parametres utilise
un verrou de ligne pour preserver les autres preferences lors de deux
`PROPPATCH` concurrents. Une modification effective invalide le cache de
reponses de l organisation ; renvoyer les memes valeurs conserve ce cache.
Les jetons de synchronisation des evenements restent inchanges.

Cette gestion suit les instructions atomiques de la
[RFC 4918, section 9.2](https://www.rfc-editor.org/rfc/rfc4918.html#section-9.2).

## Publication et verification

Les PROPPATCH sur les calendriers par organisation et les groupes scoped ne
chargent plus les evenements ni leur contenu ICS. Les controles de membre actif,
application et perimetre restent executes. Test HTTP PHP 8.5 : 9 requetes SQL
pour un calendrier par organisation, 17 pour un groupe scoped, aussi bien avec
0 qu avec 12 evenements. Ces nombres locaux ne prejugent pas du temps en production.

### Diagnostic temporaire des clients

Apres publication, activer dans le `.env` de production :

```dotenv
CALDAV_DIAGNOSTICS_ENABLED=true
```

Laisser passer 15 a 30 minutes avec les clients habituels puis remettre `false`.
Extraire les lignes contenant `CALDAV_DIAGNOSTIC` du fichier `php-errors.log`
dans `RUNTIME_LOG_DIR`, ou par defaut dans le dossier `log` voisin du repertoire
du site. Pas besoin d activer le journal de toutes les requetes SQL.
Les lignes JSON donnent l heure, le compte, l organisation, le User-Agent, la
route sans query string, Depth, le statut HTTP, le cache (HIT, MISS, BYPASS ou
NOT_ELIGIBLE), la revision, les temps total/authentification/resolution et les
compteurs SQL. Sur un HIT, la resolution est sautee et son temps est absent.
PROPFIND ajoute les noms des proprietes demandees ; PROPPATCH ajoute les noms
et statuts par propriete ainsi que preferences_changed pour les sauvegardes.
Une empreinte SHA-256 du XML permet de reperer les requetes identiques sans
enregistrer leurs valeurs. Les mots de passe, Authorization, cookies, valeurs
des preferences et contenu des evenements ne sont pas collectes.

Le User-Agent identifie le logiciel declare, pas un appareil unique ; plusieurs
appareils peuvent utiliser le meme compte et la meme signature. Comparer des
plages horaires apres publication, sans effacer l historique du tableau de bord.
Un 207 peut contenir des 403/409/424 ; zero erreur serveur ne suffit donc pas.
La colonne PROPPATC vient de la limite actuelle de huit caracteres des methodes
dans les statistiques, sans modification de la methode HTTP recue.

Analyser d abord les PROPPATCH identiques repetes et leurs refus, puis le taux
de HIT des PROPFIND et les revisions qui changent. Un NOT_ELIGIBLE sur PROPPATCH
est normal : une ecriture ne reutilise pas une reponse de lecture en cache.
L authentification reste executee meme sur un HIT ; auth_ms mesure ce cout.
La reaction des clients aux refus de frequence doit etre suivie dans ces traces.

### Limitation des lectures de decouverte

La limitation est active par defaut avec ces valeurs :

```dotenv
CALDAV_READ_LIMIT_ENABLED=true
CALDAV_READ_LIMIT_SECONDS=900
CALDAV_READ_LIMIT_BURST_SECONDS=30
```

Elle concerne uniquement OPTIONS et PROPFIND avec une authentification Basic.
La premiere requete passe l authentification et les controles habituels.
Seule une reponse HTTP 2xx sans erreur fatale cree un enregistrement de limitation.
Les repetitions restent autorisees pendant 30 secondes pour laisser un cycle
de synchronisation se terminer. Ensuite, la meme requete recoit HTTP 429
Too Many Requests et Retry-After, jusqu a 900 secondes apres le premier succes.
Les repetitions autorisees ou refusees ne repoussent jamais cette echeance.

La cle est une empreinte HMAC avec le secret existant AUTH_RATE_LIMIT_SECRET
(ou son repli existant), couvrant les identifiants Basic exacts, l hote, la
methode, l URI, Depth, le contenu XML et la politique. Aucun identifiant Basic
ni contenu XML n est conserve dans les fichiers. Le numero utilisateur dans
l URL ne permet pas de creer un blocage pour un autre compte. Un changement
de mot de passe presente produit une nouvelle cle et repasse par l authentification.
Les appareils partageant les memes identifiants et la meme requete partagent
la limite ; le User-Agent et l IP ne constituent pas une identite fiable.

Le refus intervient avant la verification du mot de passe et n autorise aucun
acces aux donnees. Il ne renvoie jamais de calendrier en cache. Une lecture
limitee reste refusee meme si les identifiants ont ete revoques depuis le
premier succes ; a expiration, l authentification normale les rejettera.
Les lectures sans Basic, les corps de plus de 128 Kio, GET, HEAD, REPORT et
toutes les ecritures (dont PROPPATCH) gardent leur traitement habituel.
Cela limite les repetitions observees, sans constituer une protection generale
contre des requetes volontairement differentes ou des tentatives de connexion.

Les PROPFIND de calendriers et de listes deja couverts par le cache de reponses
memorisent aussi leurs revisions d organisation : un changement de revision
reouvre la lecture concernee. Les requetes de principal et OPTIONS restent
soumises a leur delai ; la reaction du logiciel client au 429 reste a verifier.
La limitation peut donc retarder la prochaine synchronisation du client.

Stockage prive dans le sous-dossier read-limits de CALDAV_CACHE_PATH, sans SQL.
Les verrous locaux protegent les acces concurrents ; un stockage indisponible,
occupe ou invalide laisse passer vers l authentification normale. Le cron
de nettoyage CalDAV supprime les vieux enregistrements expires. Sur plusieurs
serveurs PHP, un stockage partage avec verrous fiables est necessaire pour
appliquer une limite commune. Le refus economise l authentification et le
traitement CalDAV, mais PHP et son bootstrap restent executes.

Les 429 comptent dans les erreurs HTTP 4xx du suivi CalDAV, avec le compte
precedemment verifie ; cela ne cree aucune session authentifiee. La trace
temporaire indique rate_limit=blocked, auth_ms=0 et retry_after.
X-OMO-CalDAV-Rate-Limit: blocked identifie aussi ces reponses HTTP.
Pour desactiver immediatement la limitation : CALDAV_READ_LIMIT_ENABLED=false.
Modifier le delai ou la fenetre initiale cree une nouvelle cle de politique.

Test : `docker compose exec -u www-data -T app php tests/caldav_rate_limit_test.php`.
Mesure locale : lecture repetee authentifiee 225 ms, refus anticipe 30 ms,
zero requete SQL sur le refus (hors ecriture de la mesure de performance).

### Deploiement

Publier les fichiers PHP modifies suffit : aucune migration SQL ni regle Apache
supplementaire n est necessaire. Le client peut ensuite relancer sa
synchronisation. `OPTIONS` annonce `PROPPATCH` sur les calendriers.

Un acces log ne donne que le statut HTTP global : `207` peut contenir un refus
par propriete. Examiner le corps XML si un client signale encore une erreur.
Les autres proprietes propres a un client restent refusees ; leur prise en
charge demande de connaitre leur nom XML et leur valeur.

Test HTTP avec les fixtures Docker locales, sous le meme utilisateur que le
serveur web pour les droits du cache temporaire :

```sh
docker compose exec -u www-data -T app php tests/caldav_proppatch_test.php
```
