<?php
require_once __DIR__ . '/../shared_functions.php';
require_once __DIR__ . '/legal_page_helper.php';
require_once __DIR__ . '/ai_disclosure.php';

$siteTitle = 'OpenMyOrganization';
$locale = commonLegalResolveLocale();
$policyDate = (new DateTimeImmutable('today'))->format('d.m.Y');
$privacyContactEmail = trim((string)($GLOBALS['siteAdminEmail'] ?? ''));
if ($privacyContactEmail === '' && function_exists('envValue')) {
    $privacyContactEmail = trim((string)envValue('INSTALL_ADMIN_EMAIL', ''));
}
if ($privacyContactEmail === '' && function_exists('envValue')) {
    $privacyContactEmail = trim((string)envValue('MAIL_USER', ''));
}
$privacyContact = filter_var($privacyContactEmail, FILTER_VALIDATE_EMAIL)
    ? '<a href="mailto:' . htmlspecialchars($privacyContactEmail, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($privacyContactEmail, ENT_QUOTES, 'UTF-8') . '</a>'
    : 'l’adresse de contact présentée dans le menu Aide';

$sourceLang = array_merge(commonGetLegalSharedSourceLang(), [
    'legal.privacy.page_title' => [
        'text' => 'Politique de confidentialité - {siteTitle}',
        'context' => 'Browser page title for the privacy policy page.',
    ],
    'legal.privacy.document_title' => [
        'text' => 'Politique de confidentialité',
        'context' => 'Main heading on the privacy policy page.',
    ],
    'legal.privacy.badge' => [
        'text' => 'Version du {date}',
        'context' => 'Current version date displayed on the privacy policy page.',
    ],
    'legal.privacy.intro.1' => [
        'text' => 'Cette politique explique de manière claire quelles données personnelles peuvent être traitées dans le cadre d’OpenMyOrganization et de ses logiciels dérivés (EasyPV, EasyCIRCLE, EasyMEMO et toute la gamme Easy), pourquoi elles le sont, combien de temps elles sont conservées et quels sont vos droits.',
        'context' => 'First introductory paragraph on the privacy policy page.',
    ],
    'legal.privacy.intro.2' => [
        'text' => 'OpenMyOrganization et ses logiciels dérivés forment un commun numérique à but non lucratif, organisé selon une gouvernance multi-organisations. Le projet ne repose pas sur la vente de données, la publicité ciblée ou la constitution de profils commerciaux.',
        'context' => 'Second introductory paragraph describing the non-profit and common-good nature of the project.',
    ],
    'legal.privacy.section.1.title' => [
        'text' => '1. Responsable du traitement et périmètre',
        'context' => 'Section title identifying the data controller and policy scope.',
    ],
    'legal.privacy.section.1.body.1' => [
        'text' => 'Le responsable du traitement est l’entité ou l’organisation qui exploite l’instance concernée d’OpenMyOrganization ou de l’un de ses logiciels dérivés. Pour les demandes relatives à la confidentialité, vous pouvez contacter {contact}.',
        'context' => 'First paragraph identifying the data controller and privacy contact.',
    ],
    'legal.privacy.section.1.body.2' => [
        'text' => 'Cette politique concerne l’instance hébergée et administrée par le projet OpenMyOrganization. Lorsqu’une organisation installe elle-même OpenMyOrganization ou l’un de ses logiciels dérivés, elle devient responsable de sa propre instance, de ses utilisateurs, de son hébergement, de ses sauvegardes et du respect des obligations qui lui sont applicables.',
        'context' => 'Second paragraph defining the scope of the hosted service and self-hosted installations.',
    ],
    'legal.privacy.section.2.title' => [
        'text' => '2. Principes du projet',
        'context' => 'Section title describing the privacy principles of the project.',
    ],
    'legal.privacy.section.2.body.1' => [
        'text' => 'Les données sont traitées conformément aux principes de licéité, de bonne foi, de proportionnalité et de finalité. Nous ne collectons et ne conservons que les informations utiles au fonctionnement d’OpenMyOrganization et de ses logiciels dérivés, à la coopération entre les membres et à la sécurité de l’instance.',
        'context' => 'First paragraph describing the core data protection principles.',
    ],
    'legal.privacy.section.2.body.2' => [
        'text' => 'Les données ne sont ni vendues, ni louées, ni utilisées à des fins publicitaires. Elles ne servent pas à établir des profils commerciaux et ne sont pas réutilisées pour une finalité étrangère au fonctionnement d’OpenMyOrganization et de ses logiciels dérivés, sauf obligation légale ou accord explicite concernant une intégration précise.',
        'context' => 'Second paragraph stating the no-sale, no-advertising and no-commercial-profiling commitments.',
    ],
    'legal.privacy.section.3.title' => [
        'text' => '3. Données susceptibles d’être traitées',
        'context' => 'Section title listing the categories of personal data processed.',
    ],
    'legal.privacy.section.3.body' => [
        'text' => 'Selon les applications utilisées et les choix de votre organisation, les catégories suivantes peuvent être concernées :',
        'context' => 'Introductory paragraph for the personal data categories list.',
    ],
    'legal.privacy.section.3.list.1' => [
        'text' => 'données de compte et d’identité : nom, prénom, nom d’utilisateur, adresse e-mail, photo ou initiales, préférences et rattachements à une organisation ;',
        'context' => 'Personal data category for user accounts and identity.',
    ],
    'legal.privacy.section.3.list.2' => [
        'text' => 'contenus saisis dans le logiciel : structure, espaces, responsabilités, projets, documents, procès-verbaux, calendriers, indicateurs, processus, règles, décisions, propositions, votes et discussions ;',
        'context' => 'Personal data category for organization and collaboration content.',
    ],
    'legal.privacy.section.3.list.3' => [
        'text' => 'données liées à une participation publique : adresse e-mail utilisée pour obtenir un accès, code ou lien personnel, statut de participant, pseudonyme ou choix d’anonymat lorsque cette fonction est utilisée ;',
        'context' => 'Personal data category for public decision participation.',
    ],
    'legal.privacy.section.3.list.4' => [
        'text' => 'données techniques nécessaires : session, journaux de sécurité, informations de connexion et éléments indispensables au diagnostic, à la maintenance et à la prévention des abus ;',
        'context' => 'Personal data category for technical operation and security.',
    ],
    'legal.privacy.section.3.list.5' => [
        'text' => 'données de communication lorsque vous nous contactez ou demandez un accès, ainsi que les informations nécessaires à l’envoi du message demandé.',
        'context' => 'Personal data category for communications and requested emails.',
    ],
    'legal.privacy.section.3.list.6' => [
        'text' => 'réponses à des questionnaires, évaluations ou consultations, lorsqu’une organisation active ces fonctionnalités ;',
        'context' => 'Personal data category for survey and assessment responses.',
    ],
    'legal.privacy.section.4.title' => [
        'text' => '4. Finalités et fondements du traitement',
        'context' => 'Section title describing processing purposes and legal grounds.',
    ],
    'legal.privacy.section.4.body' => [
        'text' => 'Ces données sont traitées uniquement dans la mesure nécessaire pour :',
        'context' => 'Introductory paragraph for the processing purposes list.',
    ],
    'legal.privacy.section.4.list.1' => [
        'text' => 'créer et sécuriser les comptes, authentifier les personnes et gérer les droits d’accès ;',
        'context' => 'Processing purpose for accounts and access control.',
    ],
    'legal.privacy.section.4.list.2' => [
        'text' => 'permettre la coopération, la documentation de l’organisation, la préparation et le suivi des décisions ;',
        'context' => 'Processing purpose for collaboration and organizational documentation.',
    ],
    'legal.privacy.section.4.list.3' => [
        'text' => 'envoyer les invitations, codes d’accès, notifications et autres messages demandés par les utilisateurs ou les organisations ;',
        'context' => 'Processing purpose for requested service emails and notifications.',
    ],
    'legal.privacy.section.4.list.4' => [
        'text' => 'prévenir les accès frauduleux, les abus, les pertes de données et les incidents de sécurité ;',
        'context' => 'Processing purpose for security and abuse prevention.',
    ],
    'legal.privacy.section.4.list.5' => [
        'text' => 'respecter les obligations légales et répondre aux demandes légitimes des personnes concernées.',
        'context' => 'Processing purpose for legal obligations and data subject requests.',
    ],
    'legal.privacy.section.4.list.6' => [
        'text' => 'produire des statistiques et conduire des études scientifiques destinées à soutenir la recherche sur l’impact des modes de gouvernance, uniquement à partir de données anonymisées et dans le cadre de la recherche académique.',
        'context' => 'Processing purpose for anonymized statistics and academic governance research.',
    ],
    'legal.privacy.section.5.title' => [
        'text' => '5. Absence de revente et d’exploitation commerciale',
        'context' => 'Section title describing the project’s non-commercial data commitment.',
    ],
    'legal.privacy.section.5.body.1' => [
        'text' => 'Nous ne vendons, ne louons et ne monétisons aucune donnée personnelle ni aucun contenu. Nous n’utilisons pas les informations saisies dans OpenMyOrganization ou ses logiciels dérivés pour de la publicité, du ciblage, de la prospection ou une analyse commerciale.',
        'context' => 'First paragraph reaffirming the prohibition on commercial use of personal and content data.',
    ],
    'legal.privacy.section.5.body.2' => [
        'text' => 'Les statistiques techniques nécessaires à la disponibilité et à la sécurité du service ne sont pas utilisées pour suivre les personnes à des fins publicitaires. Aucun dispositif publicitaire ou outil de profilage commercial n’est intégré par défaut à l’instance.',
        'context' => 'Second paragraph explaining the absence of advertising and commercial tracking by default.',
    ],
    'legal.privacy.section.5.body.3' => [
        'text' => 'Certaines données peuvent être agrégées et anonymisées à des fins statistiques pour conduire des études scientifiques destinées à soutenir la recherche sur l’impact des modes de gouvernance. Ces données restent anonymisées, ne sont en aucun cas vendues et leur utilisation est limitée à la recherche académique.',
        'context' => 'Third paragraph defining the anonymized statistical use for academic governance research.',
    ],
    'legal.privacy.section.6.title' => [
        'text' => '6. Hébergement et destinataires',
        'context' => 'Section title describing hosting and data recipients.',
    ],
    'legal.privacy.section.6.body.1' => [
        'text' => 'L’instance hébergée par le projet est exploitée chez <a href="https://www.infomaniak.com/fr/certifications" target="_blank" rel="noopener noreferrer">Infomaniak</a>, un prestataire suisse indépendant. Ce choix s’appuie sur ses engagements publiés en matière de confidentialité, de sécurité, de souveraineté et de durabilité. Infomaniak indique notamment que ses données et services sont hébergés en Suisse et que ses centres de données sont exploités sous son contrôle.',
        'context' => 'Paragraph describing the official hosting provider and its published commitments.',
    ],
    'legal.privacy.section.6.body.2' => [
        'text' => 'L’hébergeur, les personnes chargées de l’exploitation et les prestataires techniques autorisés ne peuvent accéder aux données que dans la mesure nécessaire à leur mission, à la sécurité ou à la résolution d’un incident. Aucune donnée n’est transmise à des tiers pour être vendue ou exploitée à des fins commerciales.',
        'context' => 'Paragraph describing limited access by hosting and technical providers.',
    ],
    'legal.privacy.section.6.body.3' => [
        'text' => 'Les services facultatifs activés par une organisation, notamment l’envoi de courriels ou une intégration externe, peuvent impliquer les prestataires correspondants. Seules les données nécessaires à la fonction concernée doivent alors leur être transmises, et l’organisation qui active cette fonction doit en informer les personnes concernées lorsque cela est requis.',
        'context' => 'Paragraph describing optional third-party integrations and their responsibility.',
    ],
    'legal.privacy.ai.title' => [
        'text' => "6.1. Services d'intelligence artificielle utilises sur cette instance",
        'context' => 'Privacy policy section listing current AI services from runtime configuration.',
    ],
    'legal.privacy.ai.configuration' => [
        'text' => "Les services ci-dessous correspondent aux parametres actuels de cette instance. Cette liste est mise a jour automatiquement lorsque la configuration change. Les contenus et traductions deja enregistres peuvent provenir d'autres configurations.",
        'context' => 'Explain live instance-specific disclosure, distinct from historical data or another instance sharing content.',
    ],
    'legal.privacy.ai.data_title' => ['text' => '6.2. Donnees transmises aux services IA', 'context' => 'Privacy policy subsection describing data sent for each AI feature.'],
    'legal.privacy.ai.functions' => ['text' => 'Fonctions IA, resumes et chat integre', 'context' => 'Purpose of the main text model.'],
    'legal.privacy.ai.translation' => ['text' => "Traduction de l'interface", 'context' => 'Purpose of the separate translation model.'],
    'legal.privacy.ai.transcription' => ['text' => 'Transcription audio', 'context' => 'Purpose of the independently configured audio service.'],
    'legal.privacy.ai.service' => [
        'text' => '<strong>{purpose}</strong> : service {provider} ; modele {model}.',
        'context' => 'AI service list entry; provider and model are escaped runtime values, never translated or cached as source copy.',
    ],
    'legal.privacy.ai.disabled' => ['text' => '<strong>{purpose}</strong> : service desactive sur cette instance.', 'context' => 'Explicitly disabled AI feature, hiding retained model settings.'],
    'legal.privacy.ai.unconfigured' => ['text' => '<strong>{purpose}</strong> : service indisponible ou configuration incomplete.', 'context' => 'AI service cannot currently be called with the instance settings.'],
    'legal.privacy.ai.unknown' => ['text' => 'Service personnalise', 'context' => 'Fallback service label when a configured provider cannot be identified.'],
    'legal.privacy.ai.text_data' => [
        'text' => "Lorsque vous demandez un resume, une reformulation ou utilisez le chat integre, du contenu texte est envoye au service IA configure : vos questions et consignes, ainsi que les extraits de documents ou les informations de l'organisation necessaires pour traiter votre demande, dans la limite de vos droits d'acces.",
        'context' => 'Disclose text transmission for summaries, rewriting and organization chat, including permission-scoped tool results.',
    ],
    'legal.privacy.ai.translation_data' => [
        'text' => "La traduction automatique de l'interface transmet au service de traduction les libelles et textes de l'interface a traduire.",
        'context' => 'Disclose transmission of UI source strings to the translation provider.',
    ],
    'legal.privacy.ai.audio_data' => [
        'text' => "Pour la transcription, l'enregistrement audio et, le cas echeant, des consignes textuelles sont envoyes au service audio configure. Le texte obtenu peut ensuite etre envoye au service IA de texte si vous demandez un resume ou une reformulation.",
        'context' => 'Accurately disclose audio and optional text prompts for transcription, then separate text processing when requested.',
    ],
    'legal.privacy.ai.external' => [
        'text' => 'Les assistants externes connectes par API ou MCP utilisent leur propre fournisseur, choisi par la personne qui les utilise. Les donnees recues par ces assistants dependent des acces et autorisations accordes.',
        'context' => 'Distinguish AI called by the site from external MCP/API clients.',
    ],
    'legal.privacy.section.7.title' => [
        'text' => '7. Logiciel libre et auto-hébergement',
        'context' => 'Section title describing the open-source and self-hosting model.',
    ],
    'legal.privacy.section.7.body.1' => [
        'text' => 'OpenMyOrganization et ses logiciels dérivés (EasyPV, EasyCIRCLE, EasyMEMO et toute la gamme Easy) sont des logiciels open source. Toute organisation qui le souhaite peut installer et exploiter sa propre instance afin de conserver elle-même la maîtrise de son hébergement et de ses données.',
        'context' => 'First paragraph describing the open-source and self-hosting option.',
    ],
    'legal.privacy.section.7.body.2' => [
        'text' => 'Une instance auto-hébergée est toutefois placée sous la responsabilité de l’organisation qui l’installe : choix du serveur, mises à jour, sauvegardes, contrôle des accès, sécurité, sous-traitants, durées de conservation et information des utilisateurs. La présente politique ne s’applique pas automatiquement à ces installations indépendantes.',
        'context' => 'Second paragraph describing the responsibilities of self-hosted installations.',
    ],
    'legal.privacy.section.8.title' => [
        'text' => '8. Conservation et suppression',
        'context' => 'Section title describing retention and deletion.',
    ],
    'legal.privacy.section.8.body.1' => [
        'text' => 'Les données de compte et les contenus d’organisation sont conservés aussi longtemps qu’ils sont nécessaires au service ou que l’organisation qui les gère en a besoin. Les codes d’accès temporaires et les données techniques à courte durée de vie sont supprimés ou renouvelés selon leur fonction.',
        'context' => 'First paragraph describing retention periods for accounts, content and temporary access data.',
    ],
    'legal.privacy.section.8.body.2' => [
        'text' => 'Lorsqu’une donnée n’est plus nécessaire, elle est supprimée, anonymisée ou rendue inaccessible dans un délai raisonnable, sous réserve des sauvegardes techniques et des obligations légales. Les sauvegardes sont conservées pendant le cycle nécessaire à la restauration et sont ensuite écrasées ou supprimées.',
        'context' => 'Second paragraph describing deletion, anonymization and backups.',
    ],
    'legal.privacy.section.9.title' => [
        'text' => '9. Sécurité',
        'context' => 'Section title describing security measures and limitations.',
    ],
    'legal.privacy.section.9.body.1' => [
        'text' => 'Nous mettons en œuvre des mesures techniques et organisationnelles proportionnées aux risques : contrôle des accès, sessions protégées, séparation des organisations, sauvegardes, maintenance et surveillance des incidents. L’hébergement officiel bénéficie en outre de l’infrastructure et des mesures de sécurité publiées par Infomaniak.',
        'context' => 'First paragraph describing technical and organizational security measures.',
    ],
    'legal.privacy.section.9.body.2' => [
        'text' => 'Aucun système connecté à Internet ne peut garantir une sécurité absolue. Nous demandons donc aux administrateurs et aux utilisateurs de choisir des mots de passe robustes, de limiter les droits au nécessaire et de signaler rapidement tout accès suspect ou incident.',
        'context' => 'Second paragraph describing the limits of security and user responsibilities.',
    ],
    'legal.privacy.section.10.title' => [
        'text' => '10. Vos droits',
        'context' => 'Section title describing data subject rights.',
    ],
    'legal.privacy.section.10.body.1' => [
        'text' => 'Vous pouvez demander l’accès aux données personnelles vous concernant, leur rectification, leur suppression lorsque les conditions sont réunies, ainsi que des informations sur leur traitement. Vous pouvez également vous opposer à un traitement ou demander sa limitation lorsque le droit applicable le permet.',
        'context' => 'First paragraph describing access, rectification, deletion, objection and restriction rights.',
    ],
    'legal.privacy.section.10.body.2' => [
        'text' => 'Lorsque les conditions légales sont remplies, vous pouvez demander la remise ou la transmission des données que vous avez communiquées dans un format électronique couramment utilisé. Pour traiter une demande, nous pouvons vérifier votre identité et tenir compte des droits d’autres personnes ainsi que des obligations de conservation.',
        'context' => 'Second paragraph describing portability, identity verification and limitations.',
    ],
    'legal.privacy.section.10.body.3' => [
        'text' => 'Pour exercer vos droits ou poser une question, utilisez {contact}. Si vous estimez que le traitement de vos données ne respecte pas la législation applicable, vous pouvez également vous adresser au Préposé fédéral à la protection des données et à la transparence (PFPDT).',
        'context' => 'Third paragraph describing the privacy contact and supervisory authority.',
    ],
    'legal.privacy.section.11.title' => [
        'text' => '11. Évolution de cette politique',
        'context' => 'Section title describing policy updates.',
    ],
    'legal.privacy.section.11.body' => [
        'text' => 'Cette politique peut être mise à jour lorsque le logiciel, son hébergement, ses intégrations ou les exigences légales évoluent. La date de la dernière version est indiquée en tête du document. En cas de changement important, nous chercherons à en informer les personnes concernées par les moyens appropriés.',
        'context' => 'Section body describing updates to the privacy policy.',
    ],
]);
$lang = commonLegalLoadBundle('common_legal_privacy_page', $sourceLang, $locale);

// Keep dynamic settings outside translation sources and the shared translation cache.
$aiServiceItems = [];
foreach (commonAiGetPublicServices() as $purpose => $service) {
    $purposeLabel = htmlspecialchars(commonLegalT('legal.privacy.ai.' . $purpose, [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8');
    $entryKey = match ($service['status']) {
        'configured' => 'legal.privacy.ai.service',
        'disabled' => 'legal.privacy.ai.disabled',
        default => 'legal.privacy.ai.unconfigured',
    };
    $aiServiceItems[] = commonLegalT($entryKey, [
        'purpose' => $purposeLabel,
        'provider' => htmlspecialchars($service['provider'] ?: commonLegalT('legal.privacy.ai.unknown', [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8'),
        'model' => htmlspecialchars($service['model'], ENT_QUOTES, 'UTF-8'),
    ], $lang, $sourceLang);
}

commonRenderLegalPage([
    'siteTitle' => $siteTitle,
    'forceLightEmbed' => !empty($_GET['survey']),
    'locale' => $locale,
    'pageTitle' => commonLegalT('legal.privacy.page_title', ['siteTitle' => $siteTitle], $lang, $sourceLang),
    'documentTitle' => commonLegalT('legal.privacy.document_title', [], $lang, $sourceLang),
    'badge' => commonLegalT('legal.privacy.badge', ['date' => $policyDate], $lang, $sourceLang),
    'accent' => '#0f766e',
    'accentSoft' => '#ccfbf1',
    'backgroundStart' => '#ecfeff',
    'pageBackground' => '#f8fafc',
    'noteBackground' => '#f0fdfa',
    'borderColor' => '#dbe4ee',
    'intro' => [
        commonLegalT('legal.privacy.intro.1', ['siteTitle' => $siteTitle], $lang, $sourceLang),
        commonLegalT('legal.privacy.intro.2', [], $lang, $sourceLang),
    ],
    'sections' => [
        [
            'title' => commonLegalT('legal.privacy.section.1.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.1.body.1', ['siteTitle' => $siteTitle, 'contact' => $privacyContact], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.1.body.2', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.2.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.2.body.1', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.2.body.2', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.3.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.3.body', [], $lang, $sourceLang),
            ],
            'list' => [
                commonLegalT('legal.privacy.section.3.list.1', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.3.list.2', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.3.list.3', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.3.list.4', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.3.list.5', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.3.list.6', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.4.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.4.body', [], $lang, $sourceLang),
            ],
            'list' => [
                commonLegalT('legal.privacy.section.4.list.1', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.4.list.2', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.4.list.3', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.4.list.4', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.4.list.5', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.4.list.6', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.5.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.5.body.1', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.5.body.2', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.5.body.3', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.6.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.6.body.1', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.6.body.2', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.6.body.3', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.ai.title', [], $lang, $sourceLang),
            'paragraphs' => [commonLegalT('legal.privacy.ai.configuration', [], $lang, $sourceLang)],
            'list' => $aiServiceItems,
        ],
        [
            'title' => commonLegalT('legal.privacy.ai.data_title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.ai.text_data', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.ai.translation_data', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.ai.audio_data', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.ai.external', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.7.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.7.body.1', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.7.body.2', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.8.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.8.body.1', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.8.body.2', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.9.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.9.body.1', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.9.body.2', [], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.10.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.10.body.1', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.10.body.2', [], $lang, $sourceLang),
                commonLegalT('legal.privacy.section.10.body.3', ['contact' => $privacyContact], $lang, $sourceLang),
            ],
        ],
        [
            'title' => commonLegalT('legal.privacy.section.11.title', [], $lang, $sourceLang),
            'paragraphs' => [
                commonLegalT('legal.privacy.section.11.body', [], $lang, $sourceLang),
            ],
        ],
    ],
]);
