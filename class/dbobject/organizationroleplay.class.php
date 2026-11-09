<?php
namespace dbObject;

/** Isolated characters and delivery rules for organization roleplay imports. */
class OrganizationRoleplay
{
        private static function uiMessage(string $key, array $variables = []): string
{
    static $sourceLang = [
        'copy.aucun_administrateur_reel_ne_peut_recevoir_les_e_mails_du_jeu_de_role' => ['text' => 'Aucun administrateur réel ne peut recevoir les e-mails du jeu de rôle.', 'context' => 'Interface text in class/dbobject/organizationroleplay.class.php'],
        'copy.destinataire_de_jeu_de_role_ambigu_organisation_requise' => ['text' => 'Destinataire de jeu de rôle ambigu : organisation requise.', 'context' => 'Interface text in class/dbobject/organizationroleplay.class.php'],
    ];
    require_once __DIR__ . '/../../common/translation_bundles.php';
    static $bundles = [];
    $locale = \translationBundleResolveRequestLocale();
    $bundle = class_exists(\dbObject\TranslationBundle::class)
        ? ($bundles[$locale] ??= \loadTranslationBundle('class_dbobject_organizationroleplay_class', $locale, $sourceLang)) : $sourceLang;
    return \t($key, $variables, $bundle, $sourceLang);
}

    public static function isInvalidEmail(string $email): bool
    {
        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
        return $domain === 'invalid' || str_ends_with($domain, '.invalid');
    }

    public static function invalidEmail(string $email, int $sourceId = 0): string
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { return 'character-' . $sourceId . '@roleplay.invalid'; }
        $masked = self::isInvalidEmail($email) ? $email : $email . '.invalid';
        return strlen($masked) <= 250 ? $masked : 'character-' . $sourceId . '@roleplay.invalid';
    }

    /** Mask structured addresses, including email-keyed invitation maps. */
    public static function preparePayload(array $payload, bool $emailField = false): array
    {
        $result = [];
        foreach ($payload as $key => $value) {
            $isEmail = $emailField || (is_string($key) && stripos($key, 'email') !== false);
            if ($emailField && is_string($key) && filter_var($key, FILTER_VALIDATE_EMAIL)) {
                $key = self::invalidEmail($key);
            }
            if (is_array($value)) { $value = self::preparePayload($value, $isEmail); }
            elseif ($isEmail && is_string($value) && filter_var(trim($value), FILTER_VALIDATE_EMAIL)) {
                $value = self::invalidEmail($value);
            }
            $result[$key] = $value;
        }
        return $result;
    }

    public static function adminMailAddresses(int $organizationId): array
    {
        $members = new ArrayUserOrganization();
        $members->loadActiveForOrganization($organizationId);
        $addresses = [];
        foreach ($members as $member) {
            if (!$member->isOrganizationAdmin()) { continue; }
            $user = $member->get('user');
            if (!$user || !$user->get('active') || $user->isRoleplayCharacter()) { continue; }
            $email = strtolower(trim((string)$member->getScopedEmail()));
            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !self::isInvalidEmail($email)) { $addresses[$email] = $email; }
        }
        if ($addresses === []) { throw new \DomainException(self::uiMessage('copy.aucun_administrateur_reel_ne_peut_recevoir_les_e_mails_du_jeu_de_role')); }
        return array_values($addresses);
    }

    private static function recipientOrganization(string $email, ?int $organizationId): int
    {
        if ($organizationId > 0) {
            $organization = new Organization();
            if ($organization->load($organizationId) && $organization->getParameter('roleplay')) { return $organizationId; }
        }
        $bindings = ['global_email' => $email, 'scoped_email' => $email];
        $scope = '';
        if ($organizationId > 0) { $scope = ' AND m.IDorganization = :organization_id'; $bindings['organization_id'] = $organizationId; }
        $rows = DbObject::fetchAll('SELECT u.parameters, m.IDorganization FROM `user` u
            INNER JOIN user_organization m ON m.IDuser = u.id
            WHERE (LOWER(u.email) = :global_email OR LOWER(m.email) = :scoped_email)' . $scope, $bindings);
        $organizations = [];
        foreach ($rows ?: [] as $row) {
            $parameters = json_decode((string)$row['parameters'], true);
            $id = (int)$row['IDorganization'];
            if (!empty($parameters['roleplay_character']) && (int)($parameters['roleplay_organization_id'] ?? 0) === $id) {
                $organizations[$id] = $id;
            }
        }
        if ($organizations === []) { return 0; }
        if (count($organizations) !== 1) { throw new \DomainException(self::uiMessage('copy.destinataire_de_jeu_de_role_ambigu_organisation_requise')); }
        return (int)reset($organizations);
    }

    /** Run at final delivery, including workers, with no dependency on the active session. */
    public static function routeMail(array $envelope, ?int $organizationId = null): array
    {
        $routed = []; $seen = []; $admins = [];
        foreach (['to', 'cc', 'bcc'] as $channel) {
            $routed[$channel] = [];
            foreach ($envelope[$channel] ?? [] as $email) {
                $destinations = [$email];
                if (self::isInvalidEmail($email)) {
                    $id = self::recipientOrganization($email, $organizationId);
                    if ($id > 0) { $destinations = $admins[$id] ??= self::adminMailAddresses($id); }
                }
                foreach ($destinations as $destination) {
                    if (!isset($seen[$destination])) { $routed[$channel][] = $destination; $seen[$destination] = true; }
                }
            }
        }
        return $routed;
    }
}
