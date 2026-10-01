<?php
require_once __DIR__ . '/../shared_functions.php';
require_once __DIR__ . '/legal_page_helper.php';

$siteTitle = 'OpenMyOrganization';
$locale = commonLegalResolveLocale();
$versionDate = (new DateTimeImmutable('today'))->format('d.m.Y');
$sourceLang = [
    'legal.terms.page_title' => [
        'text' => 'Conditions générales d’utilisation - {siteTitle}',
        'context' => 'Browser page title for the OpenMyOrganization terms.',
    ],
    'legal.terms.document_title' => [
        'text' => 'Conditions générales d’utilisation',
        'context' => 'Main heading on the OpenMyOrganization terms page.',
    ],
    'legal.terms.version' => [
        'text' => 'Version du {date}',
        'context' => 'Current version date displayed on the terms page.',
    ],
    'legal.terms.intro' => [
        'text' => 'Les présentes conditions décrivent le cadre d’utilisation d’OpenMyOrganization (OMO), logiciel libre de coopération et de gouvernance. L’accès et l’utilisation de l’instance officielle impliquent leur acceptation. Elles distinguent le service hébergé par le projet des installations exploitées par des organisations tierces.',
        'context' => 'Introductory paragraph defining the scope of these terms.',
    ],
    'legal.terms.section.1.title' => [
        'text' => '1. Le logiciel et ses instances',
        'context' => 'Terms section title about the open-source software and deployments.',
    ],
    'legal.terms.section.1.body' => [
        'text' => 'OMO est un logiciel libre sous licence <a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" rel="noopener noreferrer">GNU Affero General Public License, version 3 uniquement (AGPL-3.0-only)</a>. Le texte de la licence et le code source sont disponibles dans le <a href="https://github.com/OpenGovernance-community/OMO2" target="_blank" rel="noopener noreferrer">dépôt GitHub du projet</a>. Toute organisation peut installer et exploiter sa propre instance sous réserve de cette licence et du droit applicable. En particulier, les personnes qui interagissent à distance avec une version modifiée doivent pouvoir obtenir le code source correspondant selon les modalités prévues par l’AGPL. Les présentes conditions décrivent l’instance hébergée par le projet ; elles ne créent pas de contrat de service entre le projet et les personnes qui utilisent une instance indépendante. L’organisation qui exploite cette instance fixe ses propres conditions et en assume la responsabilité.',
        'context' => 'AGPL-3.0-only terms, remote network users source rights and distinction between deployments.',
    ],
    'legal.terms.section.2.title' => [
        'text' => '2. Service hébergé par le projet',
        'context' => 'Terms section title for the project-hosted instance.',
    ],
    'legal.terms.section.2.body' => [
        'text' => 'L’accès à l’instance officielle est proposé librement, sans abonnement contractuel, engagement de durée ni niveau de service garanti. Le projet peut faire évoluer, limiter, interrompre ou mettre fin à tout ou partie du service, notamment pour la maintenance, la sécurité ou la continuité du projet. Dans la mesure du possible, les interruptions planifiées ou changements importants seront annoncés par les moyens disponibles.',
        'context' => 'Availability, pricing model and possible changes to the hosted service.',
    ],
    'legal.terms.section.3.title' => [
        'text' => '3. Contributions et dons',
        'context' => 'Terms section title about project funding.',
    ],
    'legal.terms.section.3.body' => [
        'text' => 'Le projet est financé notamment par des dons. Des messages peuvent inviter les personnes qui ne sont pas identifiées comme contributrices à soutenir le projet. Ces invitations sont informatives et peuvent être affichées régulièrement. Un don est volontaire : il ne constitue pas le paiement d’un abonnement ou d’une prestation, ne garantit pas un accès particulier et ne confère aucun droit de contrôle sur le développement. Le traitement d’un don effectué par l’intermédiaire d’une plateforme externe dépend aussi des conditions de cette plateforme.',
        'context' => 'Voluntary donations, contribution prompts and external donation processors.',
    ],
    'legal.terms.section.4.title' => [
        'text' => '4. Comptes et utilisation',
        'context' => 'Terms section title for accounts and acceptable use.',
    ],
    'legal.terms.section.4.body' => [
        'text' => 'Les utilisateurs doivent fournir des informations exactes lorsque cela est requis, protéger leurs moyens d’accès et respecter les droits des autres personnes. Il leur appartient de ne pas utiliser le service pour une activité illicite, porter atteinte aux systèmes, contourner les contrôles d’accès ou déposer des contenus dont ils n’ont pas le droit de disposer. Les administrateurs d’une organisation déterminent les accès, les contenus publiés et les paramètres de leur espace ; ils veillent à disposer des bases et autorisations nécessaires au traitement des données qu’ils y saisissent.',
        'context' => 'Account security, acceptable use and organization administrator responsibilities.',
    ],
    'legal.terms.section.5.title' => [
        'text' => '5. Hébergement, données et sauvegardes',
        'context' => 'Terms section title about hosting and data backups.',
    ],
    'legal.terms.section.5.body.1' => [
        'text' => 'L’instance hébergée par le projet est hébergée chez Infomaniak. Les sauvegardes de cette infrastructure sont effectuées selon les services et la politique d’Infomaniak applicables à l’hébergement concerné. Le projet ne promet pas de sauvegarde distincte, de fréquence ou de délai de restauration supplémentaires. Les modalités du prestataire peuvent évoluer ; les informations publiées par Infomaniak et la politique de confidentialité d’OMO complètent ce point.',
        'context' => 'Hosting provider and scope of backup commitments for the official instance.',
    ],
    'legal.terms.section.5.body.2' => [
        'text' => 'Pour une instance auto-hébergée, l’organisation qui l’exploite est seule chargée de son hébergement, de la sécurité de ses systèmes, de ses sauvegardes, de leur vérification et de la restauration de ses données. Elle est également responsable des comptes, des droits d’accès, de la conformité des traitements et de l’information des personnes concernées. Le projet n’administre pas ces instances et ne peut garantir leur sécurité ni récupérer leurs données.',
        'context' => 'Self-hosting responsibilities for security, backups and data protection.',
    ],
    'legal.terms.section.6.title' => [
        'text' => '6. Mises à jour et maintenance',
        'context' => 'Terms section title about updates and maintenance.',
    ],
    'legal.terms.section.6.body' => [
        'text' => 'Le projet peut publier des corrections, améliorations et mises à jour de sécurité. Une organisation qui installe OMO depuis GitHub peut configurer des mises à jour automatiques si son environnement le permet ; cette automatisation ne garantit ni que chaque mise à jour sera appliquée ni qu’elle se déroulera sans intervention. L’organisation qui exploite son serveur reste responsable de suivre les mises à jour, de les appliquer dans un délai adapté, de surveiller leur résultat et de maintenir ses dépendances et son infrastructure. Il lui est recommandé de disposer de sauvegardes vérifiées et d’une procédure de retour arrière.',
        'context' => 'Updates, optional automation and the operator responsibility to maintain self-hosted deployments.',
    ],
    'legal.terms.section.7.title' => [
        'text' => '7. Bugs et signalements',
        'context' => 'Terms section title about software defects and reports.',
    ],
    'legal.terms.section.7.body' => [
        'text' => 'Le logiciel fait l’objet d’un développement et d’une maintenance continus, mais peut contenir des erreurs, des interruptions ou des fonctions qui ne répondent pas à tous les besoins. Les anomalies peuvent être signalées dans la section <a href="https://github.com/OpenGovernance-community/OMO2/issues" target="_blank" rel="noopener noreferrer">Issues du dépôt GitHub</a>. Avant de publier un signalement, évitez d’y inclure des données personnelles, des mots de passe, des jetons ou des informations sensibles. Pour une faille de sécurité présumée, utilisez les indications de signalement privé proposées par GitHub plutôt qu’un message public contenant des détails exploitables.',
        'context' => 'Bug reports and safe handling of security vulnerability details.',
    ],
    'legal.terms.section.8.title' => [
        'text' => '8. Sécurité et limites de responsabilité',
        'context' => 'Terms section title about security and liability.',
    ],
    'legal.terms.section.8.body.1' => [
        'text' => 'Le projet met en œuvre des efforts raisonnables pour sécuriser la plateforme et protéger les données de l’instance qu’il héberge. Les risques évoluent et les tentatives de compromission des services en ligne augmentent ; aucune mesure ne permet de garantir une sécurité absolue, une disponibilité permanente, l’absence de bogues ou la conservation sans incident de toutes les données. Le logiciel est fourni selon les termes de sa licence open source, sans garantie générale de fonctionnement ininterrompu ou d’adéquation à un usage particulier.',
        'context' => 'Good-faith security efforts and absence of absolute security or availability guarantees.',
    ],
    'legal.terms.section.8.body.2' => [
        'text' => 'Dans les limites autorisées par le droit applicable, le projet décline toute responsabilité pour les dommages résultant notamment d’une interruption, d’un bogue, d’une perte ou altération de données, d’un accès non autorisé, d’un service tiers ou de l’utilisation d’une instance auto-hébergée. Rien dans ces conditions n’exclut ni ne limite une responsabilité qui ne peut légalement l’être, notamment en cas de dol ou de faute grave lorsque le droit suisse s’applique, ni les droits impératifs dont bénéficie une personne utilisatrice. Les règles obligatoires de protection des données restent applicables.',
        'context' => 'Liability limits subject to mandatory law and non-excludable liability.',
    ],
    'legal.terms.section.9.title' => [
        'text' => '9. Services tiers et données personnelles',
        'context' => 'Terms section title for third-party services and privacy.',
    ],
    'legal.terms.section.9.body' => [
        'text' => 'Certaines fonctions peuvent dépendre de services tiers ou transmettre des données à des prestataires choisis ou configurés par l’organisation. Leur utilisation est soumise à leurs propres conditions et politiques. Les informations sur les traitements de données liés à l’instance hébergée par le projet figurent dans la <a href="/common/politique-confidentialite.php">politique de confidentialité</a>. Pour une instance indépendante, l’organisation qui l’exploite détermine les traitements et fournit les informations requises à ses utilisateurs.',
        'context' => 'Third-party integrations and reference to the privacy policy.',
    ],
    'legal.terms.section.10.title' => [
        'text' => '10. Évolution des conditions et droit applicable',
        'context' => 'Terms section title for revisions and applicable law.',
    ],
    'legal.terms.section.10.body' => [
        'text' => 'Ces conditions peuvent être mises à jour lorsque le logiciel, son hébergement ou les exigences applicables évoluent. La version publiée sur cette page s’applique à compter de sa publication. Les présentes conditions sont interprétées selon le droit suisse, sous réserve des règles impératives applicables et des protections dont bénéficie toute personne utilisatrice en vertu du droit de son lieu de résidence. Si une disposition est inapplicable, les autres dispositions conservent leur effet dans la mesure permise par la loi.',
        'context' => 'Terms revision, Swiss law and mandatory protections.',
    ],
];
$lang = commonLegalLoadBundle('common_legal_terms_page', $sourceLang, $locale);

$sections = [];
for ($sectionNumber = 1; $sectionNumber <= 10; $sectionNumber++) {
    $paragraphKeys = $sectionNumber === 5 ? ['body.1', 'body.2'] : ($sectionNumber === 8 ? ['body.1', 'body.2'] : ['body']);
    $paragraphs = [];
    foreach ($paragraphKeys as $paragraphKey) {
        $paragraphs[] = commonLegalT(
            'legal.terms.section.' . $sectionNumber . '.' . $paragraphKey,
            [],
            $lang,
            $sourceLang
        );
    }

    $sections[] = [
        'title' => commonLegalT('legal.terms.section.' . $sectionNumber . '.title', [], $lang, $sourceLang),
        'paragraphs' => $paragraphs,
    ];
}

commonRenderLegalPage([
    'siteTitle' => $siteTitle,
    'locale' => $locale,
    'pageTitle' => commonLegalT('legal.terms.page_title', ['siteTitle' => $siteTitle], $lang, $sourceLang),
    'documentTitle' => commonLegalT('legal.terms.document_title', [], $lang, $sourceLang),
    'badge' => commonLegalT('legal.terms.version', ['date' => $versionDate], $lang, $sourceLang),
    'accent' => '#2563eb',
    'accentSoft' => '#dbeafe',
    'backgroundStart' => '#eff6ff',
    'pageBackground' => '#f8fafc',
    'noteBackground' => '#f8fbff',
    'borderColor' => '#dbe4ee',
    'intro' => [
        commonLegalT('legal.terms.intro', [], $lang, $sourceLang),
    ],
    'sections' => $sections,
]);
