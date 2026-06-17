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

Le popup `Configurer` du `Jugement majoritaire` permet maintenant de regler l echelle directement dans `parameters`: pour chacun des 7 crans, on peut definir un libelle et l activer ou non. Le vote, la sauvegarde et les resultats reutilisent ensuite automatiquement ces mentions actives, y compris pour masquer le centre ou reduire l echelle a 4, 5 ou 6 choix.

La gestion des invitations a ete reequilibree dans les formulaires de scrutin: le bloc se trouve maintenant en fin de formulaire, au niveau du scrutin complet. Tant qu aucune invitation explicite n existe encore, il apparait directement en mode inline avec onglets `Holons`, `Membres` et `Invites`, puis ces donnees sont enregistrees en meme temps que le scrutin. Des qu une premiere invitation explicite existe, l interface repasse ensuite sur le resume avec ouverture par popup.

Dans le detail public embarque d une prise de decision, l entete visuelle gagne aussi en compacite: le bloc du haut garde le titre, le descriptif est affiche une seule fois juste en dessous, et ce second bloc n utilise plus de sticky inutile. Les blocs de `Jugement majoritaire` affichent aussi maintenant une legende coloree sticky reprenant les mentions actives configurees par l utilisateur, placee sous la description de la question.

Le panneau `Invites` peut maintenant ouvrir la participation sans invitation: via le lien public, une adresse e-mail non encore utilisee peut d abord demander un code de verification, puis seulement saisir ce code pour acceder au scrutin. Cela cree automatiquement un participant sans invitation prealable, et les participants ainsi ajoutes sont conserves lors des resynchronisations d invitations. Le masquage progressif des blocs de ce formulaire respecte aussi maintenant correctement les elements `hidden`, sans etre force par les `display` CSS locaux. Le lien personnel par token est desormais persiste avant redirection, ce qui evite les ouvertures publiques partielles ou incoherentes juste apres validation du code. Le mail d acces offre maintenant les deux usages: copier le code ou cliquer directement sur un lien personnel avec token.

Dans la liste `Decisions`, le clic direct sur une ligne et l ouverture via le menu `...` reutilisent maintenant le meme entete de drawer pour le detail: seul le titre remonte en en-tete, sans rebrancher le descriptif par erreur.

Le compteur affiche dans l entete de la liste `Decisions` ne montre maintenant plus que le nombre brut, tout en continuant a se mettre a jour apres filtrage pour rester coherent avec les autres pages.

La sauvegarde des scrutins avec edition inline des invitations accepte maintenant correctement les e-mails saisis dans le formulaire principal. Cela corrige un plantage PHP silencieux qui renvoyait un message generique d echec sans erreur SQL.

L acces public par code d une prise de decision retrouve maintenant mieux le bon participant quand plusieurs entrees partagent la meme adresse e-mail. Cela corrige un cas ou un code bien envoye etait ensuite refuse a tort avec le message indiquant qu aucun code valide n avait ete trouve.

La synchronisation automatique des participants conserve maintenant le code d acces public temporaire deja emis dans les `parameters` du participant. Cela evite qu un code envoye par e-mail disparaisse juste avant sa verification.

Le bloc d informations contextuelles dans la page publique de participation garde maintenant son comportement d accordeon aussi sur desktop et en mode embarque, au lieu d etre force ouvert en permanence dans le panneau de contexte.

Les liens publics des scrutins utilisent maintenant des URLs plus lisibles via `.htaccess`, autant pour le lien generique du scrutin que pour les acces personnels par token. Les anciennes URLs en query string restent toutefois compatibles.

Les pages publiques OMO se rapprochent aussi visuellement: la participation a un scrutin reprend maintenant la topbar, l aide OMO partagee et le fond de la page de partage de structure. Elle reutilise aussi le meme decoupage en deux panneaux redimensionnables que la vue partagee de structure, avec le contexte du scrutin a gauche et la participation a droite, y compris une navigation mobile simple entre `Infos` et `Scrutin`. La vue partagee de structure dispose elle aussi maintenant de sa barre mobile de bascule en bas de page entre `Infos` et `Structure`. La navigation interne de la structure partagee conserve aussi de nouveau son zoom/focus fluide entre cercles et roles, sans rechargement inutile du panneau droit a chaque changement de `cid`. Le bandeau d information public a aussi ete affine pour etre plus fin, sans bordure, et mieux detache du fond clair comme du fond sombre. Dans la vue partagee de structure comme dans la page publique d un scrutin, il peut maintenant aussi s afficher bord a bord, sans marge ni angle arrondi, juste au-dessus des deux panneaux. Le menu `Aide` propose desormais trois entrees distinctes sur ces pages: presentation d OMO, explication du role de la page courante, et ouverture de la politique de confidentialite existante dans une popup. Sur mobile, la hauteur de ces pages a aussi ete recalee pour supprimer l espace vide qui pouvait apparaitre sous la barre de navigation basse. Le lien `Contacter l organisateur` du scrutin public est maintenant rendu a la fin du contenu principal, pour rester en bas apres les resultats plutot que dans le panneau de contexte.

La gestion des prises et des invitations a aussi ete raffinee: suppression reelle seulement quand aucun vote n existe encore, sinon archivage; sujet d envoi centralise; ciblage par defaut des participants n ayant pas encore repondu; et meilleure prise en charge des invitations liees au holon racine de l organisation.

Un script SQL de demo a enfin ete ajoute pour injecter rapidement un cas complet de test en `Jugement majoritaire`.

## 2026-06 Documents OMO

Le module `Documents` gere maintenant mieux les dossiers, la visibilite, les types de contenu et le stockage de fichiers, avec une interface plus lisible en modes `detail` et `compact`. L edition a ete enrichie avec verrou temporaire, brouillon live, partage public, insertion d autres documents et prise en charge HTML plus large.

Dans la liste `Decisions`, le menu flottant `...` du mode `compact` detache maintenant correctement ses anciennes ecoutes globales quand le panneau est recharge. Cela evite que les actions du menu soient encore traitees par une ancienne instance invisible, ce qui empechait l ouverture du drawer de detail.

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
