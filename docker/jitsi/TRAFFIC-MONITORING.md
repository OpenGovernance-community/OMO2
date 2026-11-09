# Prototype de suivi du trafic Jitsi

## Mesure

Le collecteur lit les compteurs cumules PacketStreamStats des endpoints JVB, toutes les 30 secondes. Le sens est celui du serveur : reception depuis les participants, emission vers les participants. Les branches RTP/RTCP du sender partagent leur compteur : elles ne sont pas additionnees.

Les differences positives sont conservees dans video_traffic. Les releves repetes et baisses de compteur ne sont pas recomptes. La cle comprend le bridge, la conference, sa creation et l endpoint. Le createur et l organisation selectionnee sont copies depuis video_room a chaque releve ; les historiques survivent a la suppression de la salle. Aucun contenu audio/video, JWT, adresse IP ou nom de participant n est stocke.

La page /common/admin/stats.php, reservee aux super administrateurs, propose le regroupement par organisation ou personne. Elle utilise la periode et le filtre organisation existants et trie les volumes sortants decroissants. Les salles sans organisation restent dans une ligne distincte. L heure du dernier succes, les echecs et les salles non retrouvees sont affiches.

## Limites du prototype

Ce sont des minima observes, pas des totaux reseau exhaustifs ni une base de facturation : derniers paquets avant la disparition d un endpoint et connexions entieres entre deux releves peuvent manquer. Une interruption ou un redemarrage du bridge peut perdre des compteurs. Les premiers compteurs d une connexion deja ouverte sont imputes au moment du premier releve. Le P2P, TURN, la signalisation et les en-tetes IP/UDP ne sont pas compris. Pas de repartition de CPU par salle. Les anciens historiques ne peuvent pas etre reconstruits.

L API /debug de JVB est experimentale et destinee au diagnostic ; son interrogation peut avoir un impact sur les grandes conferences. Cette integration reste un prototype, a valider en charge. Elle ne publie aucun port HTTP du bridge sur l hote et le collecteur ne fait que des GET vers JVB. Ne jamais exposer /debug au public. Reference : https://github.com/jitsi/jitsi-videobridge/blob/master/jvb/src/main/java/org/jitsi/videobridge/rest/root/debug/Debug.java

## Local

Appliquer les migrations puis lancer le fichier additionnel :

```sh
docker compose exec app php scripts/run-migrations.php
docker compose -f compose.yaml -f docker/jitsi/compose.monitoring.yaml up -d jitsi-jvb visio-traffic
```

Le collecteur utilise l image locale openmyorganization-app et les identifiants de base deja configures. Il tourne independamment des visites du site. Logs : docker logs openmyorganization-visio-traffic-1. Le status en base est mis a jour meme quand aucune salle n est active.

Pour arreter le prototype : docker compose -f compose.yaml -f docker/jitsi/compose.monitoring.yaml stop visio-traffic, puis docker compose up -d --no-deps --force-recreate jitsi-jvb. Les mesures restent en base.

## Serveur distant

Depuis le 8 novembre 2026, le VPS utilise un collecteur Python dans le service
Compose `visio-traffic`, avec redemarrage automatique, toutes les 30 secondes.
Il rejoint uniquement le reseau prive Jitsi et lit `http://jvb:8080/debug?full=true`.
Les quatre conteneurs Jitsi ne sont pas recrees lors de son installation.

Le collecteur envoie seulement les identifiants haches des compteurs, les codes
opaques de salles et les totaux recus/envoyes a
`https://opengov.tools/visio/traffic.php`, par POST HTTPS signe. Il n envoie pas
le JSON de diagnostic complet, les noms, les adresses des participants ni les
medias. Aucun identifiant de base de donnees n est copie sur le VPS.

`beta.opengov.tools`, `opengov.tools` et `omo2.org` utilisent la meme base :
**un seul collecteur et une seule destination** suffisent. Chaque domaine voit
les memes mesures dans les statistiques. Ne pas lancer un collecteur par domaine.

### Fichiers et publication OMO

Publier `visio/traffic.php`, `common/visio_traffic.php`, les classes
`VideoTraffic` et `VideoTrafficCollector`, ainsi que la page de statistiques.
Les tables viennent de la migration existante `2026-11-07-01-video-traffic.sql` ;
aucun changement de schema supplementaire n est necessaire.

Le recepteur est independant des sessions navigateur et n accepte que POST.
La signature couvre le corps JSON et un horodatage, avec une tolerance de
deux minutes. La cle de collecte est derivee du secret JWT commun par
`HMAC-SHA256("omo-visio-traffic-v1", secret JWT)` ; cette cle derivee ne permet
pas de fabriquer un jeton de reunion. Le secret derive reste dans
`/opt/jitsi/omo-integration/traffic/collector.env`, prive et hors Git.
Apres rotation du secret JWT, regenerer cette cle puis recreer le collecteur.

Le recepteur valide les codes et compteurs et utilise les methodes dbObject
existantes. Les retransmissions ne recomptent pas les octets deja enregistres.
Une erreur de collecte JVB est signalee au recepteur pour mettre a jour
`last_attempt` et `last_error`, meme sans nouveau trafic.

### Exploitation du VPS

Sources : `docker/jitsi-vps/traffic/collector.py`,
`docker/jitsi-vps/compose.traffic.yaml` et `docker/jitsi-vps/compose.sh`.
Sur le VPS, le script est dans `omo-integration/traffic/`, le fichier Compose
additionnel dans `omo-integration/compose.traffic.yaml`. Le wrapper
`/opt/jitsi/compose.sh` charge cet overlay lorsqu il existe et conserve
les fichiers prives et le profil JWT habituels.

```sh
sudo /opt/jitsi/compose.sh ps visio-traffic
sudo /opt/jitsi/compose.sh logs --tail 30 visio-traffic
sudo /opt/jitsi/compose.sh up -d --no-deps visio-traffic
```

Le service n expose aucun port et utilise une image Python sans bibliotheque
additionnelle, avec limites de memoire et de processus. Son systeme de fichiers
est en lecture seule, sauf le dossier prive `traffic/state/`, appartenant a
l utilisateur 65534. Les compteurs non livres sont conserves dans
`state/pending.json`, y compris si les participants quittent la salle pendant
une indisponibilite de la destination. La file est limitee a 10 000 compteurs ;
un depassement provoque une erreur visible dans les logs, sans effacer la file.
Le corps JVB est limite a 16 Mio ; les livraisons regroupent au plus 1000
compteurs, et le recepteur refuse les corps de plus de 1 Mio.

Sauvegarde de l installation du 8 novembre :
`/opt/jitsi/backups/20261108T082508Z-traffic/`.
Pour arreter la collecte, utiliser `sudo /opt/jitsi/compose.sh stop visio-traffic` ;
conserver le dossier `state/` et les mesures de la base.

### Diagnostic

- `Traffic delivery failed: HTTP 404` : le point de reception OMO n est pas encore publie.
- HTTP 403 : configuration Jitsi desactivee, cle JWT differente, rotation non reportee ou horloges decalees de plus de deux minutes.
- HTTP 422 : format incompatible ; mettre a jour les sources du collecteur et du recepteur ensemble.
- HTTP 503 : verifier la configuration et les migrations de la base ainsi que le journal PHP de l instance OMO.
- `JVB collection failed` : verifier le JVB dans son reseau prive ; ne pas ouvrir son API au public.
- `status: ok` avec zero endpoint : le releve a reussi, mais aucune conference ne passe actuellement par le pont.

Apres publication du recepteur, le message Aucun releve recu doit laisser place
a une date de succes sous 30 secondes environ, meme sans appel. Pour verifier
les volumes, utiliser au moins trois participants : un appel a deux peut rester
en P2P et ne produit alors aucun trafic media mesure par le JVB. Les volumes
anterieurs a l activation du collecteur ne peuvent pas etre reconstitues.
