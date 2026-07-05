# Journal Des Nouveautes

Ce fichier garde une vue d ensemble courte des evolutions recentes, avec un angle plus fonctionnel que technique.

## 2026-07-05

Le chargement des proprietes de holon heritees releve maintenant explicitement la limite de `GROUP_CONCAT` avant de reconstruire les valeurs ancetres. Les longues listes JSON, comme certaines `Missions attendues`, ne sont ainsi plus coupees au milieu dans `getOrg` ou dans l editeur compact. Une migration SQL explicite aligne aussi `holonproperty.value` en `mediumtext` pour rattraper les environnements restes sur un schema plus ancien.

Dans la vue compacte des `Documents` OMO, la colonne `Type` disparait maintenant au profit d une colonne `Nom` plus confortable, la colonne de date est resserree pour mieux coller a son contenu, le bouton `...` de fin de ligne reserve moins de largeur, et les actions du menu flottant (`Editer`, `Deplacer`, `Partager`) passent maintenant par un dispatch de clic unifie. L edition de document routee par hash s aligne aussi davantage sur le schema deja utilise dans `Decisions` et `Calendrier`, avec une ouverture partagee par identifiant et un fallback direct vers l URL d edition du contexte courant. Les helpers JS reutilises par les clics de detail et d edition ont aussi ete recadres dans le bon scope pour eviter les `ReferenceError` lors de la navigation, les liens documentaires ouverts directement dans une fenetre externe ne changent plus le hash OMO, les actions `Editer` ou `Deplacer` ne restent visibles que si la personne a bien `CAN_CREATE_DOCUMENT` sur l emplacement reel du document, y compris dans les vues globales ou descendantes, tandis que `Partager` reste disponible pour les documents HTML deja visibles.

Dans l editeur de document OMO, les champs `Type` et `Visibilite` partagent maintenant une meme ligne en deux colonnes sur ecran large, tout en revenant en pile sur mobile.

Ce meme editeur expose maintenant aussi un vrai champ de tags a capsules, relie au stockage existant des `keywords` en chaine separee par virgules. La saisie transforme un tag en capsule sur `TAB` ou `,`, et la sauvegarde conserve le format courant du type `atelier,benevoles,association`.

La recherche OMO de documents ne traite plus non plus ces `keywords` comme un simple bloc de texte. Les tags separes par virgules sont maintenant scores comme de vrais tags individuels, avec un poids nettement plus fort quand la recherche correspond exactement a un tag ou a son debut.

Depuis la popup de recherche OMO, l ouverture d un resultat `Document` ne passe plus par une modale de detail isolee quand la route peut etre resolue. Le clic reconstruit maintenant la navigation OMO normale vers le hash du document pour l ouvrir dans son drawer `Documents`.

La recherche de la topbar OMO propose maintenant aussi les scopes `FAQ` et `Tutoriels`. Les resultats FAQ interrogent les contenus question/reponse/detail selon le contexte courant, et les resultats tutoriels couvrent a la fois les parcours LMS visibles et le texte des missions associees.

L ouverture des resultats d aide passe maintenant aussi par le hash OMO pour les FAQ et les tutoriels. Une FAQ trouvee peut ouvrir directement son detail, et un tutoriel peut ouvrir le LMS embarque sur le bon parcours, voire directement sur la mission cible quand la recherche remonte une mission.

Le resume des resultats de recherche OMO en tete de popup devient aussi interactif. Un clic sur une tuile comme `FAQ` ou `Tutoriels` filtre maintenant en pur JS les cartes deja chargees pour ne montrer que ce module, et un second clic sur la tuile deja active restaure toute la liste sans relancer la recherche.

La popup de recherche OMO utilise maintenant un bandeau de recherche compact borde a borde en haut, aligne sur le style des entetes de drawer partages, sans textes d introduction ni fond parasite autour du conteneur principal.

La popup de recherche OMO passe maintenant aussi par le hash applicatif avec memorisation d etat par onglet. Quand un resultat ouvre un drawer ou une autre popup, le retour navigateur peut rouvrir la recherche precedente avec son `SearchJob` et ses resultats deja retrouves, sans relancer manuellement la requete.

Les popups topbar qui dependaient du padding global de la modale reprennent maintenant leur propre structure interne. Les formulaires et panneaux principaux utilisent des shells locaux avec padding neutre, et plusieurs popups comme recherche, partages, tensions, bug report, applications et invitations exposent maintenant un vrai bandeau borde a borde base sur `generic-drawer-header`.

Les popups de profil membre `popup/user.php` et d edition de profil `popup/profil.php` s alignent elles aussi sur ce schema, avec un header borde a borde en tete puis un shell interne pour les onglets et contenus.

La popup de profil personnel charge maintenant ses onglets principaux a la demande. Les contenus `Previsualisation`, `Profil general`, `Profil specifique`, `Competences` et `Patreon` ne sont calcules et injectes qu au moment ou l onglet correspondant est ouvert.

La fiche membre contextuelle charge maintenant aussi son onglet `Droits` a la demande. Cet onglet liste, pour l utilisateur consulte dans l organisation de contexte, les droits effectifs calcules avec leur portee et le holon source quand cette origine peut etre determinee.

La fiche membre contextuelle a aussi ete recomposee avec un bandeau pleine largeur en tete pour la photo, le nom et l e-mail, puis un systeme d onglets en dessous dont `Infos` arrive en premiere position pour regrouper presentation, dates, identifiant et apercu.

## 2026-07-04

Dans OMO, la gestion des applications de la barre de gauche repose maintenant aussi sur un nouveau droit de holon `CAN_ADD_APP`. Le bouton `+` apparait desormais soit quand le mode admin d organisation est actif, soit quand ce droit est accorde a la personne via ses holons.

Le calcul des droits de holon ne traite plus un droit simplement "existant" comme s il etait accorde. Les droits globaux d organisation comme `CAN_ADD_APP`, `CAN_CREATE_PARCOURS` et `CAN_EDIT_PARCOURS` ne sont maintenant accordes qu aux personnes effectivement liees aux holons porteurs de ces droits, avec invalidation du cache de permissions de session.

La construction de l arbre des droits ne remonte plus automatiquement les droits poses sur les holons ancetres d un holon utilisateur. Un membre recupere maintenant les droits de ses holons lies et de leurs templates, mais plus ceux des parents structurels si aucun lien direct ne l y rattache.

Le calcul des droits ne confond plus non plus appartenance a l organisation et appartenance au holon racine de cette organisation. Un droit confie a un holon avec une portee `Toute l organisation` reste maintenant reserve aux membres de ce holon porteur, meme si son effet s applique ensuite partout dans l organisation.

Une page temporaire `omo/api/debug_permissions.php` permet maintenant d inspecter, pour l organisation de contexte, les holons effectifs de l utilisateur connecte, les holons sources pris en compte pour ses droits, l arbre des droits definis et le resultat final calcule.

Dans l editeur de holon, le resume des droits affiche maintenant separement les droits herites depuis la chaine de templates, puis les droits directement associes au holon courant.

Les menus de la topbar commune passent maintenant au-dessus des drawers OMO, y compris pendant l edition d un holon, tout en gardant les modales et drawers propres a la topbar encore au-dessus de ces menus.

## 2026-07-02

Le lien `Tutoriels` de la topbar OMO ouvre maintenant le LMS embarque sur le host courant plutot que force sur le host racine. Quand OMO tourne sur un sous-domaine d organisation, l iframe LMS peut ainsi relire directement les preferences locales de theme et de palette du navigateur.

Les pages LMS ouvertes en iframe recopient maintenant explicitement le theme et la variante de couleur de la page parente OMO, puis se resynchronisent quand l utilisateur change de mode clair/sombre ou de palette pendant la session.

Dans le drawer LMS, chaque vue reinitialise maintenant proprement son scroll interne, et le retour depuis l editeur d une mission vers l editeur de parcours restaure la position precedente au lieu de reutiliser ou perdre le scroll de l autre vue.

Le LMS charge maintenant aussi les feuilles de theme communes, ce qui permet aux pages catalogue et parcours de respecter la preference visuelle deja choisie par l utilisateur, y compris hors du shell OMO principal.

Dans l editeur de parcours du LMS, le bandeau sticky du drawer est maintenant vraiment colle en haut de la zone scrollable, sans jour visible au-dessus pendant le scroll.

Les drawers OMO, Memo et le drawer principal de la topbar reutilisent maintenant un bandeau commun `generic-drawer-header`, ce qui harmonise partout l entete pleine largeur avec degrade leger et permet aussi un mode sticky partage pour les editeurs qui en ont besoin.

Dans l editeur de parcours du LMS, l entete du drawer reprend maintenant le look standard OMO en bandeau pleine largeur non arrondi, colle en haut de la zone et garde les actions `Annuler` et `Enregistrer` toujours visibles pendant le scroll.

Dans le LMS, l ouverture du drawer d edition verrouille maintenant le scroll de la page en arriere-plan, ce qui supprime le double ascenseur visible a droite et laisse uniquement le scroll interne de l editeur.

Dans le LMS, l affichage des cartes de parcours masquees en grise pour les admins d organisation depend maintenant du vrai mode admin explicitement active depuis le menu profil, et non plus du simple statut admin permanent. Les anciens flags de session legacy ne sont plus repris automatiquement comme si ce mode avait ete active.

Dans le LMS, le champ `detail` des devoirs passe maintenant en vrai HTML via `adminEdit`, avec un editeur volontairement simple limite au formatage de base et aux listes. Le detail des devoirs est aussi rendu comme HTML dans la vue mission, pour afficher correctement ces contenus enrichis.

`adminEdit.php` reutilise maintenant plus directement les primitives visuelles partagees du site pour ses champs, panneaux et boutons, afin d aligner le rendu des formulaires d edition sur le reste de l interface.

Dans l editeur de mission OMO du LMS, le formulaire specifique des devoirs a aussi ete aligne sur ce rendu partage et branche au meme editeur HTML simple, car cette zone n utilisait pas `adminEdit.php` mais un formulaire custom.

Les devoirs LMS peuvent maintenant etre marques `admins uniquement` via un flag `onlyAdmin`. Ce nouveau champ est stocke en base, editable dans le formulaire de devoir, et les vues ainsi que la validation des missions ignorent automatiquement ces devoirs pour les membres non admins de l organisation.

Une nouvelle primitive partagee `generic-drag-handle` est disponible dans `components.css` pour les poignees de reordonnancement. Les questions LMS, les devoirs et les missions de l editeur de parcours reutilisent maintenant cette meme base, documentee aussi dans le styleguide.

Dans la popup de profil personnel, le bloc de changement de mot de passe est maintenant replie par defaut derriere une case `Modifier le mot de passe`. Le formulaire ne s affiche qu au besoin, ce qui allegre l editeur au quotidien.

Quand Patreon est active sur le serveur, ses informations de connexion et ses actions de synchronisation sont maintenant affichees dans un onglet dedie de l editeur de profil, au meme niveau que les autres sections.

## 2026-07-01

Le hub `Parametres` de OMO affiche maintenant des cartes mieux alignees pour `Profil`, `Organisation` et `Modeles de holons`, avec meme hauteur, entete plus lisible et petit visuel dedie pour mieux reperer chaque entree.

La popup de profil personnel a ete redecoupee en vrais onglets reutilisant le composant generique commun, avec un panneau `Profil actuel`, un formulaire `Profil specifique`, un formulaire `Profil general`, puis un onglet dedie a l edition des competences. Cet onglet affiche maintenant une liste compacte avec bouton `Editer` sur chaque competence et un unique editeur partage ouvert au besoin pour modifier ou ajouter une competence.

Le profil d organisation personnel est maintenant protege par un garde-fou SQL supplementaire pour la colonne `user_organization.image`, avec mise a jour du bootstrap Docker et un message d erreur AJAX un peu plus explicite si un enregistrement echoue encore.

Les dumps Docker de base `user_organization` ont aussi ete remis a niveau pour inclure directement `username`, `image`, `email`, `presentation` et `latlong`, afin qu un refresh complet reconstruise une base compatible sans dependre d un rattrapage ulterieur.

`adminEdit.php` sait maintenant generer un bundle de traduction a partir des labels, descriptions et placeholders declares dans les `dbObject`, puis les traduire au rendu sans deplacer la logique de langue dans les classes metier elles-memes.

Le centre de parametres OMO ouvert depuis la barre de gauche, ainsi que les ecrans `Admin du serveur` et `Modeles de holons`, reutilisent maintenant des bundles de traduction dedies avec helpers partages pour leurs titres, actions, etats vides, messages JS et retours JSON principaux.

Dans OMO, les ecrans principaux encore en dur du LMS et de l app `Documents` sont maintenant branches au mecanisme de traduction via des blocs `sourceLang` locaux et des helpers `t(...)` dedies par vue ou sous-panneau.

Les drawers et panneaux `Documents` couvrent maintenant aussi la traduction de l index, du detail, du deplacement, du partage et de l editeur de creation pour les principaux libelles, boutons, etats de chargement et messages visibles.

Dans le LMS OMO, les ecrans `creer / editer un parcours`, `editer une mission`, les gestionnaires de prerequis, missions, questions, devoirs et l affichage des packs utilisent maintenant le meme schema de bundle de traduction que les autres apps recentes.

Les endpoints secondaires `Documents` relies a la reecriture, la transcription, le resume et le deplacement, ainsi que les vues LMS `index` et `getMissionDetail`, ont ete completes pour reutiliser eux aussi des bundles de traduction locaux sur leurs messages, drawers et actions visibles.

Le mecanisme commun de traduction tient maintenant compte du parametre d URL `lang` avant de retomber sur le cookie ou la langue navigateur. Cela permet de recreer correctement les bundles manquants d une page lors d un test direct en `?lang=en` ou autre locale supportee.

La langue choisie depuis la topbar est maintenant aussi memorisee sur le compte utilisateur connecte via `user.parameters`, puis relue cote serveur avant le cookie navigateur. Les vues OMO, dont l index LMS, utilisent ainsi la langue du membre connecte meme sans parametre `lang`.

## 2026-06-30

La topBar OMO propose maintenant un bouton `Tension` avec un pictogramme eclair, a cote du bouton bug. Il ouvre une popup de saisie rapide pour enregistrer une tension de gouvernance partagee avec titre court, description libre et contexte holon modifiable.

Une nouvelle table `tension` et son `dbObject` associe ont ete ajoutes pour stocker ces besoins ouverts avec auteur, organisation, holon, dates et statut actif, de facon compatible avec le flux de migration SQL du projet.

La construction de l arborescence de holons pour les selects contextuels s appuie maintenant sur un cache de session par organisation, invalide automatiquement des qu une nouvelle entree apparait dans l historique de l organisation. Cette premiere mecanique est posee dans le helper commun des tensions pour pouvoir etre generalisee ensuite.

Dans la popup de creation de tension, le select de contexte n affiche maintenant que les holons utiles au membre courant: ses propres holons restent selectionnables, leurs ancetres restent visibles mais grises, et les autres branches sont masquees.

Dans l editeur LMS des missions, le champ `Nom du groupe` devient un `select editable`: il reste libre a la saisie, mais un bouton propose maintenant les noms de groupes deja utilises dans le parcours courant pour eviter les fautes de frappe. Le composant est partage dans la bibliotheque commune et documente dans la page de styleguide pour pouvoir etre reutilise ailleurs.

## 2026-06-29

Le bootstrap PWA de OMO ne force plus une mise a jour du service worker a chaque rechargement, et ignore maintenant une seconde initialisation du meme script sur la page. Cela evite les boucles de rechargement observees en debug navigateur quand l option DevTools `Update on reload` est active.

Dans l interface de decision par consentement, les boutons de position sont maintenant presentes dans l ordre `Objection`, `Pas d objection`, `Pour`, avec une palette rouge, jaune-orange et verte, ainsi que les pictos `Faux`, `Amour` et `Vrai` pour rendre le choix plus lisible au premier coup d oeil.

La zone de parametrage du module de consentement reprend maintenant la meme presentation compacte que les modules `vote` et `jugement majoritaire`, avec un resume inline des reglages et un bouton d ouverture harmonise.

Les blocs `sourceLang` definis en tete des pages OMO, Choice, Calendrier, Documents, Decisions et popup profil ont ete relus pour remettre les accents, corriger plusieurs formulations francaises et supprimer quelques textes encore mal encodes.

Les droits de holon ne sont plus reserves aux seuls holons templates. L editeur classique des holons affiche maintenant un resume compact des droits deja attaches, avec un bouton `Editer` qui deploie le meme type de selection de portees pour attribuer ou retirer des permissions directement sur n importe quel holon modifiable.

Depuis le panneau `getOrg`, l action `Modifier` sur un template visible reouvre maintenant bien le template courant dans l editeur compact, au lieu de tomber sur un nouveau modele vide.

Les parcours LMS peuvent maintenant definir des prerequis vers d autres parcours simples de la meme organisation. Tant que ces prerequis ne sont pas termines a 100%, le parcours cible reste masque dans le catalogue et bloque en acces direct.

## 2026-06-26

Dans le LMS integre de OMO, deux nouveaux droits de holon `CAN_CREATE_PARCOURS` et `CAN_EDIT_PARCOURS` pilotent maintenant la creation, l import, la suppression-detachement et l edition des parcours selon le contexte courant. Sans `edit`, les parcours importes de type pack sont masques et seuls les packs proprietaires restent visibles.

Le catalogue de droits sait maintenant distinguer les permissions `contextuelles` de celles purement `organisation`. Dans l editeur de holon, un droit non contextuel ne propose plus que `Toute l organisation`, et son calcul ne depend plus de l heritage par holon.

Dans l entree directe `/lms/`, les packs de parcours ne sont maintenant plus affiches du tout, ni dans le catalogue ni via une ouverture directe. Ils restent reserves a l interface LMS integree de OMO.

Le point d entree direct `/lms/` reprend maintenant beaucoup plus fidèlement la presentation de `/omo/api/lms/`: sections separees, packs de parcours, progression locale des visiteurs, et ouverture des packs vers leurs sous-parcours visibles. Il reste accessible hors OMO tout en affichant seulement les elements publics aux visiteurs ou aux non-membres, et tous les parcours aux membres de l organisation.

La topbar du `/lms/` direct propose maintenant aussi une connexion meme en visite publique, sans forcer un ecran de login tant qu au moins un contenu public peut etre affiche.

Le LMS sait maintenant creer des `packs` de parcours via le nouveau flag `ispack`. Un pack ne contient pas de missions mais d autres parcours simples appartenant a la meme organisation, et son editeur remplace donc la gestion des missions par une gestion de sous-parcours reordonnables.

Quand une organisation ajoute un pack a son LMS, les parcours qu il contient sont maintenant attaches automatiquement a cette organisation, meme s ils ne sont pas publics individuellement. Les parcours masques par une application desactivee restent invisibles, tandis que le pack lui-meme peut rester importable et servir de point d installation unique.

Dans la liste LMS, les parcours simples restent affiches en premier, puis les packs apparaissent dans une section separee plus bas. Ouvrir un pack affiche directement les parcours visibles qu il contient au lieu d une liste de missions.

L editeur LMS des parcours sait maintenant retrouver correctement l organisation proprietaire meme sur d anciens parcours dont `parcours.IDorganization` etait reste vide. Ces parcours redeviennent donc modifiables par leur organisation proprietaire, au lieu d etre bloques a tort comme imports externes.

La popup FAQ OMO sait maintenant rattacher une entree soit a l organisation courante via un holon, soit a un parcours LMS disponible dans cette organisation, avec une option `FAQ generique` reservee au mode super admin. L aide d une organisation peut ainsi melanger FAQ generiques, FAQ de holons et FAQ de parcours publics ou partages.

Les FAQ rattachees a un parcours LMS restent maintenant editables seulement par l organisation proprietaire du parcours, mais elles sont bien visibles dans toutes les organisations qui ont lie ce parcours dans leur LMS.

Les parcours LMS peuvent maintenant etre lies a une application OMO. Quand cette application est desactivee dans une organisation, le parcours et les FAQ rattachees a ce parcours disparaissent automatiquement dans cette organisation, y compris via un lien direct.

Dans l app `Calendrier` de OMO, l effet visuel estompe ne sert plus a opposer `holon courant` et `autre holon`: il distingue maintenant les reunions ou la personne courante est reellement concernee via son appartenance de contexte de celles qui restent seulement informatives.

Dans le resume personnel OMO, le bloc `Calendrier` s intitule maintenant `Mes prochaines reunions`, pour mieux refleter qu il liste avant tout les dates a venir de la personne courante.

La popup de recherche OMO declare maintenant explicitement son parametre `Organization` nullable dans `search_popup.php`, ce qui supprime l avertissement deprecation remonte par les versions recentes de PHP.

Les hash OMO des objets embarquent maintenant aussi leur mode d ouverture quand c est necessaire: `Documents` distingue la lecture `#documents-d12` de l edition `#documents-de12`, et `Decisions` distingue `voir`, `gerer` et `participer` avec des routes dediees. Un lien direct ou un retour navigateur reouvre donc bien la meme vue, sans retomber sur un drawer par defaut.

Le cache session des droits par organisation ne reste plus fige jusqu a reouverture: avant de reutiliser `permissionCacheByOrganization`, OMO compare maintenant son marqueur au dernier `history.id` actif de l organisation courante. Les droits ne sont recalcules que si une nouvelle entree d historique est apparue pour cette organisation, y compris apres une action d un autre utilisateur.

Dans la parametrisation OMO du `Vote simple`, le recap des reglages reprend maintenant une presentation plus proche du `Jugement majoritaire`: les options s affichent en lignes pleine largeur, le rappel `Autoriser les propositions pendant la consultation` y apparait aussi, et le bloc de ponderation occupe bien toute la largeur dans la popup de configuration.

Dans ce meme recap du `Vote simple`, le `mode de choix` est maintenant plus compact: au lieu d une ligne separee pour le maximum, il affiche directement `Une seule reponse` ou `Plusieurs reponses (max N)`.

Dans le resume personnel a droite de OMO, l app `Calendrier` remonte maintenant aussi les prochaines dates liees a la personne courante: dates d organisation, dates du holon d organisation, et dates des cercles ou roles ou cette personne est active, avec ouverture directe vers l evenement cible.

Le recap personnel de droite est maintenant invalide puis recharge correctement apres l enregistrement d un evenement dans l app `Calendrier`, ce qui evite de conserver un ancien cache apres creation ou modification.

Le filtre de ces dates reutilise maintenant directement la logique d appartenance des holons OMO, y compris pour les membres herites ou calcules dans les cercles, au lieu de reposer sur une deduction SQL plus limitee.

Dans ce meme recap, le bloc `Calendrier` suit maintenant aussi le contexte courant de l holarchie: au niveau organisation il reste global, mais dans un cercle, groupe ou role il se limite aux dates du holon courant et de ses descendants visibles.

La resolution du holon courant dans ce recap reutilise maintenant la meme verification que l app `Calendrier` via la racine structurelle, ce qui evite de perdre le contexte sur des holons derives qui ne portent pas directement `IDorganization`.

Dans le bloc `Team` du recap personnel, les celebrations sont maintenant limitees a trois cas ordonnes de facon previsible: nouveaux collaborateurs pendant 7 jours apres l arrivee, anniversaires professionnels pendant les 7 jours avant la date, et anniversaires personnels pendant les 7 jours avant la date.

Ce bloc `Team` suit maintenant aussi le contexte courant: dans un cercle, groupe ou role, il limite les anniversaires aux personnes visibles de ce contexte, au lieu de remonter tous les membres de l organisation.

L historique structurel OMO affiche maintenant les references de holons avec leur nom courant et leur type, par exemple `Tresorier (role)`, meme si le holon a ete renomme depuis l enregistrement. Les references de holons restent stockees sous forme de token type/id/libelle et deviennent cliquables dans le recap personnel et dans la popup d historique.

Dans cet historique, les references de holons templates invisibles restent affichees mais ne sont plus rendues cliquables, pour eviter des liens vers des elements non navigables.

Quand une reference d historique n est pas cliquable, elle n emprunte plus non plus le style visuel d un lien: elle reste simplement en gras.

Les references de holons dans l historique savent maintenant comparer le nom stocke a l epoque avec le nom actuel: elles peuvent afficher `anciennement ...` en cas de renommage, ou `supprime depuis` quand le holon n existe plus. Dans ce dernier cas, aucun lien n est propose.

Dans le resume `Decisions` de l espace personnel OMO, les lignes a `0` ne sont plus affichees. Le tri entre `consultations en cours` et `decisions a prendre` suit maintenant le statut reel du processus: `consultation` reste dans les consultations, et seule la phase `evaluation` remonte dans les decisions a prendre.

Les boutons `Ouvrir` des blocs de resume OMO peuvent maintenant forcer un scope de drawer a l ouverture. Depuis un cercle, groupe ou role, les blocs `Decisions`, `Documents`, `Calendrier` et `Team` ouvrent directement leur panneau en scope `descendants`, pour retrouver les elements listes dans le resume sans tomber sur une vue contextuelle vide.

Le bloc `Documents` du resume personnel reutilise maintenant la meme logique de chargement que l app `Documents` pour le scope `descendants`. Dans un holon non racine, il remonte donc aussi les documents visibles du holon courant et de ses descendants, y compris ceux partages a l organisation ou en public.

Dans l app `Documents` de OMO, le selecteur de tri distingue maintenant `Modification`, `Creation` et `Alphabetique`. On peut donc alterner entre un ordre chronologique par derniere activite et un ordre chronologique par date de creation, au lieu d un seul mode `Date`.

Quand le drawer `Documents` est deja ouvert dans OMO, un clic `Ouvrir` depuis le resume personnel avec un scope force ne recharge plus tout le drawer. L app bascule maintenant directement sa vue locale vers le scope demande, par exemple `descendants`, y compris en revenant d un hash detail vers `#documents`.

Dans le resume personnel OMO, le bloc `Documents - dernieres modifications` devient plus compact: chaque ligne affiche la date courte et le titre sur une meme ligne, avec une icone de visibilite a la place du libelle complet.

Dans l app `Decisions` de OMO, le filtre `Actif` ne remonte plus toutes les decisions simplement visibles. Il liste maintenant seulement les prises de decision non archivees ou la personne courante est reellement impliquee, comme auteur ou invite.

Depuis la racine organisationnelle de OMO, les boutons `Ouvrir` du resume personnel ne forcent plus un scope `descendants` inexistant. Ils basculent maintenant en `global`, qui y est equivalent, pour ouvrir correctement `Decisions`, `Documents`, `Calendrier` et `Team`.

Dans `Decisions`, un sous-holon n affiche plus les decisions purement rattachees a l organisation quand on est en scope `contextuel` ou `descendants`. Ces decisions sans holon restent reservees a la vue organisationnelle ou au scope `global`.

## 2026-06-25

Tous les e-mails systeme peuvent maintenant ajouter automatiquement un footer `Soutenir le projet sur Patreon` sur les instances ou Patreon est configure, mais ce rappel est omis des qu un compte Patreon connecte est implique comme expediteur logique ou destinataire du message.

Dans OMO, la leftbar et la popup `Gerer les applications` masquent maintenant automatiquement les apps dont la page cible n existe pas sur l instance courante, ce qui evite d afficher en prod des modules encore actifs seulement en test.

Dans OMO, les ouvertures de detail dans `Documents`, `Calendrier` et `Decisions` repoussent maintenant aussi un hash dedie par objet, avec support du format court `#documents-d12`, `#calendar-e12` et `#decision-d12`: un lien direct peut rouvrir l objet si l acces est autorise, et `back` ou `Fermer` reviennent a la liste de l app au lieu de laisser un drawer detail orphelin.

Dans l app `Equipe` de OMO, la liste contextuelle des membres d un cercle se base maintenant sur le meme helper que `getOrg.php`: elle re-affiche donc aussi les personnes remontees via les roles du cercle et via les liens de cercles specifiques, au lieu de se limiter aux seuls liens directs du cercle.

Dans la liste OMO des prises de decision, le menu `...` des cartes reste maintenant present apres avoir ouvert puis referme un scrutin: le rerendu via le composant partage rebranche aussi correctement les actions detaillees apres refresh.

Les exports OMO de prises de decision sans `gid` explicite n emportent maintenant plus seulement le bloc principal: les formats `CSV`, `JSON` et `XML` regroupent correctement tous les blocs actifs d un meme scrutin quand ils utilisent la meme methode.

Dans les parametres de bloc des scrutins OMO `Vote`, `Jugement majoritaire` et `Consentement`, une ponderation optionnelle des votes peut maintenant etre definie de facon generique avec une question et un petit editeur a lignes `coefficient + libelle`, incluant une base `1x` verrouillee, puis resumee dans le recapitulatif du bloc.

Dans les parcours de participation `Vote simple` et `Jugement majoritaire`, les participants peuvent maintenant choisir leur coefficient de ponderation via un selecteur segmente. Les resultats s affichent par defaut en version ponderee, avec une case a cocher par bloc pour reveler en dessous la comparaison non ponderee.

Un script SQL de demo local permet maintenant d injecter deux scrutins termines dedies aux tests de ponderation, l un en `Vote simple` et l autre en `Jugement majoritaire`, avec des participants de poids differents pour verifier visuellement les ecarts entre resultats ponderes et non ponderes.

Dans les resultats OMO de `Jugement majoritaire`, la comparaison `non ponderee` recompte maintenant bien une reponse pour une reponse, sans reutiliser par erreur l echelle interne de ponderation. Le script SQL de demo associe montre aussi une palette de mentions plus variee pour mieux lire les barres.

Dans les resultats OMO `Vote simple`, la vue principale applique maintenant bien les poids quand la ponderation est active. Dans `Jugement majoritaire`, les cartes separent aussi desormais le nombre brut de mentions et leur poids cumule, pour eviter de confondre `6 votes` avec un total pondere comme `2.5`.

Les blocs de comparaison `resultat non pondere` dans `Vote simple` et `Jugement majoritaire` restent maintenant vraiment masques tant que leur case n est pas cochee, y compris apres rerendu dynamique.

Dans l editeur de ponderation des votes, la ligne fixe `1x` reste verrouillee mais le badge visuel `Base 1x` a ete retire pour alleger l interface.

Dans ce meme editeur, le premier champ numerique verrouille de la ligne `1x` affiche maintenant `Reference` au lieu de `Coefficient`, pour mieux distinguer la base neutre des autres valeurs.

Les reglages de ponderation des votes se replient maintenant comme un petit accordion: seule la case d activation reste visible tant que la ponderation est desactivee. Dans le `Jugement majoritaire`, une case `Redefinir les mentions` masque ou revele de la meme facon l edition avancee de l echelle.

Dans cette edition des mentions du `Jugement majoritaire`, les libelles techniques `Mention 1`, `Mention 2`, etc. sont maintenant remplaces visuellement par `Mention` suivi d un petit rond colore reprenant la palette de la mention.

Dans les scrutins OMO deja prets mais pas encore termines, le menu `...` propose maintenant `Imprimer les codes QR`, avec une page imprimable qui genere un QR individuel par participant a partir du meme lien direct que celui envoye par e-mail.

Cette planche QR de scrutin met maintenant aussi le titre du scrutin, l organisation, l auteur et les dates utiles directement sur chaque carte, tout en forcant une vraie grille compacte d impression pour tenir au moins quatre QR sur une page A4.

Les fiches QR de scrutin adoptent maintenant une composition verticale type `A6`: QR en haut, informations participant en dessous, puis un bloc unique `Organisateur` qui regroupe organisation, contexte et auteur, sans afficher le statut temporaire du scrutin.

Quand un scrutin QR utilise le holon racine de type `organisation`, le bloc `Organisateur` masque maintenant aussi ce contexte s il reprend deja le meme libelle que l organisation, pour eviter tout doublon visuel.

## 2026-06-24

Avant connexion, la popup FAQ bascule maintenant automatiquement sur la portee `Global` quand aucun contexte organisation/holon n est disponible, ce qui re-affiche bien les FAQ generiques non rattachees a une organisation ou a un holon.

Depuis la topbar OMO sans contexte d organisation, le lien `Tutoriels` ouvre maintenant un catalogue public de parcours `isbasic`. Les cartes LMS et le viewer associe conservent ce mode `basic` sur leurs appels AJAX pour permettre aussi l ouverture des tutos hors orga.

Dans ce catalogue public LMS, la liste des missions anonymes recalcule maintenant correctement les dependances sans erreur SQL, y compris quand aucune mission n a encore ete terminee puis apres les premieres progressions locales.

## 2026-06-23

Le stockage LMS des `Questions` est maintenant explicitement separe du stockage `FAQ`: les migrations et le seed Docker creent des tables dediees `question`, `question_choice`, `mission_question` et `user_question_response` sans jamais renommer ni recycler les tables `faq*`.

L ancienne migration `2026-05-01-lms-questions.sql` est maintenant neutralisee: elle ne renomme plus `faq`, `faq_choice`, `mission_faq` ni `user_faq_response` vers les tables LMS `question*`, ce qui protege les bases DEV/PROF ou la FAQ reste stockee separement.

Une migration de rattrapage recree maintenant la table `faq` si une ancienne base locale n a garde que `question`, puis recopie automatiquement les entrees non rattachees aux missions LMS pour restaurer le contenu FAQ perdu apres l ancien renommage.

La popup FAQ ne plante plus si la base cible n a pas encore de table `faq`: elle s ouvre avec un message explicite, masque l ajout impossible, et attend simplement que les migrations soient appliquees.

La classe `FAQ` degrade maintenant proprement son tri sur les bases plus anciennes: si les colonnes `displayorder` ou `updated` manquent encore, les listes FAQ utilisent un ordre compatible au lieu de provoquer une erreur SQL.

Dans le LMS OMO, un parcours importe depuis une autre organisation n expose plus l action `Editer` et toute tentative directe d ouverture ou de sauvegarde est maintenant refusee cote serveur: seule l organisation proprietaire peut modifier son parcours.

Les editeurs OMO qui utilisent les widgets `sized-image-field` et `simple-html-field` attendent maintenant explicitement que leurs scripts AJAX soient prets avant le premier rendu. Les loaders d image de logo et de banniere apparaissent donc correctement des la premiere ouverture du panneau.

Dans l editeur OMO des holons de type organisation, la reouverture d un holon existant recharge maintenant correctement son nom, ses proprietes locales et son statut de partage comme modele, au lieu d afficher un formulaire vide de type `Nouveau`.

Les champs image recadrables de l editeur OMO attendent maintenant la fin effective du recadrage avant l envoi du formulaire. Les illustrations de holons et de modeles d organisation ne se perdent plus quand on valide juste apres avoir choisi ou ajuste une image.

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

Le menu `...` des scrutins proposes aux gestionnaires offre maintenant un export par mode de prise de decision. Une popup permet de choisir entre `CSV`, `JSON` et `XML`, avec une entree `PDF` deja visible mais laissee inactive pour la suite. Chaque mode (`vote`, `majority_judgment`, `consent`) genere son propre contenu d export dans son dossier de module, avec un `CSV` enrichi pour la reimportation minimale, et des `JSON/XML` resserres autour d un blueprint du scrutin et de ses resultats.

La creation d une nouvelle prise de decision peut maintenant repartir d un fichier d export `CSV`, `JSON` ou `XML`. L import reconstruit la structure globale du processus et de ses blocs en dispatchant chaque bloc vers le module cible (`vote`, `majority_judgment`, `consent`), sans jamais reimporter les participants, invitations, reponses ou resultats.

## Invitations Et Participation

La gestion des invitations a ete simplifiee et rendue plus robuste. Il est maintenant plus facile d ouvrir un scrutin a la participation sans invitation, de suivre les personnes ajoutees via un lien public, de gerer les acces par code ou par lien personnel, et de retrouver clairement qui participe vraiment au scrutin.

## Pages Publiques Et Partagees

Les pages publiques de participation et de partage ont ete harmonisees avec le reste d OMO. Elles reprennent une topbar commune, une aide mieux structuree, une navigation mobile plus propre, et des panneaux d information plus lisibles. Les comportements d accordeon, les entetes et les liens publics ont aussi ete affines pour etre plus naturels a utiliser.

## Documents Et Espace Personnel

Le module `Documents` a gagne en lisibilite et en souplesse, autant pour le rangement que pour l edition et le partage. En parallele, la page d accueil OMO peut maintenant afficher un espace personnel plus utile, adapte aux applications actives de l organisation.

## Acces, Authentification Et Admin

La gestion des acces a ete amelioree a plusieurs niveaux: demandes d acces a une organisation, modes admin, connexions plus souples selon le contexte, reinitialisation et gestion du mot de passe, ainsi qu un environnement local plus simple a administrer pour les tests et la configuration.

## Recherche, FAQ Et Outils Transverses

La recherche de topbar couvre mieux plusieurs applications et repose sur une base plus solide. La FAQ prend mieux en charge les votes, la mise en valeur des questions utiles, et un triple filtre `contextuel` / `descendants` / `global` aligne sur les autres visibilites OMO. Les icones de scope ont aussi ete remplacees dans les toggles partages et etendues au mode `descendants`. Les FAQ restent maintenant consultables meme quand une organisation n'a pas de holarchie, en retombant proprement sur les FAQ portees par l'organisation. L'app `Team` reprend maintenant le meme selecteur pour naviguer entre membres du holon courant, du sous-arbre, ou de toute l'organisation, avec une transition de curseur plus fluide comme dans `Calendrier` et `Decisions`, ainsi qu'une vue compacte pour balayer rapidement `nom / prenom / telephone / e-mail`. Plusieurs elements partages ont aussi ete harmonises pour limiter les incoherences entre modules.

## LMS Et Parcours

Dans le LMS integre a OMO, les cartes de parcours proposent maintenant une action de suppression ou de detachement selon le contexte. Une previsualisation annonce d'abord si l'action va seulement detacher le parcours ou le supprimer definitivement. Un parcours importe depuis une autre organisation ne peut jamais etre supprime par l'organisation courante: il est seulement detache. La suppression retire d'abord le rattachement a l'organisation courante, puis efface vraiment le parcours et ses missions, questions et devoirs devenus orphelins; si le parcours est encore utilise ailleurs, il est seulement detache sans casser les autres organisations qui l'ont importe.

Les prerequis LMS bloquent desormais l'ouverture du contenu sans vider la liste des parcours. Cela evite qu'un prerequis mal renseigne ou encore incomplet masque toute la vue, tout en gardant le controle d'acces au moment d'ouvrir le parcours.

Les parcours actuellement apportes par un pack rattache a l'organisation ne proposent plus d'action de detachement ou de suppression sur leur propre carte. L'interface et le backend demandent maintenant de detacher d'abord le pack parent pour eviter les incoherences.

Les editeurs de parcours voient maintenant aussi les parcours temporairement masques par des prerequis ou par une application desactivee. Ces cartes restent visibles en grise avec un indicateur de masquage, tandis que les utilisateurs standard continuent a ne voir que les parcours effectivement accessibles.

Les packs de parcours basculent maintenant vers un modele dynamique cote LMS: les parcours enfants ne sont plus materialises comme des rattachements directs a l'installation, mais resolus a la volee a partir des packs attaches. Les modifications d'un pack se repercutent ainsi automatiquement dans les organisations qui l'utilisent.

Dans le LMS, le mode admin d'organisation donne maintenant aussi les droits de creation et d'edition des parcours, meme si aucune structure de holons n'est encore en place pour porter explicitement ces permissions.

L'editeur de packs permet maintenant aussi de retirer un parcours deja ajoute depuis le menu `...`, et l'ordre defini dans le pack est mieux repris dans les affichages dynamiques du LMS.

L'editeur de parcours permet maintenant aussi de retirer une mission deja rattachee depuis le menu `...`, sans passer uniquement par le reordonnancement ou l'ajout.

Les popups OMO continuent d etre reajustees apres la suppression du padding global du body modal, avec un espacement local rajoute dans l editeur de templates de holons pour garder un contenu moins colle aux bords.

La popup de creation et d edition d organisation reprend elle aussi un padding local quand elle est ouverte dans la modale topbar, pour conserver ses cartes et actions avec un peu d air autour.

La meme popup `organization_create` expose maintenant aussi un vrai header de drawer borde a borde dans la topbar, avec le formulaire repousse dans un shell interne comme les autres popups harmonisees.

Depuis le hub OMO, l ouverture de creation d organisation repasse maintenant aussi par une route OMO dediee qui reouvre la modale topbar au chargement si besoin, au lieu de basculer directement sur la page popup en fallback.

Le header de `organization_create` remonte maintenant au-dessus des couches de carte geographique type Strada ou Leaflet, pour eviter qu une carte embarquee passe visuellement devant le bandeau.

Les controles Leaflet de cette meme popup, comme le zoom `+/-` et le footer d attribution, sont aussi explicitement rabaisses pour rester sous le header au scroll.

La popup de profil applique maintenant la meme protection sur ses cartes embarquees: le header sticky remonte au-dessus, et les couches Leaflet comme zoom et attribution restent sous le bandeau.

Dans l editeur de templates de holons, la barre sticky de sauvegarde vient maintenant jusqu au bord bas du panneau de formulaire, au lieu de rester visuellement prisonniere de la zone de marge interne.

## Calendrier, CardDAV Et CalDAV

Le calendrier OMO devient plus exploitable au quotidien avec plusieurs vues, une edition plus simple et un comportement plus stable. En complement, des points d entree CardDAV et CalDAV ont ete poses pour preparer les usages de synchronisation avec des outils externes.

## Stabilite Generale

Une partie importante du travail a aussi porte sur la fiabilite: meilleurs comportements apres recharge, correction de blocages silencieux, meilleure compatibilite des pages selon le contexte, et remise en coherence de certaines bases locales ou de demonstration pour eviter des erreurs au redemarrage.
- Le retrait d un parcours depuis un pack nettoie maintenant aussi les anciens liens directs herites dans les autres organisations, pour eviter qu un enfant supprime du pack reste visible apres import.
- Le catalogue d import LMS masque maintenant les parcours et packs dont l organisation proprietaire s est detachee, afin de ne plus proposer des contenus non maintenus.
- Le catalogue d import LMS distingue maintenant visuellement les packs des parcours simples et affiche pour les packs leur nombre de parcours au lieu du nombre de missions.
- L editeur de mission permet maintenant de definir des prerequis entre missions d un meme parcours, avec ajout et retrait depuis l interface LMS.
- Le bloc des prerequis dans l editeur de mission reprend maintenant la presentation visuelle de l editeur de parcours, avec cartes et picker harmonises.
- L ouverture d un pack dans le LMS affiche maintenant ses parcours avec les memes cartes visuelles que la page d accueil, incluant image et progression.
- La vue detail d un pack reutilise maintenant le meme ratio d image et le meme cercle de progression chiffre que les cartes du catalogue LMS.
- L editeur de mission permet maintenant de modifier aussi le nom du groupe stocke sur le lien parcours mission.
- 2026-07-02 : LMS missions acceptent maintenant Vimeo, YouTube et les URLs/player iframe Infomaniak VOD, y compris les liens `share` et les iframes `embed`, via un helper commun de conversion en embed. Le champ `mission.video` passe a 1000 caracteres pour accepter aussi des URLs longues ou un code iframe colle.
