# CalDAV

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
