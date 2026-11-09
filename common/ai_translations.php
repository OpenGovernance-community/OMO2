<?php

function commonAiUiT(string $key, array $variables = []): string
{
    static $sourceLang = [
        'copy.la_requete_ia_a_echoue' => ['text' => 'La requête IA a échoué.', 'context' => 'Interface text in common/ai_client.php'],
        'copy.la_reponse_ia_est_incomplete' => ['text' => 'La réponse IA est incomplète.', 'context' => 'Interface text in common/ai_client.php'],
        'copy.la_reponse_ia_est_vide' => ['text' => 'La réponse IA est vide.', 'context' => 'Interface text in common/ai_client.php'],
        'copy.configuration_ou_requete_ia_invalide' => ['text' => 'Configuration ou requête IA invalide.', 'context' => 'Interface text in common/ai_client.php'],
        'copy.impossible_de_preparer_la_requete_ia' => ['text' => 'Impossible de préparer la requête IA.', 'context' => 'Interface text in common/ai_client.php'],
        'copy.la_reponse_ia_est_invalide' => ['text' => 'La réponse IA est invalide.', 'context' => 'Interface text in common/ai_client.php'],
        'copy.transcription_en_francais_d_une_dictee_pour_un_document_rends_la_ponct' => ['text' => 'Transcription en français d’une dictée pour un document. Rends la ponctuation naturelle et garde les retours à la ligne évidents.', 'context' => 'Interface text in common/openai_audio.php'],
        'copy.impossible_de_preparer_la_requete_de_transcription' => ['text' => 'Impossible de préparer la requête de transcription.', 'context' => 'Interface text in common/openai_audio.php'],
        'copy.la_requete_de_transcription_a_echoue' => ['text' => 'La requête de transcription a échoué.', 'context' => 'Interface text in common/openai_audio.php'],
        'copy.le_service_a_renvoye_une_transcription_vide' => ['text' => 'Le service a renvoyé une transcription vide.', 'context' => 'Interface text in common/openai_audio.php'],
        'copy.impossible_de_preparer_le_suivi_de_transcription_infomaniak' => ['text' => 'Impossible de préparer le suivi de transcription Infomaniak.', 'context' => 'Interface text in common/openai_audio.php'],
        'copy.impossible_de_recuperer_la_transcription_infomaniak_http_code' => ['text' => 'Impossible de récupérer la transcription Infomaniak (HTTP {code}).', 'context' => 'Interface text in common/openai_audio.php'],
        'copy.reponse_de_suivi_infomaniak_invalide' => ['text' => 'Réponse de suivi Infomaniak invalide.', 'context' => 'Interface text in common/openai_audio.php'],
        'copy.infomaniak_a_renvoye_une_transcription_vide' => ['text' => 'Infomaniak a renvoyé une transcription vide.', 'context' => 'Interface text in common/openai_audio.php'],
        'copy.la_transcription_infomaniak_a_echoue_ou_a_ete_annulee' => ['text' => 'La transcription Infomaniak a échoué ou a été annulée.', 'context' => 'Interface text in common/openai_audio.php'],
        'copy.etat_de_transcription_infomaniak_invalide' => ['text' => 'État de transcription Infomaniak invalide.', 'context' => 'Interface text in common/openai_audio.php'],
        'copy.la_transcription_infomaniak_n_est_pas_terminee_dans_le_delai_imparti_e' => ['text' => 'La transcription Infomaniak n’est pas terminée dans le délai imparti. Essayez un enregistrement plus court.', 'context' => 'Interface text in common/openai_audio.php'],
        'copy.le_fichier_audio_recu_est_invalide' => ['text' => 'Le fichier audio reçu est invalide.', 'context' => 'Interface text in common/openai_audio.php'],
        'copy.le_fichier_audio_depasse_la_limite_de_25_mo' => ['text' => 'Le fichier audio dépasse la limite de 25 Mo.', 'context' => 'Interface text in common/openai_audio.php'],
        'copy.le_texte_a_reecrire_est_vide' => ['text' => 'Le texte à réécrire est vide.', 'context' => 'Interface text in common/openai_text.php'],
        'copy.impossible_de_reecrire_ce_texte' => ['text' => 'Impossible de réécrire ce texte.', 'context' => 'Interface text in common/openai_text.php'],
        'copy.le_texte_a_resumer_est_vide' => ['text' => 'Le texte à résumer est vide.', 'context' => 'Interface text in common/openai_text.php'],
        'copy.impossible_de_resumer_ce_texte' => ['text' => 'Impossible de résumer ce texte.', 'context' => 'Interface text in common/openai_text.php'],
    ];
    require_once __DIR__ . '/translation_bundles.php';
    static $bundles = [];
    $locale = translationBundleResolveRequestLocale();
    $bundle = class_exists(\dbObject\TranslationBundle::class)
        ? ($bundles[$locale] ??= loadTranslationBundle('common_ai_translations', $locale, $sourceLang)) : $sourceLang;
    return t($key, $variables, $bundle, $sourceLang);
}
