-- @migration
-- Catalogue utilisateur issu de NOUVEAUTES.md, juin a octobre 2026.
-- Aucune correspondance par ID predefini : titre de question + portee generique.
-- Les FAQ existantes, leur contenu et leur publication ne sont jamais modifies.
-- Une FAQ existante non publiee produit une astuce non publiee.
-- Relancable : une astuce deja liee a cette FAQ est conservee telle quelle.

DROP TEMPORARY TABLE IF EXISTS tip_help_seed;
CREATE TEMPORARY TABLE tip_help_seed (
    seed_key varchar(80) NOT NULL PRIMARY KEY,
    question varchar(255) NOT NULL,
    answer text NOT NULL,
    detail mediumtext NOT NULL,
    tip_text varchar(500) NOT NULL,
    displayorder int NOT NULL,
    faq_id int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reperes, recherche et preferences - juin a octobre 2026
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'scope',
    'A quoi sert le switch Local / Enfants directs / Descendants ?',
    'Il permet d afficher le holon courant seul, avec ses enfants directs ou avec tous ses descendants.',
    '<p>Le mode Local affiche uniquement les elements lies au holon courant.</p><p>Enfants directs ajoute les elements lies a ses enfants, tandis que Descendants couvre tout son sous-arbre.</p>',
    'Une liste vide ne veut pas toujours dire qu''il n''y a rien : sa portee peut cacher les autres espaces.',
    1000
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'views',
    'Comment conserver mes filtres et ma vue dans une application ?',
    'Appliquez une vue pour la session ou enregistrez-la, selon votre niveau d''utilisation et vos droits.',
    '<ol><li>Ouvrez les capsules de filtres sous le titre de l''application et choisissez la portee, le tri ou la representation.</li><li>Utilisez Appliquer pour travailler avec ces choix temporairement dans cet onglet.</li><li>Si Enregistrer la vue est disponible, utilisez-le pour conserver ces choix. Sa fleche propose les autres niveaux d''enregistrement et de restauration autorises.</li></ol><p>En mode Decouverte, les membres disposent surtout des changements temporaires. Les actions de vue par defaut dependent de vos droits.</p>',
    'Gardez une vue pour vous, sans imposer vos filtres a toute l''equipe.',
    1010
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'dashboard-layout',
    'Comment personnaliser les modules de mon tableau de pilotage ?',
    'La configuration du tableau permet de choisir ses modules, leur disposition et les filtres de chacun.',
    '<ol><li>Ouvrez le tableau de pilotage dans le contexte souhaite, puis sa configuration si elle vous est autorisee.</li><li>Ajoutez ou ajustez les modules utiles et configurez leur portee individuellement.</li><li>Enregistrez la vue au niveau propose. La fleche du bouton donne acces aux vues par defaut que vous avez le droit de gerer.</li></ol><p>La personnalisation personnelle et les defauts partages dependent du mode d''utilisation de l''organisation.</p>',
    'Votre tableau de pilotage peut devenir le point de depart de votre journee, avec les informations vraiment utiles.',
    1020
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'dashboard-mine',
    'Comment limiter le tableau de pilotage à ce qui me concerne ?',
    'Réglez la portée des modules Projets, Indicateurs ou Tâches récurrentes sur Moi ou Mes espaces.',
    '<p>Dans le tableau de pilotage, ouvrez la configuration du module concerné. Pour les projets, les indicateurs et les tâches récurrentes, choisissez <strong>Moi</strong> pour privilégier les éléments qui vous sont attribués, <strong>Mes espaces</strong> pour élargir la vue à vos espaces, ou <strong>Tous</strong> pour la portée la plus large.</p><p>Les éléments effectivement visibles restent soumis à vos droits d’accès. Dans l’application Projets, le filtre distinct <strong>Suivi</strong> montre les projets suivis par au moins une personne ; il ne signifie pas « suivis par moi ».</p>',
    'Un tableau de pilotage charge peut se recentrer sur vos responsabilites.',
    1030
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'search-modules',
    'Comment chercher dans plusieurs applications a la fois ?',
    'La recherche de la topbar interroge les modules selectionnes et peut limiter les resultats a une periode.',
    '<ol><li>Saisissez quelques mots significatifs dans la recherche en haut de la page.</li><li>Cochez les modules utiles et ajustez les dates si vous cherchez une information ancienne ou recente.</li><li>Lancez la recherche. Utilisez les compteurs de modules pour parcourir plus facilement les resultats.</li></ol><p>Seuls les contenus auxquels vous avez acces sont proposes.</p>',
    'Une phrase entendue en reunion peut suffire a retrouver le point de PV qui en parle.',
    1040
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'search-preview',
    'Comment examiner un resultat sans perdre ma recherche ?',
    'L''apercu d''un resultat montre ses informations essentielles ; la navigation conserve la recherche de l''onglet.',
    '<ol><li>Lancez une recherche et ouvrez l''apercu du resultat qui vous interesse.</li><li>Consultez son resume puis ouvrez l''objet complet si necessaire.</li><li>Utilisez le bouton Retour du navigateur pour retrouver la recherche et poursuivre votre lecture.</li></ol>',
    'Vous pouvez explorer plusieurs pistes sans perdre votre recherche.',
    1050
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'profile-look',
    'Comment changer ma langue ou mon theme ?',
    'Ouvrez le menu Profil dans la topbar pour regler la langue et le theme d affichage.',
    '<p>Le menu Profil permet de retrouver les reglages personnels les plus utiles sans quitter votre espace de travail.</p><p>Vous pouvez y adapter la langue de l interface et choisir le theme qui vous convient le mieux pour votre usage quotidien.</p>',
    'Votre langue et votre theme peuvent suivre vos preferences plutot que celles de votre appareil.',
    1060
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'mobile-tabs',
    'Où sont passés les onglets sur mobile ?',
    'Sur petit écran, les onglets sont regroupés dans un menu déroulant qui affiche le nom complet de l’onglet actif.',
    '<p>Si les onglets côte à côte ne sont plus visibles sur votre téléphone, touchez le menu qui porte le nom de l’onglet actif, puis sélectionnez celui que vous voulez ouvrir. Les mêmes contenus et actions restent disponibles.</p><p>Sur un écran plus large, les onglets réapparaissent côte à côte. Dans la navigation mobile, le tableau de bord porte désormais le nom <strong>Pilotage</strong>.</p>',
    'Sur telephone, tous les onglets restent accessibles, meme lorsqu ils ne tiennent plus cote a cote.',
    1070
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'mobile-menu',
    'Ou trouver les actions d''une application sur telephone ?',
    'Le menu de l''en-tete regroupe les actions pour garder de la place sur les petits ecrans.',
    '<ol><li>Ouvrez l''application concernee.</li><li>Touchez son bouton de menu en haut a droite.</li><li>Choisissez l''action voulue. Les actions proposees dependent de l''application et de vos droits.</li></ol>',
    'Sur mobile, les actions discretes du menu donnent acces aux memes outils que sur ordinateur.',
    1080
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'fullscreen',
    'Comment agrandir une application en plein ecran ?',
    'Le plein ecran donne plus de place au contenu tout en gardant les commandes utiles accessibles.',
    '<ol><li>Ouvrez le menu d''actions de l''application.</li><li>Choisissez Plein ecran.</li><li>Utilisez la commande de sortie ou la touche Echap pour revenir a la disposition precedente.</li></ol>',
    'Un grand tableau ou un long document gagne parfois a occuper tout l''ecran.',
    1090
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'notifications',
    'Comment choisir les notifications que je recois ?',
    'Les reglages personnels permettent de choisir les evenements et les canaux disponibles pour chaque organisation.',
    '<ol><li>Ouvrez Parametres, puis les reglages de notifications personnels.</li><li>Pour l''organisation concernee, cochez les evenements et les canaux souhaites : navigateur, e-mail ou Telegram lorsqu ils sont disponibles.</li><li>Pour le navigateur, autorisez aussi les notifications sur cet appareil. Repetez cette activation sur vos autres navigateurs si necessaire.</li></ol><p>Les canaux disponibles dependent de la configuration du service et de vos connexions personnelles.</p>',
    'Vous pouvez recevoir les alertes utiles d''une organisation sans recevoir toutes celles des autres.',
    1100
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'notifications-read',
    'Comment vider les notifications non lues de la cloche ?',
    'L''action Tout marquer comme lu met a jour le panneau et son compteur.',
    '<ol><li>Ouvrez la cloche en haut de page.</li><li>Consultez les notifications utiles et leurs liens.</li><li>Choisissez Tout marquer comme lu pour retirer les autres du compteur de non-lus.</li></ol>',
    'Un retour de vacances ne vous oblige pas a ouvrir chaque ancienne notification une par une.',
    1110
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'tension',
    'Comment noter rapidement une tension ou un besoin ?',
    'Le bouton Tension de la topbar ouvre une saisie courte rattachee a une organisation et a un contexte.',
    '<ol><li>Cliquez sur le bouton eclair dans la topbar.</li><li>Decrivez le besoin et verifiez le contexte propose.</li><li>Enregistrez la tension pour la conserver. Les contextes selectionnables dependent de vos appartenances.</li></ol>',
    'Une idee surgit entre deux taches ? Gardez-en une trace sans interrompre longtemps votre travail.',
    1120
);

-- Documents et Memo - juin a octobre 2026
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'document-rights',
    'Comment distinguer les droits de lecture et d''edition d''un document ?',
    'Les champs de visibilite et d''edition permettent de definir deux publics differents.',
    '<ol><li>Ouvrez la creation ou la modification du document, selon vos droits.</li><li>Choisissez qui peut le voir, puis qui peut modifier son contenu.</li><li>Enregistrez et verifiez les deux portees. Le choix Moi designe le proprietaire du document.</li></ol><p>Pouvoir modifier le contenu collaboratif ne donne pas automatiquement le droit de deplacer le document ou de changer ses metadonnees.</p>',
    'Un document peut etre lisible par tous et modifiable par un seul role.',
    1130
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'document-tags',
    'Comment utiliser les tags pour retrouver un document ?',
    'Les mots-cles d''un document sont recherches comme des tags individuels.',
    '<ol><li>Dans le formulaire du document, renseignez les tags avec des mots courts et significatifs.</li><li>Validez chaque tag avec Tab ou une virgule, puis enregistrez le document.</li><li>Recherchez ensuite un de ces mots depuis la topbar pour retrouver les documents correspondants.</li></ol>',
    'Quelques tags bien choisis retrouvent un document meme quand vous avez oublie son titre.',
    1140
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'document-move',
    'Comment deplacer plusieurs documents dans un dossier ?',
    'La selection multiple permet un deplacement groupe vers une destination autorisee.',
    '<ol><li>Dans Documents, cochez les elements a deplacer.</li><li>Choisissez Deplacer dans les actions de selection.</li><li>Selectionnez l''espace et le dossier cible, verifiez le chemin final puis confirmez.</li></ol><p>Les destinations autorisees sont en couleur ; les destinations sans droit de depot sont grisees.</p>',
    'Un dossier a remettre en ordre ? Plusieurs documents peuvent etre ranges en une seule operation.',
    1150
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'document-collaboration',
    'Comment ecrire a plusieurs dans un document collaboratif ?',
    'Documents propose des editeurs partages lorsque les services correspondants sont actives.',
    '<ol><li>Dans Documents, ouvrez le menu de creation et choisissez le type collaboratif disponible.</li><li>Donnez un titre et reglez la visibilite ainsi que la portee d''edition.</li><li>Enregistrez, puis ouvrez le document. Les personnes autorisees peuvent travailler sur le meme contenu.</li></ol><p>Les types proposes dependent des services actives pour votre organisation.</p>',
    'Pour ecrire a plusieurs, un document partage evite les copies jointes et les versions contradictoires.',
    1160
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'document-sheet',
    'Comment creer un tableur collaboratif dans OMO ?',
    'Le menu Documents permet de creer un tableur partage si ce service est disponible.',
    '<ol><li>Ouvrez Documents puis le menu de creation.</li><li>Choisissez le tableur collaboratif et renseignez son titre et ses droits.</li><li>Enregistrez, puis ouvrez le tableur pour saisir les donnees. Le plein ecran facilite le travail sur une grande feuille.</li></ol><p>Sans droit d''edition, le tableur reste consultable en lecture seule.</p>',
    'Un tableur commun peut servir a la fois au travail de l''equipe et au suivi de ses indicateurs.',
    1170
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'document-whiteboard',
    'Comment utiliser un tableau blanc collaboratif ?',
    'Le type Whiteboard collaboratif de Documents ouvre un espace visuel partage.',
    '<ol><li>Dans le menu de creation des Documents, choisissez Whiteboard collaboratif si le type est disponible.</li><li>Definissez son titre et ses droits, puis enregistrez et ouvrez-le.</li><li>Utilisez les outils du tableau, dont le post-it a placer par un clic, pour construire votre support.</li></ol>',
    'Votre prochain atelier peut reunir idees et post-it sur un tableau blanc partage.',
    1180
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'document-preview',
    'Comment consulter une image ou ecouter un fichier audio dans Documents ?',
    'Les fichiers reconnus disposent d''un apercu ou d''un lecteur integre dans leur fiche.',
    '<ol><li>Ouvrez le fichier depuis Documents ou depuis le projet auquel il est lie.</li><li>Consultez l''image ou utilisez le lecteur audio affiche dans la fiche.</li><li>Utilisez le telechargement si vous souhaitez conserver une copie. L''apercu depend du type de fichier.</li></ol>',
    'Une image ou une note audio peut se consulter dans OMO sans telechargement systematique.',
    1190
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'memo-embed',
    'Comment inserer un Memo dans un autre document ?',
    'Le selecteur de ressources de l''editeur permet de referencer un Memo accessible.',
    '<ol><li>Ouvrez l''edition du contenu du document.</li><li>Placez le curseur a l''endroit voulu et utilisez le bouton d''insertion de document.</li><li>Cherchez le Memo dans le selecteur, inserez-le puis enregistrez le contenu.</li></ol><p>La consultation du contenu integre reste soumise aux droits du document source.</p>',
    'Une consigne presente dans plusieurs Memos peut garder une seule source a entretenir.',
    1200
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'memo-merge',
    'Comment reunir plusieurs Memos en un seul ?',
    'La fusion de Memos regroupe des contenus et propose le devenir des documents sources.',
    '<ol><li>Dans Documents, selectionnez les Memos a reunir puis utilisez Fusionner les Memos dans les actions de selection.</li><li>Relisez le contenu obtenu et verifiez sa visibilite : la fusion tient compte des portees des sources.</li><li>Choisissez de conserver ou de supprimer les sources selon les options proposees, puis confirmez.</li></ol><p>Conservez les sources si vous souhaitez pouvoir comparer les contenus apres la fusion.</p>',
    'Les notes dispersees sur un meme sujet peuvent devenir un seul Memo bien range.',
    1210
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'memo-personal',
    'A quoi sert l app Memo ?',
    'Memo rassemble vos documents personnels et vos notes dans une vue simple a parcourir.',
    '<p>La liste Memo peut regrouper les documents dont vous etes l auteur, y compris quand ils proviennent de plusieurs holons.</p><p>Le detail se consulte ensuite dans un drawer interne, ce qui permet de rester dans le meme espace sans ouvrir une nouvelle page.</p>',
    'Une note personnelle peut attendre dans Memo avant de trouver sa place dans une organisation.',
    1220
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'memo-telegram',
    'Comment terminer ou classer un memo depuis Telegram ?',
    'Utilisez les boutons proposes par le bot pour choisir une destination autorisee ou terminer dans le contexte courant.',
    '<p>Le bot ne propose que les destinations de classement qui restent autorisees pour votre contexte et vos droits.</p><p>Si le bouton Terminer ici ou certaines destinations ne sont pas visibles, cela signifie simplement que cette action nest pas disponible pour vous a cet endroit.</p>',
    'Une note prise en deplacement peut etre classee dans le bon role directement depuis Telegram.',
    1230
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'memo-dictation',
    'Comment transformer une note vocale en Memo ?',
    'La transcription vocale aide a preparer une note ecrite si le service IA est disponible pour votre compte.',
    '<ol><li>Ouvrez la saisie vocale proposee dans le parcours Memo, ou envoyez un vocal au bot Telegram apres avoir relie votre compte.</li><li>Laissez terminer la transcription puis ouvrez le Memo produit.</li><li>Corrigez le texte et son resume avant de le classer ou de le partager.</li></ol><p>L''acces depend de la configuration de l''instance et, si elle utilise Patreon, de votre contribution.</p>',
    'Une idee dictee peut devenir un Memo ecrit, a relire avant de le partager.',
    1240
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'document-template',
    'Comment créer un document à partir d’un modèle ?',
    'Dans Documents, utilisez la flèche à côté de Nouveau et choisissez un modèle compatible avec le contexte courant.',
    '<ol><li>Ouvrez Documents dans le contexte où vous voulez placer la nouvelle copie.</li><li>Cliquez sur la flèche à côté de <strong>Nouveau</strong>.</li><li>Choisissez le modèle souhaité parmi ceux proposés, puis vérifiez le duplicata créé.</li></ol><p>La copie est visible dans le contexte courant. Les réunions et les projets proposent également des modèles compatibles avec leur type et leur portée.</p>',
    'Votre prochaine reunion peut repartir d''un support eprouve sans modifier l''original.',
    1250
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'document-template-save',
    'Quels documents peut-on enregistrer comme modèles ?',
    'Les documents HTML, liens, PV, fichiers, outils collaboratifs et dossiers peuvent servir de modèles.',
    '<p>Dans Documents, ouvrez la fiche d’un document compatible et ajoutez-le à la liste des modèles. Cela concerne notamment un document HTML, un lien externe, un PV, un fichier tel qu’un ODT, un Etherpad ou EtherCalc, ainsi qu’un dossier.</p><p>Une étoile signale les modèles. Ils sont regroupés par espace et identifiés par leur icône de type pour faciliter leur choix.</p>',
    'Un document que vous recreez souvent merite peut-etre de devenir un modele.',
    1260
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'document-archive',
    'Quand choisir Archiver plutot que Supprimer pour un document ?',
    'L''archivage conserve le document ; la suppression retire definitivement les elements autorises.',
    '<ol><li>Ouvrez le menu d''actions du document.</li><li>Choisissez Archiver pour retirer un document des vues actives tout en le conservant.</li><li>N''utilisez Supprimer que si vous voulez reellement l''effacer, puis lisez la confirmation et les eventuelles restrictions.</li></ol><p>Les choix disponibles dependent du type de document, de ses liens et de vos droits.</p>',
    'Retirez un document des vues courantes tout en gardant la possibilite de le retrouver.',
    1270
);

-- Reunions et proces-verbaux - juillet a octobre 2026
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-from-event',
    'Comment associer un PV a une reunion du calendrier ?',
    'Un evenement peut porter un document de type PV, cree a la preparation ou ajoute ensuite.',
    '<ol><li>Creez ou modifiez l''evenement, puis ouvrez la partie consacree aux documents associes.</li><li>Ajoutez un PV et, si utile, choisissez un modele disponible.</li><li>Enregistrez puis ouvrez le PV depuis l''evenement pour preparer l''ordre du jour.</li></ol><p>La creation d''un document lie necessite le droit correspondant.</p>',
    'Le PV peut rester a portee de clic de la reunion, directement dans le calendrier.',
    1280
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-point',
    'Comment preparer mes points avant une reunion ?',
    'Pendant la preparation, les invites autorises peuvent ajouter et travailler leurs propres points.',
    '<ol><li>Ouvrez le PV de la reunion depuis le calendrier ou votre invitation.</li><li>Ajoutez un point, renseignez son titre et son contenu, puis choisissez Information, Consultation ou Decision.</li><li>Indiquez une duree estimee et enregistrez. Si vous etes membre, associez si besoin l''un de vos roles disponibles.</li></ol><p>Les droits d''edition dependent de la phase du PV et de votre role dans la reunion.</p>',
    'Un ordre du jour prepare ensemble laisse plus de temps pour les echanges pendant la reunion.',
    1290
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-groups',
    'Comment organiser l''ordre du jour en groupes ?',
    'L''editeur du PV peut creer des groupes imbriques et y ranger les points.',
    '<ol><li>Avec les droits de gestion de l''ordre du jour, ajoutez un groupe et donnez-lui un nom.</li><li>Rangez les points avec leurs poignees de deplacement ou les fleches disponibles.</li><li>Repliez les groupes pour vous concentrer sur une partie de la reunion et verifiez les durees cumulees.</li></ol><p>Pendant la preparation, un participant peut reclasser ses propres points dans les limites prevues ; l''editeur officiel gere l''ensemble.</p>',
    'Un ordre du jour long peut se lire par themes, avec un temps total pour chaque groupe de points.',
    1300
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-timing',
    'Comment suivre le temps disponible pendant une reunion ?',
    'Le PV affiche le temps de reunion, les durees des points et leur avancement.',
    '<ol><li>Renseignez les durees estimees des points pendant la preparation.</li><li>Passez le PV en phase Reunion au moment voulu.</li><li>Consultez le recapitulatif temporel et marquez les points traites pour actualiser la repartition du temps.</li></ol>',
    'Le PV peut vous aider a voir venir le depassement avant que la reunion ne deborde.',
    1310
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-save',
    'Comment enregistrer ou annuler les modifications d''un point de PV ?',
    'Les actions du point permettent de sauvegarder ou d''abandonner ses changements ; la navigation entre points sauvegarde le point quitte.',
    '<ol><li>Modifiez le point que vous etes autorise a editer.</li><li>Cliquez sur Enregistrer pour conserver le texte ou sur Annuler pour retrouver la version enregistree.</li><li>Avant de quitter le PV, verifiez qu''il ne reste pas de modification signalee comme non enregistree.</li></ol><p>Marquer un point comme traite conserve egalement ses modifications. Ne confondez pas l''enregistrement du point avec la validation finale du PV.</p>',
    'Une mauvaise modification de point n''oblige pas a tout reecrire : vous pouvez retrouver son texte sauvegarde.',
    1320
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-lock',
    'Pourquoi un point de PV est-il temporairement verrouille ?',
    'Un verrou protege le point pendant qu''une autre session l''edite.',
    '<ol><li>Consultez le message de verrouillage pour identifier la situation.</li><li>Laissez la personne terminer et enregistrer le point, puis quitter son edition.</li><li>Reprenez ensuite le point lorsqu il est libere. Vos propres modifications locales doivent etre enregistrees avant de fermer le PV.</li></ol>',
    'La redaction a plusieurs dispose d''une protection pour eviter de remplacer le texte d''un autre participant.',
    1330
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-handoff',
    'Comment passer la main pour la prise de notes du PV ?',
    'La passation permet a un invite de remplacer temporairement l''editeur, avec des droits limites.',
    '<ol><li>Enregistrez les modifications en cours avant la passation.</li><li>Utilisez l''action de passage de main du PV. Un invite peut alors prendre le remplacement propose.</li><li>Le remplacant poursuit le texte des points et les marque traites ; la reprise officielle reste soumise au droit correspondant.</li></ol><p>Un remplacement temporaire ne donne pas automatiquement le droit de reorganiser tout l''ordre du jour.</p>',
    'Une courte absence de l''editeur ne doit pas arreter la prise de notes de la reunion.',
    1340
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-invitations',
    'Comment inviter une personne exterieure a preparer un PV ?',
    'L''envoi d''invitations du PV fournit a chaque destinataire un acces individuel adapte a ses droits.',
    '<ol><li>Ajoutez la personne parmi les invites de l''evenement avec son adresse e-mail.</li><li>Dans le PV, ouvrez la fleche du bouton Inviter et selectionnez les destinataires de l''envoi.</li><li>Envoyez les invitations. Chaque personne utilise son propre lien pour acceder au PV.</li></ol><p>Un lien individuel identifie son destinataire : il ne doit pas etre partage comme un lien collectif.</p>',
    'Un invite sans compte OMO peut aussi preparer ses sujets et suivre le PV.',
    1350
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-confidential',
    'Qui peut lire un point confidentiel dans un PV ?',
    'La confidentialite d''un point restreint sa lecture au groupe autorise de la reunion.',
    '<ol><li>Ouvrez les options du point et verifiez son niveau de confidentialite.</li><li>Pendant la reunion, renseignez correctement la presence des invites.</li><li>Avant la diffusion du PV, controlez la visibilite du document et celle des points sensibles.</li></ol><p>Le contenu confidentiel est reserve a l''auteur, a l''editeur et aux personnes presentes selon les controles du PV.</p>',
    'Un sujet sensible peut garder une audience plus restreinte que le reste du PV.',
    1360
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-import-old',
    'Comment reprendre un point non traite d''une ancienne reunion ?',
    'La popup d''import du PV recherche les points encore a suivre dans les autres PV accessibles.',
    '<ol><li>Ouvrez l''import de points depuis le PV de la nouvelle reunion.</li><li>Utilisez la portee, les filtres de personne et les dates pour retrouver les anciens sujets.</li><li>Choisissez les points a reprendre et confirmez leur insertion.</li></ol><p>Un point provenant d''un PV encore ouvert peut etre deplace ; un point d''un PV clos conserve une trace dans son PV source.</p>',
    'Un sujet reste ouvert lors de la derniere reunion peut etre repris sans etre recopie.',
    1370
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-stop-followup',
    'Comment arreter le suivi d''un ancien point de PV sans le supprimer ?',
    'La poubelle de la popup d''import clot le suivi apres confirmation tout en conservant le point source.',
    '<ol><li>Ouvrez l''import des anciens points et retrouvez le sujet concerne.</li><li>Cliquez sur sa poubelle si vous avez le droit d''arreter son suivi.</li><li>Lisez la confirmation puis validez. Le point ne sera plus propose comme sujet a reprendre.</li></ol><p>Le point reste conserve dans son PV source ; cette action ne le transforme pas en point traite de la reunion d''origine.</p>',
    'Un ancien sujet n''a plus besoin de suivi ? Il peut sortir des rappels tout en restant dans son PV d''origine.',
    1380
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-resources',
    'Comment inserer une ressource OMO dans un point de PV ?',
    'La barre d''outils du point propose des selecteurs de ressources avec recherche et choix de contexte.',
    '<ol><li>Placez le curseur dans le contenu du point.</li><li>Utilisez le bouton du type de ressource a inserer et cherchez l''element dans le contexte voulu.</li><li>Inserez la ressource puis enregistrez le point. Utilisez le bouton + entre les blocs pour ajouter du texte autour si necessaire.</li></ol><p>Les contenus visibles et les actions des blocs restent soumis aux droits de la personne qui consulte le PV.</p>',
    'Votre ordre du jour peut montrer les vraies donnees du projet, plutot qu''une copie deja perimee.',
    1390
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-measure',
    'Comment mettre a jour un indicateur pendant une reunion ?',
    'Le bloc indicateur du PV affiche les dernieres donnees et une saisie pour les personnes autorisees.',
    '<ol><li>Inserez ou retrouvez l''indicateur dans un point du PV.</li><li>Utilisez la saisie sous sa valeur lorsqu elle est disponible pour vous.</li><li>Enregistrez la mesure et verifiez la valeur et la date affichees. Le lien du bloc ouvre l''historique complet.</li></ol>',
    'Une valeur annoncee en reunion peut etre ajoutee a son indicateur sans quitter le PV.',
    1400
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-propose',
    'Que peut-on proposer comme modification depuis un point de PV ?',
    'Un point de PV peut préparer plusieurs types de changements, qui ne prennent effet qu’après leur validation.',
    '<p>Dans un point de procès-verbal, ajoutez une modification différée, puis choisissez l’objet concerné, l’action à effectuer et le contexte. Selon vos droits collectifs, vous pouvez notamment proposer de créer, modifier ou supprimer une règle, un élément de structure, un projet, un indicateur ou une tâche récurrente.</p><p>Relisez la proposition dans le PV : préparer une modification ne l’applique pas immédiatement.</p>',
    'Une reunion peut preparer ses changements d''organisation sans les appliquer trop tot.',
    1410
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-propose-check',
    'Comment revoir ou corriger une proposition faite dans un PV ?',
    'Rouvrez la modification différée du point de PV, vérifiez son contexte et ajustez-la avant validation.',
    '<ol><li>Ouvrez le point de PV contenant la proposition.</li><li>Dépliez ses modifications différées pour retrouver l’objet, l’action et le contexte concernés.</li><li>Modifiez les champs nécessaires et enregistrez le point.</li></ol><p>Les vues de détail montrent les créations, suppressions et comparaisons avant/après. Servez-vous-en pour contrôler le résultat envisagé avant que la décision ne soit validée.</p>',
    'Avant de valider un changement, vous pouvez voir precisement ce qui sera different.',
    1420
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'proposal-applied',
    'Comment savoir si une modification décidée a réellement été appliquée ?',
    'Consultez le résultat de chaque modification : il indique si elle est appliquée, en attente, non retenue ou en échec.',
    '<p>Après la décision, ouvrez son détail et regardez le message affiché pour chaque modification. Une proposition préparée ne signifie pas à elle seule que le changement a été exécuté.</p><p>Le résultat distingue les modifications <strong>appliquées</strong>, celles qui sont <strong>en attente</strong>, celles qui n’ont <strong>pas été retenues</strong> et les éventuels <strong>échecs</strong>. En cas d’échec, vérifiez le contexte et les droits avant une nouvelle action.</p>',
    'Une decision prise et une modification appliquee sont deux etapes que vous pouvez verifier.',
    1430
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-review',
    'Comment signaler une erreur pendant la relecture d''un PV ?',
    'Chaque point dispose d''une discussion pour les corrections, tandis que l''editeur responsable modifie le texte.',
    '<ol><li>Ouvrez le PV en relecture avec votre acces d''invite.</li><li>Sur le point concerne, ouvrez la discussion ou utilisez Signaler des erreurs.</li><li>Expliquez la correction attendue. L''editeur peut corriger le texte et enregistrer le point.</li></ol><p>Apres validation, les discussions existantes restent consultables en lecture seule pour les personnes autorisees.</p>',
    'Une faute dans le PV peut etre signalee au bon endroit, sans lancer une discussion generale.',
    1440
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-finalize',
    'Que verifier avant de passer un PV en relecture ou de le valider ?',
    'Les etapes du PV ferment progressivement la preparation et l''edition ; il faut verifier le contenu avant de les franchir.',
    '<ol><li>En phase Reunion, verifiez les points traites, les notes et la liste de presence.</li><li>Enregistrez les changements, puis passez en Relecture pour recueillir les corrections.</li><li>Apres correction par l''editeur, relisez le resultat et validez seulement lorsqu il est pret a etre diffuse.</li></ol><p>Le retour a une phase de preparation ou de reunion n''est plus propose apres le passage en relecture. La validation finale rend le PV consultable en lecture seule.</p>',
    'Une relecture collective permet de stabiliser le PV avant sa diffusion finale.',
    1450
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-summary',
    'Comment obtenir un resume automatique d''un PV ?',
    'Le bouton de resume IA est propose en relecture lorsque cette fonction est disponible pour votre compte.',
    '<ol><li>Passez le PV en relecture avec les droits necessaires.</li><li>Lancez le resume automatique si le bouton est disponible.</li><li>Relisez et corrigez la description proposee, puis enregistrez-la.</li></ol><p>Le resultat est une aide a la redaction. Sa disponibilite depend de la configuration de l IA et, le cas echeant, de Patreon.</p>',
    'Un long PV peut recevoir une proposition de resume court, que vous gardez la liberte de corriger.',
    1460
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-pdf',
    'Comment telecharger un PV au format PDF ?',
    'L''export PDF est disponible depuis les actions du PV ou sa consultation, selon vos droits.',
    '<ol><li>Ouvrez le PV concerne et son menu d''actions ou d''export.</li><li>Choisissez PDF et attendez la fin de la generation.</li><li>Ouvrez le fichier telecharge et verifiez son contenu avant diffusion.</li></ol>',
    'Votre PV peut aussi voyager en PDF pour une lecture ou une impression hors d OMO.',
    1470
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-app-tabs',
    'Comment consulter les applications pendant la redaction du PV ?',
    'Les applications actives peuvent etre ajoutees comme onglets dans l''editeur de reunion.',
    '<ol><li>Ouvrez la gestion des onglets d''applications de la reunion.</li><li>Cochez les applications utiles et enregistrez la selection.</li><li>Passez de leur onglet a celui du PV pour consulter les elements et poursuivre les notes.</li></ol><p>Les actions disponibles dans ces onglets dependent des droits de la reunion et des elements affiches.</p>',
    'Projets et indicateurs peuvent rester accessibles pendant que vous prenez les notes de la reunion.',
    1480
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'pv-extend',
    'Comment prolonger une reunion depuis son PV ?',
    'En phase Reunion, une commande pres de l''horaire permet d''ajuster rapidement sa fin.',
    '<ol><li>Ouvrez le PV pendant la reunion.</li><li>Cliquez sur le + pres de l''horaire, si vous disposez du droit de modification de l''evenement.</li><li>Choisissez la duree supplementaire et verifiez le nouvel horaire.</li></ol>',
    'Une reunion qui deborde peut etre prolongee depuis son PV, sans repartir dans le calendrier.',
    1490
);

-- Calendrier, disponibilites et rendez-vous - juillet a octobre 2026
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'calendar-shortcut',
    'Comment creer rapidement un evenement a partir du calendrier ?',
    'Un double-clic dans la grille ouvre la creation avec la date ou l''horaire choisi.',
    '<ol><li>Affichez le calendrier en vue Mois, Semaine ou Jour.</li><li>Double-cliquez sur le jour ou la plage horaire voulue.</li><li>Completez le titre, les invites et les autres informations, puis enregistrez.</li></ol><p>La creation reste soumise a vos droits dans le contexte courant.</p>',
    'Le calendrier dispose d''un raccourci pour preparer une reunion directement au bon creneau.',
    1500
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'calendar-invite-busy',
    'Pourquoi un evenement que j ai cree ne bloque-t-il pas ma disponibilite ?',
    'L''indisponibilite repose sur la participation a l''evenement, pas sur le seul fait de l''avoir cree.',
    '<ol><li>Ouvrez les invites de l''evenement.</li><li>Verifiez que vous etes invite personnellement ou par un espace dont vous etes membre.</li><li>Ajoutez-vous si vous participez, puis enregistrez.</li></ol><p>Les invitations e-mail et inscriptions publiques confirmees sont aussi prises en compte pour les personnes reconnues.</p>',
    'Organiser une reunion ne signifie pas toujours y participer : votre disponibilite fait la difference.',
    1510
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'calendar-buffers',
    'Comment reserver du temps personnel autour d''une reunion ?',
    'Chaque participant peut definir ses propres temps de preparation et de cloture.',
    '<ol><li>Ouvrez l''evenement et la commande de vos temps personnels de preparation et de cloture.</li><li>Activez ces temps et renseignez les minutes necessaires avant et apres.</li><li>Enregistrez. Vos disponibilites tiennent compte de ces plages sans deplacer la reunion.</li></ol><p>Ces reglages sont personnels. Ils sont aussi possibles sur les evenements importes sans modifier leur horaire dans l''agenda externe.</p>',
    'Votre temps de preparation peut etre protege sans allonger la reunion pour les autres.',
    1520
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'calendar-sources',
    'Comment choisir les sources visibles dans mon calendrier ?',
    'Les filtres de sources permettent d''afficher les calendriers utiles a la consultation en cours.',
    '<ol><li>Ouvrez les filtres ou capsules de sources du calendrier.</li><li>Activez ou masquez l''organisation courante, les autres organisations et les agendas externes disponibles.</li><li>Appliquez la vue, puis enregistrez-la si vous voulez la retrouver.</li></ol><p>Masquer une source dans l''affichage ne supprime pas ses rendez-vous et ne rend pas leurs plages libres.</p>',
    'Votre calendrier peut devenir plus lisible sans supprimer aucun rendez-vous.',
    1530
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'calendar-external',
    'Comment afficher mon agenda externe dans OMO ?',
    'Le menu Connecter du calendrier permet d''ajouter un abonnement ICS ou un agenda CalDAV en lecture seule.',
    '<ol><li>Dans Calendrier, ouvrez Connecter puis l''ajout d''un agenda externe.</li><li>Choisissez ICS ou CalDAV et renseignez les informations demandees. Pour CalDAV, selectionnez les agendas trouves.</li><li>Donnez des noms et couleurs reconnaissables, puis synchronisez et verifiez les evenements importes.</li></ol><p>Ces agendas sont consultes dans OMO sans modifier leurs evenements sources. Pour un lien ICS prive, utilisez l''adresse d''abonnement fournie par votre service.</p>',
    'Vos engagements personnels peuvent eviter un conflit d''horaire avec une reunion OMO.',
    1540
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'calendar-google',
    'Comment suivre mon calendrier OMO dans Google Calendar ?',
    'Copiez le lien ICS proposé dans le menu Connecter du calendrier, puis ajoutez-le à Google Calendar.',
    '<ol><li>Dans OMO, ouvrez Calendrier et choisissez le contexte que vous souhaitez suivre.</li><li>Ouvrez le menu <strong>Connecter</strong> et copiez le lien ICS proposé pour cette portée.</li><li>Dans Google Calendar, ajoutez un calendrier à partir de son URL et collez ce lien.</li></ol><p>Le panneau d’aide du menu Connecter détaille les étapes. Le lien correspond au contexte choisi ; une actualisation externe peut prendre du temps selon Google Calendar.</p>',
    'Votre agenda OMO peut suivre ses evolutions dans Google Calendar, sans import manuel repete.',
    1550
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'calendar-caldav',
    'Comment synchroniser seulement une partie du calendrier OMO sur mon telephone ?',
    'Le lien CalDAV du menu Connecter reprend le contexte et la portee affiches.',
    '<ol><li>Dans OMO, ouvrez le cercle ou role voulu et reglez la portee du calendrier.</li><li>Ouvrez Connecter, choisissez CalDAV et copiez l''adresse proposee. La marche a suivre est disponible sous le lien.</li><li>Ajoutez ce compte dans votre application calendrier avec vos identifiants OMO autorises pour la synchronisation.</li></ol><p>Selon le client, les evenements modifiables et ceux en lecture seule apparaissent dans deux agendas. Les droits OMO continuent de s''appliquer.</p>',
    'Votre telephone peut suivre le calendrier d''un seul cercle plutot que celui de toute l''organisation.',
    1560
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'calendar-recurrence',
    'Comment programmer une reunion recurrente ?',
    'La recurrence propose des rythmes hebdomadaires, des intervalles en jours et des regles mensuelles.',
    '<ol><li>Creez ou modifiez l''evenement, puis activez sa recurrence.</li><li>Choisissez le rythme, le jour ou le rang dans le mois, et si besoin le report du week-end.</li><li>Verifiez les documents des prochaines occurrences et utilisez l''enregistrement qui applique la recurrence.</li></ol><p>Les prochaines dates sont creees progressivement : toutes les occurrences futures ne sont pas affichees d''un seul coup.</p>',
    'Le premier lundi du mois, ou un report du week-end : vos reunions regulieres peuvent suivre ces rythmes.',
    1570
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'calendar-single',
    'Comment modifier une seule reunion dans une serie ?',
    'L''enregistrement de la seule reunion cree une occurrence personnalisee dans la serie.',
    '<ol><li>Ouvrez la date concernee puis son formulaire de modification.</li><li>Changez son horaire ou son contenu.</li><li>Choisissez Enregistrer cette reunion, sans l''option qui applique aux suivantes.</li></ol><p>L''occurrence reste liee a sa serie mais ses changements individuels sont proteges des modifications collectives ordinaires.</p>',
    'Une exception dans votre serie de reunions n''oblige pas a changer toutes les dates suivantes.',
    1580
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'calendar-future',
    'Comment changer la recurrence sans deplacer les reunions deja planifiees ?',
    'Une strategie conserve les dates existantes et applique la nouvelle regle seulement aux prochaines creations.',
    '<ol><li>Ouvrez une reunion de la serie et modifiez les reglages de recurrence.</li><li>Dans l''effet du changement, choisissez Appliquer apres la derniere date planifiee.</li><li>Utilisez la fleche d''enregistrement pour appliquer la recurrence, puis verifiez le resume.</li></ol><p>Replanifier les suivantes correspond a un autre besoin : relisez bien le choix avant validation.</p>',
    'Un nouveau rythme de reunion peut commencer apres les dates deja planifiees, sans les deplacer.',
    1590
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'calendar-stop',
    'Comment arreter une serie de reunions en gardant les dates prevues ?',
    'L''arret de recurrence peut conserver les dates planifiees ou supprimer les suivantes, selon votre choix.',
    '<ol><li>Ouvrez la modification d''une reunion de la serie et ses options de recurrence.</li><li>Choisissez Arreter en conservant les dates planifiees.</li><li>Utilisez l''action d''enregistrement et d''arret proposee. Les dates et documents existants restent disponibles.</li></ol><p>L''autre option, Arreter et supprimer les suivantes, demande confirmation et concerne aussi les occurrences futures personnalisees.</p>',
    'Une serie peut cesser de creer de nouvelles reunions tout en gardant les dernieres dates prevues.',
    1600
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'calendar-documents',
    'Comment choisir les documents d''une reunion recurrente ?',
    'La serie peut copier de nouveaux documents ou reutiliser un support ; un PV doit rester propre a chaque reunion.',
    '<ol><li>Dans les reglages de recurrence, ouvrez Documents des prochaines occurrences.</li><li>Choisissez de nouveaux documents a chaque occurrence pour les PV et les supports a remplir separement.</li><li>Choisissez la reutilisation uniquement pour un document commun, puis enregistrez la recurrence.</li></ol><p>Ce choix concerne les prochaines creations. Les documents des reunions deja planifiees sont conserves.</p>',
    'Les reunions d''une serie peuvent partager un support tout en gardant chacune leur propre PV.',
    1610
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'calendar-next-close',
    'Comment choisir la prochaine date a la cloture de la reunion ?',
    'La recurrence Demander la prochaine date a la cloture ouvre une planification liee au PV.',
    '<ol><li>Associez un PV a la reunion et choisissez cette option de recurrence.</li><li>A la cloture, examinez les disponibilites proposees et choisissez la prochaine date, ou cloturez sans planifier.</li><li>Si vous avez differe le choix, utilisez Planifier la prochaine reunion depuis l''evenement.</li></ol><p>Le delai propose est indicatif. Une prochaine reunion deja planifiee n''est pas recreee.</p>',
    'Votre equipe peut choisir sa prochaine date en fin de reunion plutot que fixer toute une serie a l''avance.',
    1620
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'calendar-status',
    'Quelle difference entre Brouillon, Option et Confirme pour un evenement ?',
    'Le statut distingue une preparation privee, une date envisagee et un rendez-vous confirme.',
    '<ol><li>Dans le formulaire de l''evenement, choisissez son statut.</li><li>Utilisez Brouillon pour une preparation visible uniquement de son createur.</li><li>Passez en Option ou Confirme lorsque la date doit devenir visible selon les droits de l''evenement.</li></ol>',
    'Une reunion encore en preparation peut rester privee avant d''etre proposee aux autres.',
    1630
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'calendar-hybrid',
    'Comment indiquer une adresse et une visioconference sur le meme evenement ?',
    'Le lieu Mixte permet de renseigner les deux moyens de participation.',
    '<ol><li>Dans le formulaire de l''evenement, ouvrez les informations de lieu.</li><li>Choisissez Mixte et renseignez l''adresse ainsi que le lien de visio.</li><li>Enregistrez puis verifiez la fiche de l''evenement. Ces informations sont aussi reprises dans son export calendrier.</li></ol>',
    'Une reunion hybride peut reunir adresse et visio dans une seule fiche.',
    1640
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'availability-profile',
    'Comment consulter les disponibilites d''un membre sans voir ses rendez-vous ?',
    'L''onglet Disponibilites de sa fiche presente les plages utiles sans exposer les titres des evenements.',
    '<ol><li>Ouvrez la fiche du membre depuis Team ou sa vignette.</li><li>Selectionnez Disponibilites et consultez la periode voulue.</li><li>Si la prise de rendez-vous est activee pour cette personne, utilisez le bouton correspondant pour choisir un creneau.</li></ol>',
    'Vous pouvez chercher une heure libre chez un collegue sans lire le contenu de ses rendez-vous.',
    1650
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'booking',
    'Comment activer ma page de prise de rendez-vous ?',
    'La page publique propose vos creneaux disponibles et enregistre les reservations dans un agenda autorise.',
    '<ol><li>Dans le menu du Calendrier, ouvrez Prise de rendez-vous.</li><li>Activez la fonction, choisissez un nom public et un agenda CalDAV personnel autorisant la creation de rendez-vous.</li><li>Reglez vos horaires, pauses, durees et moyens de rencontre, puis enregistrez et partagez le lien propose.</li></ol><p>Les liens de visio, adresses ou numeros saisis comme moyens de rencontre sont visibles sur votre page publique.</p>',
    'Vos interlocuteurs peuvent choisir eux-memes un rendez-vous dans vos creneaux disponibles.',
    1660
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'booking-hours',
    'Comment eviter des reservations pendant mes pauses ou hors de mes horaires ?',
    'Les horaires hebdomadaires, les pauses et les temps autour du rendez-vous limitent les creneaux proposes.',
    '<ol><li>Ouvrez vos parametres de prise de rendez-vous et de disponibilites.</li><li>Renseignez les jours travailles, les horaires et les pauses, ainsi que les temps de preparation ou de cloture utiles.</li><li>Enregistrez puis verifiez les creneaux sur votre page publique.</li></ol><p>Les engagements de vos agendas connectes sont pris en compte ; une plage masquee dans le calendrier ne devient pas libre pour autant.</p>',
    'Vos pauses meritent aussi leur place dans les disponibilites proposees aux autres.',
    1670
);

-- Projets - juillet a octobre 2026
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'project-kanban-create',
    'Comment creer un projet directement dans une colonne du Kanban ?',
    'Un double-clic dans une colonne ouvre la creation avec son statut deja choisi.',
    '<ol><li>Affichez Projets en Kanban.</li><li>Double-cliquez dans la zone libre de la colonne voulue.</li><li>Completez le titre et les informations utiles, puis enregistrez.</li></ol><p>Les libelles des colonnes peuvent etre personnalises par votre organisation.</p>',
    'Le Kanban peut preparer un nouveau projet directement avec le statut de la colonne choisie.',
    1680
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'project-gantt',
    'Comment retrouver une barre hors ecran dans le Gantt ?',
    'Un clic dans la partie vide de la ligne recentre sa barre de planification.',
    '<ol><li>Dans Projets, choisissez la representation Gantt.</li><li>Reperez le nom du projet puis cliquez dans la zone vide de sa ligne.</li><li>Cliquez sur la barre datee ou sur son nom si vous voulez ouvrir son detail.</li></ol>',
    'Une barre hors ecran dans le Gantt peut revenir a vous sans parcourir toute la frise.',
    1690
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'project-hierarchy',
    'Comment organiser un projet en sous-projets et en taches ?',
    'Le detail d''un projet permet de creer des sous-projets ou de rattacher un projet sans parent.',
    '<ol><li>Ouvrez le detail du projet parent et utilisez l''ajout de sous-projet ou le rattachement.</li><li>Choisissez le contexte et la personne en charge. Les feuilles du meme contexte peuvent etre presentees comme des taches.</li><li>Suivez les statuts depuis le parent et utilisez le fil d Ariane pour remonter dans la hierarchie.</li></ol>',
    'Un objectif ambitieux peut garder une vue d''ensemble tout en etant decoupe en petites actions.',
    1700
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'project-importance',
    'Quelle difference entre priorite et importance strategique d''un projet ?',
    'La priorite sert a ordonner le travail operationnel ; l''importance strategique sert au classement calcule des projets.',
    '<ol><li>Dans le projet, renseignez la priorite si votre organisation utilise ce classement : P1 passe avant P5.</li><li>Renseignez l''importance strategique quand le projet doit porter une valeur propre.</li><li>Laissez-la vide si elle doit heriter de la chaine de projets, puis choisissez le tri adapte a votre besoin.</li></ol><p>La taille sert notamment a ponderer les barres d''avancement. Les classements disponibles dependent des reglages de l''application.</p>',
    'Une urgence du jour et une importance strategique peuvent se lire separement dans vos projets.',
    1710
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'project-deadline',
    'Comment les dates d''un projet parent influencent-elles ses sous-projets ?',
    'Une date de fin vide peut etre heritee du parent ; une date propre ne peut pas depasser sa limite.',
    '<ol><li>Planifiez la fin du projet parent.</li><li>Lors de l''edition d''un sous-projet, laissez la fin vide pour utiliser cette limite ou renseignez une fin plus proche.</li><li>Verifiez le classement Planification ou le Gantt pour reperer les projets en retard.</li></ol><p>Les dates du parent imposent aussi des limites aux statuts de planification disponibles.</p>',
    'Une echeance commune peut se transmettre aux sous-projets sans etre saisie partout.',
    1720
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'project-blocked',
    'Comment mettre un projet en attente avec une date de reexamen ?',
    'Le statut Bloque peut conserver le motif, la date et une reactivation automatique.',
    '<ol><li>Passez le projet au statut Bloque et ouvrez les informations d''attente.</li><li>Renseignez le motif et la date selon les exigences de votre organisation.</li><li>Choisissez si le projet doit revenir automatiquement a Pret ou En cours, puis enregistrez.</li></ol><p>Sans date, la reactivation automatique est desactivee. Un reexamen depasse attire l''attention sans se confondre avec la date de fin du projet.</p>',
    'Un projet en attente peut se reactiver tout seul a la date ou vous souhaitez le reprendre.',
    1730
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'project-proposal',
    'Comment soumettre un projet quand je ne peux pas le creer directement ?',
    'Si le droit de proposition est accorde, le projet reste en attente de decision dans le contexte cible.',
    '<ol><li>Choisissez le contexte vise et l''action de proposition de projet lorsqu elle est disponible.</li><li>Decrivez le projet et enregistrez votre proposition.</li><li>Retrouvez son etat en attente ou refuse dans le detail ; les personnes autorisees du contexte peuvent l''accepter ou la refuser.</li></ol><p>Le proposeur peut completer sa proposition selon les droits affiches ; proposer n''applique pas immediatement le projet.</p>',
    'Une bonne idee peut etre proposee a un role meme si vous ne gerez pas directement ses projets.',
    1740
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'project-resources',
    'Comment rattacher des ressources deja existantes a un projet ?',
    'Les onglets du projet permettent de creer une ressource ou d''importer un lien vers une ressource accessible.',
    '<ol><li>Ouvrez le projet puis l''onglet Documents, Indicateurs ou Taches recurrentes, selon les applications actives.</li><li>Ouvrez la fleche a cote de Creer puis choisissez Importer.</li><li>Recherchez la ressource dans le selecteur de contexte et liez-la au projet.</li></ol><p>Vous liez la ressource existante : son retrait du projet ne signifie pas sa suppression.</p>',
    'Un projet peut reunir ses ressources existantes sans en creer de nouvelles copies.',
    1750
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'project-resource-visibility',
    'Pourquoi une ressource liee a un projet ne figure-t-elle plus dans la liste de l''espace ?',
    'Les ressources liees a un seul projet peuvent etre masquees dans leur espace pour eviter les doublons de consultation.',
    '<ol><li>Ouvrez la ressource depuis l''onglet du projet.</li><li>Dans son edition, verifiez Afficher dans l''espace.</li><li>Cochez cette option et enregistrez si elle doit aussi apparaitre dans les listes de l''espace.</li></ol><p>Ce fonctionnement concerne les documents, indicateurs et taches recurrentes. Un element partage entre plusieurs projets reste affiche dans l''espace.</p>',
    'Une ressource reservee a un projet n''a pas besoin d''encombrer la liste de tout l''espace.',
    1760
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'project-events',
    'Comment regrouper les rendez-vous lies a un projet ?',
    'L''onglet Evenements associe des reunions OMO ou des rendez-vous externes au projet.',
    '<ol><li>Ouvrez le projet et son onglet Evenements.</li><li>Creez un evenement ou utilisez Importer un evenement pour choisir une date existante.</li><li>Selectionnez la reunion OMO ou le rendez-vous externe synchronise et confirmez le lien.</li></ol><p>Le retrait du lien ne supprime pas le rendez-vous source. Les modifications d''un evenement externe se font dans son agenda d''origine.</p>',
    'Les rendez-vous d''un projet peuvent se retrouver ensemble, meme s''ils viennent d''un agenda externe.',
    1770
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'project-chat',
    'Comment discuter d''un projet dans OMO ?',
    'La discussion du projet est accessible aux personnes autorisees a le consulter.',
    '<ol><li>Ouvrez le detail du projet et sa discussion.</li><li>Redigez votre message et envoyez-le.</li><li>Reglez vos notifications de projets si vous souhaitez suivre ces echanges sur les canaux disponibles.</li></ol>',
    'Les explications d''un projet peuvent rester pres de lui, au lieu de se perdre dans des messages separes.',
    1780
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'project-follow',
    'Comment suivre un projet et que signifie le filtre Suivi ?',
    'Chaque personne peut suivre un projet ; le filtre Suivi regroupe les projets suivis par au moins une personne.',
    '<ol><li>Dans le menu du projet, choisissez Suivre.</li><li>Reperez votre etoile jaune ; la liste des personnes qui suivent le projet est aussi consultable.</li><li>Utilisez Ne plus suivre pour arreter. Ne confondez pas le filtre Suivi avec une liste personnelle de vos seuls abonnements.</li></ol>',
    'Vous pouvez suivre un projet qui vous interesse sans en devenir responsable.',
    1790
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'project-bulk',
    'Comment archiver plusieurs projets a la fois ?',
    'La selection multiple fonctionne en Kanban, en Liste et en Gantt.',
    '<ol><li>Dans Projets, cochez les projets concernes.</li><li>Choisissez l''action d''archivage de la selection.</li><li>Verifiez les projets et leurs descendants concernes avant de confirmer.</li></ol><p>La suppression definitive est une autre action, soumise a un droit distinct.</p>',
    'La fin d''un trimestre peut se ranger en une fois : les projets s''archivent aussi par selection.',
    1800
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'project-history',
    'Comment retrouver les modifications d''un projet ?',
    'L''historique retrace notamment les titres, statuts, dates, attentes et liens de ressources.',
    '<ol><li>Ouvrez le projet puis son onglet Historique.</li><li>Reperez la date et le type de changement cherche.</li><li>Consultez son detail pour retrouver les valeurs et l''auteur, y compris pour une reactivation automatique.</li></ol>',
    'Un statut ou une echeance a change ? Le projet garde la trace de ses evolutions.',
    1810
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'project-kanban-drag',
    'Comment reclasser un projet par glisser-deposer dans le Kanban groupe ?',
    'Les depots entre lignes de contexte ou de priorite ajustent le classement si vos droits le permettent.',
    '<ol><li>Choisissez un regroupement du Kanban par contexte ou priorite.</li><li>Deplacez la carte dans la ligne et la colonne voulues.</li><li>Verifiez le statut, le contexte ou la priorite apres le deplacement.</li></ol><p>Les lignes d''importance calculee n''acceptent pas un reclassement par depot. Les deplacements restent limites aux destinations autorisees.</p>',
    'Une carte de Kanban peut changer de role ou de priorite en meme temps que de statut.',
    1820
);

-- Processus et taches recurrentes - juillet a octobre 2026
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'process-on-demand',
    'Comment lancer un processus pour un cas precis ?',
    'Un processus a la demande peut etre lance avec un nom et une date de reference propres a l''execution.',
    '<ol><li>Ouvrez le processus disponible a la demande.</li><li>Lancez une execution et donnez-lui un nom reconnaissable, par exemple le nom du dossier ou de la personne accueillie.</li><li>Choisissez la date de reference et confirmez, puis suivez les projets crees pour cette execution.</li></ol>',
    'Chaque accueil de membre peut avoir sa propre execution d''un meme processus.',
    1830
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'process-dependencies',
    'Comment enchainer les etapes d''un processus ?',
    'Les dependances d''etapes pilotent l''ouverture des projets de l''execution.',
    '<ol><li>Dans l''edition du processus, ouvrez l''etape qui doit attendre.</li><li>Definissez sa dependance et, si besoin, son delai.</li><li>Enregistrez. Lors d''une execution, terminez le projet precedent pour permettre le recalcul des etapes dependantes.</li></ol>',
    'Une etape terminee peut debloquer automatiquement la suivante dans un processus.',
    1840
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'process-overlap',
    'Que faire si une execution de processus est encore ouverte au prochain lancement ?',
    'Le processus propose trois comportements pour gerer les executions qui se chevauchent.',
    '<ol><li>Ouvrez les reglages de lancement du processus.</li><li>Choisissez de creer une execution en parallele, de bloquer jusqu''a la fin de l''execution ouverte, ou d''archiver les projets encore ouverts.</li><li>Enregistrez le choix adapte a votre pratique avant les prochains lancements.</li></ol><p>Le choix d''archivage concerne les projets d''executions precedentes : relisez son effet avant de l''utiliser.</p>',
    'Un processus peut anticiper le cas ou la prochaine execution arrive avant la fin de la precedente.',
    1850
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'recurring-task',
    'Quand utiliser une tache recurrente plutot qu''un processus ?',
    'Une tache recurrente suit des validations repetitives ; un processus organise des etapes et les projets de chaque execution.',
    '<ol><li>Pour une action simple a refaire regulierement, creez une tache recurrente.</li><li>Renseignez sa frequence, son contexte et eventuellement une personne en charge.</li><li>Utilisez un processus si chaque execution exige plusieurs etapes, dependances ou projets distincts.</li></ol>',
    'Une petite routine n''a pas toujours besoin d''un projet complet pour etre suivie.',
    1860
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'recurring-window',
    'Comment afficher une tache recurrente avant son echeance ?',
    'La fenetre de realisation distingue l''anticipation d''affichage du delai de retard.',
    '<ol><li>Dans l''edition de la tache recurrente, ouvrez Fenetre de realisation.</li><li>Reglez Afficher en avance, puis En retard apres, avec les unites adaptees.</li><li>Enregistrez et verifiez la prochaine echeance dans la liste.</li></ol>',
    'Une tache peut devenir visible avant son echeance sans etre consideree trop tot comme en retard.',
    1870
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'recurring-regularity',
    'Comment voir si une tache recurrente est realisee regulierement ?',
    'Le detail presente les dernieres occurrences et leur regularite.',
    '<ol><li>Ouvrez la fiche de la tache recurrente.</li><li>Consultez la frise des douze dernieres occurrences et sa legende.</li><li>Comparez les validations et les echeances : une action faite en retard reste distinguee d''une action non faite.</li></ol>',
    'La derniere case cochee ne raconte pas tout : une routine garde aussi la trace de sa regularite.',
    1880
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'recurring-badge',
    'A quoi sert la pastille rouge des taches recurrentes ?',
    'Elle attire l''attention sur les taches non faites en retard attribuees a la personne connectee.',
    '<ol><li>Survolez la pastille pour lire le titre et l''espace de la premiere tache concernee.</li><li>Cliquez pour ouvrir sa fiche.</li><li>Realisez et validez la tache si elle est faite, puis consultez les autres retards eventuels.</li></ol><p>Cette pastille personnelle ne remplace pas la liste de toutes les taches en retard de vos espaces.</p>',
    'Vos routines en retard peuvent attirer votre attention meme quand vous consultez une autre application.',
    1890
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'resource-archives',
    'Comment conserver l''historique d''un indicateur ou d''une tache recurrente abandonnee ?',
    'L''archivage retire l''element des vues actives en conservant ses donnees.',
    '<ol><li>Dans le menu de l''indicateur ou de la tache recurrente, choisissez Archiver.</li><li>Pour consulter ses donnees plus tard, ouvrez Voir les archives dans le menu de l''application.</li><li>Ajustez la portee, l''attribution ou la recherche si vous ne le retrouvez pas.</li></ol><p>Les archives se consultent en lecture seule avec leurs valeurs ou validations conservees.</p>',
    'Une routine abandonnee ou un ancien indicateur peut garder son historique sans rester dans les listes actives.',
    1900
);

-- Indicateurs - juillet a octobre 2026
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'indicator-reuse',
    'Comment afficher un indicateur dans un autre espace sans dupliquer ses mesures ?',
    'L''import d''un indicateur existant cree une reference vers la source et conserve un historique commun.',
    '<ol><li>Dans Indicateurs, ouvrez le menu puis Importer.</li><li>Choisissez un indicateur accessible dans l''organisation.</li><li>Confirmez son ajout au contexte. Les nouvelles mesures de la source sont retrouvees depuis cette reference.</li></ol><p>Retirer l''import du contexte ne supprime pas l''indicateur source.</p>',
    'Deux cercles peuvent suivre le meme indicateur avec un seul historique de mesures.',
    1910
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'indicator-group',
    'Comment comparer ou additionner plusieurs indicateurs ?',
    'Un groupe peut superposer des courbes ou calculer leur somme sans modifier les sources.',
    '<ol><li>Dans le menu des Indicateurs, choisissez Grouper et selectionnez les series utiles.</li><li>Choisissez la superposition pour comparer, ou la somme pour suivre un total, puis enregistrez.</li><li>Ouvrez le groupe pour consulter le graphique et sa legende.</li></ol><p>Additionnez des grandeurs compatibles. La somme interpole les series entre leurs mesures ; ce n''est pas une nouvelle mesure saisie.</p>',
    'Plusieurs courbes peuvent se comparer ou se cumuler sans changer leurs donnees sources.',
    1920
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'indicator-reference',
    'Comment comparer un indicateur a une trajectoire prevue ?',
    'La courbe de reference definit une cible datee independante des mesures.',
    '<ol><li>Dans l''edition de l''indicateur, choisissez une trajectoire de reference.</li><li>Renseignez ses extremites datees et ajoutez des points intermediaires si la cible evolue par etapes.</li><li>Enregistrez puis comparez les mesures a cette courbe dans le graphique.</li></ol><p>Un plafond horizontal convient a une limite constante ; les mesures restent des couples valeur et date.</p>',
    'Une cible progressive peut se comparer aux mesures reelles sur le meme graphique.',
    1930
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'indicator-range',
    'Comment lire les valeurs d''un indicateur sur une periode precise ?',
    'Le detail propose une selection temporelle et des infobulles sur les mesures.',
    '<ol><li>Ouvrez le detail de l''indicateur ou du groupe.</li><li>Ajustez les bornes de la periode pour isoler les dates utiles.</li><li>Survolez les points pour lire les valeurs et dates de mesure. Elargissez ensuite la periode pour retrouver l''historique.</li></ol>',
    'Un graphique peut montrer une periode precise sans vous faire perdre son historique complet.',
    1940
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'indicator-due',
    'Comment regler le rythme de mise a jour d''un indicateur ?',
    'La frequence et le moment attendu permettent de signaler les mesures a completer ou en retard.',
    '<ol><li>Ouvrez l''edition de l''indicateur et renseignez le rythme attendu.</li><li>Precisez le moment adapte, par exemple un jour de semaine ou du mois, si vous avez une echeance reguliere.</li><li>Enregistrez et ajoutez les mesures au fil du temps. Consultez la date de derniere valeur en cas d''alerte.</li></ol><p>Une marge adaptee a la frequence distingue le jaune du retard rouge. Les imports reprennent l''etat de leur source.</p>',
    'Un rythme de mesure bien regle rend les alertes de retard beaucoup plus utiles.',
    1950
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'indicator-sheet-source',
    'Comment alimenter un indicateur depuis un tableur ?',
    'Une source tableur peut lire une cellule ou synchroniser des colonnes de mesures.',
    '<ol><li>Dans l''ajout ou l''import d''indicateur, choisissez la source tableur proposee.</li><li>Selectionnez le document et renseignez la cellule ou la plage, avec les colonnes utiles.</li><li>Reglez la frequence de mesure ou de synchronisation selon le mode, puis enregistrez et verifiez les valeurs obtenues.</li></ol><p>Les sources disponibles dependent des services actives. Les en-tetes d''un tableau peuvent servir de noms aux series.</p>',
    'Un chiffre deja present dans un tableur peut alimenter un indicateur sans nouvelle saisie.',
    1960
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'indicator-cumulative',
    'Comment afficher les mesures et leur cumul sur le meme indicateur ?',
    'Un indicateur peut presenter les mesures en barres et leur cumul en courbe avec une echelle propre.',
    '<ol><li>Ouvrez l''edition de l''indicateur et ses reglages de representation.</li><li>Choisissez l''affichage des mesures en barres et activez le cumul.</li><li>Enregistrez puis ouvrez le graphique pour comparer les valeurs de chaque periode et leur total.</li></ol><p>Le cumul conserve son historique meme lorsque seule une periode recente est affichee.</p>',
    'Des barres mensuelles et leur cumul peuvent raconter deux histoires utiles sur le meme graphique.',
    1970
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'indicator-source-archive',
    'Que faire si la source d''un indicateur importe est archivee ?',
    'L''import reste consultable en gris et peut etre modifie pour choisir une nouvelle source.',
    '<ol><li>Ouvrez l''import et verifiez le message sur sa source archivee.</li><li>Consultez ses anciennes valeurs si vous en avez besoin.</li><li>Utilisez Modifier pour remplacer la source par un indicateur accessible.</li></ol><p>L''ancienne source archivee ne declenche plus de retard sur cet import.</p>',
    'Un indicateur importe devenu gris peut encore vous rendre son historique.',
    1980
);

-- Decisions et consultations - juin a octobre 2026
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'decision-consultation',
    'Comment recueillir des idees avant de choisir un mode de scrutin ?',
    'La consultation seule recueille les propositions et discussions avant une eventuelle evaluation.',
    '<ol><li>Creez une prise de decision en consultation seule.</li><li>Definissez la periode, les invites et les possibilites de proposition ou de discussion.</li><li>A la fin de la consultation, choisissez le mode de scrutin si vous souhaitez poursuivre vers une evaluation.</li></ol><p>Les controles de vote ne sont pas affiches pendant la consultation.</p>',
    'Vous pouvez recueillir les idees avant de decider comment les departager.',
    1990
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'decision-description-only',
    'Peut-on proposer une option sans titre dans un scrutin ?',
    'Les parametres du scrutin choisissent si les propositions utilisent un titre, une description et une URL.',
    '<ol><li>Avant le debut de l''evaluation, ouvrez les parametres du scrutin.</li><li>Activez les champs utiles aux propositions et desactivez le titre si une description suffit.</li><li>Enregistrez, puis verifiez le formulaire de proposition affiche aux participants.</li></ol>',
    'Une proposition peut aller a l''essentiel sans titre artificiel, ou renvoyer vers un dossier detaille.',
    2000
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'decision-discussion',
    'Comment demander une precision sur une proposition de decision ?',
    'Chaque proposition peut disposer d''une discussion si l''organisateur l''autorise.',
    '<ol><li>Ouvrez le scrutin et la proposition concernee.</li><li>Choisissez Discuter et redigez votre question.</li><li>Envoyez le message et consultez les reponses dans ce fil.</li></ol><p>Les possibilites d''ecriture et d''anonymat dependent de l''acces, de la phase et des parametres du scrutin.</p>',
    'Une question posee sous la bonne proposition peut eclairer tous les participants avant le vote.',
    2010
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'decision-withdraw',
    'Comment corriger ou retirer ma proposition avant le vote ?',
    'L''auteur dispose d''actions sur sa proposition avant le debut de l''evaluation.',
    '<ol><li>Ouvrez votre proposition avec le compte ou l''acces personnel qui l''a creee.</li><li>Utilisez Modifier pour l''ajuster, ou son menu pour la retirer.</li><li>Relisez les modifications puis confirmez avant le debut du vote.</li></ol><p>Les propositions et les reglages sont verrouilles lorsque l''evaluation commence.</p>',
    'Une proposition peut evoluer avec les echanges tant que le vote n''a pas commence.',
    2020
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'decision-public',
    'Comment participer a une decision avec un lien public ou un acces personnel ?',
    'Ouvrez la page de participation recue par lien ou demandez votre acces personnel depuis l ecran public du scrutin.',
    '<p>Certaines decisions peuvent accepter la participation sans invitation classique, directement depuis un lien public partage par l organisateur.</p><p>Si ce nest pas le cas, utilisez la page Recevoir mon acces personnel pour demander un lien individuel avant de voter.</p>',
    'Une decision peut accueillir des participants sans compte OMO.',
    2030
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'decision-qr',
    'Comment distribuer des acces individuels a un scrutin pendant une rencontre ?',
    'Le menu du scrutin propose une planche imprimable de codes QR personnels lorsqu il est pret et pas encore termine.',
    '<ol><li>Ouvrez le menu de gestion du scrutin.</li><li>Choisissez Imprimer les codes QR.</li><li>Imprimez les cartes et remettez a chaque personne celle qui lui correspond.</li></ol><p>Chaque QR code ouvre un acces personnel. Ne diffusez pas toute la planche comme un document public.</p>',
    'En salle, chaque invite peut retrouver son acces au scrutin avec sa propre carte QR.',
    2040
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'decision-anonymous',
    'Puis-je rester anonyme dans un scrutin nominatif ?',
    'Une option du scrutin peut autoriser un choix personnel d''anonymat.',
    '<ol><li>Consultez les options du scrutin avant de soumettre votre participation.</li><li>Si le choix individuel d''anonymat est propose, activez-le selon votre souhait.</li><li>Verifiez aussi le choix de publication anonyme de vos messages, lorsqu il est disponible.</li></ol><p>Le mode global est decide par l''organisateur. Un scrutin anonyme ne peut pas devenir nominatif apres certaines participations externes.</p>',
    'Dans certains votes nominatifs, chacun peut garder le choix de rester anonyme.',
    2050
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'decision-random',
    'Comment limiter l''influence de l''ordre des propositions ?',
    'Les reglages de presentation du scrutin proposent un ordre aleatoire et, selon le mode, une presentation progressive.',
    '<ol><li>Avant l''evaluation, ouvrez les parametres du scrutin.</li><li>Activez l''ordre aleatoire et, si utile, l''affichage d''une seule proposition a la fois.</li><li>Enregistrez puis verifiez le parcours de participation.</li></ol><p>L''ordre propre a un participant reste stable pour lui, afin de ne pas changer a chaque retour.</p>',
    'L''ordre de presentation des propositions peut aider a limiter l''effet du premier choix affiche.',
    2060
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'decision-intermediate',
    'Quand puis-je voir les resultats intermediaires d''un scrutin ?',
    'Leur affichage depend des parametres et peut attendre que le participant ait termine sa reponse.',
    '<ol><li>Consultez les options de participation du scrutin.</li><li>Completez et soumettez tout votre bulletin.</li><li>Ouvrez les resultats intermediaires si cette action est alors proposee.</li></ol><p>L''organisateur peut disposer d''un acces different. L''absence de resultats pendant la participation ne signifie pas que votre vote est perdu.</p>',
    'Un scrutin peut attendre votre reponse complete avant de vous montrer la tendance du groupe.',
    2070
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'decision-weight',
    'Comment fonctionne la ponderation des votes ?',
    'L''organisateur definit les coefficients autorises ; le participant choisit le sien lorsque ce reglage est active.',
    '<ol><li>Lisez les coefficients et leurs significations dans le parcours de participation.</li><li>Choisissez le coefficient adapte avant de soumettre votre bulletin.</li><li>Dans les resultats, utilisez la comparaison non ponderee si elle est proposee.</li></ol><p>Les coefficients n''ont de sens que si leur usage est compris et partage par le collectif.</p>',
    'Un scrutin pondere peut aussi offrir une comparaison avec le resultat sans ponderation.',
    2080
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'decision-duplicate',
    'Comment reutiliser la configuration d''un scrutin ?',
    'La duplication prepare un nouveau scrutin a partir d''un existant, sans le creer tant que vous ne l''enregistrez pas.',
    '<ol><li>Dans le menu du scrutin source, choisissez sa duplication.</li><li>Relisez les propositions, les parametres et les invites, puis renseignez les nouvelles dates.</li><li>Enregistrez le nouveau scrutin lorsque tout est pret.</li></ol>',
    'Une consultation reguliere peut repartir de la configuration d''un scrutin precedent.',
    2090
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'decision-changes',
    'Une option de scrutin peut-elle contenir plusieurs modifications ?',
    'Oui. Une proposition de scrutin regroupe un titre, une description et autant de modifications nécessaires.',
    '<p>Dans le scrutin, créez une proposition, renseignez son titre et sa description, puis ajoutez les modifications une par une. Vous pouvez ainsi présenter un ensemble cohérent de changements dans une seule option.</p><p>Relisez toutes les modifications avant l’enregistrement : elles appartiennent à la même proposition et leur application dépendra du résultat du scrutin.</p>',
    'Une seule proposition peut reunir plusieurs changements qui doivent rester coherents.',
    2100
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'decision-dynamic-invites',
    'Les invites d''un scrutin suivent-ils les changements de membres des roles ?',
    'Les invitations par espace suivent les membres effectifs tant que le scrutin est ouvert.',
    '<ol><li>Invitez le role ou le cercle concerne dans les invitations du scrutin.</li><li>Apres un changement d''affectation, consultez le resume des participants effectifs.</li><li>Verifiez les acces des personnes qui doivent participer avant la cloture.</li></ol><p>Apres la cloture, l''historique de participation est conserve au lieu de suivre les appartenances futures.</p>',
    'Un scrutin ouvert peut suivre les membres actuels des roles invites.',
    2110
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'decision-date-poll',
    'Comment utiliser une decision pour choisir une date de reunion ?',
    'Le sondage de dates peut associer des plages horaires et des evenements en option aux propositions.',
    '<ol><li>Creez un sondage de dates et renseignez les dates, puis les horaires si necessaire.</li><li>Choisissez la creation d''evenements en option si vous voulez reserver ces pistes dans le calendrier.</li><li>Recueillez les reponses puis prenez la decision pour confirmer la date retenue.</li></ol><p>Les bulletins soumis liberent les dates refusees ; la decision annule les autres options.</p>',
    'Un sondage peut reserver plusieurs pistes de reunion avant de confirmer la date retenue.',
    2120
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'decision-badge',
    'Comment retrouver les scrutins qui attendent ma reponse ?',
    'La pastille personnelle signale les invitations a repondre encore ouvertes dans l''organisation.',
    '<ol><li>Survolez la pastille rouge de Decisions pour consulter le contexte et le compteur.</li><li>Cliquez pour ouvrir la participation attendue.</li><li>Terminez votre reponse puis verifiez les autres invitations.</li></ol>',
    'Les scrutins qui attendent votre reponse peuvent se rappeler a vous hors de leur espace.',
    2130
);

-- Structure, equipe et regles - juillet a octobre 2026
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'structure-mini',
    'Comment naviguer dans la structure sans ouvrir toute l''application Structure ?',
    'La mini-vue sous le panneau de contexte suit la navigation et se redimensionne avec son separateur.',
    '<ol><li>Reperez le separateur en bas du panneau de contexte.</li><li>Tirez-le pour afficher ou agrandir la mini-structure.</li><li>Cliquez sur le cercle ou role voulu pour changer de contexte, ou repliez la carte pour rendre de la place au panneau.</li></ol><p>Cette vue est disponible lorsque l''application Structure est active dans l''organisation.</p>',
    'Vous pouvez changer de role sur une petite carte sans quitter votre application.',
    2140
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'structure-list',
    'Comment lire la structure autrement que sous forme de cercles ?',
    'Structure propose une vue en liste en complement de sa representation graphique.',
    '<ol><li>Ouvrez Structure.</li><li>Basculez vers la representation Liste.</li><li>Parcourez les noms et proprietes, puis revenez aux cercles lorsque vous voulez vous orienter visuellement.</li></ol>',
    'Pour lire les proprietes et les noms complets, la structure sait aussi se presenter en liste.',
    2150
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'structure-history',
    'Comment retrouver ce qui a change dans un espace ou un role ?',
    'L''historique du contexte rassemble ses modifications et les references utiles aux elements concernes.',
    '<ol><li>Ouvrez la fiche de l''espace ou du role concerne.</li><li>Consultez son historique et reperez la date recherchee.</li><li>Ouvrez les details disponibles pour comparer les anciennes et nouvelles valeurs.</li></ol><p>Une reference peut aussi rappeler l''ancien nom d''un element renomme.</p>',
    'Un role renomme ou une affectation retiree ne fait pas disparaitre les traces de son histoire.',
    2160
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'team-assignment',
    'Comment modifier une affectation depuis la fiche d''un membre ?',
    'Le menu d''un role dans la fiche utilisateur ouvre le formulaire d''affectation Team.',
    '<ol><li>Ouvrez la fiche de la personne et reperez le role concerne.</li><li>Dans son menu d''actions, choisissez la modification de l''affectation.</li><li>Ajustez les informations autorisees puis enregistrez. Annuler permet de revenir a la fiche sans sauvegarder.</li></ol>',
    'Une affectation peut se corriger directement depuis la fiche de la personne concernee.',
    2170
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'team-invitation',
    'Comment renvoyer une invitation a un membre en attente ?',
    'La fiche d''une personne invitee explique son etat et propose le renvoi aux responsables autorises.',
    '<ol><li>Dans Team ou le contexte, ouvrez la vignette de la personne en attente.</li><li>Verifiez son adresse et les acces prepares.</li><li>Utilisez le renvoi d''invitation si vous en avez le droit.</li></ol><p>L''ajout a un autre role ne valide pas son adhesion : la personne doit toujours accepter son invitation.</p>',
    'Une invitation perdue peut etre renvoyee sans recreer le membre ni ses roles.',
    2180
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'team-organization-profile',
    'Comment adapter mon profil a une organisation ?',
    'Le profil specifique complete ou remplace les informations generales dans l''organisation courante.',
    '<ol><li>Ouvrez votre profil dans l''organisation concernee.</li><li>Selectionnez Profil specifique et renseignez les informations utiles a ce collectif.</li><li>Enregistrez puis consultez la previsualisation pour verifier ce qui est affiche.</li></ol>',
    'Votre presentation peut changer selon le collectif sans remplacer votre profil general.',
    2190
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'rule-scope',
    'Comment voir les regles applicables a mon contexte ?',
    'Les portees du Reglement distinguent les regles locales, celles applicables au contexte et la vue globale.',
    '<ol><li>Ouvrez Reglement depuis l''espace ou le role concerne.</li><li>Choisissez la portee Contextuel pour lire les regles applicables, y compris celles provenant des niveaux parents.</li><li>Utilisez Local pour les regles du contexte ou Global pour elargir la consultation selon vos droits.</li></ol><p>La portee de consultation ne change pas la portee d''application definie dans chaque regle.</p>',
    'Certaines regles vous concernent meme lorsqu elles viennent d''un cercle parent.',
    2200
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'rule-review',
    'Quelle difference entre une regle a revoir et une regle echue ?',
    'La date de revue et la date d''echeance produisent des alertes distinctes.',
    '<ol><li>Ouvrez la regle et ses informations de suivi.</li><li>Verifiez la date de revue pour savoir quand la reexaminer, puis l''echeance pour sa limite de validite.</li><li>Si vous etes autorise a la modifier, actualisez le contenu et les dates apres la revue.</li></ol>',
    'Une regle a revoir et une regle echue ne demandent pas la meme attention.',
    2210
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'rule-recent',
    'Comment retrouver les regles modifiees recemment ?',
    'Le tri par modification peut se combiner a une liste sans regroupement.',
    '<ol><li>Ouvrez les capsules de filtres du Reglement.</li><li>Choisissez l''ordre par modification et un affichage sans regroupement.</li><li>Appliquez la vue puis ouvrez les informations de tracabilite des regles utiles.</li></ol>',
    'Le reglement peut mettre en avant ce qui vient de changer, plutot que son classement habituel.',
    2220
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'authority-delegations',
    'Comment comprendre les delegations d''une autorite ?',
    'Le detail d''une autorite indique son parent, sa description et ses delegations directes.',
    '<ol><li>Dans la fiche de l''espace, ouvrez l''autorite en cliquant sur son nom.</li><li>Lisez la description et le rattachement parent.</li><li>Parcourez les delegations pour retrouver les espaces responsables des parties confiees.</li></ol>',
    'Derriere une autorite, vous pouvez retrouver les delegations et les espaces qui en prennent soin.',
    2230
);

-- Temps, budget et communication - septembre a octobre 2026
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'timer-switch',
    'Comment affecter mon temps de travail a un role ou un projet ?',
    'Le Timer permet de choisir une organisation, un contexte et eventuellement un projet pour le pointage.',
    '<ol><li>Ouvrez le Timer et choisissez l''organisation puis le role ou cercle concerne.</li><li>Selectionnez le projet si le temps lui est consacre et demarrez le compteur.</li><li>Ajoutez une legende utile et changez de selection lorsque vous passez a un autre travail. Arretez le compteur a la fin.</li></ol>',
    'Votre temps peut suivre le travail reel, du role au projet, au fil de vos changements de tache.',
    2240
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'timer-correction',
    'Comment corriger ou ajouter un pointage oublie ?',
    'La feuille des pointages recents permet de modifier une ligne terminee ou d''en ajouter une manuellement.',
    '<ol><li>Dans le Timer, choisissez le contexte voulu puis ouvrez les pointages par un glissement vertical du controle.</li><li>Ouvrez une ligne pour corriger ses dates ou sa legende, ou utilisez + pour ajouter un pointage.</li><li>Verifiez les horaires et enregistrez. Une ligne Interrompu merite aussi d''etre controlee.</li></ol>',
    'Un oubli de Timer ne condamne pas votre suivi : un pointage peut etre ajoute ou corrige apres coup.',
    2250
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'budget-read',
    'Comment comparer le temps passe et le budget temps ?',
    'L''application Budget presente les pointages du contexte et les references de budget disponibles.',
    '<ol><li>Ouvrez Budget dans le contexte voulu lorsque l''application est active.</li><li>Consultez la repartition du temps entre projets, roles et cercles.</li><li>Comparez la consommation a la reference de budget affichee pour la periode.</li></ol><p>Les budgets d''affectation et ceux du contexte sont distincts. Les droits de consultation et de modification peuvent differer.</p>',
    'Le temps passe sur les projets et celui consacre au role lui-meme peuvent se lire separement.',
    2260
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'mail-members',
    'Comment envoyer un e-mail a des membres depuis OMO ?',
    'L''action e-mail propose une selection de destinataires et une redaction dans le contexte de l''organisation.',
    '<ol><li>Dans Team ou le menu d''actions du contexte autorise, choisissez Envoyer un e-mail.</li><li>Cochez les destinataires voulus et verifiez le compteur ainsi que les adresses.</li><li>Redigez le sujet et le message, relisez la selection puis envoyez.</li></ol><p>Les adresses propres a l''organisation sont utilisees lorsqu elles sont definies. Les droits limitent les destinataires accessibles.</p>',
    'Un rappel a quelques membres peut partir d OMO sans reconstruire une liste d''adresses.',
    2270
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'mail-history',
    'Comment retrouver mes e-mails envoyes depuis OMO ?',
    'Le dossier Envoyes conserve les derniers messages de votre compte dans l''organisation et leurs resultats de livraison.',
    '<ol><li>Ouvrez l''icone e-mail de la topbar puis Envoyes.</li><li>Choisissez le message pour relire son contenu et ses destinataires.</li><li>Depliez le suivi pour examiner les resultats d''envoi avant de relancer un message.</li></ol><p>Les derniers envois sont limites a trente. Un envoi accepte ne garantit pas a lui seul la lecture du message par son destinataire.</p>',
    'Vous pouvez retrouver le contenu et le suivi d''un e-mail envoye depuis OMO.',
    2280
);

-- Culture organisationnelle et modeles - septembre a octobre 2026
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'survey-perspectives',
    'Comment utiliser le questionnaire de culture organisationnelle ?',
    'Le questionnaire explore dix principes avec des regards sur la situation actuelle, la situation souhaitee et votre affinite.',
    '<ol><li>Ouvrez le questionnaire ou le lien d''invitation de votre organisation.</li><li>Repondez aux etapes en distinguant ce que vous observez et ce que vous souhaitez.</li><li>Consultez les resultats pour reperer les ecarts qui peuvent nourrir une discussion collective.</li></ol>',
    'L''ecart entre la culture percue et celle souhaitee peut offrir un bon sujet de dialogue.',
    2290
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'survey-common',
    'Comment trouver des points d''accord dans les resultats de culture ?',
    'Le rapport collectif met en evidence des constats partages, des aspirations communes et les differences entre groupes.',
    '<ol><li>Ouvrez le rapport de culture de l''organisation avec votre acces autorise.</li><li>Lisez les constats et les destinations les plus partages.</li><li>Comparez ensuite les groupes sur le radar pour comprendre les differences sans reduire le collectif a sa moyenne.</li></ol>',
    'Un diagnostic de culture peut commencer par ce qui rassemble avant d''explorer les desaccords.',
    2300
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'survey-public',
    'Que montre le rapport public de culture organisationnelle ?',
    'Le rapport public presente les perceptions agregees d''aujourd''hui et de demain, sans les reponses individuelles ni l''affinite personnelle.',
    '<ol><li>Ouvrez le rapport public propose pour l''organisation.</li><li>Verifiez les informations agregees avant de partager son lien.</li><li>Gardez les liens de resultats individuels ou de rapports prives pour leurs destinataires autorises.</li></ol>',
    'Un diagnostic collectif peut se partager sans exposer les reponses individuelles.',
    2310
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'organization-template',
    'Puis-je partager mon organisation comme modèle public ?',
    'Oui, si elle possède une structure. Le modèle conserve les réglages utiles, sans publier les membres ni l’historique privé.',
    '<p>Une organisation structurée peut être partagée comme modèle public. Depuis ses options de partage, vérifiez les informations présentées avant de confirmer. Le modèle pourra notamment servir de point de départ pour créer un nouvel espace.</p><p>Il conserve les applications activées et leurs réglages, mais exclut les membres, l’historique, les rendez-vous, les valeurs d’indicateurs et l’historique budgétaire. Vérifiez malgré tout les textes et documents que vous choisissez de partager.</p>',
    'Le cadre utile de votre organisation peut inspirer d''autres collectifs sans leur transmettre ses membres.',
    2320
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'organization-template-custom',
    'Que puis-je personnaliser en créant une organisation depuis un modèle ?',
    'Vous pouvez ajuster son nom, ses images, sa couleur, sa position et plusieurs réglages avant la création.',
    '<ol><li>Choisissez le modèle d’organisation que vous souhaitez utiliser.</li><li>Dans le formulaire de création, personnalisez le nom, les images, la couleur, la position et les réglages proposés.</li><li>Vérifiez les choix avant de créer l’organisation.</li></ol><p>Le modèle sert de point de départ ; vous pourrez ensuite compléter votre organisation et définir ses autres informations, notamment ses routes.</p>',
    'Un modele peut offrir un point de depart tout en laissant de la place aux choix de votre collectif.',
    2330
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'lexicon',
    'Peut-on changer les mots « espace », « cercle », « rôle » ou « groupe » ?',
    'Oui. Le vocabulaire de ces éléments peut être adapté à chaque organisation.',
    '<p>Dans les réglages de l’organisation, ouvrez la personnalisation du vocabulaire et choisissez les termes qui correspondent à votre fonctionnement pour les espaces, cercles, rôles ou groupes.</p><p>Cette personnalisation modifie les libellés affichés dans l’organisation, pas la nature des objets ni les droits associés. Chaque organisation peut employer son propre vocabulaire.</p>',
    'Votre collectif peut retrouver ses propres mots dans l''interface d OMO.',
    2340
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'organization-zip',
    'Comment exporter une organisation avec ses images ?',
    'L''export OMO 2 en ZIP inclut les donnees choisies et les images locales prises en charge.',
    '<ol><li>Dans les parametres de l''organisation, ouvrez l''export avec les droits necessaires.</li><li>Selectionnez les modules et telechargez le ZIP.</li><li>Lors de l''import, verifiez les modules et les membres a inviter, puis controlez les ressources documentaires externes.</li></ol><p>Le ZIP peut reprendre photos, images des espaces, logo et banniere. Les fichiers televerses et les documents lies a des services externes ne constituent pas une sauvegarde documentaire complete.</p>',
    'Les illustrations d''une organisation peuvent accompagner ses donnees lors d''un export.',
    2350
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'account-merge',
    'Comment fusionner deux de mes comptes ?',
    'La fusion se lance depuis l’onglet Outils du profil, avec une vérification du second compte.',
    '<ol><li>Ouvrez votre profil, puis l’onglet <strong>Outils</strong>.</li><li>Dépliez l’action de fusion des comptes et indiquez le second compte.</li><li>Vérifiez que vous en êtes propriétaire avec le code reçu par e-mail ou son mot de passe, puis la double authentification si elle est activée.</li><li>Consultez la confirmation avant de lancer la fusion.</li></ol><p>La fusion conserve notamment les droits d’administration et le statut de superadmin détenus par l’un ou l’autre profil.</p>',
    'Deux adresses ont cree deux comptes ? Votre parcours peut etre reuni dans un seul profil.',
    2360
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'account-dav-password',
    'Puis-je utiliser un mot de passe pour mes agendas sans autoriser la connexion web par mot de passe ?',
    'Le profil distingue le mot de passe utilise par CalDAV et CardDAV de son autorisation pour la connexion au site.',
    '<ol><li>Dans votre profil general, ouvrez la partie Mot de passe et definissez-en un si necessaire.</li><li>Laissez Autoriser le login avec mot de passe decoche si vous le reservez a la synchronisation.</li><li>Enregistrez. Utilisez ce mot de passe dans votre application de contacts ou de calendrier et le code e-mail pour le site.</li></ol>',
    'Un mot de passe peut servir uniquement a vos agendas et contacts, sans ouvrir la connexion web par mot de passe.',
    2370
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'faq-ask',
    'Comment demander une reponse absente de la FAQ ?',
    'La FAQ propose un formulaire de question lorsque la recherche ne donne pas la reponse voulue.',
    '<ol><li>Ouvrez la FAQ depuis l''aide et cherchez quelques mots sur votre probleme.</li><li>Utilisez Poser la question si vous ne trouvez pas de reponse adaptee.</li><li>Renseignez la question et les details utiles, puis envoyez la demande.</li></ol><p>La demande n''est pas publiee automatiquement comme une reponse : les administrateurs peuvent la traiter et completer l''aide.</p>',
    'Une question sans reponse peut devenir le prochain ajout utile a la FAQ.',
    2380
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'tips-recall',
    'Comment retrouver une astuce apres sa disparition ?',
    'La zone au-dessus des parametres rappelle l''ampoule et l''astuce du jour.',
    '<ol><li>Placez le pointeur juste au-dessus du bouton des parametres dans la barre de gauche.</li><li>Ouvrez l''ampoule qui reapparait. Le survol ou le focus dans la fenetre suspend son decompte.</li><li>Utilisez Suivant pour decouvrir une autre astuce ou ouvrez son lien pour lire la marche a suivre.</li></ol>',
    'Une astuce disparue n''est pas perdue : vous pouvez la rappeler et prendre le temps de la lire.',
    2390
);

-- Petits gestes utiles - juillet a octobre 2026
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'property-paste-list',
    'Comment remplir rapidement une propriete avec une liste deja ecrite ?',
    'Le collage multiligne dans une liste de textes cree des elements distincts.',
    '<ol><li>Copiez la liste depuis votre texte ou votre document.</li><li>Dans l''edition de la propriete de type liste de textes, collez les lignes dans un champ de la liste.</li><li>Verifiez les elements separes et enregistrez. Les prefixes courants de puces ou de numerotation sont retires.</li></ol>',
    'Une liste deja ecrite peut remplir une propriete en une fois, sans recopier chaque ligne.',
    2400
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'editor-between-blocks',
    'Comment ajouter du texte entre deux blocs de ressources ?',
    'Le bouton + de l''editeur cree une ligne de texte a l''endroit choisi entre les blocs.',
    '<ol><li>Ouvrez l''edition du contenu qui contient les ressources.</li><li>Survolez la limite des blocs et utilisez le petit + a l''endroit voulu.</li><li>Saisissez votre texte sur la ligne ajoutee, puis enregistrez.</li></ol>',
    'Meme entre deux ressources integrees, votre editeur sait faire de la place a une nouvelle phrase.',
    2410
);
INSERT INTO tip_help_seed (seed_key, question, answer, detail, tip_text, displayorder) VALUES (
    'survey-resume',
    'Puis-je reprendre un questionnaire de culture non termine ?',
    'Les reponses en cours sont conservees localement pour permettre la reprise sur le meme appareil.',
    '<ol><li>Rouvrez le questionnaire sur le meme appareil et dans le meme navigateur.</li><li>Reprenez les reponses en cours plutot que de recommencer.</li><li>Terminez les etapes et consultez le resultat.</li></ol><p>Un autre appareil ou l''effacement des donnees locales du navigateur ne retrouve pas ce brouillon.</p>',
    'Votre questionnaire de culture peut attendre une pause sans vous faire recommencer depuis le debut.',
    2420
);

-- Ne reutiliser que les FAQ generiques : un homonyme propre a une organisation,
-- un parcours ou une application ne doit pas recevoir un lien destine a tous.
INSERT INTO faq (question, answer, detail, displayorder, isactive, created, updated)
SELECT seed.question, seed.answer, seed.detail, seed.displayorder, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()
FROM tip_help_seed AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM faq AS existing
    WHERE existing.question COLLATE utf8mb4_unicode_ci = seed.question
      AND existing.IDorganization IS NULL
      AND existing.IDholon IS NULL
      AND existing.IDparcours IS NULL
      AND existing.IDapplication IS NULL
);

-- S il existe plusieurs FAQ de meme titre, privilegier une reponse publiee,
-- puis le premier ID reel. Cet ID peut etre different sur chaque installation.
UPDATE tip_help_seed AS seed
SET faq_id = (
    SELECT existing.id FROM faq AS existing
    WHERE existing.question COLLATE utf8mb4_unicode_ci = seed.question
      AND existing.IDorganization IS NULL
      AND existing.IDholon IS NULL
      AND existing.IDparcours IS NULL
      AND existing.IDapplication IS NULL
    ORDER BY existing.isactive DESC, existing.id ASC
    LIMIT 1
);

INSERT INTO tip (text, link, active)
SELECT seed.tip_text, CONCAT('#|faq-', faq.id), IF(faq.isactive = 1, 1, 0)
FROM tip_help_seed AS seed
JOIN faq ON faq.id = seed.faq_id
WHERE NOT EXISTS (
    SELECT 1 FROM tip AS existing WHERE existing.link = CONCAT('#|faq-', faq.id)
);

DROP TEMPORARY TABLE tip_help_seed;
