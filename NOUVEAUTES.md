# Journal Des Nouveautes

Ce fichier garde une vue d ensemble courte des evolutions recentes, avec un angle plus fonctionnel que technique.

## 2026-06-23

Le stockage LMS des `Questions` est maintenant explicitement separe du stockage `FAQ`: les migrations et le seed Docker creent des tables dediees `question`, `question_choice`, `mission_question` et `user_question_response` sans jamais renommer ni recycler les tables `faq*`.

L ancienne migration `2026-05-01-lms-questions.sql` est maintenant neutralisee: elle ne renomme plus `faq`, `faq_choice`, `mission_faq` ni `user_faq_response` vers les tables LMS `question*`, ce qui protege les bases DEV/PROF ou la FAQ reste stockee separement.

Une migration de rattrapage recree maintenant la table `faq` si une ancienne base locale n a garde que `question`, puis recopie automatiquement les entrees non rattachees aux missions LMS pour restaurer le contenu FAQ perdu apres l ancien renommage.

La popup FAQ ne plante plus si la base cible n a pas encore de table `faq`: elle s ouvre avec un message explicite, masque l ajout impossible, et attend simplement que les migrations soient appliquees.

La classe `FAQ` degrade maintenant proprement son tri sur les bases plus anciennes: si les colonnes `displayorder` ou `updated` manquent encore, les listes FAQ utilisent un ordre compatible au lieu de provoquer une erreur SQL.

Dans le LMS OMO, un parcours importe depuis une autre organisation n expose plus l action `Editer` et toute tentative directe d ouverture ou de sauvegarde est maintenant refusee cote serveur: seule l organisation proprietaire peut modifier son parcours.

Dans la grille LMS OMO, la carte d ajout de parcours propose maintenant deux entrees distinctes `Nouveau` et `Importer`, avec un drawer de catalogue pour rattacher rapidement a l organisation courante un parcours deja marque `public` ou `basic`.

La classe `DecisionProcess` expose maintenant correctement sa couche de visibilite partagee, avec compatibilite sur le champ legacy `visibility_type`, ce qui evite les fatals sur l espace personnel et re-aligne les ecrans `Decisions` avec les helpers de visibilite attendus.

Dans l editeur interne des missions du LMS OMO, les listes de devoirs et de questions peuvent maintenant etre reordonnees en glisser-deposer, avec sauvegarde immediate de l ordre dans `mission_homework.position` et `mission_question.position`.

Les formulaires rendus par `adminEdit.php` initialisent maintenant correctement les champs `html` avec un vrai editeur HTML Summernote Lite, y compris dans les drawers LMS charges dynamiquement, au lieu d afficher un simple textarea brut.

Dans la grille LMS OMO, la carte `Nouveau parcours` est maintenant rendue apres les parcours existants, pour garder d abord la liste du contenu deja disponible.

Cette meme grille separe maintenant les parcours a faire et les parcours termines: les elements a 100% passent sous une ligne de separation dans une section dediee `Parcours termines`, avec un rerangement JS complementaire pour les progressions anonymes stockees localement.

Le drawer global de la topbar traite maintenant explicitement le mode `iframe` sans scroll vertical parasite: le body du drawer passe en overflow masque dans ce cas, et l iframe remplit la zone utile sans declencher d ascenseur inutile.

Dans l editeur de parcours LMS OMO, le champ `Public` n apparait maintenant que si le mode admin d organisation est effectivement active, tandis que `Basic` reste reserve au mode super admin actif, avec controle miroir cote sauvegarde pour ignorer tout POST forge sans droit ni mode ouvert.

## 2026-06-22

Dans l editeur OMO des templates de holons, la zone d edition des proprietes replie maintenant correctement `Nom` et `Format` l un sous l autre quand le panneau devient etroit sur mobile ou petit ecran.

Dans la popup `Signaler un bug` de OMO, la connexion Patreon recharge maintenant automatiquement le contenu du formulaire des la fin du parcours OAuth, sans devoir fermer puis rouvrir la fenetre.

Les apps OMO `Calendrier`, `Decisions` et `Documents` proposent maintenant aussi une portee intermediaire `Descendants`, entre `Contextuel` et `Global`, pour afficher le holon courant avec tout son sous-arbre sans remonter a toute l organisation.

Les prises de decision disposent maintenant elles aussi d une visibilite explicite dans `decision_process`, editable dans les ecrans de creation et de modification, appliquee aux acces app, et affichee dans la liste OMO par une petite icone a cote du titre.

Dans l app `Equipe` de OMO, le resume statistique du header laisse maintenant place a un bouton `Ajouter un membre` quand le contexte courant autorise bien la permission de holon `CAN_ADD_MEMBER`, avec ouverture de la meme popup que depuis la leftbar et rafraichissement du drawer apres ajout.

Dans l app Calendrier OMO, un double-clic sur un jour ou sur une plage horaire en vue semaine/jour ouvre maintenant directement le drawer de creation d evenement, pre-rempli a la date ou a l heure visee, tout en restant desactive sans permission de creation.

Dans la liste `Documents` de OMO, la visibilite de chaque element s affiche maintenant par une petite icone inline a cote du titre, avec le libelle complet conserve en tooltip.

La FAQ globale embarque maintenant une vingtaine d entrees utilisateur pretes a l emploi, centrees sur l aide, la recherche, les drawers OMO, les documents, Memo, les decisions, le calendrier, les permissions de creation et la carte publique des organisations.

## 2026-06-21

L ancien listing racine `memo.php` est maintenant remplace par une vraie app `/memo/` avec authentification partagee, liste de tous les documents dont l utilisateur est l auteur, tous holons confondus, et ouverture du detail dans un drawer interne de style OMO. Les liens Telegram historiques avec code d acces restent servis via une vue detail dediee.

Cette nouvelle app `/memo/` reprend maintenant aussi les controles visuels de l app `Documents` de OMO pour basculer entre tri `Date / Alphabetique` et densite `Detail / Compact`, avec un rerendu local de la liste dans le meme esprit.

Les documents affiches dans `/memo/` proposent maintenant aussi une action `Editer` via un menu `...`, avec ouverture du formulaire OMO dans un drawer interne pour retrouver un parcours proche du module `Documents`.

Le bot Telegram de memo envoie maintenant ses messages et boutons via des requetes JSON explicites en `UTF-8`, ce qui fiabilise l affichage des accents et limite les problemes de mojibake dans les conversations.

Les documents `memo` sans contexte d organisation peuvent maintenant etre re-edites par leur auteur depuis `/memo/`, tandis que les documents deja classes continuent d appliquer les droits de leur contexte d organisation habituel.

## 2026-06-20

Le bot Telegram de memo filtre maintenant les destinations de classement selon la permission de holon `CAN_CREATE_DOCUMENT`: le bouton `Terminer ici` disparait sans droit, seuls les roles et sous-niveaux qui menent recursivement vers une destination autorisee restent proposes, et le callback de classement refuse aussi un emplacement forge sans autorisation.

Dans ce meme parcours Telegram, les boutons de suppression passent en `danger`, `Terminer ici` en `success` et les destinations de classement en `primary` quand le client Telegram supporte ces styles.

## 2026-06-19

Les modifications de templates de holons alimentent maintenant aussi l historique `dbObject history`, avec le detail des champs modifies, des proprietes et des changements de droits associes.

Les templates de holons peuvent maintenant recevoir quatre nouveaux droits de creation dans leur catalogue de permissions: fichiers, prises de decision, dates et FAQ. La base de donnees, le seed Docker et l editeur OMO des templates restent synchronises sur ce nouveau catalogue.

La popup FAQ OMO utilise maintenant aussi la permission de holon `CAN_CREATE_FAQ` pour afficher ou non l action `Ajouter une question`, avec le meme controle cote sauvegarde pour empecher un POST direct sans droit.

Les actions de creation `Document`, `Evenement` et `Prise de decision` suivent maintenant aussi leurs permissions de holon dediees dans les boutons d interface et dans les points d entree serveur de creation.

## 2026-06-18

La homepage racine peut maintenant afficher une carte publique des organisations OMO, alimentee uniquement par les champs explicitement exposes comme lisibles sans connexion dans les `dbObject`.

Les organisations OMO peuvent maintenant enregistrer un emplacement geographique facultatif dans leurs parametres, y compris des la creation, avec stockage en base et saisie latitude/longitude reutilisant le champ cartographique partage.

Dans les drawers OMO qui proposent le switch `Contextuel / Global`, le libelle passe maintenant a deux icones plus compactes sur mobile, tout en gardant le texte complet sur les largeurs plus confortables.

Dans les drawers OMO, les boutons de tri `Temporel / Alphabetique` utilisent aussi des icones dediees sur mobile, pour alleger les entetes sans changer le rendu desktop.

Dans ces memes drawers OMO, les bascules `Detail / Compact` affichent maintenant aussi des icones `cartes / liste` sur mobile pour compacter encore l entete.

Sur mobile, les switches `Contextuel / Global`, tri et densite restent maintenant regroupes sur une meme ligne dans les entetes de drawers OMO quand la largeur reduite le permet, au lieu de passer systematiquement l un au dessus de l autre.

Le calendrier OMO suit maintenant la meme logique sur mobile: le switch `Contextuel / Global` reste sur la meme ligne que le selecteur de vue quand la largeur le permet, sans changer le segmented `Jour / Semaine / Mois / Liste`.

Sur mobile, les actions de creation dans `Documents`, `Decisions` et `Calendrier` sont maintenant reduites a un bouton icone ancre dans le coin haut droit du header, au lieu d occuper une ligne complete.

Dans le calendrier OMO, le selecteur `Mois / Semaine / Jour / Liste` est maintenant remplace sur mobile par des icones `31 / 7 / 1 / liste`, pour reduire encore sa largeur sans changer le comportement du segmented.

Le bouton mobile de creation ancre en haut a droite utilise maintenant une forme dediee plus propre, avec seul l angle bas gauche arrondi et sans les effets visuels trop generiques du bouton principal classique.

Dans `Documents` et `Decisions`, ce bouton mobile de creation force maintenant explicitement sa largeur et sa forme dediee pour ne plus recuperer l apparence pleine largeur des boutons responsives du header.

Le meme bouton mobile de creation s ancre maintenant sur le meme repere de header dans `Documents`, `Decisions` et `Calendrier`, avec rayon force uniquement en bas a gauche pour un rendu uniforme.

Sur mobile, les fleches de navigation `precedent / suivant` du calendrier restent maintenant sur la meme ligne que le titre de periode, avec une largeur tres compacte centree sur la fleche pour eviter une ligne supplementaire.

En dessous d une faible hauteur d ecran, par exemple sur telephone en paysage, les effets `sticky` des drawers OMO sont maintenant desactives pour laisser plus de place au contenu et eviter qu une entete occupe presque toute la hauteur utile.

Les variantes compactes a icones dans les entetes de drawers OMO s activent maintenant des `1024px` de largeur et pas seulement sur tres petit mobile, afin de mieux convenir aux vues en colonnes plus etroites.

Dans OMO, les drawers ouverts depuis la leftbar laissent maintenant un petit espace libre au niveau du separateur entre panneaux, ce qui permet de garder la barre de redimensionnement accessible meme quand un module est ouvert.

La page publique `decision/access` utilise maintenant toute la hauteur disponible sur mobile, ce qui recolle bien la navigation `Infos / Scrutin` en bas de l ecran sans bande vide residuelle.

L ecran public `Recevoir mon acces personnel` du module `Decisions` est maintenant presente dans une carte centree avec largeur limitee, pour un rendu plus propre et plus lisible dans la zone principale.

Sur ce meme ecran public, le bloc `Contacter l organisateur` est maintenant compact et pousse en bas du panneau principal tout en restant visible sans scroll supplementaire quand la hauteur disponible le permet.

Sur le hub OMO, la carte de creation d organisation est maintenant reservee aux profils ayant connecte Patreon. Les autres profils voient a la place une carte de connexion Patreon avec visuel dedie en pleine largeur, applique directement en fond CSS sur le bandeau de la carte d action, puis le hub se recharge automatiquement une fois la connexion terminee.

Le formulaire de creation d organisation masque maintenant le bloc de stockage documentaire Nextcloud tant que l organisation n existe pas encore. Ce parametrage reste visible uniquement en mode modification.

Le nom court et le domaine d une organisation sont maintenant reserves aux profils Patreon eligibles cote interface et cote sauvegarde. Sans ce niveau d acces, les champs restent visibles mais grises avec un message indiquant que cette option est reservee aux associations et aux organisations.

## 2026-06 Vue D Ensemble

Le projet a avance sur quatre grands axes: une interface plus coherente, un module `Decisions` beaucoup plus abouti, un meilleur confort sur les pages publiques et partagees, et une base plus solide pour les outils transverses comme l authentification, le calendrier, la FAQ ou la recherche.

## Interface Et Theme

L apparence du site a ete unifiee autour d une palette OMO commune, avec gestion plus claire du mode clair, du mode sombre et de plusieurs variantes de couleur comme `turquoise` et `Ocean Blue`. Le menu profil propose maintenant des reglages plus compacts pour la langue et le theme, tandis que les entetes, panneaux, avatars et composants partages sont plus coherents d une page a l autre.

Les listes structurees reutilisent aussi mieux leur composant commun, avec des titres de groupe plus discrets et une separation visuelle plus legere entre les sections.

Les palettes de couleur sont maintenant mieux separees, avec un fichier CSS dedie par variante, pour faciliter l ajout de futurs themes sans alourdir un seul gros fichier central.

## Decisions OMO

Le module `Decisions` a ete fortement enrichi. La navigation est plus souple, les listes sont plus lisibles, les vues memorisent mieux les preferences de consultation, et les resultats de vote sont plus clairs. Le `Jugement majoritaire` est aussi devenu plus parametrable, avec une echelle plus flexible et une presentation plus homogene.

Les entetes de regroupement par date y sont aussi desormais plus coherents entre les differents modes d affichage, avec un rendu partage base sur le composant generique.

## Invitations Et Participation

La gestion des invitations a ete simplifiee et rendue plus robuste. Il est maintenant plus facile d ouvrir un scrutin a la participation sans invitation, de suivre les personnes ajoutees via un lien public, de gerer les acces par code ou par lien personnel, et de retrouver clairement qui participe vraiment au scrutin.

## Pages Publiques Et Partagees

Les pages publiques de participation et de partage ont ete harmonisees avec le reste d OMO. Elles reprennent une topbar commune, une aide mieux structuree, une navigation mobile plus propre, et des panneaux d information plus lisibles. Les comportements d accordeon, les entetes et les liens publics ont aussi ete affines pour etre plus naturels a utiliser.

## Documents Et Espace Personnel

Le module `Documents` a gagne en lisibilite et en souplesse, autant pour le rangement que pour l edition et le partage. En parallele, la page d accueil OMO peut maintenant afficher un espace personnel plus utile, adapte aux applications actives de l organisation.

## Acces, Authentification Et Admin

La gestion des acces a ete amelioree a plusieurs niveaux: demandes d acces a une organisation, modes admin, connexions plus souples selon le contexte, reinitialisation et gestion du mot de passe, ainsi qu un environnement local plus simple a administrer pour les tests et la configuration.

## Recherche, FAQ Et Outils Transverses

La recherche de topbar couvre mieux plusieurs applications et repose sur une base plus solide. La FAQ prend mieux en charge les votes, la mise en valeur des questions utiles, et un triple filtre `contextuel` / `descendants` / `global` aligne sur les autres visibilites OMO. Les icones de scope ont aussi ete remplacees dans les toggles partages et etendues au mode `descendants`. L'app `Team` reprend maintenant le meme selecteur pour naviguer entre membres du holon courant, du sous-arbre, ou de toute l'organisation, avec une transition de curseur plus fluide comme dans `Calendrier` et `Decisions`, ainsi qu'une vue compacte pour balayer rapidement `nom / prenom / telephone / e-mail`. Plusieurs elements partages ont aussi ete harmonises pour limiter les incoherences entre modules.

## Calendrier, CardDAV Et CalDAV

Le calendrier OMO devient plus exploitable au quotidien avec plusieurs vues, une edition plus simple et un comportement plus stable. En complement, des points d entree CardDAV et CalDAV ont ete poses pour preparer les usages de synchronisation avec des outils externes.

## Stabilite Generale

Une partie importante du travail a aussi porte sur la fiabilite: meilleurs comportements apres recharge, correction de blocages silencieux, meilleure compatibilite des pages selon le contexte, et remise en coherence de certaines bases locales ou de demonstration pour eviter des erreurs au redemarrage.
