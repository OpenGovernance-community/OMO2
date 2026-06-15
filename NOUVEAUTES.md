# Journal Des Nouveautes

Ce fichier garde une trace courte des evolutions fonctionnelles et techniques recentes du projet.

## 2026-06 Seed Docker Utilisateur

Le schema Docker de base inclut maintenant aussi les champs `image`, `presentation`, `latlong` et `birthdate` sur la table `user`, en coherence avec les migrations deja presentes. Cela evite les erreurs apres un reset complet de la base locale.

Le chargement commun des `dbObject` inclut egalement le helper partage des avatars, afin que les vues de structure reutilisent partout la meme logique sans erreur fatale.

## 2026-06 Avatars Sans Photo

Les avatars sans photo affichent maintenant jusqu a deux initiales et reutilisent une couleur de fond deterministe calculee a partir des initiales, de l identifiant et du libelle de la personne. La topbar OMO et le panneau `getOrg.php` restent ainsi visuellement coherents pour un meme utilisateur.

## 2026-06 Demandes d acces organisation

Quand une personne connectee essaie d ouvrir une organisation dont elle ne fait pas encore partie, elle peut maintenant envoyer une demande d acces avec un court message aux administrateurs. Ces demandes reutilisent la table `invitation` avec une origine explicite `member` ou `admin`, les administrateurs recoivent un lien de validation directe, puis la personne recue un mail de confirmation une fois ajoutee.

Si un administrateur ajoute une adresse deja en attente, l ajout est finalise directement sans nouveaux envois inutiles. Le popup de demande d acces est aussi autorise meme avant l obtention des droits sur l organisation cible.

## 2026-06 Structure OMO

Les actions `Ajouter` et `Modifier` du panneau de structure passent maintenant par un drawer global externe, superposable au contenu courant sans remplacer l application ouverte. Apres sauvegarde, la structure se recharge plus finement et recentre plus proprement le noeud concerne.

## 2026-06 Entetes OMO

Les entetes des applications `Decisions`, `Documents` et `Team` ont ete rapproches du modele du calendrier, avec une hierarchie plus nette entre titre, actions et changements de vue. Le chargeur AJAX OMO preserve aussi mieux certains scripts de donnees lors de l injection initiale, ce qui evite des pertes de rendu au premier chargement, notamment dans `Documents`.

## 2026-06 Decisions OMO

Le module `Decisions` a gagne un affichage plus souple, avec memorisation locale des modes `detail/compact` et `temporel/alphabetique`, filtres mieux places, et reutilisation plus fiable du drawer deja charge quand on revient sur la meme app. Le mode `Contextuel` est maintenant limite au holon courant, les etats vides sont masques, et plusieurs listes ou cartes utilisent des menus `...` pour alleger l interface.

Les resultats de `Vote simple` et de `Jugement majoritaire` proposent des tris `Classement`, `Ordre initial` et `Alphabetique`, avec une presentation plus homogene des barres et du classement. Dans `Jugement majoritaire`, la mention `Sans avis` sort du calcul principal tout en restant visible comme information complementaire.

La gestion des prises et des invitations a aussi ete raffinee: suppression reelle seulement quand aucun vote n existe encore, sinon archivage; sujet d envoi centralise; ciblage par defaut des participants n ayant pas encore repondu; et meilleure prise en charge des invitations liees au holon racine de l organisation.

Un script SQL de demo a enfin ete ajoute pour injecter rapidement un cas complet de test en `Jugement majoritaire`.

## 2026-06 Documents OMO

Le module `Documents` gere maintenant mieux les dossiers, la visibilite, les types de contenu et le stockage de fichiers, avec une interface plus lisible en modes `detail` et `compact`. L edition a ete enrichie avec verrou temporaire, brouillon live, partage public, insertion d autres documents et prise en charge HTML plus large.

Une liste compacte generique `generic-file-list` a aussi ete introduite pour mutualiser les rendus avec entetes sticky, navigation plus stable et menu d actions flottant. Le module integre egalement plusieurs aides IA OpenAI, masquees si la configuration ou les droits necessaires ne sont pas disponibles.

## 2026-06 Espace Personnel OMO

La page d accueil OMO peut charger a droite un espace personnel via `fetch`, avec un resume adapte aux applications actives de l organisation. Son rafraichissement evite les recalculs inutiles quand un drawer masque temporairement cette zone.

## 2026-06 Admin Local Et Environnement

Le panneau d administration serveur simplifie l edition des variables sensibles comme Patreon, OpenAI, SMTP, Telegram ou GitHub. En local, les changements passent par `docker/app/.env.private` plutot que par le fichier versionne principal, et le seed Docker prepare aussi plus facilement un compte admin local avec connexion Patreon simulee.

## 2026-06 FAQ OMO

La FAQ prend maintenant en charge les votes positifs et negatifs, avec blocage simple du revote sur une meme question pendant la journee. Le modele prepare aussi un score plus evolutif dans le temps, et l affichage peut reconstruire une note relative plus lisible a partir des compteurs enregistres.

## 2026-06 Recherche Topbar OMO

La recherche de topbar repose desormais sur des jobs asynchrones stockes en base, avec popup de relance et prise en charge etendue a `Decisions` et au `Calendrier`. Le schema SQL correspondant est aussi reporte dans le seed Docker local pour que les nouvelles bases disposent directement de cette infrastructure.

## 2026-06 CardDAV Et Authentification

Un endpoint CardDAV en lecture est disponible sous `/omo/api/carddav/` pour synchroniser les membres des organisations communes dans un carnet d adresses compatible. L authentification accepte mieux plusieurs formes d identite selon le contexte, tandis que la gestion du mot de passe utilisateur a ete etendue avec creation, modification, reinitialisation par e-mail et validation harmonisee entre les differents formulaires.

## 2026-06 Calendar Et CalDAV

L application calendrier OMO permet maintenant de creer et modifier des evenements simples relies a une organisation, avec plusieurs vues (`mois`, `semaine`, `jour`, `liste`), memorisation locale de la derniere vue choisie, et un en-tete plus coherent avec le reste d OMO. Les vues ont ete reprises pour mieux gerer hauteur, scroll, lisibilite et reutilisation locale des donnees deja chargees.

Un premier endpoint CalDAV en lecture seule a aussi ete ajoute sous `/omo/api/caldav/`, avec route `/.well-known/caldav`, afin d exposer un calendrier par organisation active. Cette base prepare la suite pour les invitations, les participants et, plus tard, des ecritures CalDAV ou des evenements plus riches.
