# Revue des styles OMO - 2026-09-17

## Constats et corrections

- La bibliotheque de composants est deja largement utilisee. Les principaux
  doublons venaient de surcharges locales des composants, plutot que d'une absence
  de styles communs.
- Les champs et boutons dynamiques de l'editeur Holon reproduisaient les controles
  partages. Ils utilisent maintenant `generic-form-field`, `generic-form-control`
  et `generic-action-button`, y compris les listes de proprietes.
- Les boutons d'ouverture du tableau de bord, de fermeture des tiroirs, des
  activites, des checklists et des cartes de parametres partagent les variantes
  standard ou compacte. Les surcharges de hauteur et les boutons en pilule
  specifiques a ces actions ont ete retires.
- Les compteurs du tableau de bord utilisent `generic-action-button--metric`,
  avec chiffres en pastille et libelles sur la meme ligne, et `aria-pressed` pour
  annoncer la selection. La variante partagee `generic-action-button--metric-alert`
  colore en rouge les retards non nuls des projets et activites, y compris au survol
  et pendant la selection. L espacement de 16 px passe par `--param-section-gap`
  pour rester effectif apres le chargement des feuilles partagees.
  Les ombres des tuiles et les bordures des lignes internes ont ete allegees ; les
  reperes colores des evenements et des indicateurs en retard sont conserves.
- Les formulaires Holons, Documents, Agenda, Activites, Checklists, Regles et
  Indicateurs utilisent `generic-form-section--divided`. Les sections internes
  sont separees par une ligne, sans cumuler fonds, bordures et ombres. Les cartes
  autonomes et les panneaux de retour d'erreur conservent leur role visuel.
- Les onglets du formulaire Agenda utilisent `generic-tabs--embedded` : leur
  conteneur ne rajoute pas un cadre a celui du tiroir. Le padding inline et la
  declaration contradictoire ont ete retires.
- Les boutons ont des ombres plus discretes et un focus clavier explicite. Les
  boutons de suppression utilisent la couleur de danger du theme, y compris en
  mode sombre. Les champs et les controles composites de tags partagent leur
  traitement du focus ; les transitions respectent la reduction des animations.
- Les surfaces secondaires des palettes claires sont beaucoup plus discretes :
  le gris commun passe de `#e7e9ec` a `#f5f6f8`. Les variantes turquoise et ocean
  sont egalement eclaircies. Les palettes sombres sont conservees. Ce reglage
  appartient aux themes partages et beneficie donc aux autres pages qui les utilisent.
- Les textes secondaires du tableau de bord utilisent le token de theme existant
  `--color-text-light`, au lieu de `--color-text-soft`, absent des palettes et dont
  la couleur de secours etait peu lisible en mode sombre.
- Les blocs CSS des editeurs Holons, Documents et Agenda et de la page Parametres
  sont maintenant des feuilles externes, a cote des modules. Les regles locales
  qui repetaient les composants et plusieurs regles `[hidden]` redondantes ont
  ete supprimees.
- Les declarations locales `--generic-*` des quatre feuilles extraites et de
  `assets/css/styles.css` ont ete retirees ou remplacees par des `--param-*`.
  Les composants partages gardent leurs valeurs par defaut.
- L'import des composants dans la feuille OMO est versionne avec le chargement
  principal pour eviter qu'une ancienne version en cache reintroduise les styles
  precedents. Le style de fermeture historique reste disponible pour Memo.

## Calendrier : fenetres du menu Actions

- Menu descriptif et navigation par fleches, Debut/Fin et Echap avec retour au bouton.
  Les trois actions sont directement visibles dans le menu Actions sur mobile,
  sans second menu a ouvrir.
- Connexion et partage : sections avec separateurs et largeur de lecture limitee.
- Rendez-vous : horaires hebdomadaires compacts, petits champs de 104 px, pause
  de midi entre debut et fin, actions d enregistrement visibles pendant le defilement.
- Feuilles `calendar/calendar.css` et `calendar/popups.css` externalisees ; les
  controles avec action et les pieds de formulaire fixes sont des primitives partagees.
- Navigation mensuelle avec les memes chevrons que les vues semaine et jour ;
  reperes de jours allegees.

## Limites et suites possibles

- Il reste des styles integres dans les reponses PHP, notamment les grandes vues
  Structure, Team et Decisions. Leur presence ne prouve pas une redondance :
  les grilles calendaires, les cartes et les editeurs riches ont des besoins propres.
  Une extraction supplementaire doit conserver les conditions PHP et l'ordre de
  la cascade. Les modifications locales preexistantes de la vue Agenda ont ete conservees.
- Les composants de decision communs restent centralises dans `/common/choice/`.
  Aucune nouvelle bibliotheque parallele n'a ete ajoutee dans OMO.
- Les selecteurs servant aussi de reperes JavaScript sont conserves, meme lorsque
  leur presentation est maintenant entierement fournie par une classe generique.
- Cette revue ne certifie pas que chaque selecteur du projet est utilise : les
  classes composees dynamiquement et les ecrans conditionnes par les droits
  necessitent des parcours avec des donnees representatives.

## Validation

- Syntaxe des fichiers PHP modifies controlee sous PHP 8.5.10.
- Syntaxe de `assets/js/app.js` et `git diff --check` controles.
- Planche temporaire utilisant les vraies feuilles CSS : themes clair et sombre,
  largeurs de 390 et 1280 px, focus des champs et des tags, boutons standard et
  compacts, sections de formulaire et listes de proprietes.
- Formulaire Documents ouvert dans l'application avec un compte de test local,
  sur ordinateur et mobile. A 390 px, la page reste a 390 px et le contenu du
  tiroir ne deborde pas horizontalement. Aucun document de test n'a ete enregistre.
- Formulaire Agenda et bascule entre ses onglets Evenement et Invites verifies
  dans l'application. Invitation acceptee dans l'organisation configuree fournie
  par l'utilisateur pour controler aussi le tableau de bord avec ses donnees.
- Tableau de bord verifie en clair et sombre dans cette organisation ; le filtre
  d'un compteur masque les elements attendus et les retablit au second clic.
- Les controles visuels de composants ne remplacent pas un test de tous les
  parcours de sauvegarde et de permissions ; aucune logique metier n'a ete changee.
