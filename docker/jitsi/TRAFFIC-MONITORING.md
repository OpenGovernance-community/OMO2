# Prototype de suivi du trafic Jitsi

## Mesure

Le collecteur lit les compteurs cumules PacketStreamStats des endpoints JVB, toutes les 30 secondes. Le sens est celui du serveur : reception depuis les participants, emission vers les participants. Les branches RTP/RTCP du sender partagent leur compteur : elles ne sont pas additionnees.

Les differences positives sont conservees dans video_traffic. Les releves repetes et baisses de compteur ne sont pas recomptes. La cle comprend le bridge, la conference, sa creation et l endpoint. Le createur et l organisation selectionnee sont copies depuis video_room a chaque releve ; les historiques survivent a la suppression de la salle. Aucun contenu audio/video, JWT, adresse IP ou nom de participant n est stocke.

La page /common/admin/stats.php, reservee aux super administrateurs, propose le regroupement par organisation ou personne. Elle utilise la periode et le filtre organisation existants et trie les volumes sortants decroissants. Les salles sans organisation restent dans une ligne distincte. L heure du dernier succes, les echecs et les salles non retrouvees sont affiches.

## Limites du prototype

Ce sont des minima observes, pas des totaux reseau exhaustifs ni une base de facturation : derniers paquets avant la disparition d un endpoint et connexions entieres entre deux releves peuvent manquer. Une interruption ou un redemarrage du bridge peut perdre des compteurs. Les premiers compteurs d une connexion deja ouverte sont imputes au moment du premier releve. Le P2P, TURN, la signalisation et les en-tetes IP/UDP ne sont pas compris. Pas de repartition de CPU par salle. Les anciens historiques ne peuvent pas etre reconstruits.

L API /debug de JVB est experimentale et destinee au diagnostic ; son interrogation peut avoir un impact sur les grandes conferences. Cette integration reste un prototype local, a valider en charge avant deploiement. Elle ne publie aucun port HTTP du bridge sur l hote et le collecteur ne fait que des GET. Ne jamais exposer /debug au public. Reference : https://github.com/jitsi/jitsi-videobridge/blob/master/jvb/src/main/java/org/jitsi/videobridge/rest/root/debug/Debug.java

## Local

Appliquer les migrations puis lancer le fichier additionnel :

```sh
docker compose exec app php scripts/run-migrations.php
docker compose -f compose.yaml -f docker/jitsi/compose.monitoring.yaml up -d jitsi-jvb visio-traffic
```

Le collecteur utilise l image locale openmyorganization-app et les identifiants de base deja configures. Il tourne independamment des visites du site. Logs : docker logs openmyorganization-visio-traffic-1. Le status en base est mis a jour meme quand aucune salle n est active.

Pour arreter le prototype : docker compose -f compose.yaml -f docker/jitsi/compose.monitoring.yaml stop visio-traffic, puis docker compose up -d --no-deps --force-recreate jitsi-jvb. Les mesures restent en base.

## Serveur distant

Non active automatiquement sur le VPS. Installer un collecteur CLI proche du JVB avec un acces reseau prive, configurer VISIO_TRAFFIC_URL et lancer php scripts/collect-visio-traffic.php --watch sous supervision, ou sans --watch toutes les minutes. Utiliser un seul bridge pour ce prototype. Ne pas reutiliser le fichier compose local sur le VPS.
