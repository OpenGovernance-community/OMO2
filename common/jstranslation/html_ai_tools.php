<?php
require_once dirname(__DIR__, 2) . '/omo/api/bootstrap.php';

$sourceLang = [
    'ia' => ['text' => 'IA', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'masquer_les_outils_ia' => ['text' => 'Masquer les outils IA', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'afficher_les_outils_ia' => ['text' => 'Afficher les outils IA', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'en_cours' => ['text' => 'En cours…', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'dicter' => ['text' => 'Dicter', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'demarrer_une_dictee' => ['text' => 'Démarrer une dictée', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'transcription' => ['text' => 'Transcription…', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'transcrire' => ['text' => 'Transcrire', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'arreter_l_enregistrement_et_transcrire' => ['text' => 'Arrêter l’enregistrement et transcrire', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'annuler' => ['text' => 'Annuler', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'annuler_action_ia' => ['text' => 'Annuler l’action IA en cours', 'context' => 'Cancel the current recording, transcription, summary or rewrite.'],
    'reecriture' => ['text' => 'Réécriture…', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'reecrire' => ['text' => 'Réécrire', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'reecrire_la_selection' => ['text' => 'Réécrire la sélection', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'resume' => ['text' => 'Résumé…', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'resumer' => ['text' => 'Résumer', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'resumer_la_selection' => ['text' => 'Résumer la sélection', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'l_acces_au_micro_a_ete_refuse' => ['text' => 'L’accès au micro a été refusé.', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'aucun_micro_n_est_disponible_sur_cet_appareil' => ['text' => 'Aucun micro n’est disponible sur cet appareil.', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'action_ia_annulee' => ['text' => 'Action IA annulée.', 'context' => 'Notification after cancelling an AI editor operation.'],
    'la_dictee_n_est_pas_disponible_sur_ce_navigateur' => ['text' => 'La dictée n’est pas disponible sur ce navigateur.', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'enregistrement_en_cours_quand_vous_avez_termine_cliquez_sur_transcrire' => ['text' => 'Enregistrement en cours. Quand vous avez terminé, cliquez sur Transcrire.', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'transcription_en_cours' => ['text' => 'Transcription en cours…', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'aucun_son_n_a_ete_enregistre' => ['text' => 'Aucun son n’a été enregistré.', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'transcription_inseree_dans_le_document' => ['text' => 'Transcription insérée dans le document.', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'selectionnez_un_bloc_de_texte_avant_de_lancer_la_reecriture' => ['text' => 'Sélectionnez un bloc de texte avant de lancer la réécriture.', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'reecriture_en_cours_2' => ['text' => 'Réécriture en cours…', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'impossible_de_remplacer_la_selection_dans_l_editeur' => ['text' => 'Impossible de remplacer la sélection dans l’éditeur.', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'selection_reecrite' => ['text' => 'Sélection réécrite.', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'selectionnez_un_bloc_de_texte_avant_de_lancer_le_resume' => ['text' => 'Sélectionnez un bloc de texte avant de lancer le résumé.', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'resume_en_cours_2' => ['text' => 'Résumé en cours…', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'selection_resumee' => ['text' => 'Sélection résumée.', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'error' => ['text' => 'Impossible de terminer cette action IA.', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
    'acces_micro' => ['text' => 'Accès au microphone en cours…', 'context' => 'Shared AI toolbar for Memo and PV HTML editors.'],
];
$bundle = omoLoadTranslationBundle('common_document_html_ai_tools', $sourceLang);
$payload = [];
foreach ($sourceLang as $key => $definition) $payload[$key] = t($key, [], $bundle, $sourceLang);
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
