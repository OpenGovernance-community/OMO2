# Journal Des Nouveautes

Ce fichier garde une vue d ensemble courte des evolutions recentes, avec un angle plus fonctionnel que technique.

## 2026-07-28

- L editeur de templates de holons est maintenant global depuis la racine: il affiche toutes les declinaisons et heritages sans choix de contexte, la navigation ne recharge plus les parametres, et ses deux colonnes defilent independamment.
- L editeur de templates de holons utilise maintenant toute la surface du panneau et permet d ajuster la largeur de ses deux colonnes avec un separateur vertical. Les barres de defilement fines sont maintenant appliquees dans toute l interface OMO.
- Les apercus d icone et de banniere de l editeur de templates suivent maintenant le modele selectionne et rechargent correctement les illustrations enregistrees.
- L icone effective d un holon est maintenant affichee devant son nom dans le panneau de contexte lorsqu elle est definie.
- Les personnes en charge affichees dans le panneau de contexte sont maintenant mises en evidence par un contour blanc et un halo sombre discret.
- L editeur de template ouvert depuis le panneau de contexte utilise maintenant toute la largeur disponible en mode compact.
- Les verrous d icone et de banniere d un template restent des regles pour ses instances sans bloquer l edition des illustrations du template lui-meme.
- Le seed Docker a ete regenere depuis la base locale actuelle apres reinitialisation. Il contient les donnees courantes et les 137 migrations enregistrees dans `sql_migration`, avec le nom actuel de la migration d heritage des admins.
- L import d une organisation OMO1 peut maintenant etre cale sur un modele d organisation: chaque template structurel importe peut etre associe manuellement a son equivalent du modele, y compris lorsqu il est defini plus bas dans la holarchie. Les roles associes reprennent alors ses droits et sa definition, tout en conservant leur nom importe lorsque le nom du template cible n est pas verrouille; les proprietes suivent l ordre du template cible, y compris dans la leftbar, et les alias RDE et Raison d etre sont reconnus comme une meme propriete. Les templates non associes restent importes tels quels. Lorsqu une propriete aurait besoin d une conversion ambigue, son format source est conserve pour ne pas perdre les donnees existantes.
- Dans l editeur de templates de holons, l entete des droits et les onglets Membres et Admins restent visibles en haut de la zone de defilement de la liste des permissions.
- Les droits sont maintenant classes par themes dans les editeurs de templates et de holons. Les titres de groupes restent visibles pendant le defilement de chaque liste.
- Les bornes minimum et maximum d admins des templates peuvent maintenant rester vides pour heriter de leur ancetre. Un verrou pose sur une borne est applique a toute la descendance.
- Les checkbox et boutons radio des champs de l editeur de templates ne recoivent plus le style reserve aux champs texte, afin de conserver une taille normale.

## 2026-07-27

- Correction du chargement des traductions du panneau Lexique afin de respecter la signature du chargeur de bundles OMO.
- Un lexique propre a chaque organisation permet maintenant de personnaliser les termes Tension et Admin. Il est editable depuis une nouvelle carte des parametres; le menu de profil conserve le libelle fixe Admin d organisation.
- La popup d ajout d une personne a un holon permet maintenant de la definir comme admin. Le droit est applique immediatement pour un membre actif ou memorise dans l invitation jusqu a sa validation.
- Les permissions des holons peuvent maintenant etre definies separement pour les membres et les admins. Un membre admin cumule les droits generaux et les droits admin de son contexte, tandis qu un membre normal ne recoit que les droits generaux.
- Les templates de holons peuvent maintenant definir un nombre minimum et maximum d admins, chacun verrouillable ou redefinissable par une instance. Le minimum est requis avant l ajout d un membre normal; le dernier admin peut toutefois etre retire lorsque le role devient vide.
- Les admins directs du holon courant sont maintenant places en tete de sa liste de membres et leur vignette est mise en evidence, sans confondre les admins d un role enfant ou de l organisation.
- Un template de role peut maintenant etre marque Admin parent: les admins de ses instances, y compris les roles templates specialises, deviennent aussi admins du cercle parent, pour les droits et la liste de membres.
- Le terme Admin du lexique est maintenant repris dans la liste des membres et les ecrans de configuration des holons.
- TEAM reprend aussi ce terme dans les badges, les actions et les confirmations lies au statut de membre admin.
- L editeur de holon permet maintenant d ajouter, modifier et retirer des proprietes locales, avec nom, format, type de liste et valeur. Ces actions utilisent les droits de proprietes de holon et restent distinctes des proprietes heritees du template.
- La fenetre Inserer un lien de Summernote adopte maintenant le theme sombre, y compris ses champs et ses actions espacees du bord, afin de conserver un contraste lisible.
- Les droits distinguent maintenant les proprietes definies par un template de celles ajoutees directement sur un holon: ajout, modification et retrait disposent chacun de leur droit contextuel.
- Une nouvelle page publique `index2.php` presente Open My Organization 2 sous l angle de la maturite organisationnelle. Elle detaille les principes, fonctionnalites, usages, possibilites de contribution et parcours de demonstration dans une mise en page responsive et accessible.
- Les proprietes de type liste peuvent maintenant utiliser le type Autorite. Depuis la fiche d un holon, ce champ cree exclusivement de nouvelles autorites, rattachees au holon et a une autorite parente existante; le holon Organisation peut aussi creer les autorites racines sans parent. Le detail du holon les affiche ensuite sous forme de liste.
- Les autorites affichees dans le detail d un holon indiquent maintenant leur nombre de delegations directes. Un clic sur leur nom affiche uniquement cette premiere liste d autorites deleguees.
- La liste deroulee des delegations d autorite indique aussi le holon responsable de chaque autorite deleguee.
- Le formulaire Nouvelle regle permet maintenant de rattacher la regle a une autorite directement confiee au holon courant, ou de la conserver locale a ce holon.
- Les autorites disposent maintenant d une description facultative, renseignee a leur creation. Leur detail affiche l autorite dont elles heritent, leur description et leurs delegations directes.
- L historique des proprietes remplace maintenant les identifiants bruts des projets et autorites par leurs libelles, et produit des apercus lisibles pour les listes et contenus HTML.
- Dans les editeurs de proprietes, une autorite existante peut maintenant etre ouverte par un clic sur son nom, modifiee puis enregistree. La croix la supprime au prochain enregistrement; les delegations et regles restent rattachees a son parent lorsque cela est possible. Les modifications et suppressions disposent d entrees d historique explicites.
- L editeur de holon utilise maintenant les notifications centralisees de l application pour signaler ses erreurs d enregistrement, avec son message local uniquement comme solution de repli hors de l interface OMO.
- Les evenements d historique lies aux autorites sont maintenant rattaches explicitement au holon responsable, afin qu ils apparaissent dans l historique de sa fiche.
- Les horodatages de l historique sont enregistres explicitement en UTC puis affiches dans le fuseau horaire detecte dans le navigateur de chaque utilisateur.
- La suppression d une autorite dans les editeurs reste desormais visible avant enregistrement : la ligne est grisee et barree, et la meme croix permet d annuler cette suppression.
- Les details des historiques de listes conservent maintenant les libelles des projets, holons et autorites au moment de la modification, au lieu d afficher leurs identifiants techniques.
- Le detail d une modification d autorite affiche maintenant explicitement les anciennes et nouvelles valeurs du libelle, de la description et de l autorite parente.
- Le choix d une autorite parente est maintenant limite aux autorites directement confiees au holon parent, avec une liste reduite a leurs seuls libelles.
- La suppression d une autorite demande maintenant explicitement le devenir de l autorite, de ses sous-autorites et des regles attachees. Les elements conserves remontent au holon parent et a l autorite survivante la plus proche; les regles ainsi deplacees sont immediatement a revoir et expirent au plus tard deux mois plus tard.
- Les choix de suppression affichent entre parentheses le nombre de sous-autorites et de regles effectivement concernees, recalcule selon les options selectionnees; les blocs sans element concerne ne sont pas affiches.
- Lors du retrait d une autorite, la remontee au holon parent est maintenant le choix par defaut afin de privilegier la conservation des delegations.
- La remontee d une autorite deplace aussi sa reference de la propriete Autorite vers le holon parent, afin que son affichage et son rattachement de donnees restent coherents.
- La creation d une autorite commence maintenant par le choix de son parent et distingue delegation partielle et complete. Une delegation complete transmet toute la branche; une coquille en italique conserve le chemin lorsque le deplacement direct creerait un saut dans l arbre.
- Les autorites et sous-autorites transmises par delegation complete restent toutes referencees dans la propriete du holon receveur apres l enregistrement du formulaire.
- Lorsqu une delegation complete cree une coquille, ses regles sont transferees vers l autorite active. La coquille apparait en italique avec le statut deleguee, sans compteur de delegation.
- Dans le detail d un holon, une branche d autorites detenue par ce meme holon est compacte en une seule autorite racine. Un clic affiche ensuite la hierarchie interne apres la description et avant les delegations vers d autres holons.
- La liste du reglement affiche maintenant le holon, l autorite eventuelle, ainsi que les dates et personnes de creation et de derniere modification des regles. Ces personnes sont desormais enregistrees directement sur chaque nouvelle regle et modification.
- Les cartes du reglement mettent maintenant le contenu normatif de la regle au premier plan, puis son intention. Les informations de suivi et de tracabilite sont repliees dans un volet discret.
- Le Reglement propose maintenant le meme filtre compact de perimetre que les Projets : une barre pleine largeur sous le titre reunit la capsule de vue active, une recherche rapide et le menu Local, Enfants directs ou Descendants, avec les actions Appliquer et Enregistrer cette vue. Il affiche les regles effectivement applicables a au moins un holon du perimetre choisi.
- Le Reglement propose maintenant aussi les capsules Ordre et Regroupement, memorisables avec le perimetre. Les regles peuvent etre ordonnees alphabetiquement, par creation ou par modification, puis presentees dans l arbre des holons ou des autorites avec des titres numerotes et sticky.
- Les groupes imbriques du Reglement sont maintenant sans bordure. Le decalage de leurs titres sticky est calcule dans le navigateur selon la hauteur reelle de chaque titre parent, y compris lorsqu un libelle revient sur plusieurs lignes, et prime correctement sur le positionnement generique des listes.
- Le menu des options d affichage du Reglement reste maintenant au-dessus des titres de rubrique sticky lorsqu il est ouvert.
- Le regroupement du Reglement par autorite ignore maintenant les coquilles des delegations completes. Les autorites actives sont raccordees visuellement a leur premier ancetre actif, sans niveau de titre artificiel.
- L editeur d autorites de l Organisation propose maintenant Sans racine. Ce choix affiche directement les champs de libelle et description pour creer ou modifier une autorite racine, meme lorsqu aucune autorite parente n existe encore.
- La suppression d une autorite issue d une delegation complete annule maintenant cette delegation : la coquille source est reactivee et recueille les sous-autorites ou regles conservees, au lieu de rester inactive.
- Lors de la suppression d un holon, ses autorites sont maintenant rattachees a son holon parent avec leurs branches et leurs references de proprietes. Les regles locales sont elles aussi conservees sur le parent; une delegation complete est annulee en reactivant sa coquille source.

- L application Projets regroupe maintenant ses filtres de contexte, attribution, ordre et representation dans un champ compact : les choix actifs apparaissent en capsules et ouvrent un panneau commun vertical. Appliquer modifie la vue courante sans la memoriser, tandis que Enregistrer cette vue en fait la vue de base du holon pour la prochaine ouverture. Le champ propose aussi une recherche rapide locale qui masque correctement les projets sans correspondance.
- L application Projets propose une premiere vue Gantt. Elle affiche les projets du perimetre sous forme d arbre, avec les sous-projets immediatement apres leur parent. Chaque borne de planification absente peut heriter de celle du parent ; les projets sans date effective restent visibles sans barre.
- La vue Gantt garde maintenant les libelles de projets sur un fond opaque pendant le defilement. Le libelle compact de date reste dans sa barre, se fixe a cote de la colonne projet tant que la barre le permet, puis repart avec sa date de fin. La vue se positionne aussi automatiquement autour de la date du jour a l ouverture.
- Les plages de dates du Gantt utilisent maintenant un format compact qui ne repete pas inutilement le mois ou l annee. Leur libelle reste limite a la largeur reelle de la barre et se termine par des points de suspension quand l espace manque.
- L echelle du Gantt conserve maintenant au minimum 16 pixels par jour. Une barre d une seule journee occupe ainsi exactement la largeur d un jour, sans largeur minimale arbitraire.
- Dans le Gantt, un clic sur la zone vide d une ligne recentre sa barre lorsqu elle est hors ecran. Les clics sur la barre datee ou sur la cellule gauche continuent d ouvrir le detail du projet.
- Le Gantt met maintenant en evidence en rouge la cellule gauche des projets non termines dont la date de fin effective, propre ou heritee, est deja passee.
- Dans la vue en liste triee par planification, les projets termines ne sont plus classes en retard lorsque leur date de fin est passee. Ils sont regroupes dans une section Termines placee tout en bas.
- Le bouton Appliquer des filtres Projets conserve maintenant une vue temporaire prioritaire pendant toute la session de l onglet, y compris lors de la navigation. Enregistrer cette vue reste le seul moyen de remplacer la vue durable du holon et efface alors la variante temporaire.

## 2026-07-26

La recherche globale peut maintenant inclure les applications Projets et Indicateurs lorsqu elles sont actives. Leurs resultats respectent la periode et la visibilite du contexte, affichent un extrait contenant le terme trouve, puis ouvrent directement le detail correspondant par navigation hash. La frequence des indicateurs est traduite dans la langue de l interface.

- Le menu Parametres propose maintenant un export JSON complet de l organisation, avec selection des modules, conservation de la structure et compatibilite avec l import OMO 2.
- L import d organisation restaure maintenant les liens des proprietes de type liste de projets vers les projets et sous-projets importes.
- Les proprietes `Strategie` de type HTML et liste conservent maintenant leur format composite lors de l import, au lieu d etre forcees en HTML simple.
- L import d organisation repare maintenant les bases ou la migration des invitations etait enregistree sans avoir ajoute la colonne `request_origin`; les erreurs et avertissements de la popup passent aussi par les notifications centralisees.
- L import d organisation declenche maintenant la maintenance cron simulee afin d activer immediatement les projets dus des checklistes importees.
- Le seed Docker a ete regenere depuis la base MariaDB actuelle du conteneur. Il contient maintenant l etat courant des donnees ainsi que les 129 migrations enregistrees comme appliquees, pour permettre une restauration fidele apres recreation du volume.
- Les FAQ peuvent maintenant etre liees de facon optionnelle a une application OMO. Le choix est regroupe dans le bloc Attachement et reserve aux administrateurs FAQ, comme le rattachement a un parcours; la base stocke ce lien, et les FAQ ainsi rattachees sont masquees quand l application correspondante n est pas activee dans l organisation courante.
- La migration des FAQ repare aussi les bases dont `IDparcours` avait ete enregistree sans etre creee. Le seed Docker contient desormais ce schema et son historique de migration coherents.

## 2026-07-25

- Les libellés français des indicateurs utilisent maintenant les apostrophes et accents manquants dans les actions, formulaires, messages d erreur et états vides.
- Le module Projets utilise maintenant son bundle de traduction pour les vues, les formulaires, les reponses JSON et les libelles d accessibilite; ses textes francais ont aussi ete corriges avec les accents et la ponctuation attendus.
- Un systeme generique de notifications est maintenant utilise par OMO et ses drawers : les sauvegardes des parametres et plusieurs erreurs d action remontent en rouge ou en vert, restent affichees 10 secondes, se mettent en pause au survol, s empilent et peuvent etre fermees individuellement.
- Dans la creation d un indicateur, le choix de la frequence remplit maintenant immediatement le selecteur du moment attendu, par exemple les jours du mois pour une frequence mensuelle.
- Le logo de l organisation systeme #1 utilise maintenant l image PNG versionnee dans `/img`, avec une migration SQL reproductible.
- La banniere de l organisation systeme #1 utilise maintenant l image PNG versionnee dans `/img`, redimensionnee a 1024 pixels de large.
- Le contour du holon actuellement selectionne dans la mini-navigation de la structure est maintenant limite a 2 pixels.
- La banniere de l organisation exemple #2 utilise maintenant l image PNG fournie, versionnee dans `/img` et redimensionnee a 1024 pixels de large.
- Le logo de l organisation exemple #2 utilise maintenant l image PNG fournie, versionnee dans `/img`.
- Une première maquette de statistiques globales est disponible dans le fichier racine `stats.php`, volontairement non référencé depuis l application.
- Les dates de connexion sont maintenant séparées : la date globale est actualisée à la création ou à la restauration d une session, tandis que la date du rattachement est actualisée à l ouverture effective d une organisation.
- La page racine `stats.php` affiche maintenant aussi le total des holons, les holons modifiés récemment et un graphique double des créations et modifications de holons.

## 2026-07-24

- La mise a jour automatique du site detaille maintenant les fichiers modifies localement, signale ceux egalement modifies par le patch distant et permet a l admin de lancer explicitement une synchronisation forcee. Cette action remplace les modifications locales suivies par Git, tandis que les fichiers non suivis sont conserves.
- Les controles de validite et de revision des rules declarent maintenant explicitement leur date nullable, ce qui elimine leurs avertissements de depreciation sous PHP 8.5.
- Le compte administrateur de demonstration de la base Docker utilise maintenant le mot de passe `1234`. L assistant d installation serveur continue de definir le mot de passe choisi lors de sa configuration.
- Le seed de base de donnees a ete regenere depuis la base locale de demonstration. Les reinitialisations Docker et les premieres installations serveur importent le meme snapshot complet, avec les nouvelles donnees de demo.
- Le seed Docker embarque maintenant aussi l historique `sql_migration` correspondant a son schema. Une nouvelle installation ne tente donc plus de rejouer des migrations deja presentes dans le snapshot.
- L organisation systeme #1 est maintenant protegee contre toute suppression. Ses administrateurs ne peuvent pas non plus la quitter; sa carte OMO remplace ces actions par une information sur son usage pour les messages et les tutoriels de base.
- Les modeles de holons visibles et uniques comptent maintenant comme leur propre instance dans leur cercle. Ils ne peuvent donc plus etre proposes une seconde fois, et les controles de suppression obligatoire reconnaissent aussi cette instance originale. Le selecteur de creation affiche uniquement le nom du modele, avec le nom du holon parent entre parentheses seulement lorsque plusieurs modeles portent le meme nom.
- La page d accueil presente maintenant la video Vimeo du projet juste sous le lien de decouverte de la nouvelle version.
- Les cartes Kanban et les lignes de la vue Liste des projets affichent maintenant leur pastille P1 a P5 juste a cote de la taille S a XXL. Ces pastilles reutilisent le code couleur de priorite deja present dans les sous-projets et les vues Structure.
- Le curseur de priorite suit maintenant l ordre operationnel 0, P5, P4, P3, P2, P1. La priorite est affichee uniformement sous la forme P1 a P5, sans notation sur cinq.
- L ancienne notion d importance est renommee Importance strategique dans les projets, leurs reglages et les elements de checklist, afin de la distinguer clairement de la priorite.
- Une importance strategique vide herite maintenant de la derniere valeur renseignee dans la chaine de projets. Si aucune valeur n est renseignee dans cette chaine, le score est 0. La valeur neutre configurable des projets racines a ete supprimee.
- La penalite de profondeur holarchique est maintenant appliquee une seule fois au projet racine d une chaine. Les descendants conservent donc le score de leur parent lorsqu ils ont la meme importance strategique.
- Le classement Planification separe maintenant les projets en retard uniquement lorsque leur fin est depassee, les projets en cours lorsque leur debut est atteint et leur fin encore ouverte, les echeances futures (demain, apres-demain, semaines suivantes) et les projets sans planification.
- Le detail d un projet ne repete plus l entete du drawer. Lorsqu il appartient a une hierarchie, un fil d Ariane cliquable permet maintenant de remonter vers ses projets parents; les longues chaines sont compactees entre le premier et le dernier projet, puis peuvent etre depliees.
- L option de tri Importance des projets utilise maintenant l importance calculee, de la plus elevee a la plus faible. Les tris Priorite, Planification et Holon conservent leur comportement propre.
- Les projets disposent maintenant d une importance calculee, persistante et non editable. Le score normalise combine l importance declaree, la chaine de projets et la profondeur holarchique; une branche rattachee a un projet du holon racine reste exempte de penalite de profondeur. Toute modification d importance, de parent ou de holon recalcule seulement le projet concerne et ses descendants, tandis que les deplacements de holons et les reglages globaux recalculent les branches concernees. Les projets existants sont initialises une seule fois de facon idempotente a la premiere ouverture de l application apres la migration.
- L application Projets possede maintenant son panneau Parametres, accessible dans les Parametres de l organisation pour les administrateurs. Il permet de regler le poids du parent et la penalite de profondeur; un vrai changement recalcule tous les projets de l organisation.
- La mini-vue de structure sous le panneau de contexte peut maintenant etre repliee entierement. Son separateur reste accessible en bas et la rouvre directement a sa hauteur minimale apres le franchissement du seuil.
- Les listes de projets associees aux proprietes affichent maintenant la pastille du statut propre a chaque projet. Seuls les projets ayant des enfants affichent une barre de repartition; cette barre charge et deploie recursivement chaque niveau de sous-projets a la demande.
- Les checklists de processus inserees dans un point de PV remplacent le libelle Instances en cours par une indication en italique lorsqu aucune instance n est en cours.
- Les groupes d indicateurs inseres dans un point de PV conservent maintenant leur rendu graphique et leur statut apres la sauvegarde du point.
- Le type Normal a ete retire des points de PV. Les imports OMO1 et les anciennes donnees le convertissent maintenant en Consultation.
- Le seed Docker integre maintenant le suivi et les verrous d edition des points de PV. La creation et la sauvegarde des points utilisent a nouveau les colonnes attendues par l editeur.
- Le seed Docker inclut desormais les regles de visibilite des objets. La creation d un evenement avec document associe enregistre a nouveau les deux regles de visibilite du document.
- L editeur de projet permet maintenant de choisir le cercle ou le role auquel le projet est confie, avec le selecteur circulaire partage et le holon courant preselectionne.
- Le detail d un projet permet maintenant de creer directement un sous-projet avec son parent deja choisi, ou d attacher un projet sans parent via le selecteur circulaire de structure.
- L editeur de projet affiche a nouveau directement le choix de taille S, M, L, XL ou XXL dans sa section Planification.
- Les plafonds d indicateur se definissent maintenant par une valeur unique, sans dates ni points de trajectoire. Leur ligne couvre toute la periode effectivement affichee par le graphique; une trajectoire horizontale sur une periode reste disponible via le mode Objectif ou trajectoire.
- Chaque indicateur peut definir une valeur basse facultative pour son echelle. Cette valeur est incluse dans les bornes du graphique et devient un repere discret lorsqu elle se trouve entre les mesures visibles.
- Les groupes d indicateurs reprennent ces deux reglages : plafond unique sans dates et valeur basse facultative pour l echelle et son repere visuel.
- Les echelles des indicateurs et de leurs groupes prennent leur point de depart sur la plus petite valeur visible, avec une graduation arrondie et lisible. Une valeur basse definie sert de borne inferieure, sauf lorsqu une mesure ou une reference est plus basse.
- Les miniatures et les graphiques de detail etendent a nouveau leurs mesures sur toute la largeur utile lorsqu aucune trajectoire de reference ne fixe une periode explicite.

## 2026-07-23

- L initialisation Docker applique maintenant les evolutions Document et Evenement dans le meme ordre que les migrations versionnees : les types et les colonnes d edition sont crees avant le workflow de PV, et les evenements avant leurs invitations. Une base locale vierge peut ainsi etre recreee depuis le seed sans erreur de colonne manquante ou de cle etrangere.
- Le seed Docker est maintenant autonome : il contient le schema et les donnees de demonstration complets, y compris les evolutions Document, PV, indicateurs, projets, checklistes et reglements. Une nouvelle instance ne depend plus d un rejeu de migrations separe.
- Chaque element de checklist, y compris dans un processus lance a la demande, peut maintenant definir son affichage anticipe et son delai de realisation. L instance cree un projet planifie a la date de reference, le rend visible a la date calculee et lui attribue sa date limite. Les elements recurrents conservent les memes valeurs dans leur planification existante.
- L editeur des modeles de holons permet maintenant d effacer un modele existant depuis son formulaire. Le bouton rouge reprend l icone corbeille de l application, demande confirmation et supprime aussi les sous-modeles rattaches.
- L application Reglement affiche les rules applicables au contexte courant et permet d en creer une locale avec son intention, son contenu, sa date de requestionnement et son echeance.
- Les autorites et les rules disposent maintenant de leurs objets de donnees et de leur schema. Une autorite forme un arbre, est confiee a un holon et peut etre declinee vers des domaines plus precis; une rule porte un titre, une intention, un contenu HTML, une date de requestionnement, une date d echeance et une portee globale, descendante ou locale. Elle est rattachee soit a une autorite, soit directement a un holon. La suppression d une autorite rebranche ses enfants et ses rules sur son parent afin de ne perdre aucune rule.

## 2026-07-22

- Le formulaire Projet a ete refait sans editeur generique : les membres proposes comme responsables sont limites aux personnes actives de l organisation, le projet parent se choisit avec la navigation circulaire partagee de la structure et une recherche, et la saisie regroupe clairement planification, niveaux d attention et options secondaires.
- L import OMO 1 prend maintenant les proces-verbaux : chaque reunion exportee avec son historique devient un document PV lie a l evenement cree. Le titre du PV reprend la reunion et sa date; les mises en forme HTML des titres historiques sont conservees dans le contenu. Les entrees associees a une meme tension sont rassemblees dans un point unique portant le titre de la tension, avec les textes et actions lies dans son contenu. Les points d information OMO 1 restent identifies; les autres deviennent des points normaux.
- Les points de PV issus d une tension reprennent maintenant l auteur de la tension OMO 1. Lorsque cette personne n est pas importee, le point reste sans auteur au lieu d etre attribue par erreur a la personne qui lance l import.
- Les projets archives dans OMO 1 sont maintenant importes termines et inactifs dans OMO 2. Ils restent donc conserves dans les donnees, sans apparaitre dans les listes de projets actives.
- Les checklistes OMO 1 sont maintenant exportees dans le format de containers OMO 2 et importees avec leurs projets modeles. Les checklistes a recurrence sont regroupees dans un container par role, avec une recurrence propre a chaque element; les checklistes sans declencheur temporel deviennent des checklistes individuelles a la demande. Leur historique de validation OMO 1 n est pas repris.
- Une organisation creee par import OMO 1 reprend desormais la date de sa donnee historique la plus ancienne parmi les modules selectionnes. La recherche peut donc proposer toute la periode importee, au lieu d etre limitee a la date du jour de l import.
- La propriete `Strategie` des structures OMO 1 est maintenant creee au format HTML. L import reconnait aussi ce champ dans les exports deja produits et assainit son contenu HTML avant enregistrement.
- La carte `Importer une organisation` utilise maintenant son illustration dediee de chantier, dans le meme format que la carte de creation.
- La popup d import d organisation est accessible depuis le repertoire, y compris lorsqu aucune organisation courante n est encore ouverte. Ses endpoints restent proteges par la connexion utilisateur.
- L export OMO 1 vers OMO 2 exige maintenant que l utilisateur connecte soit membre de l organisation demandee.
- Lors d un import OMO 1, les membres ajoutes recoivent maintenant une invitation par e-mail et restent inactifs dans la nouvelle organisation jusqu a leur acceptation. Leurs roles et les references vers les projets, documents et autres objets sont deja conserves, puis leurs roles sont actives avec l invitation.
- Le refus d une invitation ou le retrait d un membre dissocie maintenant son vrai compte de l organisation concernee. Un compte historique local, inactif et non consultable conserve son nom pour les projets termines, valeurs d indicateur, evenements passes, documents archives, PV, tensions et historique; les responsabilites encore actives sont retirees.
- Les nouveaux documents recoivent par defaut un droit d edition limite au role de leur contexte. Sans role cible, le droit reste reserve au proprietaire pour eviter une regle de role sans cible. L import OMO 1 enregistre aussi explicitement ce droit sur chaque document importe et reutilise les comptes existants par adresse e-mail, sans en creer un nouveau en cas d ambiguite.
- Les documents OMO 1 lies a un projet ne recoivent plus de holon lors de l import. Leur lien `project_document` est conserve, ce qui les reserve au projet au lieu de les afficher aussi dans le contexte de structure.
- L import des indicateurs OMO 1 reprend maintenant leur recurrence exploitable : quotidien, hebdomadaire, mensuel, trimestriel, semestriel ou annuel. Quand OMO 1 definissait un declencheur, le jour attendu est aussi conserve; sinon OMO2 conserve la cadence relative a la derniere mesure, comme OMO 1.
- Les fichiers joints issus d OMO 1 sont maintenant importes comme des telechargements sans fichier distant. Documents les signale par une carte et une capsule rouges `Fichier absent`, afin de reperer clairement les fichiers a transferer manuellement.
- Les reunions OMO 1 sans titre reprennent maintenant un nom derive de leur type lors de l import, par exemple `Reunion operationnelle` ou `Reunion de gouvernance`. La portee `Enfants directs` traverse aussi les groupes purement visuels : leurs contenus sont donc traites comme des enfants directs du contexte qui les englobe.
- La page d accueil OMO propose maintenant une carte `Importer une organisation`. Elle ouvre un assistant qui cree une nouvelle organisation depuis un export JSON OMO 1 et laisse choisir les membres, documents, projets, taches, indicateurs et calendrier a reprendre. La structure reste obligatoire; les taches OMO 1 sont converties en projets enfants. Les fichiers binaires historiques ne sont pas recopies automatiquement et sont signales dans les documents importes.
- Les checklists Conteneur peuvent maintenant porter une recurrence sur chacun de leurs elements independants. Chaque echeance cree directement un projet simple affecte au role de l element, sans creer de projet parent de conteneur; les occurrences sont conservees pour empecher les doublons. Les frequences et les moments sont identiques a ceux des indicateurs et le traitement est integre a la maintenance OMO lors du retour au premier plan.
- Les projets generes par ces occurrences ajoutent maintenant leur periode au titre (par exemple `Cloture comptable - 1er trimestre 2026` ou `Salaires - 25 juillet 2026`) afin de distinguer les echeances successives.
- Les elements recurrents permettent maintenant d indiquer une anticipation d affichage et un delai de realisation. Le projet peut ainsi apparaitre avant sa date planifiee et recevoir automatiquement une date limite.
- Les projets issus des elements de checklist, y compris dans les checklists lancees a la demande et les projets termines non archives, sont maintenant representes directement dans chaque bloc par des barres colorees selon leur statut. Leur infobulle indique les dates planifiee et limite; le nombre d occurrences de conteneur actives apparait aussi dans la liste principale.
- Chaque barre d occurrence est limitee a 100 pixels et se reduit avec les autres barres lorsque l espace disponible manque.
- Les couleurs des statuts de projet sont maintenant declarees comme variables partagees; les barres Checklist et les vues Projets utilisent la meme palette, avec le statut A verifier en violet.
- Cliquer une barre d occurrence active ouvre maintenant le projet associe dans l application Projets via la navigation par hash.
- Les infobulles des barres de projet indiquent maintenant aussi le libelle du statut, en plus des dates planifiee et limite.
- Les projets en retard affichent maintenant une icone d alerte superposee au debut de leur barre, tout en conservant la couleur de leur statut.
- Le detail d une checklist utilise maintenant une route hash `checklist-c<ID>`. Apres l ouverture d un projet lie, le bouton precedent du navigateur revient donc directement a la checklist ouverte.
- Le sous-drawer Checklist reprend maintenant la largeur partagee des autres applications OMO, sans largeur locale qui pouvait depasser son panneau parent.
- L activation manuelle d une checklist permet maintenant de nommer son instance. Le nom est pre-rempli avec celui du processus et devient le titre modifiable du projet racine cree.
- Les PV proposent maintenant un bloc Checklist. Le selecteur contextuel permet de choisir les processus a suivre, puis le bloc affiche une barre de statuts pour leurs elements recurrents ou leurs instances ouvertes, avec un avertissement en cas de retard. Chaque instance est affichee par son nom, sa capsule et sa barre ponderee par la taille de ses elements, chargee automatiquement. Un clic revele la liste : une tache simple garde sa capsule, tandis qu un element compose de sous-projets affiche leur barre de statut partagee. Les checklists Conteneur offrent le meme depliage sur leur barre permanente d elements recurrents.
- Le resume PV reconnait aussi les declencheurs Conteneur volontairement desactives : leurs elements recurrents ne sont donc plus confondus avec une checklist sans instance.
- La revue PV d une checklist Conteneur affiche maintenant toutes les occurrences encore actives de chaque element recurrent, y compris les echeances en retard, et non plus seulement la plus recente.
- Les lignes detaillees de revue Checklist dans un PV indiquent maintenant aussi le role ou contexte de chaque element et la personne responsable lorsqu elle est definie.
- Les metadonnees role et responsable des lignes Checklist reprennent la presentation compacte des sous-projets, a cote du titre lorsque la largeur le permet.
- L editeur du PV peut maintenant valider et archiver directement un projet de checklist depuis sa ligne de revue. Le projet et tous ses sous-projets actifs passent a Termine puis sont archives; la revue et le tableau Projects se rafraichissent, sans conserver les projets archives dans le detail ni refermer la liste alors deployee. Cette action reste absente pour les auteurs de points et les autres participants.
- Lorsqu un projet de checklist passe a Termine, les dependances de son instance sont recalculees. Les etapes jusque-la bloquees sont alors activees des que leurs conditions et leur eventuel delai le permettent; la maintenance reguliere applique la meme regle aux projets termines depuis leur tableau.
- Les blocs Checklist des PV ne conservent plus leur ancien libelle dynamique dans le contenu enregistre. Le contexte reste affiche une seule fois, et le libelle Instances ou Elements recurrents vient exclusivement de la revue chargee.
- La sauvegarde d un point de PV retire maintenant toute la revue dynamique d un bloc Checklist, y compris son texte lorsqu il venait deja d etre aplati. Le bloc conserve uniquement sa reference et son contexte; les barres et instances sont toujours rechargees a l affichage.
- Le bloc Instances en cours est masque pour les checklists Conteneur, dont le suivi passe entierement par les barres de leurs elements.
- Les conteneurs ne proposent plus les options de parent, de visibilite, de dependance ou de delai des checklists de processus; leurs elements restent des taches simples pilotees uniquement par leur recurrence eventuelle.
- Lorsque OMO revient au premier plan, retrouve le reseau ou restaure une page conservee par le navigateur, il relance maintenant sa maintenance legere sans recharger toute l application. Les projets de checklist arrives a echeance sont alors generes; une session expiree provoque un rechargement pour reprendre le parcours de connexion. Les vues Projets et Checklist se rafraichissent sans interrompre un sous-drawer ouvert.

## 2026-07-21

- Les projets inseres dans les PV affichent maintenant une barre recursive dynamique de repartition des statuts de leurs sous-projets. Chaque barre peut etre deployee a son tour et charge seulement ses enfants directs; les lignes indiquent leur contexte et leur responsable. Le statut, la priorite et la taille du projet sont affiches dans des capsules dans l entete, avec un fond clair et un texte fonce derives de leur couleur, y compris pour les blocs deja inseres lors de leur reouverture. Le rendu lecture seule reserve aussi un retrait pour l icone projet. Dans Summernote, cette revue est integree temporairement a la carte projet sans etre enregistree dans le contenu du PV; en lecture seule, le controle Sous-projets est masque pour garder la carte compacte. Le rendu reste a jour dans le lecteur et dans l export PDF. Les titres des sous-projets ouvrent leur detail par la navigation interne, en repliant la reunion sans recharger la page.
- Les checklists disponibles a la demande peuvent maintenant etre activees avec une date de reference. Chaque activation cree une instance et son projet racine, planifie les etapes autour de cette date y compris avant celle-ci avec des delais negatifs, cree les projets deja exigibles et conserve les suivants en attente jusqu a leur echeance. Les instances ouvertes apparaissent dans le detail et leur nombre dans la liste principale; une instance est terminee lorsque son projet racine passe au statut Termine.
- Les checklists proposent maintenant un troisieme mode de declenchement, Conteneur. Ce mode regroupe des elements sans pouvoir lancer la checklist elle-meme et efface toute planification residuelle lors de son enregistrement.
- Les etapes d une checklist sont maintenant presentees selon leur ordre probable d apparition : elements immediats, delais croissants et dependances placees apres les elements requis. Le delai d une chaine de dependances est cumule pour conserver un deroulement coherent, tandis que l ordre de creation ne sert plus que de departage.
- Les boutons d enregistrement des formulaires Checklist et des editeurs generiques sont maintenant grises pendant la sauvegarde afin d eviter les doubles creations. Le verrou couvre aussi les actions placees dans l entete d un drawer et se libere apres une erreur pour permettre de reessayer; le partage de documents a ete aligne sur cette mecanique.
- L application Checklist propose un premier flux complet : liste par contexte, enfants directs ou descendants, creation progressive des informations generales puis ajout et edition des etapes une par une depuis la vue de detail. Les projets-modeles gardent leur hierarchie, leur affectation par holon, leurs delais, dependances et recurrence. Le module utilise le nom `checklist` de bout en bout et reste accessible directement avec la route `#checklist`.
- Les projets distinguent maintenant les projets operationnels des modeles de checklist, qui restent hors des vues Projet ordinaires et conservent leur affectation par holon.
- Les indicateurs et les futurs declencheurs de checklist utilisent desormais le meme contrat de recurrence pour les frequences et les moments attendus.
- Les selecteurs de portee des apps utilisent maintenant Local, Enfants directs et Descendants. Enfants directs inclut le contexte courant et ses enfants immediats. La portee Global a ete retiree; depuis la racine de l organisation, Descendants couvre toute la structure.
- Les apps Calendrier et Documents reconnaissent maintenant le holon racine comme contexte courant lorsque l organisation est ouverte sans `cid`, ce qui affiche aussi Enfants directs et Descendants.
- Les proprietes de type liste peuvent maintenant referencer des projets. Les editeurs de definitions, de templates et de holons proposent les projets actifs de l organisation, stockent leurs identifiants et affichent ensuite leurs titres dans les details et la vue Structure.
- Les listes de projets presentent d abord les projets les plus importants, puis les priorites les plus urgentes (P1 avant P5).
- Les projets affiches dans les proprietes de holon montrent maintenant leur priorite dans une pastille et une barre recursive de repartition des statuts de leurs sous-projets.
- Le titre d un projet reference dans les proprietes de holon ouvre maintenant son detail dans le panneau droit via la route hash Projets.
- Les cartes de projets referencees dans les proprietes de holon peuvent maintenant etre deployees pour charger a la demande leurs sous-projets directs.
- Les proprietes proposent maintenant un texte avec detail HTML repliable, ainsi qu un format HTML avant et apres une liste.
- Le format HTML et liste propose le meme choix de type d elements que les listes standard.
- Les deux editeurs HTML du format composite utilisent maintenant le composant Summernote partage et conservent leurs valeurs a la sauvegarde.
- La serialisation du format HTML et liste conserve maintenant toute sa structure JSON, y compris les deux blocs HTML et les elements de liste.
- Les tableaux ne sont plus proposes dans le toolbar Summernote des proprietes HTML.
- Le format HTML et liste n imbrique plus ses deux Summernotes et ses controles dans un label unique; les styles de libelle ne ciblent plus les elements internes de l editeur.
- Tous les champs Summernote des editeurs de holons et de templates utilisent maintenant un conteneur de formulaire neutre au lieu d un label englobant.
- L editeur de projet ne recharge plus la feuille des composants generiques dans son sous-drawer; les cartes Kanban utilisent maintenant les variables de generic-section sans changer d apparence a l ouverture du formulaire.
- Les proprietes HTML et liste vides ne produisent plus de section vide dans le detail d un holon.
- Les blocs HTML et les listes de projets des proprietes HTML et liste sont maintenant separes par un espacement regulier.
- Les proprietes texte avec detail HTML sont maintenant affichees sans bordure, avec leur texte en gras et le controle de deplie place en bas a droite.
- Le controle des proprietes texte avec detail HTML affiche maintenant le libelle Voir détail et utilise une fleche plus grande qui pivote de 90 degrés.
- Un double-clic dans une colonne du Kanban Projets ouvre maintenant la creation d un projet avec le statut de la colonne deja selectionne.
- Le formulaire de projet place maintenant l action principale avant Annuler et ne repete plus le titre ni le texte d introduction sous l entete.
- Les droits contextuels CAN_CREATE_PROJECT et CAN_CREATE_INDICATOR sont maintenant disponibles et protegent la creation des projets et des indicateurs.
- Le detail des projets propose maintenant un onglet Informations et un onglet Documents associes, avec ouverture des documents via leur route hash.

## 2026-07-19

- Le modele initial des projets est maintenant disponible avec titre, description HTML simple, statut, dates planifiees, priorite, importance, hierarchie parent-enfant, responsable, equipe et references vers plusieurs documents de l organisation. Le mode de capture futur pour Telegram distingue aussi les documents multiples du journal unique.
- L application Projets affiche maintenant les vrais projets dans un Kanban filtrable par contexte local, descendants ou organisation. Les changements de statut se font par glisser-deposer ou selecteur mobile, et le detail ainsi que la creation s ouvrent dans un sous-drawer.
- Le Kanban Projets utilise maintenant toute la hauteur disponible, sans laisser de zone vide sous les colonnes.

## 2026-07-20

- L application Projets propose maintenant une vue Liste, en plus du Kanban. Elle affiche tous les projets du contexte choisi, y compris les taches, et les regroupe par echeances relatives ou par priorites P1 a P5.
- Le survol ou le focus d un projet dans la vue Liste met en evidence ses sous-projets directs visibles.
- La sauvegarde d un projet actualise immediatement le Kanban ou la Liste en cours, tout en conservant leur position de defilement.
- Le filtre Projets propose maintenant les enfants directs du holon courant, et la vue Liste peut regrouper les projets par holon dans les perimetres multi-holons.
- Les choix de perimetre, de vue et de tri de l application Projets sont maintenant conserves localement pour chaque utilisateur de ce navigateur.
- La restauration locale des choix de Projets masque l etat par defaut pendant le chargement afin d eviter un changement visuel tardif.
- Les preferences de perimetre se replient temporairement sur Global depuis l organisation pour Descendants, et sur Local depuis un role sans enfant pour Enfants ou Descendants. Un cercle qui a des enfants conserve la preference enregistree.
- Les tris par planification, priorite et holon restent aussi disponibles dans le Kanban et ordonnent les cartes au sein de chaque colonne.
- Apres un changement de statut, le Kanban reapplique son tri tout en conservant son defilement horizontal.
- Les actions Annuler et Enregistrer des formulaires projet sont maintenant placees dans l entete du sous-drawer, a cote de Fermer.
- L editeur de PV peut maintenant inserer un bloc projet lie, avec son resume et un lien vers son detail. Le bouton et la carte compacte reprennent l icone Projets comme les autres inclusions; la carte affiche aussi priorite, taille, holon, responsable, statut et dates de planification.
- Les actions Enregistrer et Supprimer d un point de PV sont maintenant placees a droite de la barre Summernote persistante, pour rester accessibles pendant l edition.
- Les actions fixes de la barre Summernote des points de PV utilisent un format compact, pour ne pas augmenter inutilement la hauteur de la barre.
- Le selecteur de projet d un point de PV propose maintenant deux onglets : choisir un projet existant ou en creer un rapidement puis l inserer directement.
- La creation rapide de projet depuis un PV permet maintenant de choisir son holon sur la carte graphique et son responsable parmi les membres actifs de ce holon.
- Le selecteur graphique de holon du selecteur de projet PV reste maintenant fixe a gauche, quel que soit l onglet choisi.
- Le selecteur de holon du dialogue projet PV est aligne a gauche comme les autres selecteurs, et les actions de creation ou insertion restent visibles en bas du dialogue.
- Le filtre de projets du dialogue PV inclut explicitement les projets locaux du role selectionne, y compris pendant l initialisation de sa portee.
- Le selecteur de projets d un PV ne retire plus un projet selon la visibilite individuelle de son holon apres le chargement organisationnel, ce qui conserve les projets locaux de tous les roles accessibles dans le PV.
- Les portees Local, Enfants, Descendants et Global de l app Projets utilisent maintenant la meme comparaison explicite apres chargement organisationnel, afin de rendre les projets locaux de chaque role de facon coherente.
- La creation rapide de projet PV reconnait maintenant le holon organisationnel sans appeler de methode protegee, ce qui retablit la creation avec responsable.
- Les cartes du Kanban affichent maintenant une barre coloree recursive indiquant l etat des feuilles de leur arborescence de sous-projets, avec une ponderation qui respecte la repartition de chaque niveau.
- Les projets disposent maintenant d une taille S, M, L, XL ou XXL, utilisee comme coefficient de ponderation dans les barres de synthese. La taille M est appliquee par defaut.
- Les projets existants peuvent maintenant etre modifies depuis le bouton d action de leur entete de detail, avec le meme formulaire que pour leur creation.
- Le detail d un projet affiche maintenant sa barre recursive en pleine largeur, puis la liste de ses sous-projets avec leur propre barre de synthese.
- Les feuilles rattachees a un projet dans le meme holon sont maintenant traitees comme des taches : elles restent visibles dans le detail du projet mais ne surchargent plus le Kanban. Elles redeviennent visibles si elles changent de contexte ou recoivent des sous-projets.
- Les couleurs du Kanban sont maintenant plus douces mais plus lisibles, avec une barre verticale de statut sur chaque projet et des compteurs de colonnes colores selon leur etape.
- La liste des sous-projets distingue maintenant visuellement les projets et les taches avec leurs icones respectives, en reutilisant l asset product de l application Projets.
- Les sous-projets affichent maintenant leur responsable, leur statut et leur taille ; les taches peuvent changer de statut directement, et un clic ouvre le detail du sous-projet.

## 2026-07-18

- Le menu des groupes propose maintenant lui aussi un acces distinct au detail et a la modification.
- Le menu des indicateurs distingue maintenant l ouverture du detail et l acces direct au formulaire de modification.
- Le compteur d indicateurs des cartes de groupe laisse maintenant lui aussi la place au menu d actions.
- La date de la derniere valeur est maintenant directement placee sous la valeur sur les cartes d indicateurs en retard.
- Les cartes d indicateurs en retard indiquent maintenant le nombre de jours de retard sous leur derniere valeur, et leur compteur de valeurs laisse la place au menu d actions.
- Les groupes en somme affichent maintenant leur derniere valeur agregee, sa date et son pourcentage par rapport a leur reference.
- Les courbes de reference des indicateurs et des groupes utilisent maintenant le meme gris.
- Les groupes peuvent maintenant recevoir une courbe de reference avec le meme editeur interactif que les indicateurs; les points sont stockes dans la table de references partagee.
- La date de la derniere valeur occupe maintenant une ligne distincte dans la vue compacte des indicateurs, y compris face aux styles generiques des listes.
- Les valeurs actuelles des indicateurs avec courbe de reference affichent maintenant leur rapport a la reference interpolee a la meme date, sous la forme valeur (pourcentage).
- Les dates des points intermediaires de la courbe de reference sont calculees automatiquement selon leur position entre les deux extremites. Ces points peuvent aussi etre deplaces directement sur la ligne de la courbe.
- Le deplacement et la saisie des positions des points de reference utilisent maintenant un pas de 0,2 %.
- Le deplacement des points sur la ligne de reference ne reagit maintenant qu a un appui maintenu, avec des poignees plus faciles a attraper.
- Les poignees de deplacement des points intermediaires reprennent maintenant le meme aspect que les points d extremite.
- Les poignees de la courbe utilisent maintenant des elements neutres plutot que des boutons, afin d eviter les styles globaux des actions.
- Le deplacement d une poignee de courbe continue maintenant a etre suivi meme lorsque le pointeur sort legerement du rail ou de la poignee.
- Les poignees du rail sont maintenant correctement reliees aux lignes de formulaire correspondantes, afin que leur deplacement modifie bien le pourcentage et la date.
- Les graphiques des indicateurs avec courbe de reference utilisent maintenant la duree de cette courbe dans les petits affichages. Dans le detail, le curseur s ouvre par defaut sur cette meme periode tout en laissant l historique accessible.
- Les petits graphiques interpolent maintenant aussi la mesure au debut et a la fin de la periode de reference lorsque des valeurs existent de part et d autre.
- Les petits graphiques des indicateurs sans reference sont maintenant limites aux douze dernieres periodes de leur frequence de mesure. Les vues grandes continuent d afficher tout l historique.
- Les petits graphiques des groupes combines appliquent maintenant eux aussi cette fenetre de douze periodes, en retenant la duree necessaire la plus large lorsque les frequences different.
- L ouverture du detail des indicateurs et groupes preselectionne maintenant elle aussi les douze dernieres periodes, tout en conservant la possibilite d afficher l historique complet.
- Pour les indicateurs avec courbe de reference, la selection initiale du detail revient prioritairement aux dates de cette courbe ; les douze periodes restent la regle pour les autres.
- Lorsqu une integration Patreon est configuree, les fonctions IA sont reservees aux contributeurs actifs avec une contribution courante strictement positive. Sans Patreon configure, elles restent ouvertes si OpenAI est disponible. Les super-admins conservent cet acces. Le bot Telegram informe les personnes non contributrices avant toute transcription audio.
- Le bot Telegram accueille maintenant les utilisateurs avec /start, indique le compte relie et permet a /cancel de supprimer effectivement cette liaison.
- EasyMEMO utilise maintenant le scroll de la page pour la liste des documents. Son entete defile d abord avec le contenu, puis reste colle avec un decalage negatif : le resume sort de l ecran, tandis que l icone, le titre, son compteur compact et les selecteurs d affichage restent accessibles en haut, y compris sur telephone.
- La visualisation d un document EasyMEMO s ouvre dans un drawer fixe a la fenetre. Son contenu repart en haut a chaque ouverture et son entete conserve le bouton Fermer pendant le scroll.
- EasyMEMO charge aussi les styles des indicateurs pour afficher correctement leurs graphiques dans les PV.
- Les liens d acces directs EasyMEMO affichent maintenant le titre du document et permettent de faire defiler tout son contenu.
- Les indicateurs inseres dans les points de PV affichent maintenant clairement leur statut A jour ou En retard. Dans l editeur Summernote, les personnes autorisees sur l indicateur et l editeur du PV peuvent aussi ajouter directement une valeur datee du moment, avec un controle serveur dedie au contexte de la reunion.
- L affichage des indicateurs dans les points de PV est maintenant plus compact : le graphique est place a gauche dans une petite carte en degrade, avec bornes arrondies, nom et pastille de statut a cote, puis une saisie de valeur reduite sous la mesure actuelle avec un bouton +.
- Les indicateurs de PV ont maintenant une saisie placee directement sous la valeur et une description affichee sur trois lignes maximum sous leur titre.
- Le cadre general des indicateurs de PV est conserve comme celui des cartes Documents et Decisions, avec un second cadre reserve au graphique dans un format proche du 16:9.
- La sauvegarde de l editeur normal des indicateurs fonctionne maintenant aussi lorsque ses boutons sont places dans l entete du drawer.
- Le bloc indicateur des PV repartit maintenant le graphique, le texte et les valeurs en trois zones : graphique plafonne a 210 px, texte sur une ligne de titre et trois lignes de description, puis colonne de valeurs fixe.
- L ordre des deux colonnes de droite est maintenant force : texte au centre et valeurs a droite.
- Les reperes haut et bas du graphique sont maintenant alignes sur la zone utile de la courbe, avec la marge interne du SVG.
- Les reperes du graphique suivent maintenant directement la zone compacte du SVG, centree dans le cadre 16:9, sans modifier les proportions de la courbe.
- L echelle min/max des graphiques compacts est maintenant integree directement au SVG, avec les memes coordonnees que la courbe.
- Les styles de l echelle integree sont conserves dans les graphiques exportes en PDF.
- Les indicateurs en retard affichent maintenant le nombre de jours de retard ; les retards inferieurs ou egaux a 10 % de la periode restent en jaune avant de passer au rouge.
- Les graphiques des indicateurs dans Stats affichent maintenant une echelle min/max simple et homogene dans les cartes comme dans la vue compacte.
- Les libelles d echelle des cartes Stats disposent maintenant d une marge interne de 5 px a gauche.
- La phrase descriptive sous le titre de la page Stats a ete retiree pour alleger l en-tete.
- Les entetes de drawers avec selecteurs d affichage utilisent maintenant un espacement uniforme de 5 px entre le titre et la seconde ligne de controles.
- La vue compacte de Stats reserve maintenant la place du menu d actions a droite sur toutes les lignes, y compris celles qui n ont pas de menu.
- Les menus d actions des indicateurs et groupes Stats sont maintenant flottants et se repositionnent automatiquement pour eviter les recouvrements et les sorties de l ecran.
- Les menus d actions des Documents, Decisions et Stats, y compris la liste Memo, partagent maintenant les memes primitives generiques de bouton, panneau et action.
- Les panneaux de menus generiques respectent maintenant toujours l attribut hidden, meme lorsqu une page leur applique un affichage local.
- Les menus generiques utilisent maintenant une typographie legerement plus compacte.
- La vue compacte du Calendrier propose maintenant l edition directe des evenements dont l utilisateur est le createur.
- Le menu compact du Calendrier propose aussi la suppression des evenements lorsque le droit CAN_DELETE_EVENT est accorde, avec confirmation et gestion des documents associes.
- La vue compacte du Calendrier affiche maintenant les colonnes dans l ordre date, evenement, horaire puis contexte.
- Le badge EV sans signification a ete retire de la vue compacte du Calendrier.

## 2026-07-17

- Les droits sont maintenant autorises par defaut pour les membres d une organisation tant qu aucun holon ne les configure. Des qu un droit est attribue quelque part dans l organisation, il doit etre accorde explicitement selon l arbre des droits.

- L affichage compact des indicateurs repete maintenant l entete de colonnes dans chaque bloc temporel ou alphabetique, tandis que le separateur de bloc reste colle aux bords et la liste conserve la marge interne de Documents.

- La mini-navigation et les boutons conservent maintenant la couleur definie par l organisation, independamment de la couleur propre aux applications ouvertes, notamment Calendrier et Decisions. Les modules utilisent des variables d accent locales et ne redéfinissent plus `--color-primary`.

- Les indicateurs des PV sont maintenant styles dans l export PDF avec une mise en page compatible Dompdf, incluant la courbe SVG, les points, la valeur et la date.

- Les courbes SVG des indicateurs sont maintenant converties en images SVG embarquees pour l export PDF, car Dompdf gere ces SVG via les images mais pas de maniere fiable lorsqu ils sont directement integres dans le HTML.

- L export PDF des PV utilise de nouveau une valeur CSS compatible avec Dompdf; les variables CSS du navigateur ne sont plus injectees dans la feuille de style PDF.

- Les styles des indicateurs integres aux PV valides sont maintenant charges des l ouverture de l application, y compris en lecture seule; l affichage ne depend plus d un passage prealable par l editeur.

- La suppression definitive d un document nettoie maintenant explicitement ses invitations et presences associees, en plus des points de PV deja supprimes par cascade.

- La suppression d un evenement propose maintenant de supprimer aussi ses documents associes avec un choix Oui/Non; les documents sont traites dans la meme transaction et les PV orphelins peuvent ensuite etre supprimes depuis Documents.

- Ajout du droit contextuel `CAN_DELETE_EVENT` pour separer la creation d evenements de leur suppression; le bouton et l endpoint de suppression verifient maintenant ce droit dans le contexte de l evenement.

- Ajout d une suppression d evenement depuis son detail, avec confirmation, bouton poubelle dans l entete et controle d autorisation cote serveur.

Les details des indicateurs proposent maintenant un curseur a deux poignées pour limiter la periode visible. Le graphique est redessine localement avec ses dates et son echelle verticale recalculees selon la selection; le chargement du script necessaire au redessin est aussi correctement initialise dans le sous-drawer. Les cartes et mini-graphiques restent inchanges.

Lorsque la selection commence ou se termine entre deux mesures, les graphiques ajoutent maintenant une valeur interpolee sur la borne du slider afin de conserver la portion de courbe visible, meme en presence d un grand intervalle entre les mesures.

Les points des graphiques de detail affichent maintenant une infobulle native avec leur valeur et leur date de mesure exactes, y compris apres une selection temporelle et son redessin.

Cette infobulle est maintenant remplacee par une fenetre maison qui apparait immediatement au survol du point, se repositionne pour rester visible dans la fenetre et reprend le style des infobulles de Structure.

Les details des indicateurs et des groupes ne repetent plus leur grand panneau de titre dans le contenu. Le nom reste dans l entete du sous-drawer, dont le sous-titre indique maintenant la methode de suivi; le gain de hauteur rend le graphique et sa legende plus accessibles sur petit ecran.

Les graphiques des indicateurs utilisent maintenant une echelle verticale arrondie sur des pas lisibles (1, 2, 5 puis dizaines, centaines, etc.), avec des bornes alignees sur ces graduations.

## 2026-07-16

Les selecteurs Documents, Decisions, Evenements et Indicateurs des points de PV proposent maintenant une navigation holarchique graphique sur canvas et une portee locale, descendants ou globale. La liste se filtre selon le holon et la portee choisis, par defaut sur les descendants du contexte; un clic zoome la carte sur le holon choisi. La colonne de navigation conserve une hauteur stable, affiche le holon selectionne ou survole dans la carte, et la recherche rapide avec icone remplace les anciens libelles. Sur petit ecran, la navigation est masquee et la recherche globale reste disponible.

Le detail des evenements est maintenant organise en une carte de synthese (horaire, contexte, statut et lieu), puis en deux colonnes adaptees au sous-drawer : description, lieu et invites a gauche; document associe et informations rapides a droite. L entete du sous-drawer recoit directement le titre, le sous-titre et les actions du contenu charge. Il expose aussi une petite API JavaScript pour les prochains contenus qui devront les modifier ou y ajouter des boutons. Le bouton Fermer utilise le bouton generique et l action d enregistrement des formulaires de creation ou de modification est placee dans cet entete. Pendant une modification, Annuler revient au detail sans enregistrer et le bouton principal est simplement nomme Enregistrer.

Les sous-drawers Documents, Decisions et Indicateurs reposent maintenant sur le meme controleur partage. Il normalise le bouton de fermeture et expose dans chaque application une API pour definir titre, sous-titre et boutons d entete; un contenu charge peut aussi declarer ces informations et deplacer ses actions dans l entete.

Les actions principales sont aussi remontees dans les entetes lorsque le formulaire le permet : Modifier n apparait dans le detail d un document que pour une personne qui possede effectivement le droit d edition, et les formulaires Documents et Indicateurs placent Annuler et Enregistrer dans le sous-drawer tout en restant rattaches a leur formulaire.

Les boutons Fermer et Annuler du formulaire Documents utilisent maintenant la meme fermeture du sous-drawer : nettoyage du brouillon et du verrou, puis retour au detail du document sans enregistrer. L action Annuler est aussi captee directement par le conteneur, y compris apres son deplacement dans l entete.

Le detail d un groupe d indicateurs affiche maintenant le bouton Modifier le groupe dans son entete, lorsque les droits sur le contexte du groupe le permettent.

Le detail et l edition des Documents partagent maintenant un seul sous-drawer. Modifier remplace son contenu sans superposer un second panneau, Fermer ferme toujours ce panneau, et Annuler revient au detail du document sans enregistrer.

La creation et la modification des groupes d indicateurs utilisent maintenant le sous-drawer Indicateurs au lieu d une popup. La recherche, la selection multiple et le choix du mode restent disponibles, avec Annuler et Enregistrer dans l entete.

Les droits de visualisation et d edition des documents indiquent maintenant directement le nom du proprietaire lorsqu ils lui sont reserves.

Les liens vers des documents, decisions et evenements inclus dans les points de PV sont maintenant compacts. Ils conservent leur titre cliquable et un resume borne, les informations de participation ou de resultat pour une decision, et les horaires avec le lieu pour un evenement.

L ouverture directe d un document depuis un lien avec hash attend maintenant que son panneau soit pret avant de consommer la demande. Un chargement initial un peu plus lent n ouvre donc plus seulement le holon.

Le reordonnancement des points d ordre du jour conserve maintenant la nouvelle position d un point en cours d edition, sans ecraser son brouillon local.

Les points de PV peuvent maintenant integrer un indicateur ou un groupe visible, cumule ou superpose. Le bloc conserve un lien, le mini graphique de suivi, la derniere valeur ou le nombre de membres, sa date et un signalement de retard, y compris lorsqu il est affiche en lecture seule. Le renderer du viewer reconstruit aussi les attributs du bloc securise, afin qu ils ne soient pas perdus lors de l affichage. Un clic replit le volet de reunion et utilise la navigation hash interne vers Stats, sans rechargement de page.

Le statut `Vous etes editeur du PV.` respecte maintenant aussi l attribut `hidden` apres une passation. Il n apparait que pour la personne qui tient effectivement le PV.

La liste de presence des PV affiche maintenant seulement le nom ou username de chaque invite. Son adresse e-mail reste disponible au survol et devient le libelle visible uniquement lorsqu aucun autre nom n est connu.

Les cadres rectangulaires partages utilisent maintenant le token unique `--radius-md` defini dans les composants communs. Les anciennes valeurs fixes, ainsi que les anciens aliases `--radius-sm` et `--radius-lg`, ont ete migres ou retires dans les applications OMO, Memo, Circle et les ecrans partages; seuls les cercles, pastilles et formes volontairement asymetriques gardent leur rayon propre.

## 2026-07-15

L editeur de PV propose maintenant un bouton de resume automatique: le titre, la description, l evenement et les points sont transmis a l IA, puis le resultat remplace localement la description et reste a enregistrer.

Le resume automatique des PV respecte maintenant la configuration de l instance: Patreon limite l acces lorsqu il est configure, tandis qu une instance sans Patreon autorise l IA avec une cle OpenAI disponible.

Le resume automatique des PV produit maintenant un paragraphe unique mettant en avant les themes, sujets et resultats principaux, avec une formulation concise et attractive.

Le bouton de resume automatique est maintenant disponible uniquement pendant la phase de relecture.

La visibilite du bouton de resume se synchronise maintenant immediatement lorsque l etape du PV change, sans rechargement de l editeur.

Le helper OpenAI n appelle plus `curl_close`, desormais deprecie en PHP 8.5.

La recherche globale de la topbar et sa popup proposent maintenant une periode ajustable entre la creation de l organisation et aujourd hui. Dans la topbar, elle est placee sous les modules coches. Les champs date compacts encadrent un curseur aligne en bas, a deux poignees superposees au rail avec des reperes annuels discrets; ils restent synchronises avec le slider. Le filtre est applique cote serveur aux resultats de chaque module puis conserve pendant le traitement asynchrone et la restauration de la recherche.

La mise a jour du profil rafraichit maintenant le contexte `getOrg.php`, le drawer actif et le menu `Profil` des l enregistrement ou la fermeture du formulaire. Les drawers fermes sont vides afin de recharger leurs donnees a leur prochaine ouverture.

Les libelles visibles de compte utilisent maintenant uniformement `Nom d'utilisateur` pour designer le nom de connexion.

Le menu `Profil` avertit aussi avant la fermeture ou le changement d onglet lorsqu un formulaire contient des donnees non sauvegardees.

L application contextuelle Indicateurs dispose maintenant de ses fondations completes: affichages en cartes ou en liste compacte, portees contextuelle, descendante et globale, graphiques historiques, ouverture par navigation hash dans un sous-drawer et formulaire de creation ou de modification reutilisant `adminEdit`.

Les mesures et la reference sont volontairement separees. Une mesure reste un simple couple valeur/date, ajoutable rapidement et supprimable depuis l historique. La courbe de reference possede ses propres extremites datees et peut recevoir autant de points intermediaires positionnes en pourcentage que necessaire, sur le modele des stops d un degrade. Les plafonds restent horizontaux tandis que les objectifs peuvent suivre une trajectoire personnalisee.

Les indicateurs, leurs mesures et leurs points de reference reposent sur trois `dbObject` normalises et une migration ordonnee. Cette structure laisse la place aux futurs imports entre cercles, indicateurs composes et series issues automatiquement des donnees du logiciel sans les confondre avec les saisies manuelles actuelles.

Chaque indicateur simple peut maintenant definir un rythme de mesure attendu: jour, semaine, mois, trimestre, semestre ou annee. Le moment associe est adapte a la cadence (heure, jour, cycle de mois) et reste facultatif, afin de pouvoir plus tard estimer le respect du rythme a partir de l historique lorsqu aucune echeance precise n est fixee.

Les indicateurs dont la derniere valeur n est plus dans les temps sont maintenant signales par un degrade rouge et une courbe rouge. Le statut utilise l echeance configuree ou, a defaut de moment, la periode choisie depuis la derniere mesure. Les imports et groupes heritent automatiquement du statut depasse de leurs sources.

Les Indicateurs peuvent maintenant etre importes dans un autre contexte via le menu `...`, sans dupliquer leur historique ni leur source. La recherche rapide ne propose que les indicateurs visibles de l organisation. En portee descendante ou globale, une serie importee n apparait pas deux fois lorsque son contexte d origine est deja inclus.

Le meme menu permet de composer un groupe d indicateurs selectionnes. Un groupe peut afficher les courbes de chaque serie superposees ou additionner les valeurs mesurees a la meme date; il reste une composition de references et ne modifie jamais les donnees sources.

Le calcul des groupes en mode somme interpole maintenant lineairement chaque serie aux dates des autres mesures avant de les additionner. Une valeur intermediaire est donc correctement prise en compte au lieu de ne sommer que les points strictement synchrones; hors de la periode connue d une serie, sa contribution est simplement nulle.

Les groupes additionnes normalisent aussi leurs dates avant le calcul: heure pour une periode courte, jour pour plusieurs semaines ou mois, puis semaine pour les historiques longs. Une serie plus frequente conserve une precision adaptee a sa cadence mediane, tandis que plusieurs saisies dans le meme creneau n engendrent plus de points distincts.

Les imports et cumuls peuvent maintenant etre modifies depuis leur menu d actions: la source d un import peut etre remplacee, et un groupe peut etre renomme, changer de mode ou de sources. Ils peuvent aussi etre retires du contexte courant sans supprimer les indicateurs d origine.

La page des indicateurs propose maintenant un classement alphabetique ou par temporalite de mesure. Les resultats sont regroupes par separateur, comme les documents; un cumul reprend la frequence la plus rapprochee de ses indicateurs sources.

Les selecteurs de classement et de densite sont maintenant regroupes sur une seule ligne dans l entete des indicateurs.

Les fenetres de selection Summernote dans l editeur de PV passent maintenant au-dessus du drawer au lieu d etre masquees derriere lui.

Le choix de modele PV dans la creation de documents est maintenant masque pour les types URL, HTML, fichier et dossier; il apparait uniquement pour les PV.

Les cartes et lignes compactes des groupes d indicateurs ouvrent maintenant un detail grand format dans le drawer. Le graphique reprend les axes et la periode complete, avec une legende qui identifie chaque indicateur source et son contexte.

Dans le detail grand format d un groupe additionne, les courbes sources restent visibles en transparence derriere la somme principale. Les cartes et lignes compactes conservent uniquement la courbe additionnee. La legende du detail reprend les couleurs des sources avec un segment explicite et distingue la courbe calculee des indicateurs qui la composent.

Le changement de vue masque maintenant explicitement le panneau inactif, y compris lorsque les styles generiques de liste imposent leur propre mode d affichage.

Le bouton d enregistrement des indicateurs est maintenant desactive des le premier clic pour eviter les creations en double, tout en restant reutilisable si le serveur renvoie une erreur. Les cartes et la vue compacte proposent aussi un menu `...` pour modifier ou masquer un indicateur; ses valeurs historiques sont conservees.

## 2026-07-14

Les auteurs et editeurs autorises peuvent maintenant supprimer un point PV directement depuis son editeur avec un bouton rouge de confirmation. Les points traites, les groupes et les points auxquels l utilisateur n a pas acces restent proteges.

L API Documents est maintenant organisee par type sans casser les anciennes URL: le module complet des PV vit sous `omo/api/documents/pv`, les outils de contenu HTML sous `html`, et le telechargement des fichiers stockes sous `upload`. Les anciens points d entree restent de simples relais de compatibilite. La construction des donnees d un point PV est partagee entre le premier rendu, les sauvegardes et la synchronisation, avec un seul calcul des roles disponibles par reponse. L endpoint d actions PV refuse desormais les mutations par URL et accepte uniquement les requetes `POST`.

Le secretaire officiel d un PV peut maintenant passer la main sans perdre immediatement son attribution. Cette passation demande d abord que les modifications locales soient enregistrees, puis affiche une attente animee et ouvre le bouton de remplacement aux personnes invitees. Une personne disposant de `CAN_CLAIM_PV` peut a tout moment reprendre la responsabilite; un remplacant conserve la possibilite de sauvegarder un brouillon deja verrouille par sa session. Ces droits sont controles cote serveur et se synchronisent entre les editeurs ouverts.

L entete de l editeur de PV a ete reorganise en une interface plus visuelle: identite du document et informations de reunion, processus en quatre etapes, auteur et secretaire, actions de gestion puis liste de presence dans une carte distincte. Lorsqu un evenement est associe, son nom, son horaire et son lieu occupent une ligne complete avec leurs pictogrammes. Le selecteur de visibilite compact surplombe maintenant les etapes, les horaires d une reunion tenue sur une seule journee ne repetent plus sa date de fin, et le bouton d enregistrement apparait sous la description uniquement lorsqu une modification doit etre sauvee. La composition reste compacte et s adapte aux ecrans etroits sans changer les mecanismes de sauvegarde ou de synchronisation.

Les menus compacts de l editeur de PV, de la liste Documents et de la consultation en lecture seule proposent maintenant un export PDF telechargeable. Il reutilise le rendu de consultation et ses controles d acces pour produire un document A4 avec le titre, la description, les informations de reunion, les groupes imbriques et le contenu enregistre des points.

Les images statiques locales des PV sont maintenant embarquees dans leur export PDF avec leur type MIME reel. Les chemins web relatifs, notamment les icones PNG des types de points, ne produisent plus d erreur d image introuvable dans DomPDF; les icones ont une taille explicite et les images de contenu conservent leurs proportions dans une zone limitee.

Les PV peuvent maintenant etre enregistres comme modeles reutilisables depuis le menu compact de leur editeur. Lors de la creation d un PV depuis Documents ou depuis un evenement, les modeles visibles selon les droits d organisation, cercle ou role sont proposes; leur arborescence, leurs points, leurs durees et leurs contenus sont dupliques sans conserver de lien au modele, d invitations, d auteurs, de roles, de statut traite ni de verrou d edition.

Les ordres du jour des PV peuvent maintenant etre structures en groupes imbriques. Le secretaire cree et renomme les groupes, chacun peut les ouvrir ou les replier, et les points se deplacent visuellement entre leurs niveaux par glisser-deposer ou avec les fleches, qui permettent aussi d entrer dans un groupe voisin et d en sortir. Un indicateur flottant stable distingue clairement l insertion entre deux elements du depot dans un groupe, sans deplacer la liste sous le curseur. Leur numerotation suit l arborescence (`4`, `4.1`, `4.2`, etc.). Pendant la preparation, un participant peut reclasser uniquement ses propres points sans modifier l ordre relatif des autres; le secretaire conserve la gestion complete de l arborescence.

Les groupes affichent maintenant sous leur titre le nombre total de points qu ils contiennent et la duree cumulee de ces points, y compris dans les groupes imbriques. Ces indicateurs restent synchronises apres une modification, un deplacement ou une actualisation du PV.

Le numero des groupes reste maintenant sur la ligne du titre, comme celui des points, tandis que le recapitulatif commence sous le numero sur la ligne inferieure. La fleche d ouverture ou de fermeture conserve sa place en premier.

Les actions d ajout d un point ou d un groupe dans l editeur utilisent maintenant des boutons carres avec leurs pictogrammes, tout en conservant les libelles accessibles et les infobulles.

La poignee de groupe couvre explicitement les deux lignes de son en-tete dans la grille, afin de rester etiree sur toute sa hauteur.

L icone du bouton sombre d ajout de point est maintenant automatiquement inversee en blanc pour rester lisible sur son fond.

Une zone poubelle apparait maintenant a gauche des actions pendant le deplacement d un point ou d un groupe. Un depot confirme supprime l element; les points contenus dans un groupe supprime sont conserves et remontent au niveau superieur.

Le message indiquant que l utilisateur est editeur du PV est maintenant affiche uniquement lorsque son identifiant correspond effectivement a l editeur officiel courant, y compris apres une synchronisation ou une passation.

La recherche globale propose maintenant une rubrique PV distincte lorsque l application Documents est active. Elle recherche dans les titres et le contenu des points d ordre du jour, sans melanger les PV avec les autres documents et en conservant leurs controles de visibilite.

Les resultats PV de cette rubrique utilisent maintenant le meme bouton Ouvrir que les documents et conduisent vers le document trouve.

Les blocs de reference inseres dans les PV peuvent maintenant cibler les documents HTML, les liens URL et les fichiers televerses. Les dossiers et les PV restent exclus de ce selecteur.

## 2026-07-13

Les documents disposent maintenant d une action `Archiver` et d une action `Supprimer` protegee: les PV, les documents lies a un evenement et les dossiers encore utilises sont uniquement archivables.

Le menu `Deplacer` des documents reconnait maintenant l auteur initial ou le secretaire d un PV comme gestionnaire autorise, avec la meme verification appliquee par l API de deplacement.

Un auteur initial sans secretaire dispose maintenant des memes droits complets que le secretaire du PV: reordonner, attribuer et editer les points, sous reserve des points deja traites.

La prise du role de secretaire rafraichit maintenant immediatement les droits d edition de tous les points du PV, sans ecraser un brouillon local en cours.

Le graphique de timing des PV sans evenement n affiche plus de zone de marge artificielle ni de ligne de marge dans sa legende, y compris lorsque la mise en page en grille est active.

La visibilite du document PV se choisit maintenant avec le switch graphique commun et ses icones de portee, tout en conservant les infobulles et la sauvegarde des metadonnees.

L auteur initial d un PV est maintenant affiche dans l editeur et conserve ses droits de gestion tant qu aucun secretaire n est affecte. Pour un PV sans holon de contexte, il devient le secretaire par defaut, independamment de `CAN_CLAIM_PV`; les PV rattaches a un holon continuent d appliquer cette permission.

Les invitations des evenements, prises de decision, documents et futures ressources reposent maintenant sur un modele generique unique. Les adaptateurs metier existants restent compatibles, les anciennes invitations sont reprises par migration, et un PV sans evenement peut gerer directement ses propres holons, membres et adresses e-mail invites. La popup conserve correctement l identifiant du document lors de son enregistrement, y compris quand ce PV autonome ne possede aucun holon.

Les modales communes de la topbar apparaissent maintenant au-dessus du drawer d edition des PV, afin que la popup `Inviter` reste accessible lorsqu elle est ouverte depuis cet editeur.

Les invitations disposent maintenant d un champ de reponse `accepted`, distinct du statut technique de l invitation. Il est repris dans la liste de presence et synchronise avec les coches de presence, pour les evenements comme pour les PV autonomes.

Un point de PV traite n affiche plus l aide indiquant que son auteur peut encore le modifier; il affiche directement son etat verrouille.

Pendant la phase `Preparation`, le secretaire d un PV, ou son createur tant qu aucun secretaire n est attribue, peut maintenant ouvrir le bouton `Inviter` directement depuis l editeur. Il reutilise la liste d invitations du Calendrier et la mise a jour est reprise dans l editeur sans recharger la page.

Les fonctions PV et Calendrier ne dependent plus implicitement de toutes les applications: sans `TEAM`, la liste de presence disparait de l editeur et son endpoint ne peut plus etre utilise. Sans `structure`, les evenements n affichent plus de rattachement ni d onglet holons; les membres actifs de l organisation constituent alors la liste d invites par defaut, y compris pour la liste de presence quand `TEAM` reste actif.

La creation ou la simple edition d une organisation n active plus automatiquement toutes les applications OMO disponibles. Une nouvelle organisation demarre maintenant sans application connectee, puis la barre de gauche permet d ajouter seulement celles souhaitees.

La popup `profil_scope.php` recharge maintenant explicitement les helpers partages du profil utilisateur avant de calculer l anniversaire et la date de naissance. Cela evite un fatal error en production quand le fragment `current` tente d appeler `commonUserProfileBuildBirthdaySummary()`.

L etape d un PV est maintenant representee dans son editeur par une barre de processus compacte, composee de fleches colorees. L etape courante apparait en couleur pleine, les autres restent attenuees et les personnes autorisees peuvent changer d etape directement en cliquant sur le segment correspondant, avec confirmation conservee pour la validation irreversible.

Les segments de cette barre de processus s imbriquent maintenant visuellement sans espace parasite: chaque etape recoit exactement la pointe de la precedente dans son encoche gauche et se termine elle-meme en fleche vers l etape suivante.

La liste Documents affiche maintenant l etape des PV non valides a toute personne qui peut consulter le document, et non plus uniquement a son createur ou son secretaire. L etape finale `Valide` reste volontairement implicite.

Pour une personne non invitee a une reunion, un PV associe ne devient visible qu une fois les deux conditions reunies: il est `Valide` et l heure de debut de la reunion est atteinte. Cette meme regle protege aussi les ouvertures directes par URL; les invites, le createur et le secretaire gardent leur acces de travail habituel.

Le selecteur `Information` / `Consultation` / `Decision` des points de PV suit maintenant la navigation clavier d un groupe radio: `Tab` atteint uniquement le choix actif, puis les fleches gauche et droite changent de type.

Lorsqu un PV est valide, son editeur se ferme automatiquement apres la sauvegarde de l etape et ouvre le viewer en lecture seule via sa route hash Documents. Un brouillon local encore non enregistre demande confirmation avant cette transition finale.

## 2026-07-12

L editeur PV affiche maintenant aussi une vraie liste de presence dans l entete de droite quand le document est lie a une reunion du calendrier. Les personnes invitees y apparaissent avec une case a cocher pour signaler leur presence, y compris quand l invitation vient d un holon et doit donc etre deduite a partir de ses membres actuels.

Les PV peuvent maintenant avoir un secretaire, enregistre directement sur le document. Le droit contextuel `CAN_CLAIM_PV` permet de prendre ce role; lorsqu il cree un PV, son createur le recoit automatiquement s il possede ce droit. Le secretaire peut reordonner les points, modifier tous leurs contenus et attribuer un point a une autre personne invitee avec les roles correspondants. Les autres auteurs gardent la modification de leurs propres points. Un PV passe en etape `Valide` devient entierement non modifiable.

L ouverture d un PV suit maintenant son etape de travail, aussi bien depuis Documents que depuis le bouton `Voir le document` du Calendrier: les PV restent editables en `Preparation` et `Reunion`; en `Relecture`, seul le secretaire ouvre l editeur; et un PV `Valide` ouvre toujours le viewer en lecture seule.

La liste Documents ne montre desormais un PV lie a une reunion qu en `Relecture` ou `Valide`, sauf pour son createur et son secretaire qui le retrouvent toujours. Leur ligne indique son etape entre parentheses, hors `Valide`. Le secretaire, ou le createur tant qu aucun secretaire n est designe, gere les metadonnees, l ordre, la presence et les etapes; les auteurs restent limites a leurs propres points. Valider un PV demande maintenant une confirmation explicite et verrouille irreversiblement toute modification.

L entete de l editeur PV permet maintenant au secretaire, ou au createur sans secretaire, de modifier directement le titre, la description et la portee de visualisation du document. Ces champs restent discrets, se sauvegardent sans ouvrir l editeur generique et participent a la protection contre une fermeture avec des modifications non enregistrees.

La synchronisation distante de l editeur PV inclut maintenant aussi son entete. Une empreinte basee sur la date de modification du document, son titre, sa description, son etape, son secretaire et sa visibilite evite tout rerendu inutile; les changements recents d un autre poste sont repris, tandis qu un brouillon local de metadonnees reste protege.

Les invites qui consultent le PV dans l editeur voient maintenant aussi le titre et la description de l entete se mettre a jour a distance. Les libelles en lecture seule utilisent le meme payload synchronise que les champs editables du secretaire.

Les titres des points a l ordre du jour ne sont plus limites a trois mots. Cette ancienne recommandation ne bloque plus ni la saisie ni les sauvegardes des PV.

Avant sa validation, un PV lie a une reunion n est plus accessible qu a ses invites, son createur et son secretaire, y compris par URL directe. Le bouton `Consulter le document` dans le detail Calendrier suit cette restriction; apres `Valide`, la visibilite normale du document est a nouveau appliquee.

Seul le secretaire peut maintenant marquer un point comme traite. Cet etat place immediatement le point en lecture seule pour tout le monde, y compris le secretaire, jusqu a ce qu il le decoche.

Les synchronisations distantes de l editeur PV ne remplacent plus une carte qui contient un brouillon, une sauvegarde active ou le focus courant. Elles conservent aussi la position de la carte active, ou de la carte centrale, lorsque des contenus ou l entete changent a distance.

Le rerendu selectif ne deplace plus inutilement les cartes quand leur ordre reste identique. L element qui avait le focus, avec sa position de curseur ou sa selection, est restaure apres une synchronisation distante afin de maintenir la frappe continue.

## 2026-07-10

Le module Documents peut maintenant creer des PV meme sans activer l Agenda. Le type `PV` apparait dans le formulaire de creation standard, la creation passe par le flux document habituel avec date de creation immediate, et l action `Editer` d un PV autonome ouvre ensuite directement le drawer PV special en pleine page au lieu de l ancien editeur generique.

Les documents PV portent maintenant aussi une premiere notion d etape de workflow: `Preparation`, `Reunion`, `Relecture` et `Valide`. Cette etape est stockee en base sur le document, alignee dans le schema Docker, et un premier selecteur apparait dans l entete de l editeur PV pour preparer les futurs comportements specifiques a chaque phase.

La liste des documents affiche maintenant aussi une icone dediee pour les PV, distincte des fichiers generiques, afin de les reperer plus vite visuellement.

L editeur PV optimise encore l espace de preparation: l entete du document est maintenant place dans la colonne d edition a droite, la colonne d ordre du jour prend toute la hauteur, et le recapitulatif temporel combine maintenant un anneau d agenda avec points traites, points restants et marge ou depassement, plus un Time Timer interieur pour le temps reel restant.

La liste d ordre du jour de l editeur PV est plus compacte: les points restent colles en haut, chaque ligne a un seul cadre commun avec poignee a gauche et case traitee a droite, tandis que les boutons monter/descendre sont maintenant dans les cartes editables. Le glisser-deposer affiche aussi un repere d insertion plus explicite.

Les cartes editables des points de PV gagnent encore en densite: le numero, le titre et la duree estimee tiennent sur une meme ligne, la duree se modifie inline, et l auteur peut associer son point a un role concerne parmi les roles qui lui sont attribues dans le contexte du PV. L entete generale du PV defile maintenant avec la zone de contenu plutot que de rester figee au-dessus des points.

Les editeurs HTML simples bases sur Summernote n affichent plus la phrase technique d aide sous le champ. Leur zone de saisie grandit maintenant avec le contenu au lieu d ajouter un ascenseur interne, ce qui rend notamment l edition des points de PV plus naturelle.

La barre d outils des editeurs HTML simples reste maintenant sticky en haut de l editeur pendant le defilement. Les points de PV tres longs gardent donc les actions de mise en forme accessibles sans devoir remonter au debut du point.

Les types de points de PV utilisent maintenant des icones dediees pour `information`, `consultation` et `decision`. Dans l editeur, les points modifiables affichent un switch compact a icones seules pres de la duree; en lecture seule, la capsule coloree conserve son libelle et ajoute l icone.

L editeur PV protege maintenant les brouillons locaux: le bouton `Enregistrer` reste grise tant qu aucun changement n est present, devient une action principale des qu un point est modifie, signale les modifications non enregistrees et demande confirmation avant une fermeture ou un rafraichissement navigateur.

Le verrouillage des points de PV evite maintenant les faux blocages par soi-meme apres rechargement ou fermeture. Le jeton d edition reste stable pour le document dans la session courante, les verrous du meme utilisateur peuvent etre repris proprement, et les locks actifs sont liberes quand l editeur est ferme.

L annulation d une fermeture de l editeur PV conserve maintenant correctement les boutons `Enregistrer` actifs sur les points qui ont encore des modifications locales.

La protection de fermeture de l editeur PV est maintenant volontairement simple: elle demande confirmation uniquement si au moins un bouton `Enregistrer` est actif, sans recalculer ni modifier l etat des points pendant la fermeture.

Lorsqu un point de PV est rerendu pendant un brouillon local, l etat du bouton `Enregistrer` actif est maintenant capture et restaure explicitement. Un refresh distant du point ne peut donc plus desactiver le bouton alors que le contenu local est encore modifie.

La liste des roles concernes dans l editeur PV est plus fiable: elle reutilise maintenant la mecanique d affectations visibles du contexte courant, comme les vues de profil/contexte, en partant du cercle d ancrage du document ou de l evenement associe et en retombant sur la structure de l organisation si necessaire.

## 2026-07-09

L editeur plein ecran des PV a ete compacte pour mieux preparer une reunion: le titre du document, le nom de la rencontre, l horaire et le lieu ne s affichent plus qu une seule fois en tete, la colonne de gauche montre une liste d ordre du jour plus dense avec sujet, auteur, duree estimee et case `traite`, et les points peuvent maintenant etre reordonnes par glisser-deposer avec synchronisation immediate de la colonne d edition. Le statut `traite` est aussi persiste en base avec migration SQL et alignement Docker.

L editeur PV avant reunion sait maintenant aussi suivre les changements distants: les points embarquent une vraie date de modification et un dernier modificateur, la page poll regulierement les mises a jour des autres participants, et un verrou temporaire par point s active des qu on commence a editer pour limiter les collisions. Les brouillons locaux sont conserves pendant les rerendus et pendant les reordonnancements.

La colonne de gauche de l editeur PV reprend maintenant aussi l esprit du module historique `/pv/` avec un recapitulatif de timing colle en bas. On y voit la duree de la reunion, le temps restant pendant la reunion, la somme des durees prevues et celle des points encore non traites, plus un petit graphique circulaire distinguant les points traites, les points restants et la marge ou le depassement.

Le menu `Export` du panneau Structure propose maintenant `JSON`, `XML` et `CSV`. Les trois sorties embarquent aussi les droits des holons et des holon templates, avec le format compact complet en `JSON`, une variante structuree en `XML`, et une vue a plat en `CSV` ou les permissions sont listees par code et portee dans une cellule.

Le panneau Structure ne cumule plus ses handlers d export au fil des rechargements partiels. Le choix de format reutilise maintenant un declenchement plus robuste pour eviter les faux telechargements locaux du type `exportStructure.php` sans vraie requete reseau.

Le module Documents sait maintenant afficher un nouveau type `pv` en lecture seule. Un document PV ouvre un viewer dedie qui liste les points a l ordre du jour, leur type, leurs durees, leur auteur, le holon concerne, les holons adresses, les tensions liees et leur contenu HTML.

La base SQL a recu une nouvelle structure pour stocker ces points de PV et leurs liaisons vers plusieurs holons et plusieurs tensions, sans reutiliser l ancienne table `pv` historique. Un jeu de demo separe a aussi ete ajoute pour charger un premier PV testable, et le seed Docker local embarque maintenant ce cas de test avec le schema document attendu par le module.

Le module Agenda sait maintenant decrire le lieu d un evenement en `presentiel`, `virtuel` ou `mixte`, avec une adresse, un lien de visio, ou les deux. Le detail de l evenement les affiche, l export calendrier reprend aussi le lieu et la visio, et un evenement peut desormais porter un document associe unique de type lien, HTML, telechargement ou PV.

Quand un document est lie a un evenement depuis le formulaire Agenda, sa date de creation est alignee sur la date de l evenement pour le retrouver plus facilement dans les documents. Cette date suit aussi automatiquement les deplacements ulterieurs de l evenement, avec migration SQL et schema Docker alignes pour les nouveaux champs `event`.

Les documents lies a un evenement ne remontent plus dans la liste Documents tant que l evenement n est pas termine. Leur `datecreation` est maintenant synchronisee sur la fin de l evenement, et la liste masque automatiquement les documents dont cette date est encore dans le futur, tout en laissant l acces direct via le module Agenda.

Le rattachement Agenda/Documents a ete inverse: c est maintenant le document qui garde la reference vers l evenement. Cela prepare l ouverture vers plusieurs documents sur une meme rencontre et permet deja d afficher, dans le detail d un document lie, un resume de la rencontre associee avec son horaire, son lieu et son contexte.

Le formulaire Agenda ne tente plus d embarquer l editeur complet du document associe. Il se contente maintenant de choisir un type, puis de creer un document vide avec des metadonnees par defaut modifiables ensuite dans le module Documents, y compris pour les types `lien externe` et `telechargement` qui peuvent donc exister sans URL ni fichier tant qu ils ne sont pas completes.

Dans ce flux Agenda, le document peut a nouveau recevoir un nom saisi manuellement avant creation. Si le type choisi est `PV` et que ce nom est laisse vide, un titre par defaut du style `PV Nom evenement du date evenement` est genere, avec un mot-cle `PV` passe par la couche de traduction, et la visibilite par defaut du document cree est maintenant `organisation`.

Les documents gerent maintenant une deuxieme portee, dediee a l edition. On peut donc definir separement qui voit un document et qui peut le modifier, avec les memes niveaux `public`, `organisation`, `cercle`, `role` et `moi`. Les nouveaux documents reprennent par defaut ces deux valeurs depuis les parametres de l application Documents, aux cotes de la configuration Nextcloud.

Quand un document reste en portee `moi` pour la vue ou l edition, `moi` designe bien son auteur. Si cette personne quitte ensuite le role, le cercle ou l organisation qui porte le document, les portees `moi` restantes sont maintenant remplacees automatiquement par une portee de releve plus durable, prioritairement `role`, sinon `cercle`, sinon `organisation`.

Le module Calendrier peut maintenant memoriser des invites explicites sur les evenements, en invitant des holons, des membres individuels ou des adresses e-mail externes. La creation et l edition d un evenement passent par un nouvel onglet `Invites`, pre-cochent le contexte courant pour limiter les oublis, et la fiche detail affiche ensuite un resume de cette liste avec un bouton `Edit` ouvrant une popup dediee.

## 2026-07-08

Le repertoire local `tmp/` n est plus synchronise avec GitHub. Il est maintenant ignore par le depot, et les fichiers temporaires qui y etaient deja suivis ont ete retires de l index sans etre supprimes localement.

Le choix explicite de langue dans le profil reprend maintenant bien la main sur l affichage. Si un compte gardait encore une ancienne preference en base, le cookie mis a jour par le selecteur etait auparavant ignore au profit de cette valeur stale, ce qui pouvait laisser l interface en anglais meme apres avoir choisi `Francais`.

Une regression de resolution d URL a aussi ete corrigee pour les acces sous `.../omo/`. Les chargements relatifs comme `api/getOrg.php` ou `api/getStructure.php` etaient resolves a tort depuis la racine du domaine, ce qui envoyait certaines requetes vers `/api/...` au lieu de `/omo/api/...` et provoquait des `404`.

Le chargeur de traductions traite maintenant la langue source comme un affichage direct, sans creer de bundle `fr` vide marque `outdated`. Pour les autres langues, le comportement reste non bloquant: la page affiche tout de suite les textes deja disponibles, lance le refresh en arriere-plan si le bundle est manquant ou obsolete, puis la traduction complete est utilisee au chargement suivant.

Les textes source francais des bundles ont ete relus et corriges sur les parcours Documents et sur l authentification partagee. Les accents, apostrophes, libelles de boutons et quelques formulations trop bancales ou trop brutes ont ete remis dans un francais plus propre, avec aussi un meilleur singulier/pluriel sur certains messages.

Le module Team a recu la meme passe de relecture sur ses textes source francais. Les vues cartes, compacte et carte geo, la popup d actions membre et les messages de retour API affichent maintenant des accents corrects et des libelles plus naturels, y compris pour les textes autour de la geolocalisation.

Le libelle `Systeme (...)` des selecteurs de langue n affiche plus la langue forcee par une preference utilisateur. Il montre maintenant uniquement la langue detectee par le navigateur ou le poste, meme si l interface affiche temporairement une autre langue choisie explicitement.

Le moteur de bundles relance a nouveau une traduction asynchrone quand une langue explicite comme `en` ou `de` est choisie mais que le bundle correspondant a ete vide ou supprime. Un ancien job `completed` pour le meme hash ne bloque plus la recreation effective du contenu traduit.

Les appels d apps OMO transportent maintenant explicitement la locale resolue de la page via `lang`, y compris pour les drawers et modales charges en `fetch` ou en `iframe`. Cela evite qu un module recharge son contenu avec un fallback serveur different de la langue deja affichee. Le module Team a aussi ete rebranche sur son propre bundle de traduction pour que son drawer puisse a nouveau demander des jobs asynchrones dans les langues non source.

## 2026-07-07

Dans le menu `Deplacer` des documents OMO, la liste des destinations tient maintenant compte du droit reel `CAN_CREATE_DOCUMENT`. Les holons et dossiers proposes sont filtres selon les permissions effectives de la personne connectee, et l action serveur refuse aussi un deplacement manuel vers une destination non autorisee.

Le deplacement d un document n emet plus de warning PHP en production quand le message de retour verifie si la visibilite a ete adaptee. Le code compare maintenant le type de visibilite final au type initial reel du document, au lieu de lire une variable inexistante.

Quand la preference de langue utilisateur reste sur `system`, les selecteurs partages affichent maintenant partout un code court coherent comme `FR`, `EN` ou `DE` au lieu de variantes melangees, et les bundles OMO seeds pour le topbar, l ecran d accueil et le panneau `Structure` ont ete completes pour eviter les retours ponctuels en francais au milieu d une interface anglaise ou allemande.

Dans OMO, le volet gauche peut maintenant se decouper entre le contexte `getOrg` en haut et une mini vue Structure vivante en bas. Cette carte compacte reste visible hors drawer, suit la navigation courante, accepte les clics pour changer de holon, se redimensionne avec un separateur horizontal memorise localement, et les refresh structure existants se propagent maintenant aussi a cette vue persistante. Son rendu reprend aussi mieux le comportement de la vue principale, avec zoom de focus lors de la navigation, tri D3 aligne pour rapprocher le placement des noeuds, contour actif recale sur le disque reel, et repli automatique quand l application `Structure` est desactivee pour l organisation.

Dans la structure OMO, les holons peuvent maintenant stocker un `nom complet` facultatif, distinct du nom court. La vue graphique, les chemins et les selects de contexte gardent le nom court, tandis que la vue liste de `Structure` et la fiche `getOrg` affichent le nom complet quand il est renseigne, avec migration SQL et schema Docker alignes.

Dans OMO, cliquer sur une vignette membre encore invitee n ouvre plus un simple refus de profil. La fiche membre detecte maintenant l invitation en attente, explique clairement pourquoi le profil detaille reste indisponible, liste les acces en attente, et permet aux responsables du contexte de renvoyer l e-mail sans exposer le lien d invitation ni le profil complet avant acceptation, y compris en mode admin d organisation.

Quand une personne est retiree d un holon dans OMO, une entree d historique est maintenant enregistree comme pour l ajout. Si le retrait coupe aussi des rattachements en cascade, l historique liste tous les holons effectivement desactives afin que chaque contexte retire garde une trace lisible de l operation.

Dans les editeurs de proprietes de holon et de holon template, le collage dans une liste de texte decoupe maintenant automatiquement les elements multi-lignes. Un collage venant d un texte brut ou d un HTML avec des blocs comme `p`, `div` ou `li` cree des lignes distinctes au lieu de tout concatener dans le meme champ, et les prefixes de liste les plus courants comme `-`, `*`, `•`, `1.` ou `a)` sont retires au passage.

Dans TEAM, les cartes membres en attente proposent maintenant une action `Annuler l invitation` dans le menu `...`. L annulation supprime les rattachements inactifs prepares pour cette invitation et conserve le lien recu dans un etat explicite, avec un message du type `Desole, cette invitation a ete annulee.` si quelqu un essaie encore de l ouvrir ensuite.

Dans OMO, ajouter une personne deja invitee a un holon ou la re-rattacher a l organisation ne valide plus implicitement son adhesion. Les rattachements supplementaires restent maintenant inactifs tant que l invitation admin d origine n a pas ete acceptee, ce qui conserve bien l etat `pending` tout en preparant les acces de holon a activer plus tard.

Dans le LMS OMO embarque, le catalogue public hors organisation affiche maintenant uniquement les parcours vraiment accessibles. Les cartes `public` et `basic` visibles ne ressortent plus grisees par erreur, les parcours lies a une application restent masques tant qu aucune organisation n est selectionnee, et les prerequis anonymes deja completes via le suivi local peuvent aussi etre pris en compte dans le filtrage du catalogue.

## 2026-07-06

Le detail de mission du LMS embarque retrouve maintenant un vrai retrait interne dans le drawer OMO. Le contenu repasse par une variante partagee de `generic-section` sans bord ni arrondi, ce qui remet de l air autour de la mission sans casser les headers pleine largeur.

Le bouton `Lancer` du moteur de recherche dans la topbar et dans la popup OMO reutilise maintenant la primitive partagee `generic-action-button--main`, et le champ associe passe aussi par `generic-form-control`. La recherche s aligne ainsi sur le look des boutons generiques du site au lieu de conserver une variante locale.

La popup de resultats de recherche OMO aligne maintenant aussi ses cartes d etat, ses badges de resume, ses compteurs de modules et son champ de saisie sur les variables de theme partagees. En mode sombre, le resume, le bloc `Recherche en attente` et l input n imposent donc plus de fonds clairs ou gris.

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

- 2026-07-13 : Les points des PV peuvent maintenant etre portes soit par un membre de l organisation, soit par une adresse e-mail externe issue des invites de la reunion. Les auteurs externes restent disponibles dans le selecteur et ne recoivent pas de role ou holon concerne.
- 2026-07-13 : Le rafraichissement automatique de l editeur de PV compare maintenant une empreinte du vrai contenu des points. Les pulsations de verrou locales et une liste de presence inchangee ne reconstruisent plus les cartes, ce qui preserve la saisie, les menus ouverts et la position de scroll.

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
- 2026-07-06 : Les parametres specifiques aux applications OMO peuvent maintenant etre stockes dans `organization_application.parameters`. Le hub `Parametres` detecte les ecrans `/omo/api/<app>/params/index.php`, et l application Documents gere desormais sa configuration Nextcloud dans son propre panneau reserve au mode admin d organisation.
- 2026-07-06 : La resolution de la configuration Nextcloud de Documents accepte maintenant aussi une URL WebDAV complete collee dans les parametres applicatifs et relit plus robustement le JSON stocke sur `organization_application.parameters`, afin d eviter des `404` lies a une URL dupliquee ou a un format de config intermediaire.
- 2026-07-07 : La mini-carte de structure du volet gauche se met maintenant a jour automatiquement apres une modification de la liste des applications OMO. Elle se masque si l application `STRUCTURE` n est plus disponible, reapparait quand elle revient, et reste desactivee sur mobile pour ne pas reserver de place inutile.
- 2026-07-09 : Les invitations d evenements pilotent maintenant aussi la visibilite des reunions dans le resume personnel OMO et dans l export CalDAV. Sans invitation explicite, l evenement reste visible aux membres du holon rattache, ou a toute l organisation s il n a pas de holon. Avec une liste d invites, seuls les holons, membres ou e-mails invites continuent d y acceder.
- 2026-07-09 : Les onglets generiques ignorent maintenant correctement les jeux d onglets imbriques. Dans l editeur d evenement, cela retablit notamment la selection normale du premier onglet d invitations quand le panneau `Invites` contient lui-meme des sous-onglets.
- 2026-07-09 : L editeur d invites des evenements propose maintenant un filtre rapide dans les onglets `Holons` et `Membres`. Le filtrage garde visibles les elements deja coches, ainsi que les branches parentes utiles dans l arbre des holons, pour faciliter les selections sans perdre le contexte.
- 2026-07-09 : Le filtre rapide des invites force maintenant aussi le rendu CSS des elements `hidden` dans l editeur calendrier, pour eviter que des `display: grid` ou `display: flex` locaux empechent visuellement la disparition des lignes filtrees.
- 2026-07-09 : Dans le resume personnel OMO, une invitation explicite a un evenement prime maintenant sur le simple filtre de contexte courant. Une reunion rattachee a un autre cercle reste donc visible a la personne explicitement invitee, meme si elle n appartient pas a ce cercle.
- 2026-07-10 : Depuis le detail d un evenement, le bouton d ouverture du document associe passe maintenant par la navigation hash de l app OMO au lieu de charger le document dans le drawer calendrier. Le retour navigateur permet donc de revenir plus naturellement a l evenement precedent.
- 2026-07-10 : Le panneau Documents continue de cacher les documents dates dans le futur dans sa liste, mais une ouverture directe par hash ou URL peut maintenant tout de meme afficher leur detail si les droits le permettent. En cas d acces refuse ou de document introuvable, le drawer affiche desormais explicitement le message de refus au lieu de rester silencieux.
- 2026-07-10 : Les routes hash directes de type `#documents-d...` ou `#documents-de...` rebasculent maintenant correctement le drawer Documents en portee `global` quand le document vise n appartient pas a la liste deja chargee. Coller une URL directe vers un document pendant que le module Documents est deja ouvert recharge donc bien le bon contenu au lieu de rester sur la vue courante.
- 2026-07-10 : Les changements de hash internes au module Documents, y compris via `back` navigateur ou collage direct d une URL `#documents-d...`, sont maintenant appliques aussi par un helper global du module. Le sous-drawer du document cible se rouvre donc meme quand on reste deja dans le drawer Documents.
- 2026-07-10 : Le reroutage interne du module Documents tolere maintenant aussi un echec du helper global et retombe proprement sur son evenement de route habituel. Une erreur JS locale dans ce helper ne bloque donc plus l ouverture d un document au clic ni via changement de hash.
- 2026-07-10 : Le viewer direct des Documents ne depend plus de la presence du document dans la liste courante. Un document masque de la liste parce qu il est date dans le futur peut maintenant quand meme s ouvrir par hash ou URL si ses droits de visibilite le permettent dans l organisation, y compris pour les PV consultes avant la reunion.
- 2026-07-10 : Le contenu detail d un document peut maintenant piloter lui-meme l entete du sous-drawer Documents. Le vrai nom du fichier ou du document remonte donc depuis la vue detail apres chargement, y compris pour les ouvertures directes hors liste.
- 2026-07-10 : Les PV associes a une reunion future s ouvrent maintenant dans un drawer horizontal dedie en haut de page, inspire d EasyPV. Les invites de la reunion peuvent y preparer le PV avant le debut, voir tous les points, ajouter les leurs et n editer que les points dont ils sont auteurs, sans perdre le contenu charge quand ils ferment puis rouvrent ce drawer.
- 2026-07-10 : Le drawer special de preparation des PV avant reunion s ouvre maintenant en vrai plein ecran sur OMO, pleine largeur et pleine hauteur du viewport, au lieu de rester contraint a la zone de contenu laterale.
- 2026-07-10 : Ce drawer plein ecran de preparation des PV se comporte maintenant comme un vrai panneau de reunion. Il descend plus visiblement depuis le haut, passe au-dessus de toute l interface OMO, et peut se replier en laissant une languette centrale avec le nom du document pour rouvrir rapidement la reunion pendant la navigation.
- 2026-07-10 : La languette centrale des PV est maintenant vraiment placee sous le panneau de reunion ouvert. Le top drawer reserve une bande basse pour cette poignee, et en mode replie le panneau peut remonter completement hors ecran tant que la languette reste disponible pour le rouvrir.
- 2026-07-10 : La poignee des PV suit maintenant visuellement le mouvement du top drawer au lieu de sauter entre le bas et le haut. Elle reste centree et collee au bord du panneau, ce qui renforce l effet d onglet attache au drawer.
- 2026-07-10 : La languette de reunion des PV garde maintenant en permanence des angles arrondis uniquement en bas, sans espace avec le drawer quand il est ouvert. Sa hauteur a aussi ete reduite pour rester legerement inferieure a celle de la topbar OMO.
- 2026-07-10 : Le top drawer de preparation des PV laisse maintenant passer les clics sur le reste de l interface OMO. Seuls son contenu interactif et sa languette de reunion capturent encore les interactions, ce qui facilite la navigation pendant la preparation.
- 2026-07-10 : La languette de reunion des PV propose maintenant aussi une petite croix de fermeture definitive. Elle masque completement la reunion ouverte, vide son etat conserve et retire sa route hash, de sorte qu il faut repasser par le calendrier pour la rouvrir.
- 2026-07-10 : L editeur de PV avant reunion n a plus besoin d une route hash dediee pour s ouvrir. Depuis le calendrier et depuis la liste Documents, un PV preparable s ouvre maintenant directement dans son top drawer sans modifier l URL, et il disparait naturellement au rechargement de page.
- 2026-07-10 : La fermeture ou la suppression de ce top drawer PV ne modifie plus non plus le hash courant. L editeur reste donc totalement independant de la navigation par URL, aussi bien a l ouverture qu a la fermeture.
- 2026-07-10 : Une ouverture de document via hash `#documents-d...` repasse maintenant toujours par le viewer normal, y compris pour les PV. L editeur de preparation PV n est plus declenche que par des clics directs qui le demandent explicitement, ce qui retablit l affichage correct des PV deja passes.
- 2026-07-10 : Depuis la liste Documents elle-meme, un clic sur un document n ouvre plus jamais l editeur PV avant reunion. Le module Documents repasse systematiquement par son hash et par le viewer normal, tandis que l ouverture directe de l editeur reste reservee aux boutons du calendrier.
- 2026-07-10 : L editeur de preparation des PV propose maintenant aussi un separateur vertical draggable entre la liste des points et la zone de contenu. Chaque colonne possede son propre scroll, et leur largeur peut etre ajustee a la souris comme dans l ecran principal OMO.
- 2026-07-10 : Les drawers internes OMO ne sont plus bloques a une largeur fixe sur grand ecran. Ils occupent maintenant toute la largeur disponible moins une bande de 50px, tout en gardant le comportement plein ecran des qu il n y a plus assez de place.
- 2026-07-10 : Le detail d un document suit maintenant aussi cette largeur et ne recentre plus son article a `920px` quand il est affiche dans un sous-drawer OMO. Hors overlay, sa presentation centree reste inchangee.
- 2026-07-09 : La mini structure de la homepage OMO garde maintenant le nom de l element courant uniquement dans la capsule basse, et affiche les libelles de ses sous-cercles ou roles directs seulement quand leur taille a l ecran le permet.
- 2026-07-13 : Les verrous d edition des points de PV sont maintenant aussi liberes lors d une vraie sortie de page, y compris apres confirmation d un rechargement ou d une navigation navigateur. Un fallback `fetch` avec `keepalive` complete le beacon; les verrous actifs restent renouvelles toutes les 30 secondes et un verrou sans signal devient automatiquement inactif apres 120 secondes.
- 2026-07-14 : L editeur Summernote des points de PV peut maintenant inserer un document visible via un bouton dedie. Le selecteur reprend la mecanique Documents et produit un bloc de document lie dans le contenu; il n est propose que lorsque l application `DOCUMENTS` est activee.
- 2026-07-14 : Les blocs Documents inseres dans un point de PV utilisent maintenant une structure HTML valide et preservee par le sanitiseur serveur. Leur lien et leur cadre restent donc presents apres enregistrement et rechargement du point; le bouton Summernote affiche aussi l icone Documents de facon fiable.
- 2026-07-14 : Les inclusions de documents conservees dans un point de PV utilisent aussi le rendu HTML partage hors edition. Leur cadre reste donc visible apres une sauvegarde, y compris lorsque le point est reaffiche en lecture seule.
- 2026-07-14 : Les points de PV peuvent maintenant aussi inclure une decision visible via un bouton Summernote carre. Le bloc conserve le titre et le type de la decision apres sauvegarde, avec le meme mecanisme de sanitation partage que les inclusions de documents.
- 2026-07-14 : Le type d une decision integree est maintenant affiche dans une capsule au sein de son bloc, en edition comme en lecture seule, afin de preparer des variantes visuelles selon les futures phases de decision.
- 2026-07-14 : Les inclusions de documents et decisions dans les points de PV peuvent maintenant etre modifiees par double-clic. Le chargement du champ HTML est aussi versionne pour que les navigateurs recents recoivent bien le sanitiseur qui conserve les nouveaux blocs de decisions.
- 2026-07-14 : Les documents inclus dans un point de PV ouvrent desormais leur detail par hash sans navigation complete. Une action secondaire permet aussi de les ouvrir dans un nouvel onglet.
- 2026-07-14 : L ouverture interne d un document depuis un point de PV conserve maintenant le drawer de reunion. Le PV se replie automatiquement dans sa languette persistante pendant que le detail du document s ouvre, au lieu d etre ferme et de perdre son contexte de travail.
- 2026-07-14 : Les blocs de documents gardent maintenant leur cadre `Document lie` dans tous les etats de rendu de l editeur PV. Les blocs de scrutins utilisent aussi la navigation interne par hash et replient le PV sans le fermer, comme les documents lies.
- 2026-07-14 : Les documents et scrutins inseres dans les points de PV sont maintenant presentes sous forme de cartes compactes : vignette a gauche, titre sur une ligne et resume limite a deux lignes pour les documents.
- 2026-07-14 : Les cartes de documents et scrutins des PV ne repetent plus leur libelle `lie`. Les marges de paragraphes Summernote sont aussi neutralisees autour de ces inclusions pour rapprocher titre, resume et cartes successives.
- 2026-07-14 : Les points de PV peuvent maintenant referencer une date deja programmee via un bloc Calendrier compact. Le selecteur ne s affiche que si Calendar est actif, conserve le bloc a la sauvegarde et ouvre l evenement par son hash tout en repliant le PV.
- 2026-07-14 : Les selecteurs de documents, decisions et dates integres aux points de PV permettent maintenant aussi de supprimer un bloc existant. Les popups recoivent egalement une marge interne pour mieux respirer.
- 2026-07-15 : Les groupes de la mini structure OMO utilisent maintenant un pointille plus fin et plus court, avec un trace a `1px`, pour alleger leur rendu dans la homepage.
- 2026-07-15 : L ajout ou l edition d un holon depuis le menu contextuel `getOrg.php` relance maintenant aussi le vrai refresh partage des vues Structure. La mini structure de la homepage se recharge donc en meme temps que le drawer Structure, au lieu de rester sur un etat visuel stale.
- 2026-07-15 : Dans la mini structure OMO, la selection d un role affiche maintenant aussi le nom du role courant et les libelles de ses elements freres dans le meme cercle, au lieu de se limiter aux enfants du noeud courant.
- 2026-07-15 : L action `Supprimer` n apparait plus dans `getOrg.php` pour la derniere instance d un role obligatoire. Le controle regarde maintenant toute la chaine de templates heritee afin de bloquer aussi les roles derives d un modele obligatoire.
- Le changement de statut d'une tache depuis sa fiche recharge le detail et les barres recursives concernees ; le kanban est actualise a la fermeture du drawer.
- Les details, editions et creations de projets utilisent maintenant des routes hash distinctes (`projects-d`, `projects-e`, `projects-new`) pour permettre la navigation avec les boutons precedent et suivant.
- Les changements de route entre detail et edition des projets reutilisent maintenant le drawer interne sans recharger le drawer principal, ce qui preserve le kanban et sa position de defilement.
- Les barres d'avancement des sous-projets affichent maintenant leurs segments dans un ordre stable : pret, en cours, bloque, a verifier, termine, puis un jour peut-etre.
- Le menu contextuel des cartes projet permet maintenant de deplacer un projet avec la carte graphique des holons, d'archiver son arborescence et de supprimer definitivement un projet avec ses sous-projets.
- Le deplacement de projet reconnait correctement les holons rattaches a une organisation par leur ancetre structurel.
- Les lignes de sous-projets indiquent maintenant le holon concerne avant le responsable.
- Une tache sans sous-projet reste visible dans son contexte lorsque son projet parent est hors du perimetre kanban courant.
- Le survol d'une carte projet met maintenant en evidence ses enfants directs visibles dans le kanban.
- La creation rapide d un projet depuis un PV actualise immediatement le drawer Projects ouvert; lorsqu il est ferme, son contenu en cache est vide pour etre recharge a la prochaine ouverture.
- Les blocs Projet inseres dans un PV sont maintenant reconnus par le sanitiseur serveur et conservent leur mise en page, leurs liens et leurs metadonnees apres sauvegarde.
- Un lien direct vers le detail d un projet ouvre maintenant aussi son sous-drawer lorsque les preferences locales Projects remplacent la vue initiale.
- L ouverture d un projet depuis un bloc lie dans un PV relit maintenant le hash apres le chargement du drawer principal, afin d ouvrir de facon fiable son sous-drawer de detail.
- Depuis une reunion ouverte dans la fenetre courante, un lien Projet relance aussi explicitement son detail apres le repli du drawer de reunion.
- La fermeture du detail Projet depuis une reunion ne tente plus de charger un projet sans identifiant lorsque le hash revient a la vue Projects.
- Les indicateurs a completer utilisent maintenant un avertissement jaune pendant un delai de grace adapte a leur frequence. Le retard devient rouge uniquement apres ce delai et affiche alors son nombre de jours.
- Les graphiques combines reprennent maintenant la severite de leurs indicateurs : jaune quand ils sont seulement a completer, rouge uniquement lorsqu au moins un indicateur est reellement en retard.
- L editeur de FAQ nettoie maintenant ses instances Summernote avant chaque remplacement de popup ou de vue. La creation reste disponible lors des ouvertures successives, et le formulaire d edition initialise explicitement son editeur riche apres son chargement asynchrone.
- Les documents associes depuis le detail d un projet utilisent maintenant la navigation hash de l application.
- Les documents associes d un projet sont maintenant charges uniquement a l ouverture de leur onglet.
- L onglet Documents affiche un cadre d etat vide avec une action Ajouter un fichier lorsqu aucun document n est associe.
- Le menu contextuel des cartes Projet reste maintenant au-dessus des cartes voisines lorsqu il est ouvert.
- Les taches du detail Projet peuvent maintenant etre archivees ou supprimees depuis leur selecteur de statut, avec confirmation avant suppression.
- L archivage ou la suppression d une tache retire maintenant directement sa ligne du detail Projet, sans recharger le drawer.
- Les taches sans holon propre heritent maintenant des droits de gestion de leur projet parent pour le statut, l archivage et la suppression.
- Les invitations peuvent a nouveau verifier les contraintes d administration d une organisation sans appeler une methode protegee du holon.
- Les bornes minimum et maximum d administrateurs indiquent maintenant clairement leur valeur heritee dans l editeur de modeles de holons.
- L editeur de modeles de holons ouvre maintenant sur un ecran d accueil, au lieu d afficher directement un formulaire de nouveau modele.
- L ecran d accueil de l editeur de modeles disparait correctement des l ouverture du formulaire de creation.
- Le detail d un projet affiche maintenant un lien texte vers ses sous-projets archives, avec leur nombre et une popup chargee a la demande.
- Le lien des archives est maintenant presente dans une capsule grise avec le nombre dans un rond blanc.
