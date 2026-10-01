<?php

require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

if ((int)commonGetCurrentUserId() > 0) {
    header('Location: /survey/?invite=1');
    exit;
}

commonRenderMagicLoginPage([
    'title' => 'Inviter à évaluer une organisation',
    'appName' => 'OMO',
    'intro' => 'Connectez-vous pour envoyer un questionnaire aux membres et parties prenantes de votre organisation.',
    'returnTo' => '/survey/?invite=1',
]);
