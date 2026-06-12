# Journal Des Nouveautes

Ce fichier sert a garder une trace courte des evolutions fonctionnelles et techniques recentes du projet.

## 2026-06 Structure OMO

Les actions `Ajouter` et `Modifier` du panneau gauche de structure n'ouvrent plus un module OMO classique qui remplace le drawer courant. Elles passent maintenant par un drawer global externe, affiche au-dessus de la zone de travail OMO.

Ce drawer externe peut se superposer a l'application actuellement ouverte, mais aussi a l'espace personnel de droite quand aucune application n'est active. La sauvegarde referme ensuite ce drawer temporaire et relance un rafraichissement du contexte sous-jacent, y compris pour la structure et les editions compactes de modeles.

Quand le contexte sous-jacent est precisement le panneau `Structure`, le retour apres sauvegarde evite maintenant le rechargement complet du drawer. La structure recharge seulement ses donnees internes puis recentre le noeud cible avec un mouvement plus doux.

## 2026-06 Entetes OMO

Les entetes des applications `Decisions`, `Documents` et `Team` ont ete rapproches du modele du calendrier. Le titre peut maintenant afficher une icone dediee, les actions principales restent alignees a droite, et les selecteurs de portee ou de vue sont mieux regroupes dans la zone haute.

Dans `Decisions`, le basculement `Contextuel/Global` a ete remonte dans l entete au lieu de rester dans le contenu. `Documents` et `Team` profitent aussi d une hierarchie plus proche du calendrier, avec des controles mieux separes entre titre, actions et changement de vue.

Un correctif a aussi ete ajoute dans `Documents` pour garder toute la zone de contenu dans le conteneur principal au premier chargement. Cela retablit le bon style initial et permet aux boutons de changement d affichage de s initialiser des l ouverture de la page.

Le chargeur AJAX OMO preserve maintenant aussi les scripts de donnees non executables comme `application/json` lors de l injection initiale d un drawer. Cela evite que certains modules, notamment `Documents`, perdent leur payload de rendu au premier chargement alors qu un rechargement interne continuait ensuite a fonctionner.

## 2026-06 Decisions OMO

L'ecran `omo/api/decision/index.php` reprend maintenant la meme logique d'affichage que `documents`, avec memorisation locale des modes `detail/compact` et `temporel/alphabetique`. Le mode compact reutilise `generic-file-list`, ses entetes sticky et ses groupes temporels, tandis que le mode detail garde les cartes de decision en les triant de facon coherente selon le mode choisi.

## 2026-06 Documents OMO

Les documents OMO ont fortement evolue. Ils peuvent maintenant etre classes dans des dossiers imbriques, deplaces via une popup en arbre, et leur affichage tient compte de la visibilite avec un compteur `visible (total)` plus explicite.

L'edition a ete enrichie avec un verrou temporaire, un brouillon live, le partage public avec suivi en temps reel, ainsi que l'insertion d'autres documents a l'interieur d'un contenu. L'editeur HTML accepte aussi davantage d'elements de mise en page, notamment les titres et les tables.

Le systeme gere desormais plusieurs types de documents: contenu HTML, lien externe et fichier telecharge. Les liens externes peuvent s'ouvrir en iframe ou dans une nouvelle fenetre, et les fichiers peuvent etre stockes dans Nextcloud via une configuration d'organisation.

L'interface a ete retravaillee avec de meilleurs rendus pour les fichiers et dossiers, des icones dediees, l'affichage des mots-cles en tags, la memorisation temporaire de l'ouverture des dossiers, et quelques correctifs d'integration comme le `z-index` des drawers internes.

Une liste compacte plus generique a aussi ete introduite, avec entetes sticky sur plusieurs niveaux, ligne de dossier persistante pendant le scroll, menu d'actions flottant sorti des lignes, et un composant reutilisable `generic-file-list` documente dans la page de styleguide.

Le stockage des fichiers a encore ete consolide avec des champs dedies pour le chemin, le nom, le type MIME et la taille du fichier effectivement stocke. Un point de configuration `parameters` a egalement ete ajoute sur l'organisation pour centraliser des reglages globaux comme ceux relies au stockage documentaire.

Ces evolutions s'appuient sur plusieurs migrations SQL autour de la visibilite, des dossiers, du suivi d'edition, du partage, des types de documents, du stockage des fichiers et des parametres d'organisation.

L'ecran documents a aussi gagne en ergonomie: en-tete plus compacte, modes `detail/compact` et `date/alphabetique` memorises dans le navigateur, navigation globale ou contextuelle plus lisible, fil d'ariane cliquable via le hash, et menu `...` pour editer rapidement un document existant.

L'editeur de documents integre maintenant plusieurs aides IA via OpenAI. Une zone `IA` peut afficher a la demande des actions pour dicter un texte, inserer sa transcription a la position du curseur, reecrire une selection pour l'harmoniser avec le reste du document, ou la resumer par etapes. Ces outils restent masques si OpenAI n'est pas configure ou si la connexion Patreon de l'utilisateur n'est pas active.

La visibilite des documents repose enfin sur un mecanisme reutilisable de portee par objet. Un document peut etre visible pour tous, pour l'organisation, pour un cercle, pour un role ou pour soi, avec filtrage automatique a l'affichage et indicateur de visibilite dans les listes et les details.

## 2026-06 Espace Personnel OMO

La page d'accueil OMO peut maintenant afficher a droite un espace personnel charge via `fetch`, sans alourdir directement `index.php`. Ce panneau rassemble un resume des elements qui concernent l'utilisateur selon les applications actives de l'organisation.

Selon le contexte, il peut montrer un recapitulatif des decisions en cours, les derniers documents ajoutes, les anniversaires d'equipe ou les dernieres modifications de structure. La logique de collecte a ete repartie autant que possible dans les `dbObject` et `array...` correspondants pour garder une base reutilisable.

Le chargement de ce panneau a aussi ete optimise pour ne pas recalculer inutilement son contenu: si un drawer est ouvert, aucun `fetch` n'est lance, et la mise a jour est rattrapee seulement quand le panneau redevient visible et que le contexte a vraiment change.

## 2026-06 Admin Local Et Environnement

Le panneau d'administration serveur permet maintenant de modifier plus facilement les variables sensibles comme Patreon, OpenAI, SMTP, Telegram ou GitHub, avec verification temporaire par mot de passe et edition des secrets sans les reafficher.

En developpement local sur `localhost` ou `localtest.me`, les changements ne sont plus ecrits dans le fichier versionne principal. L'application charge d'abord le `.env` normal puis un override non versionne `docker/app/.env.private`, et le panneau d'admin ecrit automatiquement dans ce fichier local.

Le seed Docker local a aussi ete renforce pour faciliter les tests: un utilisateur `1` peut etre prepare avec droits admin, mot de passe local et connexion Patreon simulee, afin d'acceder directement aux fonctions d'administration et aux outils IA sans parcours externe complet.

## 2026-06 FAQ OMO

Le module de FAQ a ete enrichi avec un systeme de vote positif et negatif. Chaque question peut maintenant cumuler un score positif, un score negatif, ainsi qu'un total historique de votes.

Pour limiter les abus sans alourdir le parcours, un blocage simple par session PHP empeche de revoter sur la meme FAQ le meme jour, tout en autorisant un nouveau vote des le lendemain.

La FAQ prepare aussi une logique de score evolutif dans le temps. En plus des compteurs de votes, une notion de `reliability` et des dates de recalcul et d'oubli progressif ont ete ajoutees pour permettre ensuite un mecanisme de decay.

L'affichage a ete adapte en consequence: la liste peut montrer une note relative sur 5 etoiles calculee par comparaison avec les autres FAQ, tandis que la vue individuelle reconstruit un affichage lisible des votes positifs et negatifs a partir du total et des proportions enregistrees.

## 2026-06 Recherche Topbar OMO

La recherche de la topbar repose maintenant sur un systeme de jobs asynchrones. Une table `search_job` stocke les recherches en attente, en cours ou terminees, avec leur contexte, leurs scopes et leur resultat.

Le popup de recherche affiche les resultats par module et peut desormais relancer une recherche directement depuis la popup elle-meme. Le champ de recherche et la selection des scopes y sont repris pour permettre d'ajuster le texte ou les modules sans revenir au menu principal.

La recherche couvre maintenant aussi les decisions et le calendrier. Les events retrouves peuvent ouvrir directement le bon drawer via le hash, par exemple `#calendar-event-12`, avec recentrage sur l evenement cible et ouverture de sa fiche detail dans le drawer interne. Depuis cette fiche, l auteur peut ensuite basculer vers `Modifier` sans exposer directement le formulaire aux autres utilisateurs.

Le module `Decisions` normalise aussi mieux le contexte implicite de l organisation. Quand le cercle courant correspond en fait au holon racine, le switch `Contextuel/Global`, les liens d action et certaines ouvertures internes n injectent plus ce `cid` dans les URLs, ce qui evite des chargements bloques autour de la portee globale.

Le schema SQL correspondant a aussi ete reporte dans le seed Docker local pour que les nouvelles bases de developpement disposent directement de cette infrastructure de recherche.

## 2026-06 CardDAV Et Authentification

Un endpoint CardDAV en lecture a ete ajoute pour exposer la liste des membres des organisations communes a chaque utilisateur. Il est disponible sous `/omo/api/carddav/`, avec prise en charge des routes `/.well-known/carddav`, et permet a un telephone ou a un client compatible de synchroniser un carnet d'adresses de membres.

L'authentification a ete etendue pour que CardDAV fonctionne avec le mot de passe utilisateur et plusieurs formes d'identite selon le contexte: e-mail principal, e-mail d'organisation, ainsi que les identifiants scopes lorsqu'ils sont non ambigus. Plusieurs ajustements de compatibilite PHP ont aussi ete faits sur le code CardDAV, ainsi qu'un correctif de diagnostics pour eviter des avertissements de type `deprecated`.

La gestion du mot de passe utilisateur a ete ajoutee dans le profil, avec creation ou modification du mot de passe selon le cas. Lors d'une modification, l'ancien mot de passe est demande, la confirmation est obligatoire, et l'interface empeche le copie-colle dans les champs sensibles pour favoriser une saisie volontaire.

La page principale de connexion prend maintenant en charge deux modes: le code magique par e-mail et la connexion directe par mot de passe. Un lien permet de basculer entre les deux interfaces, et le lien de reinitialisation du mot de passe n'apparait que dans le mode mot de passe, juste sous le champ concerne.

Un parcours complet de reinitialisation du mot de passe a ete ajoute: demande depuis l'ecran de connexion, envoi d'un lien par e-mail, puis page dediee pour definir un nouveau mot de passe dans le style des autres parcours e-mail du projet. Le systeme d'envoi a aussi ete ajuste pour mieux fonctionner avec la configuration SMTP Infomaniak.

La politique de robustesse des mots de passe a ete harmonisee avec celle de l'installation initiale. Les formulaires de profil, de reinitialisation, de confirmation et de creation de compte reutilisent maintenant une validation commune avec indicateur visuel de progression, exigences minimales explicites, et verification de correspondance entre les deux champs de saisie.

## 2026-06 Calendar Et CalDAV

L'application calendrier OMO permet maintenant de creer et modifier des evenements simples relies a une organisation, avec createur, titre, plage horaire, journee entiere optionnelle et rattachement possible a un cercle ou un role. L'edition reutilise le meme drawer que la creation, y compris par double-clic sur un evenement.

L'interface a ete alignee sur les autres apps OMO, avec un en-tete plus coherent et quatre vues `mois`, `semaine`, `jour` et `liste`. L'entete du calendrier a aussi ete recompose sur deux lignes, en pleine largeur, avec icone d'application, titre et compteur a gauche, actions rapides a droite, puis switches de portee et d'affichage repartis sur la ligne suivante. Un correctif de structure HTML a ensuite ete applique pour eviter qu'une fermeture de conteneur parasite fasse gonfler l'entete sur toute la hauteur visible, puis le conteneur principal du calendrier a ete remis en layout vertical `flex` pour que le corps du panneau absorbe bien l'espace restant au lieu de partager visuellement la hauteur avec l'entete. La vue `mois` a aussi ete compactee en grille continue sans cartes arrondies, avec ajustements de couleurs pour rester lisible en theme sombre, suppression du conteneur arrondi inutile autour de la grille, retour du scroll vertical du corps, et bloc sticky dedie pour garder visibles la navigation du mois et les jours de semaine pendant le scroll. Le scroll a ensuite ete retire des couches partagees entre les quatre vues pour etre rattache localement aux bons conteneurs: panneau `mois`, vue `liste`, et grilles horaires `semaine/jour`. Une derniere passe a aussi distingue le layout des vues actives pour que `mois`, `liste` et `semaine/jour` recuperent chacun leur propre gestion de hauteur et de scroll sans se perturber. Le calendrier memorise maintenant aussi localement la derniere vue choisie (`mois`, `semaine`, `jour` ou `liste`) pour la rouvrir telle quelle au chargement suivant. La vue liste reprend un affichage proche de documents pour les evenements a venir, tandis que les vues `semaine` et `jour` proposent une grille horaire avec placement des evenements, entetes sticky et ouverture directe sur les heures de bureau.

Pour limiter la charge, le calendrier charge une seule fois les evenements de l'organisation puis reutilise ces donnees localement pour changer de vue. Un switch `contextuel/global`, disponible aussi au niveau de l'organisation elle-meme, permet ainsi d'afficher soit le contexte courant, soit tous les rendez-vous de l'organisation, avec mise en retrait visuelle des evenements hors contexte. Les switches partages OMO ont aussi ete harmonises sur ce rendu capsule plus doux, y compris pour les controles segmentes comme les vues du calendrier, puis legerement compactes pour privilegier la variante la plus resserree. Plusieurs ajustements de layout ont aussi ete faits pour rendre les vues timeline plus compactes et lisibles.

Une premiere couche CalDAV en lecture seule a egalement ete mise en place sous `/omo/api/caldav/`, avec route `/.well-known/caldav`, afin d'exposer un calendrier par organisation active. Un correctif de compatibilite XML a aussi ete applique pour mieux fonctionner avec des clients stricts comme DAVx5.

Cette base technique prepare la suite pour les invitations, les participants et, plus tard, les ecritures CalDAV ou l'export de types d'evenements plus riches.
