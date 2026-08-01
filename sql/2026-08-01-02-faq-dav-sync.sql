-- @migration
SET NAMES utf8mb4;

INSERT INTO `faq` (
    `id`,
    `IDhowto`,
    `IDorganization`,
    `IDholon`,
    `IDparcours`,
    `IDapplication`,
    `question`,
    `answer`,
    `detail`,
    `displayorder`,
    `isactive`,
    `created`,
    `updated`
) VALUES
    (
        3221,
        NULL,
        NULL,
        NULL,
        NULL,
        NULL,
        'Est-ce que je peux ajouter les contacts de OMO a mon telephone ou mon ordinateur ?',
        'Oui. Ajoutez un compte CardDAV avec votre identifiant OMO pour synchroniser les contacts auxquels vous avez acces.',
        '<p>OMO propose un annuaire CardDAV utilisable par la plupart des telephones et applications de contacts.</p><ol><li>Dans OMO, ouvrez votre profil et definissez un mot de passe si ce n est pas deja fait.</li><li>Dans les reglages de votre appareil ou de votre application de contacts, ajoutez un compte <strong>CardDAV</strong>.</li><li>Comme adresse du serveur, saisissez <code><em>url_serveur</em>/omo/api/carddav/</code>.</li><li>Utilisez votre adresse e-mail OMO, ou votre identifiant de connexion, ainsi que votre mot de passe OMO.</li></ol><p>Seuls les contacts des membres que vous etes autorise a voir sont proposes. La synchronisation est actuellement en lecture seule : les modifications faites sur votre appareil ne sont pas renvoyees dans OMO.</p>',
        210,
        1,
        NOW(),
        NOW()
    ),
    (
        3222,
        NULL,
        NULL,
        NULL,
        NULL,
        NULL,
        'Est-ce que je peux ajouter les rendez-vous et reunions de OMO a mon telephone ou mon ordinateur ?',
        'Oui. Ajoutez un compte CalDAV pour consulter dans votre calendrier les reunions OMO auxquelles vous avez acces.',
        '<p>OMO propose un calendrier CalDAV utilisable par la plupart des telephones et applications de calendrier.</p><ol><li>Dans OMO, ouvrez votre profil et definissez un mot de passe si ce n est pas deja fait.</li><li>Dans les reglages de votre appareil ou de votre application de calendrier, ajoutez un compte <strong>CalDAV</strong>.</li><li>Comme adresse du serveur, saisissez <code><em>url_serveur</em>/omo/api/caldav/</code>.</li><li>Utilisez votre adresse e-mail OMO, ou votre identifiant de connexion, ainsi que votre mot de passe OMO.</li></ol><p>Les calendriers des organisations dont vous etes membre et dont le calendrier est actif sont proposes automatiquement. La synchronisation est actuellement en lecture seule : creez ou modifiez les reunions directement dans OMO.</p>',
        220,
        1,
        NOW(),
        NOW()
    )
ON DUPLICATE KEY UPDATE
    `IDhowto` = VALUES(`IDhowto`),
    `IDorganization` = VALUES(`IDorganization`),
    `IDholon` = VALUES(`IDholon`),
    `IDparcours` = VALUES(`IDparcours`),
    `IDapplication` = VALUES(`IDapplication`),
    `question` = VALUES(`question`),
    `answer` = VALUES(`answer`),
    `detail` = VALUES(`detail`),
    `displayorder` = VALUES(`displayorder`),
    `isactive` = VALUES(`isactive`),
    `updated` = VALUES(`updated`);
