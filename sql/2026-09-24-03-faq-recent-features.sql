-- @migration
SET NAMES utf8mb4;

-- FAQ generale : les identifiants sont attribues par la base.
-- La verification sur la question evite les doublons si ce fichier est execute
-- manuellement en production puis repris par le flux de migrations.
INSERT INTO `faq` (`question`, `answer`, `detail`, `displayorder`, `isactive`, `created`, `updated`)
SELECT proposed.`question`, proposed.`answer`, proposed.`detail`, proposed.`displayorder`, 1, NOW(), NOW()
FROM (
    SELECT
        'Peut-on utiliser OMO sans avoir créé de structure ?' AS `question`,
        'Oui. Plusieurs fonctions restent utilisables directement au niveau de l’organisation, même sans espace ni cercle.' AS `answer`,
        '<p>Vous pouvez commencer dans une organisation qui ne possède pas encore de structure. Selon vos droits, créez directement à sa racine des règles, projets, documents, indicateurs, tâches récurrentes ou processus.</p><p>Si vous ajoutez des espaces plus tard, ces éléments restent rattachés à l’organisation et visibles depuis sa racine. Vous n’avez donc pas besoin de créer une structure provisoire pour démarrer.</p>' AS `detail`,
        300 AS `displayorder`
    UNION ALL SELECT
        'Comment créer une règle dans une organisation sans structure ?',
        'Ouvrez les règles de l’organisation et créez une règle à sa racine ; aucun espace n’est nécessaire.',
        '<ol><li>Ouvrez votre organisation, puis la rubrique des règles.</li><li>Lancez la création d’une règle et renseignez son contenu.</li><li>Enregistrez-la au niveau de l’organisation, selon les droits dont vous disposez.</li></ol><p>Le choix d’une autorité n’est proposé que si des autorités existent dans le contexte courant. La règle restera visible à la racine si une structure est ajoutée plus tard.</p>',
        310
    UNION ALL SELECT
        'Faut-il choisir un espace pour créer un projet ?',
        'Non. Dans une organisation sans structure, un projet peut être rattaché directement à l’organisation.',
        '<p>Ouvrez les projets de l’organisation, créez votre projet et complétez les informations demandées. Si l’organisation ne possède pas de structure, aucun espace n’est à choisir : le projet est enregistré à la racine.</p><p>Il reste accessible depuis cette racine même si vous créez des espaces par la suite.</p>',
        320
    UNION ALL SELECT
        'Peut-on exécuter un processus sans avoir défini d’espaces ?',
        'Oui. Un processus peut être créé et exécuté directement dans une organisation sans structure, selon vos droits.',
        '<ol><li>Depuis l’organisation, ouvrez les processus.</li><li>Créez un processus ou ouvrez-en un auquel vous avez accès.</li><li>Lancez son exécution et suivez ses étapes comme d’habitude.</li></ol><p>Lorsqu’aucune structure n’existe, les étapes ne demandent pas de choisir un espace. Les projets modèles et les projets produits par le processus restent rattachés à l’organisation.</p>',
        330
    UNION ALL SELECT
        'Où apparaissent les tâches récurrentes d’une organisation sans structure ?',
        'Elles apparaissent au niveau de l’organisation, dont le nom est indiqué dans la liste et le détail.',
        '<p>Ouvrez les tâches récurrentes depuis la racine de l’organisation. Selon vos droits, vous pouvez y créer, modifier, valider ou supprimer une tâche, sans créer d’espace au préalable.</p><p>Si une structure est ajoutée ensuite, les tâches déjà rattachées à l’organisation restent visibles depuis sa racine.</p>',
        340
    UNION ALL SELECT
        'Comment fusionner deux de mes comptes ?',
        'La fusion se lance depuis l’onglet Outils du profil, avec une vérification du second compte.',
        '<ol><li>Ouvrez votre profil, puis l’onglet <strong>Outils</strong>.</li><li>Dépliez l’action de fusion des comptes et indiquez le second compte.</li><li>Vérifiez que vous en êtes propriétaire avec le code reçu par e-mail ou son mot de passe, puis la double authentification si elle est activée.</li><li>Consultez la confirmation avant de lancer la fusion.</li></ol><p>La fusion conserve notamment les droits d’administration et le statut de superadmin détenus par l’un ou l’autre profil.</p>',
        350
    UNION ALL SELECT
        'Que se passe-t-il si je supprime mon profil ?',
        'Un récapitulatif précise les conséquences avant confirmation ; certains cas sont bloqués pour protéger les organisations.',
        '<ol><li>Dans votre profil, ouvrez <strong>Outils</strong>, puis l’action de suppression du profil.</li><li>Lisez le récapitulatif : il distingue les organisations conservées de celles qui seraient supprimées avec leur historique parce que vous en êtes le seul membre.</li><li>Résolvez les éventuels blocages, par exemple si vous êtes le dernier administrateur d’une organisation.</li><li>Si vous souhaitez toujours poursuivre, saisissez la confirmation textuelle demandée.</li></ol><p>Cette action est définitive. Les organisations conservées gardent les références historiques nécessaires grâce à un profil technique.</p>',
        360
    UNION ALL SELECT
        'Puis-je connecter Patreon depuis n’importe quel site OMO ?',
        'Oui, depuis un site autorisé : la connexion s’effectue dans une fenêtre dédiée, puis la page d’origine se met à jour.',
        '<ol><li>Depuis votre profil sur le site OMO que vous utilisez, lancez la connexion à Patreon.</li><li>Terminez l’autorisation dans la fenêtre de connexion qui s’ouvre.</li><li>Revenez à la page d’origine : elle est actualisée après la liaison du compte.</li></ol><p>La fenêtre peut utiliser un autre domaine que la page de départ. Si elle ne s’ouvre pas, vérifiez le blocage des fenêtres surgissantes et relancez la connexion depuis votre profil.</p>',
        370
    UNION ALL SELECT
        'Que peut-on proposer comme modification depuis un point de PV ?',
        'Un point de PV peut préparer plusieurs types de changements, qui ne prennent effet qu’après leur validation.',
        '<p>Dans un point de procès-verbal, ajoutez une modification différée, puis choisissez l’objet concerné, l’action à effectuer et le contexte. Selon vos droits collectifs, vous pouvez notamment proposer de créer, modifier ou supprimer une règle, un élément de structure, un projet, un indicateur ou une tâche récurrente.</p><p>Relisez la proposition dans le PV : préparer une modification ne l’applique pas immédiatement.</p>',
        380
    UNION ALL SELECT
        'Peut-on proposer un changement d’indicateur ou de tâche récurrente dans un PV ?',
        'Oui. Leur création, modification ou suppression peut être préparée dans un PV si les droits collectifs le permettent.',
        '<ol><li>Ouvrez le point de PV concerné et ajoutez une modification différée.</li><li>Choisissez <strong>Indicateur</strong> ou <strong>Tâche récurrente</strong>, puis l’action souhaitée.</li><li>Renseignez les champs dans l’éditeur proposé, relisez le résultat et enregistrez le point.</li></ol><p>Le changement n’est appliqué qu’après validation. Pour un indicateur issu d’un tableau, la modification porte sur une seule colonne de valeurs.</p>',
        390
    UNION ALL SELECT
        'Comment proposer un projet pendant la rédaction d’un PV ?',
        'Ajoutez une modification différée de type Projet au point de PV, puis choisissez la création, la modification ou la suppression.',
        '<ol><li>Dans le point de PV, ouvrez l’ajout de modification.</li><li>Sélectionnez <strong>Projet</strong>, l’action voulue et le contexte concerné.</li><li>Complétez ou relisez le formulaire du projet, puis enregistrez le point.</li></ol><p>L’option <strong>Proposer un projet</strong> apparaît lorsque vous disposez du droit collectif correspondant. Le projet proposé ne change réellement qu’après validation.</p>',
        400
    UNION ALL SELECT
        'Puis-je proposer une règle pour un autre espace ?',
        'Oui, si le contexte et vos droits collectifs permettent de proposer cette règle dans l’espace choisi.',
        '<p>Lors de l’ajout d’une modification de règle depuis un PV ou une décision, choisissez d’abord le contexte visé, puis l’action à proposer. Le formulaire adapte les choix disponibles à ce contexte et aux droits collectifs correspondants.</p><p>Avant d’enregistrer, vérifiez bien le nom de l’espace ou de l’organisation cible : c’est là que la règle sera créée ou modifiée après validation.</p>',
        410
    UNION ALL SELECT
        'Comment revoir ou corriger une proposition faite dans un PV ?',
        'Rouvrez la modification différée du point de PV, vérifiez son contexte et ajustez-la avant validation.',
        '<ol><li>Ouvrez le point de PV contenant la proposition.</li><li>Dépliez ses modifications différées pour retrouver l’objet, l’action et le contexte concernés.</li><li>Modifiez les champs nécessaires et enregistrez le point.</li></ol><p>Les vues de détail montrent les créations, suppressions et comparaisons avant/après. Servez-vous-en pour contrôler le résultat envisagé avant que la décision ne soit validée.</p>',
        420
    UNION ALL SELECT
        'Comment savoir si une modification décidée a réellement été appliquée ?',
        'Consultez le résultat de chaque modification : il indique si elle est appliquée, en attente, non retenue ou en échec.',
        '<p>Après la décision, ouvrez son détail et regardez le message affiché pour chaque modification. Une proposition préparée ne signifie pas à elle seule que le changement a été exécuté.</p><p>Le résultat distingue les modifications <strong>appliquées</strong>, celles qui sont <strong>en attente</strong>, celles qui n’ont <strong>pas été retenues</strong> et les éventuels <strong>échecs</strong>. En cas d’échec, vérifiez le contexte et les droits avant une nouvelle action.</p>',
        430
    UNION ALL SELECT
        'Une option de scrutin peut-elle contenir plusieurs modifications ?',
        'Oui. Une proposition de scrutin regroupe un titre, une description et autant de modifications nécessaires.',
        '<p>Dans le scrutin, créez une proposition, renseignez son titre et sa description, puis ajoutez les modifications une par une. Vous pouvez ainsi présenter un ensemble cohérent de changements dans une seule option.</p><p>Relisez toutes les modifications avant l’enregistrement : elles appartiennent à la même proposition et leur application dépendra du résultat du scrutin.</p>',
        440
    UNION ALL SELECT
        'Quelles modifications d’un scrutin sont appliquées à la clôture ?',
        'Seules les modifications de la proposition retenue peuvent être appliquées à la clôture ; les autres ne le sont pas.',
        '<p>À la clôture du scrutin, ouvrez le détail du résultat. La proposition retenue détermine les modifications à appliquer. Celles des propositions non retenues restent sans effet.</p><p>Contrôlez le statut de chaque modification : une modification retenue peut encore être en attente ou avoir échoué, ce que son message de résultat indique séparément.</p>',
        450
    UNION ALL SELECT
        'L’IA peut-elle résumer une proposition de décision ?',
        'Oui. Dans une décision hors réorganisation, elle peut préparer un texte que vous relisez et modifiez avant enregistrement.',
        '<ol><li>Préparez d’abord les modifications de votre proposition de décision.</li><li>Utilisez l’action de rédaction par l’IA pour obtenir une description ou un résumé.</li><li>Relisez le brouillon, corrigez-le si nécessaire et enregistrez votre proposition.</li></ol><p>Le texte est rédigé dans la langue de votre interface. Il présente les modifications comme des actions proposées, et non comme des changements déjà réalisés.</p>',
        460
    UNION ALL SELECT
        'À quoi sert le champ « Intention et contexte » d’une proposition ?',
        'Ce champ facultatif explique pourquoi vous proposez une décision et donne du contexte à sa rédaction.',
        '<p>Dans une décision hors réorganisation, affichez le champ <strong>Intention et contexte</strong> si vous souhaitez préciser l’objectif, le problème à résoudre ou les éléments utiles à la discussion.</p><p>Vous pouvez ensuite demander à l’IA de préparer une description à partir des modifications déjà saisies, puis reprendre manuellement le texte avant l’enregistrement. Le champ reste facultatif.</p>',
        470
    UNION ALL SELECT
        'Pourquoi les boutons de vote ne sont-ils pas encore visibles ?',
        'Pendant l’élaboration d’une décision, le vote n’est pas encore ouvert ; seules les contributions autorisées sont disponibles.',
        '<p>Vérifiez la phase et la date de début du vote affichées pour la décision. Tant qu’elle est en <strong>élaboration</strong>, les choix et boutons de vote simple ou de consentement ne s’affichent pas.</p><p>Vous pouvez néanmoins contribuer si vos droits le permettent. Les commandes de vote apparaissent lorsque la phase de vote commence effectivement ; un statut incohérent ne permet pas de l’ouvrir en avance.</p>',
        480
    UNION ALL SELECT
        'Quels documents peut-on enregistrer comme modèles ?',
        'Les documents HTML, liens, PV, fichiers, outils collaboratifs et dossiers peuvent servir de modèles.',
        '<p>Dans Documents, ouvrez la fiche d’un document compatible et ajoutez-le à la liste des modèles. Cela concerne notamment un document HTML, un lien externe, un PV, un fichier tel qu’un ODT, un Etherpad ou EtherCalc, ainsi qu’un dossier.</p><p>Une étoile signale les modèles. Ils sont regroupés par espace et identifiés par leur icône de type pour faciliter leur choix.</p>',
        490
    UNION ALL SELECT
        'Comment créer un document à partir d’un modèle ?',
        'Dans Documents, utilisez la flèche à côté de Nouveau et choisissez un modèle compatible avec le contexte courant.',
        '<ol><li>Ouvrez Documents dans le contexte où vous voulez placer la nouvelle copie.</li><li>Cliquez sur la flèche à côté de <strong>Nouveau</strong>.</li><li>Choisissez le modèle souhaité parmi ceux proposés, puis vérifiez le duplicata créé.</li></ol><p>La copie est visible dans le contexte courant. Les réunions et les projets proposent également des modèles compatibles avec leur type et leur portée.</p>',
        500
    UNION ALL SELECT
        'La copie d’un modèle dépend-elle encore du document d’origine ?',
        'Non pour les fichiers et outils collaboratifs : leur contenu est copié dans une ressource indépendante.',
        '<p>Quand vous créez une copie depuis un modèle, OMO duplique le document. Pour un fichier ou un outil collaboratif, le contenu est recopié dans une ressource indépendante : vous pouvez donc travailler sur la copie sans modifier la ressource du modèle.</p><p>Si le modèle est un dossier, toute son arborescence est reprise. Vérifiez ensuite les éléments copiés dans le contexte de destination.</p>',
        510
    UNION ALL SELECT
        'Que retrouve-t-on après un export puis un import OMO 2 ?',
        'Les suivis et équipes de projets, validations de tâches, exécutions de processus et types de points de PV sont préservés.',
        '<p>L’export OMO 2 conserve les informations de suivi des projets et leurs équipes, les validations des tâches récurrentes, les exécutions de processus et les types des points de procès-verbal. Ces éléments sont restaurés à l’import.</p><p>Avant d’exporter, lisez l’avertissement de la fenêtre d’export : les fichiers téléversés et les documents liés à des services externes ne sont pas inclus. Prévoyez leur conservation séparément si vous en avez besoin.</p>',
        520
    UNION ALL SELECT
        'Puis-je partager mon organisation comme modèle public ?',
        'Oui, si elle possède une structure. Le modèle conserve les réglages utiles, sans publier les membres ni l’historique privé.',
        '<p>Une organisation structurée peut être partagée comme modèle public. Depuis ses options de partage, vérifiez les informations présentées avant de confirmer. Le modèle pourra notamment servir de point de départ pour créer un nouvel espace.</p><p>Il conserve les applications activées et leurs réglages, mais exclut les membres, l’historique, les rendez-vous, les valeurs d’indicateurs et l’historique budgétaire. Vérifiez malgré tout les textes et documents que vous choisissez de partager.</p>',
        530
    UNION ALL SELECT
        'Que puis-je personnaliser en créant une organisation depuis un modèle ?',
        'Vous pouvez ajuster son nom, ses images, sa couleur, sa position et plusieurs réglages avant la création.',
        '<ol><li>Choisissez le modèle d’organisation que vous souhaitez utiliser.</li><li>Dans le formulaire de création, personnalisez le nom, les images, la couleur, la position et les réglages proposés.</li><li>Vérifiez les choix avant de créer l’organisation.</li></ol><p>Le modèle sert de point de départ ; vous pourrez ensuite compléter votre organisation et définir ses autres informations, notamment ses routes.</p>',
        540
    UNION ALL SELECT
        'Peut-on changer les mots « espace », « cercle », « rôle » ou « groupe » ?',
        'Oui. Le vocabulaire de ces éléments peut être adapté à chaque organisation.',
        '<p>Dans les réglages de l’organisation, ouvrez la personnalisation du vocabulaire et choisissez les termes qui correspondent à votre fonctionnement pour les espaces, cercles, rôles ou groupes.</p><p>Cette personnalisation modifie les libellés affichés dans l’organisation, pas la nature des objets ni les droits associés. Chaque organisation peut employer son propre vocabulaire.</p>',
        550
    UNION ALL SELECT
        'Comment suivre mon calendrier OMO dans Google Calendar ?',
        'Copiez le lien ICS proposé dans le menu Connecter du calendrier, puis ajoutez-le à Google Calendar.',
        '<ol><li>Dans OMO, ouvrez Calendrier et choisissez le contexte que vous souhaitez suivre.</li><li>Ouvrez le menu <strong>Connecter</strong> et copiez le lien ICS proposé pour cette portée.</li><li>Dans Google Calendar, ajoutez un calendrier à partir de son URL et collez ce lien.</li></ol><p>Le panneau d’aide du menu Connecter détaille les étapes. Le lien correspond au contexte choisi ; une actualisation externe peut prendre du temps selon Google Calendar.</p>',
        560
    UNION ALL SELECT
        'Documents et Calendrier retrouvent-ils ma dernière vue ?',
        'Oui. Leur portée et leur période sont mémorisées dans le navigateur pour retrouver directement la vue utilisée.',
        '<p>Après avoir choisi une portée dans Documents ou une portée et une période dans Calendrier, revenez plus tard à l’application : la préférence enregistrée dans ce navigateur est reprise dès l’ouverture.</p><p>Un lien direct vers une autre vue garde la priorité. Les préférences sont locales au navigateur ; elles peuvent donc différer sur un autre appareil ou après effacement des données du site.</p>',
        570
    UNION ALL SELECT
        'Comment limiter le tableau de pilotage à ce qui me concerne ?',
        'Réglez la portée des modules Projets, Indicateurs ou Tâches récurrentes sur Moi ou Mes espaces.',
        '<p>Dans le tableau de pilotage, ouvrez la configuration du module concerné. Pour les projets, les indicateurs et les tâches récurrentes, choisissez <strong>Moi</strong> pour privilégier les éléments qui vous sont attribués, <strong>Mes espaces</strong> pour élargir la vue à vos espaces, ou <strong>Tous</strong> pour la portée la plus large.</p><p>Les éléments effectivement visibles restent soumis à vos droits d’accès. Dans l’application Projets, le filtre distinct <strong>Suivi</strong> montre les projets suivis par au moins une personne ; il ne signifie pas « suivis par moi ».</p>',
        580
    UNION ALL SELECT
        'Où sont passés les onglets sur mobile ?',
        'Sur petit écran, les onglets sont regroupés dans un menu déroulant qui affiche le nom complet de l’onglet actif.',
        '<p>Si les onglets côte à côte ne sont plus visibles sur votre téléphone, touchez le menu qui porte le nom de l’onglet actif, puis sélectionnez celui que vous voulez ouvrir. Les mêmes contenus et actions restent disponibles.</p><p>Sur un écran plus large, les onglets réapparaissent côte à côte. Dans la navigation mobile, le tableau de bord porte désormais le nom <strong>Pilotage</strong>.</p>',
        590
) AS proposed
WHERE NOT EXISTS (
    SELECT 1
    FROM `faq` AS existing
    WHERE existing.`question` = proposed.`question`
      AND existing.`IDorganization` IS NULL
      AND existing.`IDholon` IS NULL
      AND existing.`IDparcours` IS NULL
      AND existing.`IDapplication` IS NULL
);
