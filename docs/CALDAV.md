# CalDAV

## Lectures et frequence de synchronisation

Le cache de reponses couvre les routes `scoped/...` et
`calendars/{utilisateur}/organization-{organisation}/`, y compris leurs
evenements. Les droits sont controles avant chaque reutilisation. Une lecture
de calendrier charge uniquement l organisation demandee. Les modifications
invalident les reponses via les revisions deja utilisees par CalDAV.

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
respecter. Aucun blocage de frequence n est ajoute aux synchronisations.

Test local : `docker compose exec -u www-data -T app php tests/calendar_cache_http_test.php`.

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
Une limitation de frequence doit etre decidee apres cette collecte et testee
avec les clients concernes, sans bloquer aveuglement un cycle de synchronisation.

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
