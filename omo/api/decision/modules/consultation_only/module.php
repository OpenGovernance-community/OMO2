<?php

require_once dirname(__DIR__) . '/vote/module.php';

use dbObject\DecisionGroup;
use dbObject\DecisionProcess;

if (!function_exists('omoDecisionConsultationOnlyModuleGetSourceLang')) {
    function omoDecisionConsultationOnlyModuleGetSourceLang()
    {
        $sourceLang = omoDecisionVoteModuleGetSourceLang();
        $sourceLang['decisions.vote.field.settings'] = [
            'text' => 'Parametres de la phase d’élaboration',
            'context' => 'Consultation only settings title.',
        ];
        $sourceLang['decisions.vote.field.evaluation_method'] = [
            'text' => 'Mode de consultation',
            'context' => 'Consultation only method label.',
        ];
        $sourceLang['decisions.vote.notice.consultation_only'] = [
            'text' => 'Ce mode ne comporte aucune phase de vote. Une fois la phase d’élaboration terminee, vous pourrez choisir un mode de vote et le planifier.',
            'context' => 'Consultation only manager notice.',
        ];
        $sourceLang['decisions.consultation_only.convert.title'] = [
            'text' => 'Choisir la suite',
            'context' => 'Consultation conversion panel title.',
        ];
        $sourceLang['decisions.consultation_only.convert.text'] = [
            'text' => 'La phase d’élaboration est terminee. Vous pouvez maintenant choisir un scrutin et en definir les nouveaux parametres.',
            'context' => 'Consultation conversion panel description.',
        ];
        $sourceLang['decisions.consultation_only.convert.label'] = [
            'text' => 'Mode de vote',
            'context' => 'Consultation conversion method select label.',
        ];
        $sourceLang['decisions.consultation_only.convert.action'] = [
            'text' => 'Configurer le vote',
            'context' => 'Consultation conversion action.',
        ];
        $sourceLang['decisions.consultation_only.convert.error'] = [
            'text' => 'Impossible de changer le mode de cette consultation pour le moment.',
            'context' => 'Consultation conversion generic error.',
        ];
        $sourceLang['decisions.vote.field.consultation_start'] = [
            'text' => 'Début de la phase d’élaboration',
            'context' => 'Consultation only elaboration start date.',
        ];
        $sourceLang['decisions.vote.field.consultation_end'] = [
            'text' => 'Fin de la phase d’élaboration',
            'context' => 'Consultation only elaboration end date.',
        ];
        $sourceLang['decisions.vote.option.status.consultation'] = [
            'text' => 'En élaboration',
            'context' => 'Consultation only elaboration status.',
        ];
        $sourceLang['decisions.vote.field.allow_consultation_proposals'] = [
            'text' => 'Autoriser les propositions pendant la phase d’élaboration',
            'context' => 'Consultation only proposal contribution setting.',
        ];
        return $sourceLang;
    }
}

if (!function_exists('omoDecisionConsultationOnlyModuleRender')) {
    function omoDecisionConsultationOnlyModuleRender(array $renderContext)
    {
        $context = $renderContext['context'] ?? [];
        $decision = $renderContext['decision'] ?? null;
        $lang = $renderContext['lang'] ?? [];
        $sourceLang = $renderContext['sourceLang'] ?? [];
        $escape = $renderContext['escape'] ?? static function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
        $isManageMode = (string)($context['intent'] ?? 'manage') === 'manage';
        $decisionGroup = ($context['decisionGroup'] ?? null) instanceof DecisionGroup
            ? $context['decisionGroup']
            : ($decision instanceof DecisionProcess ? $decision->getPrimaryGroup(false) : null);

        if ($isManageMode && $decision instanceof DecisionProcess && $decision->hasConsultationEnded()) {
            $registry = omoDecisionGetModuleRegistry((int)($context['organizationId'] ?? 0));
            $methods = [];
            foreach ([DecisionProcess::METHOD_SIMPLE_VOTE, DecisionProcess::METHOD_MAJORITY_JUDGMENT, DecisionProcess::METHOD_CONSENT] as $method) {
                if (!empty($registry[$method]['available'])) {
                    $methods[$method] = (string)($registry[$method]['label_key'] ?? '');
                }
            }
            if (count($methods) > 0) {
                ?>
                <section class="generic-soft-panel generic-soft-panel--stack">
                    <h3 class="generic-card-title generic-card-title--section"><?= $escape(t('decisions.consultation_only.convert.title', [], $lang, $sourceLang)) ?></h3>
                    <p class="generic-meta"><?= $escape(t('decisions.consultation_only.convert.text', [], $lang, $sourceLang)) ?></p>
                    <form class="generic-form-grid" method="post" action="/omo/api/decision/modules/consultation_only/convert.php" data-omo-decision-consultation-convert>
                        <input type="hidden" name="oid" value="<?= $escape((int)($context['organizationId'] ?? 0)) ?>">
                        <input type="hidden" name="cid" value="<?= $escape((int)($context['targetHolonId'] ?? 0)) ?>">
                        <input type="hidden" name="id" value="<?= $escape((int)$decision->getId()) ?>">
                        <input type="hidden" name="gid" value="<?= $escape($decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0) ?>">
                        <label class="generic-form-field">
                            <span class="generic-form-label"><?= $escape(t('decisions.consultation_only.convert.label', [], $lang, $sourceLang)) ?></span>
                            <select class="generic-form-control" name="target_method">
                                <?php foreach ($methods as $method => $labelKey): ?>
                                <option value="<?= $escape($method) ?>"><?= $escape(t($labelKey, [], $lang, $sourceLang)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <div class="generic-form-actions"><button type="submit" class="generic-action-button generic-action-button--main"><?= $escape(t('decisions.consultation_only.convert.action', [], $lang, $sourceLang)) ?></button></div>
                    </form>
                </section>
                <script>
                (function () {
                    var form = document.querySelector('[data-omo-decision-consultation-convert]');
                    if (!form || form.dataset.omoDecisionConsultationConvertBound === '1') return;
                    form.dataset.omoDecisionConsultationConvertBound = '1';
                    form.addEventListener('submit', async function (event) {
                        event.preventDefault();
                        var response;
                        try {
                            response = await fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin' });
                            var payload = await response.json();
                            if (!response.ok || !payload.status) throw new Error(payload.message || '<?= $escape(t('decisions.consultation_only.convert.error', [], $lang, $sourceLang)) ?>');
                            if (typeof window.omoDecisionOpenNestedDrawer === 'function') {
                                window.omoDecisionOpenNestedDrawer(payload.drawerTitle || 'Prises de decision', payload.redirectUrl, '');
                            } else if (typeof window.commonTopbarOpenDrawer === 'function') {
                                window.commonTopbarOpenDrawer(payload.drawerTitle || 'Prises de decision', payload.redirectUrl, 'fetch');
                            } else {
                                window.location.assign(payload.redirectUrl);
                            }
                        } catch (error) {
                            if (window.omoDecisionNotify) window.omoDecisionNotify(error.message || '<?= $escape(t('decisions.consultation_only.convert.error', [], $lang, $sourceLang)) ?>', 'error');
                        }
                    });
                }());
                </script>
                <?php
            }
        }

        $renderContext['consultationOnly'] = true;
        omoDecisionVoteModuleRender($renderContext);
    }
}
