<?php

$sourceLang = [
    'survey.page.title' => [
        'text' => 'Test de maturité organisationnelle',
        'context' => 'HTML page title for the survey prototype.',
    ],
    'survey.hero.eyebrow' => [
        'text' => '10 principes · 2 regards',
        'context' => 'Short label above the survey title.',
    ],
    'survey.hero.title' => [
        'text' => 'Où en est votre organisation ?',
        'context' => 'Main title on the survey introduction.',
    ],
    'survey.hero.intro' => [
        'text' => 'Explorez la maturité de votre organisation telle que vous la percevez aujourd’hui, puis dessinez celle que vous aimeriez voir émerger demain.',
        'context' => 'Introductory paragraph on the survey welcome screen.',
    ],
    'survey.invitation.page_title' => [
        'text' => 'Évaluer {organization}',
        'context' => 'Browser title for a survey opened from an organization invitation.',
    ],
    'survey.invitation.hero_eyebrow' => [
        'text' => 'Votre regard pour {organization}',
        'context' => 'Welcome eyebrow for a survey opened from an organization invitation.',
    ],
    'survey.invitation.hero_title' => [
        'text' => 'Comment percevez-vous {organization} ?',
        'context' => 'Welcome title for a survey opened from an organization invitation.',
    ],
    'survey.invitation.fact' => [
        'text' => 'Résultat associé à {organization}',
        'context' => 'Welcome fact explaining where an invited survey result will be stored.',
    ],
    'survey.intro.time' => [
        'text' => 'Environ 10 minutes',
        'context' => 'Estimated completion time shown on the welcome screen.',
    ],
    'survey.intro.questions' => [
        'text' => '10 principes à explorer',
        'context' => 'Question count shown on the welcome screen.',
    ],
    'survey.intro.private' => [
        'text' => 'Réponses enregistrées uniquement sur cet appareil',
        'context' => 'Local-only storage reassurance on the welcome screen.',
    ],
    'survey.intro.how_title' => [
        'text' => 'Comment ça marche ?',
        'context' => 'Heading above the short survey instructions.',
    ],
    'survey.intro.how_scale' => [
        'text' => 'Indiquez d’abord votre affinité avec le principe, sur une échelle de 1 à 5.',
        'context' => 'First instruction on the survey welcome screen.',
    ],
    'survey.intro.how_choice' => [
        'text' => 'Choisissez ensuite la situation qui ressemble le mieux à chaque temporalité.',
        'context' => 'Second instruction on the survey welcome screen.',
    ],
    'survey.action.start' => [
        'text' => 'Plonger dans le questionnaire',
        'context' => 'Primary button that starts a new survey.',
    ],
    'survey.action.resume' => [
        'text' => 'Reprendre là où j’en étais',
        'context' => 'Primary button that resumes a locally saved survey.',
    ],
    'survey.action.restart' => [
        'text' => 'Recommencer',
        'context' => 'Button that clears the current local answers.',
    ],
    'survey.invite.action' => [
        'text' => 'Évaluer mon orga par ses membres et parties prenantes',
        'context' => 'Welcome button opening the organization survey invitation dialog.',
    ],
    'survey.invite.eyebrow' => [
        'text' => 'Recueillir des regards',
        'context' => 'Eyebrow in the survey invitation dialog.',
    ],
    'survey.invite.title' => [
        'text' => 'Inviter à évaluer une organisation',
        'context' => 'Title in the survey invitation dialog.',
    ],
    'survey.invite.intro' => [
        'text' => 'Les réponses seront rattachées à cette organisation, sans donner aux personnes invitées le statut de membre.',
        'context' => 'Explanation in the survey invitation dialog.',
    ],
    'survey.invite.organization' => [
        'text' => 'Organisation',
        'context' => 'Organization field label in the survey invitation dialog.',
    ],
    'survey.invite.holons' => [
        'text' => 'Holons',
        'context' => 'Holon selector tab in the survey invitation dialog.',
    ],
    'survey.invite.members' => [
        'text' => 'Membres',
        'context' => 'Member selector tab in the survey invitation dialog.',
    ],
    'survey.invite.emails' => [
        'text' => 'E-mails',
        'context' => 'Email selector tab in the survey invitation dialog.',
    ],
    'survey.invite.email_help' => [
        'text' => 'Une adresse par ligne, ou plusieurs adresses séparées par des virgules.',
        'context' => 'Help below external email textarea in the survey invitation dialog.',
    ],
    'survey.invite.email_placeholder' => [
        'text' => 'partenaire@example.org',
        'context' => 'Placeholder in external email textarea in the survey invitation dialog.',
    ],
    'survey.invite.send' => [
        'text' => 'Envoyer les invitations',
        'context' => 'Submit button in the survey invitation dialog.',
    ],
    'survey.invite.sending' => [
        'text' => 'Envoi en cours…',
        'context' => 'Busy label for the invitation dialog submit button.',
    ],
    'survey.invite.sent' => [
        'text' => '{count} invitation(s) envoyée(s).',
        'context' => 'Success message after sending survey invitations.',
    ],
    'survey.invite.error' => [
        'text' => 'Les invitations n’ont pas pu être envoyées.',
        'context' => 'Generic error message in the survey invitation dialog.',
    ],
    'survey.invite.empty' => [
        'text' => 'Aucune organisation active n’est associée à votre compte.',
        'context' => 'Message when an authenticated user has no organization available for survey invitations.',
    ],
    'survey.invite.close' => [
        'text' => 'Fermer',
        'context' => 'Close label for the survey invitation dialog.',
    ],
    'survey.action.back' => [
        'text' => 'Retour',
        'context' => 'Previous step navigation button.',
    ],
    'survey.action.next' => [
        'text' => 'Suivant',
        'context' => 'Next step navigation button.',
    ],
    'survey.action.results' => [
        'text' => 'Découvrir mon aperçu',
        'context' => 'Button that opens the prototype results summary.',
    ],
    'survey.action.review' => [
        'text' => 'Revoir mes réponses',
        'context' => 'Button that returns from results to the survey.',
    ],
    'survey.progress.principle' => [
        'text' => 'Principe {current} sur {total}',
        'context' => 'Progress label for the current principle.',
    ],
    'survey.progress.complete' => [
        'text' => '{percent}% parcouru',
        'context' => 'Percentage progress label for the survey.',
    ],
    'survey.principle.label' => [
        'text' => 'Le principe',
        'context' => 'Label introducing the current principle description.',
    ],
    'survey.phase.scale.label' => [
        'text' => '1 · Affinité',
        'context' => 'Label for the scale phase of a principle.',
    ],
    'survey.phase.scale.title' => [
        'text' => 'À quel point ce principe résonne-t-il avec votre vision ?',
        'context' => 'Heading for the scale phase.',
    ],
    'survey.phase.scale.help' => [
        'text' => 'Choisissez une valeur de 1 à 5. Il n’y a pas de bonne ou de mauvaise réponse.',
        'context' => 'Guidance shown above the two scale selectors.',
    ],
    'survey.phase.choice.label' => [
        'text' => '2 · Situations',
        'context' => 'Label for the situation choice phase of a principle.',
    ],
    'survey.phase.choice.title' => [
        'text' => 'Quelle situation vous ressemble le plus ?',
        'context' => 'Heading for the situation choice phase.',
    ],
    'survey.phase.choice.help' => [
        'text' => 'Choisissez d’abord la situation actuelle, puis la situation idéale souhaitée.',
        'context' => 'Guidance shown above the situation cards.',
    ],
    'survey.period.today' => [
        'text' => 'Aujourd’hui',
        'context' => 'Label for the current perceived situation.',
    ],
    'survey.period.today.help' => [
        'text' => 'La situation telle que vous la percevez maintenant.',
        'context' => 'Short explanation of the current period.',
    ],
    'survey.period.tomorrow' => [
        'text' => 'Demain',
        'context' => 'Label for the desired future situation.',
    ],
    'survey.period.tomorrow.help' => [
        'text' => 'La situation idéale que vous souhaitez voir advenir.',
        'context' => 'Short explanation of the desired period.',
    ],
    'survey.period.done' => [
        'text' => 'Choisi',
        'context' => 'Small status shown when a period already has an answer.',
    ],
    'survey.scale.1' => [
        'text' => 'Pas du tout',
        'context' => 'Label for scale value 1.',
    ],
    'survey.scale.2' => [
        'text' => 'Peu',
        'context' => 'Label for scale value 2.',
    ],
    'survey.scale.3' => [
        'text' => 'Moyennement',
        'context' => 'Label for scale value 3.',
    ],
    'survey.scale.4' => [
        'text' => 'Beaucoup',
        'context' => 'Label for scale value 4.',
    ],
    'survey.scale.5' => [
        'text' => 'Tout à fait',
        'context' => 'Label for scale value 5.',
    ],
    'survey.save.status' => [
        'text' => 'Vos réponses restent sur cet appareil. Le questionnaire avance automatiquement.',
        'context' => 'Persistent local storage note under the survey navigation.',
    ],
    'survey.error.incomplete' => [
        'text' => 'Répondez aux deux temporalités pour continuer.',
        'context' => 'Validation message when one period is unanswered.',
    ],
    'survey.results.eyebrow' => [
        'text' => 'Première lecture',
        'context' => 'Small label above the prototype result title.',
    ],
    'survey.results.title' => [
        'text' => 'Entre aujourd’hui et demain',
        'context' => 'Heading of the survey result summary.',
    ],
    'survey.results.intro' => [
        'text' => 'Cet aperçu ne pose aucun diagnostic. Il rend simplement visibles les écarts que vous avez exprimés et peut servir de point de départ à une discussion.',
        'context' => 'Introductory disclaimer on the survey result summary.',
    ],
    'survey.results.today_average' => [
        'text' => 'Moyenne aujourd’hui',
        'context' => 'Label for the average current scale score.',
    ],
    'survey.results.tomorrow_average' => [
        'text' => 'Moyenne demain',
        'context' => 'Label for the average desired scale score.',
    ],
    'survey.results.affinity_average' => [
        'text' => 'Affinité moyenne',
        'context' => 'Label for the average affinity score.',
    ],
    'survey.results.largest_gap' => [
        'text' => 'Plus grand écart',
        'context' => 'Label for the principle with the largest desired gap.',
    ],
    'survey.results.gap_value' => [
        'text' => '{gap} point',
        'context' => 'Gap label when the difference is one point.',
    ],
    'survey.results.gap_value_plural' => [
        'text' => '{gap} points',
        'context' => 'Gap label when the difference is several points.',
    ],
    'survey.results.same_value' => [
        'text' => 'Même positionnement',
        'context' => 'Gap label when current and desired values are equal.',
    ],
    'survey.results.current_situation' => [
        'text' => 'Aujourd’hui : {title}',
        'context' => 'Current selected situation shown in a result card.',
    ],
    'survey.results.desired_situation' => [
        'text' => 'Demain : {title}',
        'context' => 'Desired selected situation shown in a result card.',
    ],
    'survey.results.affinity_value' => [
        'text' => 'Affinité : {value}/5',
        'context' => 'Affinity score shown in a result card.',
    ],
    'survey.results.radar_title' => [
        'text' => 'Carte des 10 principes',
        'context' => 'Heading above the radar chart.',
    ],
    'survey.results.radar_help' => [
        'text' => 'Affichez ou masquez chaque zone pour comparer les trois lectures.',
        'context' => 'Help text above the radar chart controls.',
    ],
    'survey.results.radar_today' => [
        'text' => 'Aujourd’hui',
        'context' => 'Radar series label for the current situation.',
    ],
    'survey.results.radar_tomorrow' => [
        'text' => 'Demain',
        'context' => 'Radar series label for the desired situation.',
    ],
    'survey.results.radar_affinity' => [
        'text' => 'Affinité',
        'context' => 'Radar series label for affinity with the principles.',
    ],
    'survey.results.radar_risk' => [
        'text' => 'La couronne 5 est volontairement colorée en rouge pâle : elle signale le risque d’aller trop loin dans l’application d’un principe.',
        'context' => 'Explanatory note for the pale red outer risk band of the radar.',
    ],
    'survey.omo.action' => [
        'text' => 'Que peut faire OMO pour vous ?',
        'context' => 'Button opening the OMO recommendation dialog from survey results.',
    ],
    'survey.omo.eyebrow' => [
        'text' => 'Des pistes avec OMO',
        'context' => 'Eyebrow in the OMO recommendation dialog.',
    ],
    'survey.omo.title' => [
        'text' => 'Que peut faire OMO pour vous ?',
        'context' => 'Heading of the OMO recommendation dialog.',
    ],
    'survey.omo.intro' => [
        'text' => 'Voici les trois axes qui combinent le plus fort potentiel de progression : une situation actuelle basse et un élan marqué vers demain ou vers le principe lui-même.',
        'context' => 'Explanation of the three selected recommendation axes.',
    ],
    'survey.omo.axis_label' => [
        'text' => 'Axe à fort potentiel',
        'context' => 'Eyebrow above each selected principle in the OMO recommendation dialog.',
    ],
    'survey.omo.scores' => [
        'text' => 'Aujourd’hui {today}/5 · Demain {tomorrow}/5 · Affinité {affinity}/5',
        'context' => 'Score summary for a selected OMO recommendation principle.',
    ],
    'survey.omo.path' => [
        'text' => 'Parcours proposé : du niveau {start} au niveau {target}',
        'context' => 'Displayed start and target levels of a tailored OMO recommendation.',
    ],
    'survey.omo.stage' => [
        'text' => 'Étape {level}',
        'context' => 'Label for one progressive OMO recommendation stage.',
    ],
    'survey.omo.risk' => [
        'text' => 'Le niveau 5 est une zone d’exploration : OMO peut soutenir ce chemin, sans en faire une norme à appliquer partout.',
        'context' => 'Caution shown when a recommended OMO path includes level 5.',
    ],
    'survey.omo.close' => [
        'text' => 'Fermer',
        'context' => 'Close button for the OMO recommendation dialog.',
    ],
    'survey.save.links' => [
        'text' => 'Obtenir mes liens',
        'context' => 'Button opening the permanent survey links dialog.',
    ],
    'survey.save.result' => [
        'text' => 'Enregistrer le résultat',
        'context' => 'Button saving a survey result for the first time.',
    ],
    'survey.save.changes' => [
        'text' => 'Sauver les modifications',
        'context' => 'Button saving changes made through a private survey link.',
    ],
    'survey.save.saved' => [
        'text' => 'Aucune modification à sauvegarder',
        'context' => 'Disabled save button label when the private survey result is unchanged.',
    ],
    'survey.save.omo' => [
        'text' => 'Enregistrer dans OMO',
        'context' => 'Button saving the survey before linking it to an OMO organization.',
    ],
    'survey.save.eyebrow' => [
        'text' => 'Résultat enregistré',
        'context' => 'Eyebrow in the permanent survey links dialog.',
    ],
    'survey.save.title' => [
        'text' => 'Vos liens sont prêts',
        'context' => 'Heading of the permanent survey links dialog.',
    ],
    'survey.save.public_label' => [
        'text' => 'Lien public',
        'context' => 'Label for the public read-only survey link.',
    ],
    'survey.save.private_label' => [
        'text' => 'Lien privé',
        'context' => 'Label for the private editable survey link.',
    ],
    'survey.save.private_help' => [
        'text' => 'Gardez ce lien pour vous : il permet de modifier vos réponses et met à jour le lien public.',
        'context' => 'Security notice below the private editable survey link.',
    ],
    'survey.save.copy' => [
        'text' => 'Copier',
        'context' => 'Button copying a permanent survey link.',
    ],
    'survey.save.copied' => [
        'text' => 'Copié',
        'context' => 'Temporary confirmation after a permanent survey link is copied.',
    ],
    'survey.save.saving' => [
        'text' => 'Enregistrement…',
        'context' => 'Temporary button label while saving survey answers.',
    ],
    'survey.save.error' => [
        'text' => 'Impossible d’enregistrer le résultat pour le moment.',
        'context' => 'Error shown when saving permanent survey links fails.',
    ],
    'survey.save.associate' => [
        'text' => 'Associer à une organisation OMO',
        'context' => 'Button opening the OMO organization association step.',
    ],
    'survey.save.close' => [
        'text' => 'Fermer',
        'context' => 'Close button for the permanent survey links dialog.',
    ],
    'survey.public.page_title' => [
        'text' => 'Situation actuelle de l’organisation',
        'context' => 'Browser title of a public organizational maturity result.',
    ],
    'survey.public.eyebrow' => [
        'text' => 'Vue publique',
        'context' => 'Eyebrow on a public organizational maturity result.',
    ],
    'survey.public.title' => [
        'text' => 'Où en est cette organisation ?',
        'context' => 'Heading on a public organizational maturity result.',
    ],
    'survey.public.intro' => [
        'text' => 'Cette vue met en regard la perception actuelle et la situation souhaitée, principe par principe.',
        'context' => 'Intro on a public organizational maturity result.',
    ],
    'survey.public.radar_title' => [
        'text' => 'Radar de la situation actuelle et souhaitée',
        'context' => 'Heading above the public current and desired situation radar.',
    ],
    'survey.public.comparison_label' => [
        'text' => 'Aujourd’hui et demain',
        'context' => 'Eyebrow above the public radar comparing current and desired situations.',
    ],
    'survey.public.radar_help' => [
        'text' => 'Les dix axes correspondent aux principes du questionnaire. Aujourd’hui montre la perception actuelle ; Demain rend visible l’ambition. La couronne 5 signale une zone de vigilance.',
        'context' => 'Help text for the public current and desired situation radar.',
    ],
    'survey.public.current_label' => [
        'text' => 'Situation actuelle',
        'context' => 'Label shown above each current situation in a public result.',
    ],
    'survey.public.desired_label' => [
        'text' => 'Situation souhaitée',
        'context' => 'Label shown above each desired situation in a public result.',
    ],
    'survey.public.not_found' => [
        'text' => 'Résultat introuvable.',
        'context' => 'Error shown when a public survey result link is invalid.',
    ],
    'survey.public.incomplete' => [
        'text' => 'Résultat incomplet.',
        'context' => 'Error shown when a public survey result has missing responses.',
    ],
    'survey.dialog.restart' => [
        'text' => 'Effacer les réponses enregistrées sur cet appareil et recommencer ?',
        'context' => 'Confirmation shown before clearing all local survey answers.',
    ],
];

$surveyQuestionDefinitions = [
    [
        'number' => 1,
        'title' => 'L’autorité assumée plutôt que l’autorité statutaire',
        'principle' => 'L’autorité ne repose pas seulement sur un titre ou une position hiérarchique. Elle se manifeste dans la capacité d’une personne à assumer une responsabilité, à décider dans son périmètre et à contribuer avec discernement au service du collectif.',
        'options' => [
            ['title' => 'L’autorité repose principalement sur la hiérarchie', 'description' => 'Les décisions importantes sont prises par les responsables hiérarchiques. Les collaborateurs exécutent principalement les orientations définies par leur manager et disposent de peu de marge pour agir ou décider sans validation préalable.'],
            ['title' => 'L’initiative est encouragée, mais reste soumise à validation', 'description' => 'Les collaborateurs peuvent faire des propositions et prendre certaines initiatives, mais les décisions significatives remontent généralement dans la hiérarchie. Dans les faits, le statut reste déterminant pour savoir qui peut trancher.'],
            ['title' => 'L’autorité commence à être liée aux responsabilités', 'description' => 'Certains rôles disposent d’une autonomie claire et peuvent prendre des décisions sans validation hiérarchique systématique. Cependant, cette autonomie dépend encore beaucoup des managers, des équipes ou des personnes concernées, et les règles ne sont pas toujours explicites.'],
            ['title' => 'L’autorité est clairement distribuée et assumée', 'description' => 'Les rôles, responsabilités et domaines de décision sont explicites. Les personnes disposent des informations nécessaires pour décider dans leur périmètre et sont encouragées à exercer pleinement leur autorité, quel que soit leur statut. Elles assument également les conséquences et les impacts de leurs décisions.'],
            ['title' => 'L’autorité est entièrement libérée des rôles formels', 'description' => 'Chacun est considéré comme pleinement légitime pour prendre des initiatives, intervenir dans les décisions et exercer son discernement, indépendamment de son rôle ou de sa position. Les frontières entre domaines de responsabilité sont volontairement souples afin de préserver au maximum l’autonomie et l’égalité entre les personnes.'],
        ],
    ],
    [
        'number' => 2,
        'title' => 'L’intelligence organisée du collectif plutôt que la décision isolée',
        'principle' => 'La qualité des décisions peut être renforcée lorsqu’elles mobilisent les personnes concernées, leurs expériences et leurs compétences. L’enjeu est d’organiser cette contribution de manière claire, afin d’enrichir le discernement sans diluer la responsabilité ni ralentir inutilement l’action.',
        'options' => [
            ['title' => 'Les décisions reposent principalement sur quelques personnes', 'description' => 'Les décisions importantes sont prises par les responsables ou les experts désignés. Les personnes concernées sont généralement informées une fois la décision prise, mais participent peu à sa construction.'],
            ['title' => 'Le collectif est consulté ponctuellement', 'description' => 'Les responsables sollicitent parfois l’avis des équipes ou de certaines personnes concernées avant de décider. Cette consultation dépend toutefois beaucoup des personnes en charge et ne suit pas de cadre ou de processus clairement établi.'],
            ['title' => 'La contribution collective est intégrée dans certaines décisions', 'description' => 'Pour les sujets importants, les personnes concernées ou disposant d’une expertise utile sont généralement associées à la réflexion. Les pratiques de consultation existent, mais leur qualité et leur usage restent variables selon les équipes ou les situations.'],
            ['title' => 'L’intelligence collective est organisée au service de la décision', 'description' => 'L’organisation sait identifier qui doit contribuer à une décision, à quel moment et de quelle manière. Les informations utiles sont accessibles, les espaces de contribution sont explicites et la responsabilité finale de décider reste claire. Le collectif enrichit ainsi les décisions sans rendre systématiquement la décision collective.'],
            ['title' => 'Toute décision importante cherche à inclure le plus grand nombre', 'description' => 'L’organisation privilégie une participation très large aux décisions afin que chacun puisse contribuer et qu’aucune perspective ne soit laissée de côté. Les décisions importantes font l’objet de nombreux échanges et cherchent autant que possible à intégrer l’ensemble des points de vue avant d’être arrêtées.'],
        ],
    ],
    [
        'number' => 3,
        'title' => 'S’orienter par la raison d’être plutôt que par les intérêts particuliers',
        'principle' => 'La raison d’être fournit un repère commun pour orienter les choix et les arbitrages. Elle permet de mettre en perspective les intérêts, préférences et urgences du moment, afin de relier les décisions à ce que l’organisation cherche réellement à servir.',
        'options' => [
            ['title' => 'Les décisions sont principalement guidées par les intérêts et contraintes du moment', 'description' => 'Les choix sont surtout influencés par les priorités des dirigeants, les rapports de force internes, les objectifs financiers ou les urgences opérationnelles. La raison d’être joue peu de rôle dans les arbitrages quotidiens.'],
            ['title' => 'La raison d’être existe, mais influence peu les décisions', 'description' => 'L’organisation dispose d’une mission, d’une vision ou d’une raison d’être clairement formulée. Elle est connue et valorisée, mais elle reste surtout un élément de communication ou d’identité et intervient rarement comme critère concret de décision.'],
            ['title' => 'La raison d’être est utilisée dans certains arbitrages', 'description' => 'Sur les décisions importantes ou stratégiques, l’organisation cherche à vérifier la cohérence avec sa raison d’être. Cette pratique existe, mais elle dépend encore beaucoup des personnes ou des équipes et n’est pas systématiquement mobilisée dans les choix du quotidien.'],
            ['title' => 'La raison d’être sert de repère vivant pour décider', 'description' => 'La raison d’être est régulièrement mobilisée pour éclairer les choix, notamment lorsque plusieurs intérêts ou priorités s’opposent. Les intérêts particuliers sont entendus et pris en compte, mais ils sont mis en perspective avec ce que l’organisation cherche collectivement à servir. La raison d’être peut elle-même être revisitée lorsque le contexte évolue.'],
            ['title' => 'La raison d’être prime sur les intérêts particuliers', 'description' => 'Les décisions sont évaluées avant tout à partir de leur alignement avec la raison d’être. Les préférences individuelles, les contraintes locales ou les intérêts particuliers sont volontairement relégués au second plan afin de préserver une orientation collective forte et cohérente.'],
        ],
    ],
    [
        'number' => 4,
        'title' => 'Le pouvoir qui circule plutôt que le pouvoir qui se concentre',
        'principle' => 'Le pouvoir peut être distribué là où il est utile, en fonction des rôles, des responsabilités et de la proximité avec le réel. L’enjeu est de permettre aux personnes concernées d’agir et de décider dans un cadre clair, plutôt que de faire remonter systématiquement l’autorité vers quelques positions centrales.',
        'options' => [
            ['title' => 'Le pouvoir est concentré dans quelques fonctions ou niveaux hiérarchiques', 'description' => 'Les décisions importantes remontent vers un nombre limité de responsables. Même sur des sujets proches du terrain, les équipes disposent de peu de latitude sans validation d’un niveau supérieur.'],
            ['title' => 'Une partie du pouvoir est déléguée', 'description' => 'Certaines décisions peuvent être prises localement, mais les domaines de délégation restent limités ou dépendent fortement de la confiance accordée par les responsables. Les décisions sensibles ou inhabituelles ont tendance à remonter dans la hiérarchie.'],
            ['title' => 'Le pouvoir est distribué dans plusieurs domaines', 'description' => 'De nombreux rôles disposent d’une autorité réelle sur leur périmètre et peuvent décider sans validation systématique. La distribution du pouvoir progresse, mais certains domaines restent flous ou continuent à dépendre de personnes centrales.'],
            ['title' => 'Le pouvoir circule à travers des rôles et des responsabilités explicites', 'description' => 'Les domaines d’autorité sont clairement définis et les décisions sont prises au plus près des personnes qui disposent des informations et des responsabilités pertinentes. Le pouvoir peut évoluer lorsque les besoins changent, et des mécanismes permettent de rendre les décisions visibles, de les questionner et d’ajuster les responsabilités.'],
            ['title' => 'Le pouvoir est rendu aussi fluide et horizontal que possible', 'description' => 'L’organisation cherche à limiter les domaines d’autorité fixes afin que le pouvoir puisse circuler librement selon les situations. Les personnes sont encouragées à intervenir, décider ou prendre le relais dès qu’elles estiment pouvoir contribuer utilement, sans dépendre fortement de frontières de rôles établies.'],
        ],
    ],
    [
        'number' => 5,
        'title' => 'La confiance et la transparence plutôt que le contrôle',
        'principle' => 'La confiance permet aux personnes d’agir avec davantage d’autonomie lorsque les informations, les responsabilités et les règles du jeu sont suffisamment claires. La transparence rend possible cette autonomie en donnant accès au contexte nécessaire pour comprendre, décider et ajuster.',
        'options' => [
            ['title' => 'Le fonctionnement repose principalement sur le contrôle', 'description' => 'Les décisions, les activités et les résultats font l’objet de validations et de suivis fréquents. L’organisation cherche avant tout à réduire les risques en encadrant étroitement les actions et en limitant les marges de manœuvre individuelles.'],
            ['title' => 'La confiance existe, mais reste conditionnelle', 'description' => 'Les collaborateurs disposent d’une certaine autonomie tant que les résultats sont conformes aux attentes. Dès qu’un sujet devient sensible, incertain ou important, le contrôle et les validations supplémentaires reprennent généralement le dessus.'],
            ['title' => 'La confiance et la transparence progressent', 'description' => 'De nombreuses informations sont partagées et certaines équipes disposent d’une réelle latitude d’action. Toutefois, le niveau de transparence, d’autonomie et de contrôle reste variable selon les sujets, les managers ou les personnes concernées.'],
            ['title' => 'La confiance repose sur un cadre transparent et explicite', 'description' => 'Les personnes disposent des informations nécessaires pour comprendre le contexte et agir dans leur périmètre. Les responsabilités, les règles et les attentes sont claires, et des espaces de feedback permettent d’ajuster ce qui ne fonctionne pas. Le contrôle systématique laisse place à la responsabilité et à la régulation.'],
            ['title' => 'La confiance est privilégiée presque sans restriction', 'description' => 'L’organisation cherche à réduire au maximum les contrôles, validations et règles afin de préserver l’autonomie individuelle. Une grande partie de l’information est rendue accessible à tous, et chacun est encouragé à agir selon son jugement avec un minimum de contraintes formelles.'],
        ],
    ],
    [
        'number' => 6,
        'title' => 'Des structures qui apprennent plutôt qu’un modèle figé',
        'principle' => 'Les rôles, règles, processus et modes de décision peuvent évoluer lorsque l’expérience montre qu’ils ne répondent plus suffisamment au réel. L’enjeu est de concevoir des structures capables d’apprendre, de s’ajuster et de rester utiles au fil du temps.',
        'options' => [
            ['title' => 'Les structures sont conçues pour rester stables', 'description' => 'Les rôles, règles et processus sont définis de manière relativement fixe. Lorsqu’un fonctionnement pose problème, l’organisation cherche d’abord à mieux faire respecter le cadre existant plutôt qu’à le remettre en question.'],
            ['title' => 'Les structures évoluent surtout lors de changements importants', 'description' => 'L’organisation ajuste ses structures lorsqu’un problème majeur apparaît, lors d’une réorganisation ou à l’occasion d’un changement stratégique. Entre ces périodes, les rôles et processus restent généralement inchangés.'],
            ['title' => 'Certains ajustements sont réalisés à partir de l’expérience', 'description' => 'Les équipes peuvent proposer des modifications de rôles, de règles ou de processus lorsque des difficultés apparaissent. Ces ajustements existent, mais ils restent ponctuels et dépendent encore beaucoup des personnes ou des situations.'],
            ['title' => 'Les structures sont régulièrement ajustées à partir du réel', 'description' => 'Les rôles, règles et processus sont considérés comme évolutifs. L’organisation observe ce qui fonctionne, teste des changements, en évalue les effets et ajuste progressivement ses structures. Cette évolution se fait dans un cadre suffisamment stable pour préserver la cohérence et la continuité de l’action.'],
            ['title' => 'Les structures sont volontairement maintenues en évolution permanente', 'description' => 'L’organisation cherche à conserver un maximum de souplesse en remettant régulièrement en question les rôles, règles et processus. Les équipes sont encouragées à modifier rapidement leur fonctionnement dès qu’une nouvelle possibilité ou un besoin apparaît, afin d’éviter que les structures ne deviennent rigides.'],
        ],
    ],
    [
        'number' => 7,
        'title' => 'Différencier les rôles plutôt que hiérarchiser les personnes',
        'principle' => 'Les rôles peuvent porter des responsabilités, des pouvoirs de décision et des niveaux d’impact différents sans que cela implique une différence de valeur entre les personnes. L’enjeu est de distinguer clairement l’autorité liée à une fonction de la considération accordée à chacun.',
        'options' => [
            ['title' => 'La position hiérarchique détermine fortement la place de chacun', 'description' => 'Le titre, le niveau de responsabilité ou l’ancienneté influencent fortement qui est écouté, qui décide et qui peut remettre en question une orientation. Les personnes occupant des fonctions élevées disposent généralement d’une légitimité plus grande dans les échanges.'],
            ['title' => 'Les différences de statut restent importantes, même si la parole est encouragée', 'description' => 'L’organisation valorise l’écoute et le respect de chacun, mais les positions hiérarchiques continuent à peser fortement dans les interactions. Certaines voix sont spontanément considérées comme plus légitimes que d’autres.'],
            ['title' => 'Les rôles et les personnes commencent à être davantage distingués', 'description' => 'Les responsabilités sont de plus en plus associées à des fonctions précises plutôt qu’au statut personnel. Chacun peut contribuer aux échanges, même si les habitudes hiérarchiques restent encore présentes dans certaines équipes ou situations.'],
            ['title' => 'Les responsabilités sont différenciées sans hiérarchiser la valeur des personnes', 'description' => 'Les rôles, leurs responsabilités et leurs domaines d’autorité sont clairement définis. Certaines fonctions disposent d’un pouvoir de décision particulier, mais chacun est considéré comme une voix légitime et digne d’attention. L’autorité peut être questionnée sans remettre en cause la personne qui l’exerce.'],
            ['title' => 'Les différences entre rôles sont volontairement minimisées', 'description' => 'L’organisation cherche à préserver une égalité maximale entre les personnes en limitant les distinctions de statut, d’autorité ou de responsabilité. Les décisions et les échanges privilégient une relation aussi horizontale que possible, indépendamment des fonctions occupées.'],
        ],
    ],
    [
        'number' => 8,
        'title' => 'Les tensions comme signal plutôt que comme menace',
        'principle' => 'Une tension peut révéler un écart entre la réalité vécue et le fonctionnement actuel de l’organisation. Lorsqu’elle peut être exprimée et examinée, elle devient une source d’information utile pour ajuster les rôles, les décisions, les relations ou les processus.',
        'options' => [
            ['title' => 'Les tensions sont principalement évitées ou contenues', 'description' => 'Les désaccords, frustrations ou difficultés sont rarement exprimés ouvertement. L’organisation privilégie la stabilité et cherche généralement à résoudre les problèmes sans exposer les tensions au collectif.'],
            ['title' => 'Les tensions sont traitées lorsqu’elles deviennent difficiles à ignorer', 'description' => 'Les problèmes sont abordés lorsqu’ils commencent à affecter clairement le travail ou les relations. Leur traitement intervient souvent tardivement, lorsque la frustration ou le conflit est déjà installé.'],
            ['title' => 'Les tensions peuvent être exprimées et donner lieu à des ajustements', 'description' => 'Les personnes disposent de certains espaces pour signaler ce qui ne fonctionne pas. Des ajustements peuvent en découler, mais les pratiques restent variables selon les équipes, les managers ou la nature des sujets.'],
            ['title' => 'Les tensions sont utilisées comme une source d’apprentissage', 'description' => 'L’organisation encourage l’expression suffisamment précoce des tensions et dispose d’espaces pour les examiner. Elles sont abordées comme des informations sur le fonctionnement du système et peuvent conduire à clarifier un rôle, ajuster une décision, modifier un processus ou traiter une difficulté relationnelle.'],
            ['title' => 'Toute tension mérite d’être pleinement explorée', 'description' => 'L’organisation considère chaque inconfort, désaccord ou frustration comme un signal potentiellement important. Les personnes sont encouragées à les exprimer et à leur consacrer le temps nécessaire afin de comprendre ce qu’elles révèlent avant de poursuivre l’action.'],
        ],
    ],
    [
        'number' => 9,
        'title' => 'La présence consciente plutôt que la réaction automatique',
        'principle' => 'La qualité d’une décision dépend aussi de la manière dont les personnes sont présentes à la situation. Prendre le temps d’observer les faits, les émotions, les tensions et les automatismes permet de répondre avec davantage de discernement plutôt que de réagir sous l’effet de l’urgence ou de la pression.',
        'options' => [
            ['title' => 'Les décisions sont principalement guidées par l’urgence et les habitudes', 'description' => 'Lorsque la pression augmente, l’organisation agit rapidement à partir des réflexes existants, des habitudes ou de l’autorité des personnes en place. Il y a peu d’espace pour prendre du recul sur ce qui se joue réellement.'],
            ['title' => 'Des temps de recul existent, mais restent exceptionnels', 'description' => 'L’organisation reconnaît l’importance de prendre du recul, mais cela se produit surtout lors de situations importantes, de crises ou de séminaires. Dans le quotidien, l’urgence reprend généralement le dessus.'],
            ['title' => 'Certaines pratiques favorisent davantage de discernement', 'description' => 'Des temps de réflexion, de feedback ou de clarification sont utilisés dans certaines équipes ou pour certaines décisions. Ces pratiques existent, mais restent dépendantes des personnes ou du contexte.'],
            ['title' => 'La présence et le discernement font partie du fonctionnement courant', 'description' => 'L’organisation crée régulièrement des espaces pour ralentir juste assez, clarifier les faits, les intentions, les tensions ou les réactions en jeu. Ces pratiques soutiennent la qualité des échanges et des décisions sans empêcher l’action.'],
            ['title' => 'Toute décision importante mérite un temps de présence approfondi', 'description' => 'L’organisation cherche à éviter au maximum les décisions prises dans la précipitation. Les personnes sont encouragées à prendre le temps d’explorer leur ressenti, leurs intentions, les dynamiques relationnelles et les tensions présentes avant de s’engager dans une décision.'],
        ],
    ],
    [
        'number' => 10,
        'title' => 'Prendre soin de l’écosystème plutôt que rechercher l’efficacité isolée',
        'principle' => 'Une organisation agit toujours au sein d’un écosystème de relations, de partenaires, de ressources et de milieux dont elle dépend. Prendre soin de cet écosystème consiste à considérer les effets de ses décisions au-delà de sa seule efficacité interne.',
        'options' => [
            ['title' => 'Les décisions sont principalement évaluées à partir de leur efficacité interne', 'description' => 'L’organisation privilégie avant tout ses propres objectifs, ses coûts, ses délais et ses résultats. Les impacts sur les partenaires, les territoires ou l’environnement sont pris en compte surtout lorsqu’ils affectent directement l’activité.'],
            ['title' => 'Les impacts externes sont pris en compte lorsqu’ils deviennent visibles', 'description' => 'L’organisation cherche à limiter certains effets négatifs et porte attention à ses principales parties prenantes. Cependant, ces considérations restent souvent secondaires face aux impératifs économiques ou opérationnels.'],
            ['title' => 'L’écosystème est intégré dans certaines décisions importantes', 'description' => 'Les impacts sur les clients, partenaires, fournisseurs, collaborateurs ou l’environnement sont régulièrement examinés, notamment pour les choix stratégiques. Cette prise en compte reste toutefois variable selon les sujets ou les équipes.'],
            ['title' => 'Les décisions intègrent les interdépendances avec l’écosystème', 'description' => 'L’organisation cherche à comprendre comment ses choix affectent les différentes parties prenantes et les ressources dont elle dépend. Elle met en dialogue ses propres besoins avec la qualité des relations, les impacts environnementaux et la santé de son écosystème afin de construire une performance durable.'],
            ['title' => 'Chaque décision cherche à préserver l’ensemble de l’écosystème', 'description' => 'L’organisation considère qu’une décision n’est pleinement satisfaisante que si elle produit un effet positif ou au minimum neutre pour l’ensemble des parties prenantes et des milieux concernés. Elle cherche à intégrer aussi largement que possible les impacts sociaux, économiques et environnementaux avant d’agir.'],
        ],
    ],
];

$surveyOmoPathDefinitions = [
    1 => [
        'stages' => [
            ['title' => 'Rendre la structure lisible', 'description' => 'Cartographiez l’organisation en cercles et rôles pour voir où les décisions remontent encore vers quelques positions centrales.'],
            ['title' => 'Nommer les responsabilités', 'description' => 'Associez les personnes aux rôles et rendez les attentes de chacun visibles dans Team et dans la structure.'],
            ['title' => 'Définir les domaines d’autorité', 'description' => 'Utilisez les autorités, propriétés et règles de holon pour expliciter ce qui peut être décidé dans chaque périmètre.'],
            ['title' => 'Faire agir au bon endroit', 'description' => 'Ajustez les droits et les périmètres afin que les rôles disposent réellement de la latitude correspondant à leur responsabilité.'],
            ['title' => 'Garder l’autorité vivante', 'description' => 'Révisez régulièrement la structure et les règles lorsque le terrain change, sans effacer la clarté des rôles.'],
        ],
    ],
    2 => [
        'stages' => [
            ['title' => 'Créer un contexte partagé', 'description' => 'Rassemblez les personnes, documents et informations utiles dans le bon cercle avant de demander un avis ou une décision.'],
            ['title' => 'Ouvrir des consultations simples', 'description' => 'Utilisez les consultations et les propositions pour recueillir des points de vue sans confondre écoute et décision.'],
            ['title' => 'Organiser la contribution', 'description' => 'Invitez les personnes concernées, reliez les échanges aux propositions et gardez les discussions au bon niveau de contexte.'],
            ['title' => 'Choisir une méthode de décision', 'description' => 'Configurez un vote simple, un jugement majoritaire ou un consentement selon le sujet, les participants et le degré d’engagement attendu.'],
            ['title' => 'Préserver le discernement', 'description' => 'Gardez une participation proportionnée grâce aux périmètres, aux droits et aux consultations ciblées plutôt que de solliciter tout le monde par défaut.'],
        ],
    ],
    3 => [
        'stages' => [
            ['title' => 'Rendre le cap visible', 'description' => 'Inscrivez la raison d’être et les attendus dans les organisations, cercles et modèles de structure concernés.'],
            ['title' => 'Relier les travaux au cap', 'description' => 'Rattachez documents, projets et décisions au bon contexte afin que leur intention reste lisible.'],
            ['title' => 'Préparer les arbitrages', 'description' => 'Utilisez les espaces de décision et les documents partagés pour exposer les options, les contraintes et le sens recherché.'],
            ['title' => 'Piloter avec des repères communs', 'description' => 'Croisez les projets et les indicateurs dans le tableau de pilotage pour suivre ce qui sert réellement les attendus du collectif.'],
            ['title' => 'Réinterroger sans se disperser', 'description' => 'Faites évoluer les attendus et la structure quand le contexte le justifie, en conservant une trace explicite des choix effectués.'],
        ],
    ],
    4 => [
        'stages' => [
            ['title' => 'Voir où le pouvoir se concentre', 'description' => 'La carte des cercles et rôles aide à repérer les passages obligés, les responsabilités absentes et les zones trop dépendantes d’une personne.'],
            ['title' => 'Poser des délégations concrètes', 'description' => 'Associez des rôles, des personnes et des périmètres de travail pour que les décisions locales puissent être assumées.'],
            ['title' => 'Outiller les domaines d’action', 'description' => 'Définissez autorités, règles et droits contextuels afin que le pouvoir soit distribué là où les informations sont présentes.'],
            ['title' => 'Rendre les décisions traçables', 'description' => 'Reliez les décisions, projets et documents à leurs cercles afin que chacun puisse comprendre qui agit, sur quoi et pourquoi.'],
            ['title' => 'Ajuster les circulations', 'description' => 'Faites évoluer les périmètres et les règles lorsque les besoins changent, sans transformer la souplesse en flou permanent.'],
        ],
    ],
    5 => [
        'stages' => [
            ['title' => 'Partager un socle fiable', 'description' => 'Donnez à chacun accès à la structure, aux membres et aux documents correspondant à son contexte de travail.'],
            ['title' => 'Clarifier les accès', 'description' => 'Utilisez les droits par organisation, cercle, rôle et document pour rendre les règles du jeu compréhensibles plutôt qu’implicites.'],
            ['title' => 'Rendre le travail visible', 'description' => 'Centralisez documents, projets, décisions, checklists et calendrier dans leurs contextes respectifs afin de réduire les informations cachées.'],
            ['title' => 'Soutenir la régulation', 'description' => 'Le tableau de pilotage, les indicateurs et les échéances permettent de partager les faits et d’ajuster avant de multiplier les validations.'],
            ['title' => 'Garder un cadre protecteur', 'description' => 'Conservez des droits, périmètres et traces adaptés aux sujets sensibles : la confiance gagne à être soutenue par des règles explicites.'],
        ],
    ],
    6 => [
        'stages' => [
            ['title' => 'Rendre les structures observables', 'description' => 'Utilisez la carte de l’organisation pour rendre visibles les cercles, rôles, responsabilités et liens qui composent le fonctionnement actuel.'],
            ['title' => 'Préparer des ajustements localisés', 'description' => 'Modifiez les propriétés, rôles ou règles du bon holon plutôt que de reconstruire toute l’organisation à chaque difficulté.'],
            ['title' => 'Tester avec des modèles réutilisables', 'description' => 'Les modèles de holon permettent de faire évoluer une forme de gouvernance tout en distinguant les valeurs héritées des adaptations locales.'],
            ['title' => 'Observer les effets', 'description' => 'Suivez projets, processus, décisions et indicateurs dans le tableau de pilotage pour voir ce que les changements produisent réellement.'],
            ['title' => 'Évoluer sans agitation', 'description' => 'Gardez des repères stables dans les modèles, les droits et les attendus tout en laissant les règles et responsabilités apprendre du terrain.'],
        ],
    ],
    7 => [
        'stages' => [
            ['title' => 'Rendre les rôles visibles', 'description' => 'La structure OMO distingue organisation, cercles, groupes et rôles afin que les responsabilités ne restent pas confondues avec les personnes.'],
            ['title' => 'Associer sans figer', 'description' => 'Dans Team, reliez les personnes à leurs rôles et rendez visibles leur focus, leurs budgets et leurs affectations.'],
            ['title' => 'Clarifier les responsabilités propres aux rôles', 'description' => 'Définissez les attendus, autorités et règles dans le bon contexte pour que l’autorité porte sur une fonction, pas sur la valeur d’une personne.'],
            ['title' => 'Faciliter les passages et les ajustements', 'description' => 'Les affectations, groupes et périmètres permettent de faire évoluer une contribution sans devoir redessiner toute la relation hiérarchique.'],
            ['title' => 'Préserver l’égalité de considération', 'description' => 'Gardez les rôles et leurs pouvoirs explicites, afin qu’ils puissent être questionnés et ajustés sans viser les personnes qui les exercent.'],
        ],
    ],
    8 => [
        'stages' => [
            ['title' => 'Donner une place aux écarts', 'description' => 'Utilisez les documents, comptes rendus et discussions de propositions pour rendre les difficultés concrètes et partageables.'],
            ['title' => 'Transformer un problème en sujet commun', 'description' => 'Ouvrez une consultation ou une discussion liée à une décision afin que les personnes concernées puissent formuler ce qui coince.'],
            ['title' => 'Relier les échanges à une action', 'description' => 'Convertissez les points clarifiés en décisions, projets, checklists ou ajustements de rôles, dans le contexte où ils se posent.'],
            ['title' => 'Suivre les ajustements', 'description' => 'Les projets, indicateurs et comptes rendus aident à vérifier si la réponse apportée résout réellement la tension observée.'],
            ['title' => 'Éviter de tout dramatiser', 'description' => 'Réservez les espaces de discussion et de décision aux sujets qui appellent une élaboration, sans imposer un traitement lourd à chaque inconfort.'],
        ],
    ],
    9 => [
        'stages' => [
            ['title' => 'Créer des points d’arrêt utiles', 'description' => 'Le calendrier, les ordres du jour et les documents de préparation donnent un cadre simple pour ne pas décider uniquement dans l’urgence.'],
            ['title' => 'Préparer les informations pertinentes', 'description' => 'Rassemblez les documents, projets et indicateurs liés au contexte avant une réunion ou une décision importante.'],
            ['title' => 'Consigner ce qui a été vu', 'description' => 'Les PV, discussions et décisions conservent les faits, les options et les engagements afin de limiter les réactions fondées sur des souvenirs partiels.'],
            ['title' => 'Réguler le rythme de travail', 'description' => 'Suivez les échéances, projets et checklists pour rendre les urgences visibles et choisir collectivement ce qui mérite une attention immédiate.'],
            ['title' => 'Garder la présence au service de l’action', 'description' => 'Utilisez ces repères pour ralentir juste assez et éclairer les choix, sans transformer chaque décision en processus excessivement long.'],
        ],
    ],
    10 => [
        'stages' => [
            ['title' => 'Voir les interdépendances proches', 'description' => 'Situez les projets, documents, rôles et cercles dans leur contexte pour rendre visibles les personnes et ressources déjà impliquées.'],
            ['title' => 'Partager les informations pertinentes', 'description' => 'Les documents, tableaux et calendriers collaboratifs permettent de travailler avec les bonnes personnes sans disperser les versions d’une même information.'],
            ['title' => 'Associer les parties concernées', 'description' => 'Les consultations et les décisions peuvent inviter les personnes concernées dans un cadre et un périmètre explicites.'],
            ['title' => 'Piloter les impacts dans la durée', 'description' => 'Reliez projets, indicateurs et documents au tableau de pilotage pour observer les effets au-delà d’un seul résultat interne.'],
            ['title' => 'Arbitrer avec discernement', 'description' => 'OMO aide à rendre les contextes et les impacts discutables ; gardez toutefois la décision située, plutôt que de rechercher une neutralité impossible pour chaque partie prenante.'],
        ],
    ],
];

foreach ($surveyQuestionDefinitions as $questionDefinition) {
    $questionNumber = (int)$questionDefinition['number'];
    $questionPrefix = 'survey.question.' . $questionNumber;
    $sourceLang[$questionPrefix . '.title'] = [
        'text' => (string)$questionDefinition['title'],
        'context' => 'Title of organizational maturity principle ' . $questionNumber . '.',
    ];
    $sourceLang[$questionPrefix . '.principle'] = [
        'text' => (string)$questionDefinition['principle'],
        'context' => 'Description of organizational maturity principle ' . $questionNumber . '.',
    ];

    foreach ($questionDefinition['options'] as $optionIndex => $optionDefinition) {
        $optionNumber = $optionIndex + 1;
        $optionPrefix = $questionPrefix . '.option.' . $optionNumber;
        $sourceLang[$optionPrefix . '.title'] = [
            'text' => (string)$optionDefinition['title'],
            'context' => 'Title of situation ' . $optionNumber . ' for principle ' . $questionNumber . '.',
        ];
        $sourceLang[$optionPrefix . '.description'] = [
            'text' => (string)$optionDefinition['description'],
            'context' => 'Description of situation ' . $optionNumber . ' for principle ' . $questionNumber . '.',
        ];
    }
}

foreach ($surveyOmoPathDefinitions as $questionNumber => $pathDefinition) {
    foreach ($pathDefinition['stages'] as $stageIndex => $stageDefinition) {
        $stageNumber = $stageIndex + 1;
        $stagePrefix = 'survey.omo.path.' . (int)$questionNumber . '.stage.' . $stageNumber;
        $sourceLang[$stagePrefix . '.title'] = [
            'text' => (string)$stageDefinition['title'],
            'context' => 'OMO recommendation stage ' . $stageNumber . ' for principle ' . (int)$questionNumber . '.',
        ];
        $sourceLang[$stagePrefix . '.description'] = [
            'text' => (string)$stageDefinition['description'],
            'context' => 'OMO recommendation description for stage ' . $stageNumber . ' and principle ' . (int)$questionNumber . '.',
        ];
    }
}
