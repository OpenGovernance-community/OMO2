<?php
function omoRuleValueT(string $key, array $replace = []): string
{
    static $sourceLang = [
        'value' => ['text' => 'Valeur incarnee (facultatif)', 'context' => 'Optional organizational value embodied by a rule'],
        'none' => ['text' => 'Aucune valeur liée', 'context' => 'Rule without an organizational value'],
        'rules' => ['one' => '{count} règle associée', 'other' => '{count} règles associées', 'context' => 'Number of rules embodying a value; opens the associated rules popup'],
        'rules_title' => ['text' => 'Règles associées a {value}', 'context' => 'Title of the popup listing rules embodying a value'],
        'rules_empty' => ['text' => 'Aucune règle associée à cette valeur.', 'context' => 'Value without associated rules'],
    ];
    static $bundle = null;
    $bundle ??= omoLoadTranslationBundle('common_rule_value', $sourceLang);
    return t($key, $replace, $bundle, $sourceLang);
}

function omoRuleValueRenderField(int $organizationId, int $selectedId = 0): void
{
    $values = \dbObject\Valeur::findForOrganization($organizationId);
    if (!$values) return;
    $escape = static fn($text) => htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    ?>
    <label class="generic-form-field"><span class="generic-form-label"><?= $escape(omoRuleValueT('value')) ?></span>
        <select class="generic-form-control" name="IDvaleur">
            <option value=""><?= $escape(omoRuleValueT('none')) ?></option>
            <?php foreach ($values as $value): ?>
                <option value="<?= (int)$value->getId() ?>"<?= $selectedId === (int)$value->getId() ? ' selected' : '' ?>><?= $escape($value->get('title')) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php
}

function omoRuleValueRender(?\dbObject\Valeur $value, bool $showRules = false): string
{
    if (!$value) return '';
    $rulesLink = '';
    if ($showRules) {
        static $countsByOrganization = [];
        $organizationId = (int)$value->get('IDorganization');
        $countsByOrganization[$organizationId] ??= \dbObject\ArrayRule::countByValue($organizationId);
        $count = $countsByOrganization[$organizationId][(int)$value->getId()] ?? 0;
        if ($count > 0) {
            $escape = static fn($text) => htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $rulesLink = '<div><a class="generic-action-button generic-action-button--secondary generic-action-button--compact generic-action-button--pill" href="/omo/api/policy/value_rules.php?id=' . (int)$value->getId()
                . '" data-value-rules-popup aria-haspopup="dialog" data-popup-title="' . $escape(omoRuleValueT('rules_title', ['value' => (string)$value->get('title')])) . '">'
                . $escape(omoRuleValueT('rules', ['count' => $count])) . '<span aria-hidden="true">&#8594;</span></a></div>';
        }
    }
    return '<details class="generic-accordion generic-accordion--inset"><summary>'
        . htmlspecialchars((string)$value->get('title'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '</summary><div class="generic-section generic-section--plain generic-rich-text">'
        . \dbObject\PropertyFormat::sanitizeHtml($value->get('principle')) . $rulesLink . '</div></details>';
}
