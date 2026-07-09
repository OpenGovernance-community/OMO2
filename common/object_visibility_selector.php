<?php

if (!function_exists('commonObjectVisibilitySelectorIconMap')) {
    function commonObjectVisibilitySelectorIconMap(): array
    {
        return array(
            \dbObject\ObjectVisibility::TYPE_EVERYONE => '/omo/assets/images/documents/visibility/everyone.png',
            \dbObject\ObjectVisibility::TYPE_ORGANIZATION => '/omo/assets/images/documents/visibility/organization.png',
            \dbObject\ObjectVisibility::TYPE_CIRCLE => '/omo/assets/images/documents/visibility/circle.png',
            \dbObject\ObjectVisibility::TYPE_ROLE => '/omo/assets/images/documents/visibility/role.png',
            \dbObject\ObjectVisibility::TYPE_SELF => '/omo/assets/images/documents/visibility/me.png',
        );
    }
}

if (!function_exists('commonRenderObjectVisibilitySelector')) {
    function commonRenderObjectVisibilitySelector(array $config): string
    {
        static $scriptInjected = false;

        $inputName = trim((string)($config['inputName'] ?? 'visibility_type'));
        $fieldLabel = trim((string)($config['fieldLabel'] ?? ''));
        $ariaLabel = trim((string)($config['ariaLabel'] ?? ''));
        $hint = trim((string)($config['hint'] ?? ''));
        $selectedValue = \dbObject\ObjectVisibility::normalizeVisibilityType((string)($config['selectedValue'] ?? ''));
        $optionLabels = is_array($config['optionLabels'] ?? null)
            ? $config['optionLabels']
            : \dbObject\ObjectVisibility::getVisibilityTypeOptions();
        $disabledValues = is_array($config['disabledValues'] ?? null)
            ? $config['disabledValues']
            : array();
        $optionDescriptions = is_array($config['optionDescriptions'] ?? null)
            ? $config['optionDescriptions']
            : \dbObject\ObjectVisibility::getVisibilityTypeDescriptions();
        $idPrefix = trim((string)($config['idPrefix'] ?? $inputName));
        $idPrefix = preg_replace('/[^a-z0-9_-]+/i', '-', $idPrefix);
        $idPrefix = $idPrefix !== '' ? $idPrefix : 'visibility';
        $icons = commonObjectVisibilitySelectorIconMap();
        $escape = static function ($value): string {
            return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };
        $normalizedOptionValues = array_values(array_map(static function ($optionValue): string {
            return \dbObject\ObjectVisibility::normalizeVisibilityType((string)$optionValue);
        }, array_keys($optionLabels)));
        $selectedIndex = array_search($selectedValue, $normalizedOptionValues, true);
        $selectedIndex = $selectedIndex === false ? 0 : (int)$selectedIndex;

        if ($ariaLabel === '') {
            $ariaLabel = $fieldLabel !== '' ? $fieldLabel : $inputName;
        }

        ob_start();
        ?>
        <div class="omo-visibility-choice-field">
            <?php if ($fieldLabel !== ''): ?>
                <span class="omo-visibility-choice-field__label"><?= $escape($fieldLabel) ?></span>
            <?php endif; ?>
            <div
                class="omo-visibility-choice"
                role="radiogroup"
                aria-label="<?= $escape($ariaLabel) ?>"
                data-omo-visibility-choice
                style="--omo-visibility-option-count: <?= max(1, count($normalizedOptionValues)) ?>; --omo-visibility-active-index: <?= $selectedIndex ?>;"
            >
                <?php foreach ($optionLabels as $optionValue => $optionLabel): ?>
                    <?php
                    $normalizedValue = \dbObject\ObjectVisibility::normalizeVisibilityType((string)$optionValue);
                    $optionId = $idPrefix . '-' . $normalizedValue;
                    $isDisabled = !empty($disabledValues[$normalizedValue]);
                    $iconUrl = (string)($icons[$normalizedValue] ?? $icons[\dbObject\ObjectVisibility::TYPE_ORGANIZATION]);
                    $optionDescription = trim((string)($optionDescriptions[$normalizedValue] ?? ''));
                    $tooltipText = trim($optionLabel . ($optionDescription !== '' ? ' - ' . $optionDescription : ''));
                    ?>
                    <input
                        class="omo-visibility-choice__input"
                        type="radio"
                        name="<?= $escape($inputName) ?>"
                        id="<?= $escape($optionId) ?>"
                        value="<?= $escape($normalizedValue) ?>"
                        <?= $normalizedValue === $selectedValue ? ' checked' : '' ?>
                        <?= $isDisabled ? ' disabled' : '' ?>
                    >
                    <label
                        class="omo-visibility-choice__button<?= $isDisabled ? ' is-disabled' : '' ?>"
                        for="<?= $escape($optionId) ?>"
                        title="<?= $escape($tooltipText) ?>"
                        aria-label="<?= $escape($tooltipText) ?>"
                    >
                        <span class="omo-visibility-choice__icon-shell">
                            <img
                                src="<?= $escape($iconUrl) ?>"
                                alt=""
                                class="omo-visibility-choice__icon black-icon"
                                loading="lazy"
                            >
                        </span>
                        <span class="omo-visibility-choice__text"><?= $escape($optionLabel) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if ($hint !== ''): ?>
                <span class="omo-visibility-choice-field__hint"><?= $escape($hint) ?></span>
            <?php endif; ?>
        </div>
        <?php if (!$scriptInjected): ?>
            <script>
            (function () {
                function syncVisibilityChoice(root) {
                    if (!root) {
                        return;
                    }

                    var inputs = Array.prototype.slice.call(root.querySelectorAll('.omo-visibility-choice__input'));
                    var activeIndex = 0;
                    inputs.forEach(function (input, index) {
                        if (input && input.checked) {
                            activeIndex = index;
                        }
                    });
                    root.style.setProperty('--omo-visibility-active-index', String(activeIndex));
                    root.style.setProperty('--omo-visibility-option-count', String(Math.max(1, inputs.length)));
                }

                window.omoSyncVisibilityChoices = function (scope) {
                    var rootScope = scope instanceof Element || scope instanceof Document ? scope : document;
                    rootScope.querySelectorAll('[data-omo-visibility-choice]').forEach(syncVisibilityChoice);
                };

                window.omoSyncVisibilityChoices(document);

                if (window.__omoVisibilityChoiceReady) {
                    return;
                }

                window.__omoVisibilityChoiceReady = true;

                document.addEventListener('change', function (event) {
                    var target = event.target;
                    if (!(target instanceof HTMLInputElement) || !target.classList.contains('omo-visibility-choice__input')) {
                        return;
                    }

                    syncVisibilityChoice(target.closest('[data-omo-visibility-choice]'));
                });
            })();
            </script>
            <?php $scriptInjected = true; ?>
        <?php endif; ?>
        <?php

        return (string)ob_get_clean();
    }
}
