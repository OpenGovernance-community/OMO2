<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once __DIR__ . '/auth.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

function commonPasswordResetEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function commonPasswordResetSourceLang(): array
{
    return [
        'password_reset.page.title' => [
            'text' => 'Réinitialisation du mot de passe',
            'context' => 'HTML title of the password reset page.'
        ],
        'password_reset.hero.kicker' => [
            'text' => 'Accès sécurisé',
            'context' => 'Small label shown in the hero area of the password reset page.'
        ],
        'password_reset.form.heading' => [
            'text' => 'Choisissez un nouveau mot de passe',
            'context' => 'Main heading shown on the password reset form.'
        ],
        'password_reset.form.copy' => [
            'text' => 'Définissez un nouveau mot de passe pour cette adresse e-mail.',
            'context' => 'Introductory paragraph shown above the password reset form.'
        ],
        'password_reset.form.email_label' => [
            'text' => 'Adresse concernée',
            'context' => 'Label shown above the email address on the password reset page.'
        ],
        'password_reset.form.password_label' => [
            'text' => 'Nouveau mot de passe',
            'context' => 'Label of the new password field on the password reset page.'
        ],
        'password_reset.form.password_confirm_label' => [
            'text' => 'Confirmer le nouveau mot de passe',
            'context' => 'Label of the password confirmation field on the password reset page.'
        ],
        'password_reset.form.password_placeholder' => [
            'text' => 'Au moins 12 caractères',
            'context' => 'Placeholder of the new password field on the password reset page.'
        ],
        'password_reset.form.password_confirm_placeholder' => [
            'text' => 'Retapez le mot de passe',
            'context' => 'Placeholder of the password confirmation field on the password reset page.'
        ],
        'password_reset.form.submit' => [
            'text' => 'Enregistrer le nouveau mot de passe',
            'context' => 'Submit button label of the password reset form.'
        ],
        'password_reset.form.back_to_login' => [
            'text' => 'Retour à la connexion',
            'context' => 'Link label to return to the main login page from the password reset page.'
        ],
        'password_reset.status.success_title' => [
            'text' => 'Mot de passe mis à jour',
            'context' => 'Heading shown after a successful password reset.'
        ],
        'password_reset.status.success_copy' => [
            'text' => 'Votre nouveau mot de passe est maintenant actif. Vous pouvez revenir à la page de connexion.',
            'context' => 'Paragraph shown after a successful password reset.'
        ],
        'password_reset.status.invalid_title' => [
            'text' => 'Lien invalide ou expiré',
            'context' => 'Heading shown when the password reset code is invalid or expired.'
        ],
        'password_reset.status.invalid_copy' => [
            'text' => "Le lien demandé n'est plus valide. Depuis la page de connexion, vous pouvez demander un nouvel e-mail de réinitialisation.",
            'context' => 'Paragraph shown when the password reset code is invalid or expired.'
        ],
        'password_reset.message.password_required' => [
            'text' => 'Veuillez saisir le nouveau mot de passe deux fois.',
            'context' => 'Validation message shown when the password reset form is incomplete.'
        ],
        'password_reset.message.password_short' => [
            'text' => 'Le mot de passe doit faire au moins 12 caractères et contenir une minuscule, une majuscule, un chiffre et un caractère spécial.',
            'context' => 'Validation message shown when the new password is too short.'
        ],
        'password_reset.policy.status.empty' => [
            'text' => 'Le mot de passe doit respecter les critères ci-dessous.',
            'context' => 'Initial password policy hint shown on the password reset page.'
        ],
        'password_reset.policy.status.valid' => [
            'text' => 'Mot de passe OK.',
            'context' => 'Success password policy hint shown on the password reset page.'
        ],
        'password_reset.policy.status.invalid' => [
            'text' => 'Mot de passe encore incomplet.',
            'context' => 'Error password policy hint shown on the password reset page.'
        ],
        'password_reset.policy.match.empty' => [
            'text' => 'Retapez le meme mot de passe pour confirmation.',
            'context' => 'Initial password confirmation hint shown on the password reset page.'
        ],
        'password_reset.policy.match.valid' => [
            'text' => 'Confirmation OK.',
            'context' => 'Success password confirmation hint shown on the password reset page.'
        ],
        'password_reset.policy.match.invalid' => [
            'text' => 'La confirmation ne correspond pas encore.',
            'context' => 'Error password confirmation hint shown on the password reset page.'
        ],
        'password_reset.policy.rule.length' => [
            'text' => 'Au moins 12 caractères',
            'context' => 'Password policy rule shown on the password reset page.'
        ],
        'password_reset.policy.rule.lower' => [
            'text' => 'Au moins une minuscule',
            'context' => 'Password policy rule shown on the password reset page.'
        ],
        'password_reset.policy.rule.upper' => [
            'text' => 'Au moins une majuscule',
            'context' => 'Password policy rule shown on the password reset page.'
        ],
        'password_reset.policy.rule.digit' => [
            'text' => 'Au moins un chiffre',
            'context' => 'Password policy rule shown on the password reset page.'
        ],
        'password_reset.policy.rule.special' => [
            'text' => 'Au moins un caractère spécial ou un espace',
            'context' => 'Password policy rule shown on the password reset page.'
        ],
        'password_reset.policy.rule.email' => [
            'text' => "Evitez de reprendre votre adresse e-mail",
            'context' => 'Advisory password policy rule shown on the password reset page.'
        ],
        'password_reset.message.password_mismatch' => [
            'text' => 'Les deux mots de passe ne correspondent pas.',
            'context' => 'Validation message shown when the two password fields do not match.'
        ],
        'password_reset.message.save_failed' => [
            'text' => "Impossible d'enregistrer le nouveau mot de passe pour le moment.",
            'context' => 'Validation message shown when the password reset could not be saved.'
        ],
        'password_reset.message.success' => [
            'text' => 'Le mot de passe a bien été réinitialisé.',
            'context' => 'Success message shown after the password reset form is submitted successfully.'
        ],
    ];
}

function commonPasswordResetIsUserCodeValid(\dbObject\User $user): bool
{
    $expiration = $user->get('codeexpiration');

    return $user->getId() > 0
        && $expiration instanceof \DateTimeInterface
        && $expiration > new \DateTime();
}

$sourceLang = commonPasswordResetSourceLang();
$lang = commonAuthLoadBundle('common_password_reset_page', $sourceLang);
$organizationContext = commonResolveOrganizationContext(1);
$organizationName = trim((string)($organizationContext['name'] ?? ''));
if ($organizationName === '') {
    $organizationName = trim((string)($GLOBALS['siteTitle'] ?? 'Organisation'));
}
$organizationColor = commonGetOrganizationExplicitColor($organizationContext);
$organizationAccentColor = $organizationColor !== '' ? $organizationColor : '#004663';
$organizationLogo = trim((string)($organizationContext['logo'] ?? ''));
$organizationBanner = trim((string)($organizationContext['banner'] ?? ''));

$code = trim((string)($_POST['code'] ?? $_GET['code'] ?? ''));
$user = new \dbObject\User();
$hasUser = $code !== '' && $user->load(['code', $code]);
$isValidCode = $hasUser && commonPasswordResetIsUserCodeValid($user);
$statusMessage = '';
$statusType = '';
$resetCompleted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isValidCode) {
        $statusMessage = commonAuthT('password_reset.status.invalid_copy', [], $lang, $sourceLang);
        $statusType = 'error';
    } else {
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

        if ($password === '' || $passwordConfirm === '') {
            $statusMessage = commonAuthT('password_reset.message.password_required', [], $lang, $sourceLang);
            $statusType = 'error';
        } elseif (empty(commonEvaluatePasswordComplexity($password, (string)$user->get('email'))['valid'])) {
            $statusMessage = commonGetPasswordPolicyValidationMessage();
            $statusType = 'error';
        } elseif ($password !== $passwordConfirm) {
            $statusMessage = commonAuthT('password_reset.message.password_mismatch', [], $lang, $sourceLang);
            $statusType = 'error';
        } else {
            $user->set('password', commonHashUserPassword($password));
            $user->set('code', null);
            $user->set('codeexpiration', null);

            if ($user->save()) {
                $statusMessage = commonAuthT('password_reset.message.success', [], $lang, $sourceLang);
                $statusType = 'success';
                $resetCompleted = true;
                $isValidCode = false;
            } else {
                $statusMessage = commonAuthT('password_reset.message.save_failed', [], $lang, $sourceLang);
                $statusType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= commonPasswordResetEscape(commonAuthGetTranslationLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= commonPasswordResetEscape(commonAuthT('password_reset.page.title', [], $lang, $sourceLang)) ?></title>
    <link rel="stylesheet" href="/shared_css.css">
    <link rel="stylesheet" href="/common/assets/auth.css">
    <script src="/common/assets/password_policy.js"></script>
    <style>
        :root {
            <?php if ($organizationColor !== ''): ?>
            --color-primary: <?= commonPasswordResetEscape($organizationColor) ?>;
            --auth-primary: <?= commonPasswordResetEscape($organizationColor) ?>;
            <?php endif; ?>
            --password-reset-primary: <?= commonPasswordResetEscape($organizationAccentColor) ?>;
        }

        .password-reset-page {
            min-height: 100vh;
            margin: 0;
            background: var(--auth-page-bg);
            color: var(--auth-page-text);
            font-family: system-ui, sans-serif;
        }

        .password-reset-shell {
            max-width: 760px;
            margin: 0 auto;
            padding: 32px 18px 48px;
        }

        .password-reset-card {
            overflow: hidden;
            border-radius: 24px;
            background: var(--auth-surface-solid);
            box-shadow: var(--auth-shadow-lg);
            border: 1px solid rgba(148, 163, 184, 0.22);
        }

        .password-reset-hero {
            position: relative;
            padding: 34px 26px;
            background: var(--password-reset-primary);
            color: #ffffff;
            text-align: center;
        }

        .password-reset-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background: <?= $organizationBanner !== '' ? 'url(' . commonPasswordResetEscape($organizationBanner) . ') center/cover' : 'linear-gradient(135deg, rgba(255,255,255,.18), rgba(255,255,255,0))' ?>;
            opacity: 0.2;
        }

        .password-reset-hero > * {
            position: relative;
        }

        .password-reset-logo {
            width: 82px;
            height: 82px;
            margin: 0 auto 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.96);
            padding: 6px;
            box-sizing: border-box;
        }

        .password-reset-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 999px;
        }

        .password-reset-kicker {
            display: inline-block;
            margin-bottom: 12px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.18);
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .password-reset-hero h1 {
            margin: 0;
            font-size: 2rem;
            line-height: 1.1;
        }

        .password-reset-body {
            padding: 28px 26px 30px;
            display: grid;
            gap: 20px;
        }

        .password-reset-section {
            display: grid;
            gap: 10px;
        }

        .password-reset-section h2 {
            margin: 0;
            font-size: 1.2rem;
        }

        .password-reset-copy,
        .password-reset-email {
            margin: 0;
            color: var(--auth-page-muted);
            line-height: 1.6;
        }

        .password-reset-email {
            font-weight: 600;
            color: var(--auth-page-text);
        }

        .password-reset-status {
            padding: 14px 16px;
            border-radius: 14px;
            font-weight: 600;
            line-height: 1.45;
        }

        .password-reset-status--success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .password-reset-status--error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        @media (max-width: 640px) {
            .password-reset-shell {
                padding: 20px 14px 32px;
            }

            .password-reset-hero,
            .password-reset-body {
                padding-left: 18px;
                padding-right: 18px;
            }
        }
    </style>
    <script src="/shared_functions.js"></script>
    <script>
    if (typeof window.sharedApplyDocumentTheme === 'function') {
        window.sharedApplyDocumentTheme(document);
    }
    </script>
</head>
<body class="password-reset-page">
    <div class="password-reset-shell">
        <div class="password-reset-card">
            <div class="password-reset-hero">
                <?php if ($organizationLogo !== ''): ?>
                <div class="password-reset-logo">
                    <img src="<?= commonPasswordResetEscape($organizationLogo) ?>" alt="<?= commonPasswordResetEscape($organizationName) ?>">
                </div>
                <?php endif; ?>
                <div class="password-reset-kicker"><?= commonPasswordResetEscape(commonAuthT('password_reset.hero.kicker', [], $lang, $sourceLang)) ?></div>
                <h1><?= commonPasswordResetEscape($organizationName) ?></h1>
            </div>

            <div class="password-reset-body">
                <?php if ($statusMessage !== ''): ?>
                <div class="password-reset-status password-reset-status--<?= commonPasswordResetEscape($statusType) ?>">
                    <?= commonPasswordResetEscape($statusMessage) ?>
                </div>
                <?php endif; ?>

                <?php if ($resetCompleted): ?>
                <div class="password-reset-section">
                    <h2><?= commonPasswordResetEscape(commonAuthT('password_reset.status.success_title', [], $lang, $sourceLang)) ?></h2>
                    <p class="password-reset-copy"><?= commonPasswordResetEscape(commonAuthT('password_reset.status.success_copy', [], $lang, $sourceLang)) ?></p>
                </div>
                <div class="auth-state-actions">
                    <a class="auth-state-btn auth-state-btn--accent" href="/"><?= commonPasswordResetEscape(commonAuthT('password_reset.form.back_to_login', [], $lang, $sourceLang)) ?></a>
                </div>
                <?php elseif ($isValidCode): ?>
                <div class="password-reset-section">
                    <h2><?= commonPasswordResetEscape(commonAuthT('password_reset.form.heading', [], $lang, $sourceLang)) ?></h2>
                    <p class="password-reset-copy"><?= commonPasswordResetEscape(commonAuthT('password_reset.form.copy', [], $lang, $sourceLang)) ?></p>
                </div>

                <div class="password-reset-section">
                    <h2><?= commonPasswordResetEscape(commonAuthT('password_reset.form.email_label', [], $lang, $sourceLang)) ?></h2>
                    <p class="password-reset-email"><?= commonPasswordResetEscape((string)$user->get('email')) ?></p>
                </div>

                <form method="post" class="auth-state-form">
                    <input type="hidden" name="code" value="<?= commonPasswordResetEscape($code) ?>">
                    <label class="auth-state-field">
                        <span class="auth-state-label"><?= commonPasswordResetEscape(commonAuthT('password_reset.form.password_label', [], $lang, $sourceLang)) ?></span>
                        <input class="auth-state-input" type="password" name="password" id="password_reset_password" autocomplete="new-password" minlength="<?= (int)commonGetPasswordPolicyMinLength() ?>" placeholder="<?= commonPasswordResetEscape(commonAuthT('password_reset.form.password_placeholder', [], $lang, $sourceLang)) ?>" required>
                    </label>
                    <label class="auth-state-field">
                        <span class="auth-state-label"><?= commonPasswordResetEscape(commonAuthT('password_reset.form.password_confirm_label', [], $lang, $sourceLang)) ?></span>
                        <input class="auth-state-input" type="password" name="password_confirm" id="password_reset_password_confirm" autocomplete="new-password" minlength="<?= (int)commonGetPasswordPolicyMinLength() ?>" placeholder="<?= commonPasswordResetEscape(commonAuthT('password_reset.form.password_confirm_placeholder', [], $lang, $sourceLang)) ?>" required>
                    </label>
                    <div
                        class="common-password-policy"
                        data-password-policy="1"
                        data-password-policy-password-selector="#password_reset_password"
                        data-password-policy-confirm-selector="#password_reset_password_confirm"
                        data-password-policy-email-value="<?= commonPasswordResetEscape((string)$user->get('email')) ?>"
                        data-password-policy-min-length="<?= (int)commonGetPasswordPolicyMinLength() ?>"
                        data-password-policy-required-keys="length,lower,upper,digit,special"
                        data-password-policy-status-empty="<?= commonPasswordResetEscape(commonAuthT('password_reset.policy.status.empty', [], $lang, $sourceLang)) ?>"
                        data-password-policy-status-valid="<?= commonPasswordResetEscape(commonAuthT('password_reset.policy.status.valid', [], $lang, $sourceLang)) ?>"
                        data-password-policy-status-invalid="<?= commonPasswordResetEscape(commonAuthT('password_reset.policy.status.invalid', [], $lang, $sourceLang)) ?>"
                        data-password-policy-match-empty="<?= commonPasswordResetEscape(commonAuthT('password_reset.policy.match.empty', [], $lang, $sourceLang)) ?>"
                        data-password-policy-match-valid="<?= commonPasswordResetEscape(commonAuthT('password_reset.policy.match.valid', [], $lang, $sourceLang)) ?>"
                        data-password-policy-match-invalid="<?= commonPasswordResetEscape(commonAuthT('password_reset.policy.match.invalid', [], $lang, $sourceLang)) ?>"
                    >
                        <span class="common-password-policy__status" data-password-status aria-live="polite"><?= commonPasswordResetEscape(commonAuthT('password_reset.policy.status.empty', [], $lang, $sourceLang)) ?></span>
                        <ul class="common-password-policy__rules">
                            <li class="common-password-policy__rule" data-password-rule="length"><?= commonPasswordResetEscape(commonAuthT('password_reset.policy.rule.length', [], $lang, $sourceLang)) ?></li>
                            <li class="common-password-policy__rule" data-password-rule="lower"><?= commonPasswordResetEscape(commonAuthT('password_reset.policy.rule.lower', [], $lang, $sourceLang)) ?></li>
                            <li class="common-password-policy__rule" data-password-rule="upper"><?= commonPasswordResetEscape(commonAuthT('password_reset.policy.rule.upper', [], $lang, $sourceLang)) ?></li>
                            <li class="common-password-policy__rule" data-password-rule="digit"><?= commonPasswordResetEscape(commonAuthT('password_reset.policy.rule.digit', [], $lang, $sourceLang)) ?></li>
                            <li class="common-password-policy__rule" data-password-rule="special"><?= commonPasswordResetEscape(commonAuthT('password_reset.policy.rule.special', [], $lang, $sourceLang)) ?></li>
                            <li class="common-password-policy__rule" data-password-rule="email"><?= commonPasswordResetEscape(commonAuthT('password_reset.policy.rule.email', [], $lang, $sourceLang)) ?></li>
                        </ul>
                        <span class="common-password-policy__match" data-password-match aria-live="polite"><?= commonPasswordResetEscape(commonAuthT('password_reset.policy.match.empty', [], $lang, $sourceLang)) ?></span>
                    </div>
                    <div class="auth-state-actions">
                        <button type="submit" class="auth-state-btn auth-state-btn--accent"><?= commonPasswordResetEscape(commonAuthT('password_reset.form.submit', [], $lang, $sourceLang)) ?></button>
                        <a class="auth-state-btn auth-state-btn--secondary" href="/"><?= commonPasswordResetEscape(commonAuthT('password_reset.form.back_to_login', [], $lang, $sourceLang)) ?></a>
                    </div>
                </form>
                <?php else: ?>
                <div class="password-reset-section">
                    <h2><?= commonPasswordResetEscape(commonAuthT('password_reset.status.invalid_title', [], $lang, $sourceLang)) ?></h2>
                    <p class="password-reset-copy"><?= commonPasswordResetEscape(commonAuthT('password_reset.status.invalid_copy', [], $lang, $sourceLang)) ?></p>
                </div>
                <div class="auth-state-actions">
                    <a class="auth-state-btn auth-state-btn--accent" href="/"><?= commonPasswordResetEscape(commonAuthT('password_reset.form.back_to_login', [], $lang, $sourceLang)) ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
