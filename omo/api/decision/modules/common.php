<?php

use dbObject\DecisionInvitation;
use dbObject\DecisionGroup;
use dbObject\DecisionParticipant;
use dbObject\DecisionProcess;
use dbObject\DecisionProposal;
use dbObject\DecisionResponse;
use dbObject\ChatThread;
use dbObject\Holon;
use dbObject\User;

if (!class_exists('OmoDecisionModuleCapturedResponse', false)) {
    class OmoDecisionModuleCapturedResponse extends RuntimeException
    {
        public int $statusCode;
        public array $payload;

        public function __construct(int $statusCode, array $payload)
        {
            parent::__construct((string)($payload['message'] ?? 'Decision module response.'));
            $this->statusCode = $statusCode;
            $this->payload = $payload;
        }
    }
}

if (!function_exists('omoDecisionModuleJsonResponse')) {
    function omoDecisionModuleJsonResponse($statusCode, array $payload)
    {
        if (!empty($GLOBALS['omoDecisionCaptureModuleResponse'])) {
            throw new OmoDecisionModuleCapturedResponse((int)$statusCode, $payload);
        }
        http_response_code((int)$statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('omoDecisionModuleDecodeParameters')) {
    function omoDecisionModuleDecodeParameters($value)
    {
        if (is_array($value)) {
            return $value;
        }

        $value = trim((string)$value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('omoDecisionModuleGetMethodParameters')) {
    function omoDecisionModuleGetMethodParameters($value, $methodKey)
    {
        $parameters = omoDecisionModuleDecodeParameters($value);
        $methodKey = trim((string)$methodKey);
        if ($methodKey === '') {
            return [];
        }

        $methodParameters = $parameters[$methodKey] ?? [];
        return is_array($methodParameters) ? $methodParameters : [];
    }
}

if (!function_exists('omoDecisionBlockSettingsGetDefaultVoteWeightOptions')) {
    function omoDecisionBlockSettingsGetDefaultVoteWeightOptions()
    {
        return [
            ['weight' => '0.75', 'label' => 'Pas important'],
            ['weight' => '1', 'label' => 'Souhaitable'],
            ['weight' => '1.5', 'label' => 'Important'],
            ['weight' => '2', 'label' => 'Vital'],
        ];
    }
}

if (!function_exists('omoDecisionBlockSettingsNormalizeVoteWeightNumber')) {
    function omoDecisionBlockSettingsNormalizeVoteWeightNumber($value)
    {
        $normalized = str_replace(',', '.', trim((string)$value));
        if ($normalized === '' || !is_numeric($normalized)) {
            return '';
        }

        $number = (float)$normalized;
        if ($number <= 0) {
            return '';
        }

        $formatted = rtrim(rtrim(number_format($number, 4, '.', ''), '0'), '.');
        return $formatted !== '' ? $formatted : '1';
    }
}

if (!function_exists('omoDecisionBlockSettingsNormalizeVoteWeightOptions')) {
    function omoDecisionBlockSettingsNormalizeVoteWeightOptions($rawOptions, $fallbackToDefault = false)
    {
        if (is_string($rawOptions)) {
            $decoded = json_decode($rawOptions, true);
            if (is_array($decoded)) {
                $rawOptions = $decoded;
            } else {
                $rows = preg_split('/\r\n|\r|\n/', $rawOptions);
                $rawOptions = [];
                foreach ((array)$rows as $row) {
                    $row = trim((string)$row);
                    if ($row === '') {
                        continue;
                    }

                    $parts = preg_split('/\s*\|\s*/', $row, 2);
                    $rawOptions[] = [
                        'weight' => $parts[0] ?? '',
                        'label' => $parts[1] ?? '',
                    ];
                }
            }
        }

        $normalized = [];
        foreach ((array)$rawOptions as $option) {
            if (!is_array($option)) {
                continue;
            }

            $weight = omoDecisionBlockSettingsNormalizeVoteWeightNumber($option['weight'] ?? ($option['value'] ?? ''));
            $label = trim((string)($option['label'] ?? ''));
            if ($weight === '' || $label === '') {
                continue;
            }

            $normalized[] = [
                'weight' => $weight,
                'label' => $label,
            ];
        }

        if (count($normalized) === 0 && $fallbackToDefault) {
            return omoDecisionBlockSettingsGetDefaultVoteWeightOptions();
        }

        return $normalized;
    }
}

if (!function_exists('omoDecisionBlockSettingsBuildVoteWeightOptionsText')) {
    function omoDecisionBlockSettingsBuildVoteWeightOptionsText(array $options)
    {
        $lines = [];
        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }

            $weight = trim((string)($option['weight'] ?? ''));
            $label = trim((string)($option['label'] ?? ''));
            if ($weight === '' || $label === '') {
                continue;
            }

            $lines[] = $weight . ' | ' . $label;
        }

        return implode("\n", $lines);
    }
}

if (!function_exists('omoDecisionBlockSettingsBuildVoteWeightConfig')) {
    function omoDecisionBlockSettingsBuildVoteWeightConfig($rawConfig)
    {
        $voteWeighting = is_array($rawConfig) && is_array($rawConfig['vote_weighting'] ?? null)
            ? (array)$rawConfig['vote_weighting']
            : (is_array($rawConfig) ? $rawConfig : []);

        $enabled = !empty($voteWeighting['enabled']) || !empty($voteWeighting['vote_weight_enabled']);
        $question = trim((string)($voteWeighting['question'] ?? ($voteWeighting['vote_weight_question'] ?? '')));
        $options = omoDecisionBlockSettingsNormalizeVoteWeightOptions(
            $voteWeighting['options'] ?? ($voteWeighting['vote_weight_options'] ?? []),
            $enabled
        );

        return [
            'enabled' => $enabled,
            'question' => $question,
            'options' => $options,
            'options_text' => omoDecisionBlockSettingsBuildVoteWeightOptionsText($options),
        ];
    }
}

if (!function_exists('omoDecisionBlockSettingsBuildVoteWeightSummaryData')) {
    function omoDecisionBlockSettingsBuildVoteWeightSummaryData(array $config)
    {
        $enabled = !empty($config['enabled']);
        $options = omoDecisionBlockSettingsNormalizeVoteWeightOptions($config['options'] ?? [], $enabled);
        $weights = [];
        foreach ($options as $option) {
            $weight = omoDecisionBlockSettingsNormalizeVoteWeightNumber($option['weight'] ?? '');
            if ($weight === '') {
                continue;
            }
            $weights[] = (float)$weight;
        }

        return [
            'enabled' => $enabled,
            'count' => count($weights),
            'min' => count($weights) > 0 ? omoDecisionBlockSettingsNormalizeVoteWeightNumber((string)min($weights)) : '',
            'max' => count($weights) > 0 ? omoDecisionBlockSettingsNormalizeVoteWeightNumber((string)max($weights)) : '',
        ];
    }
}

if (!function_exists('omoDecisionBlockSettingsBuildVoteWeightSummaryText')) {
    function omoDecisionBlockSettingsBuildVoteWeightSummaryText(array $summaryData, $yesLabel = 'Oui', $noLabel = 'Non')
    {
        if (empty($summaryData['enabled'])) {
            return $noLabel;
        }

        $count = (int)($summaryData['count'] ?? 0);
        $min = trim((string)($summaryData['min'] ?? ''));
        $max = trim((string)($summaryData['max'] ?? ''));
        if ($count <= 0 || $min === '' || $max === '') {
            return $yesLabel;
        }

        return $yesLabel . ' (' . $count . ' options de ' . $min . ' a ' . $max . ')';
    }
}

if (!function_exists('omoDecisionBlockSettingsCountVoteWeightDecimals')) {
    function omoDecisionBlockSettingsCountVoteWeightDecimals($value)
    {
        $normalized = omoDecisionBlockSettingsNormalizeVoteWeightNumber($value);
        if ($normalized === '') {
            return 0;
        }

        $separatorPosition = strpos($normalized, '.');
        if ($separatorPosition === false) {
            return 0;
        }

        return max(0, strlen($normalized) - $separatorPosition - 1);
    }
}

if (!function_exists('omoDecisionBlockSettingsGetVoteWeightScale')) {
    function omoDecisionBlockSettingsGetVoteWeightScale($configOrOptions = null)
    {
        $options = [];
        if (is_array($configOrOptions) && array_key_exists('options', $configOrOptions)) {
            $options = omoDecisionBlockSettingsNormalizeVoteWeightOptions($configOrOptions['options'] ?? [], false);
        } elseif (is_array($configOrOptions) && array_key_exists('vote_weight_options', $configOrOptions)) {
            $options = omoDecisionBlockSettingsNormalizeVoteWeightOptions($configOrOptions['vote_weight_options'] ?? [], false);
        } elseif (is_array($configOrOptions)) {
            $looksLikeOptions = true;
            foreach ($configOrOptions as $option) {
                if (!is_array($option)) {
                    $looksLikeOptions = false;
                    break;
                }
            }
            if ($looksLikeOptions) {
                $options = omoDecisionBlockSettingsNormalizeVoteWeightOptions($configOrOptions, false);
            } else {
                $weightConfig = omoDecisionBlockSettingsBuildVoteWeightConfig($configOrOptions);
                $options = (array)($weightConfig['options'] ?? []);
            }
        } else {
            $weightConfig = omoDecisionBlockSettingsBuildVoteWeightConfig($configOrOptions);
            $options = (array)($weightConfig['options'] ?? []);
        }

        $maxDecimals = 0;
        foreach ($options as $option) {
            $maxDecimals = max($maxDecimals, omoDecisionBlockSettingsCountVoteWeightDecimals($option['weight'] ?? ''));
        }

        if ($maxDecimals <= 0) {
            return 1;
        }

        return (int)pow(10, min($maxDecimals, 4));
    }
}

if (!function_exists('omoDecisionBlockSettingsVoteWeightToUnits')) {
    function omoDecisionBlockSettingsVoteWeightToUnits($value, $scale)
    {
        $normalized = omoDecisionBlockSettingsNormalizeVoteWeightNumber($value);
        if ($normalized === '') {
            return 0;
        }

        $scale = max(1, (int)$scale);
        return (int)round(((float)$normalized) * $scale);
    }
}

if (!function_exists('omoDecisionBlockSettingsVoteWeightUnitsToValue')) {
    function omoDecisionBlockSettingsVoteWeightUnitsToValue($units, $scale)
    {
        $scale = max(1, (int)$scale);
        $units = (int)$units;
        if ($units === 0) {
            return '0';
        }
        return omoDecisionBlockSettingsNormalizeVoteWeightNumber((string)($units / $scale));
    }
}

if (!function_exists('omoDecisionBlockSettingsResolveVoteWeightSelection')) {
    function omoDecisionBlockSettingsResolveVoteWeightSelection($rawWeight, $configOrParameters = null)
    {
        $config = omoDecisionBlockSettingsBuildVoteWeightConfig($configOrParameters);
        $options = omoDecisionBlockSettingsNormalizeVoteWeightOptions($config['options'] ?? [], !empty($config['enabled']));
        $optionMap = [];
        foreach ($options as $option) {
            $weight = omoDecisionBlockSettingsNormalizeVoteWeightNumber($option['weight'] ?? '');
            if ($weight === '') {
                continue;
            }
            $optionMap[$weight] = [
                'weight' => $weight,
                'label' => trim((string)($option['label'] ?? '')),
            ];
        }

        $selectedWeight = omoDecisionBlockSettingsNormalizeVoteWeightNumber($rawWeight);
        if ($selectedWeight !== '' && isset($optionMap[$selectedWeight])) {
            $selection = $optionMap[$selectedWeight];
        } elseif (isset($optionMap['1'])) {
            $selection = $optionMap['1'];
        } elseif (count($optionMap) > 0) {
            $selection = reset($optionMap);
        } else {
            $selection = [
                'weight' => '1',
                'label' => '',
            ];
        }

        $scale = omoDecisionBlockSettingsGetVoteWeightScale(['options' => $options]);

        return [
            'enabled' => !empty($config['enabled']),
            'weight' => (string)($selection['weight'] ?? '1'),
            'label' => (string)($selection['label'] ?? ''),
            'scale' => $scale,
            'units' => omoDecisionBlockSettingsVoteWeightToUnits($selection['weight'] ?? '1', $scale),
            'options' => array_values($options),
        ];
    }
}

if (!function_exists('omoDecisionBlockSettingsBuildResponseVoteWeightPayload')) {
    function omoDecisionBlockSettingsBuildResponseVoteWeightPayload($rawWeight, $configOrParameters = null)
    {
        $selection = omoDecisionBlockSettingsResolveVoteWeightSelection($rawWeight, $configOrParameters);

        return [
            'vote_weight' => (string)$selection['weight'],
            'vote_weight_label' => (string)$selection['label'],
        ];
    }
}

if (!function_exists('omoDecisionBlockSettingsExtractResponseVoteWeightSelection')) {
    function omoDecisionBlockSettingsExtractResponseVoteWeightSelection($response, $methodKey, $configOrParameters = null)
    {
        if (!$response instanceof DecisionResponse) {
            return omoDecisionBlockSettingsResolveVoteWeightSelection(null, $configOrParameters);
        }

        $parameters = omoDecisionModuleDecodeParameters($response->get('parameters'));
        $methodParameters = omoDecisionModuleGetMethodParameters($parameters, $methodKey);
        $rawWeight = $methodParameters['vote_weight'] ?? ($methodParameters['selected_vote_weight'] ?? ($methodParameters['vote_weight_value'] ?? null));

        return omoDecisionBlockSettingsResolveVoteWeightSelection($rawWeight, $configOrParameters);
    }
}

if (!function_exists('omoDecisionBlockSettingsMergeVoteWeightConfig')) {
    function omoDecisionBlockSettingsMergeVoteWeightConfig(array $methodParameters, array $config)
    {
        $voteWeightConfig = omoDecisionBlockSettingsBuildVoteWeightConfig([
            'enabled' => !empty($config['vote_weight_enabled']) || !empty($config['enabled']),
            'question' => $config['vote_weight_question'] ?? ($config['question'] ?? ''),
            'options' => $config['vote_weight_options'] ?? ($config['options'] ?? []),
        ]);

        $methodParameters['vote_weighting'] = [
            'enabled' => !empty($voteWeightConfig['enabled']) ? 1 : 0,
            'question' => (string)$voteWeightConfig['question'],
            'options' => array_values((array)$voteWeightConfig['options']),
        ];

        return $methodParameters;
    }
}

if (!function_exists('omoDecisionRenderVoteWeightEditorAssets')) {
    function omoDecisionRenderVoteWeightEditorAssets()
    {
        static $alreadyRendered = false;
        if ($alreadyRendered) {
            return '';
        }

        $alreadyRendered = true;

        return '<style>'
            . '.omo-decision-vote-weight-editor{display:grid;gap:12px;}'
            . '.omo-decision-vote-weight-editor__content{display:grid;gap:12px;}'
            . '.omo-decision-vote-weight-editor__content[hidden]{display:none !important;}'
            . '.omo-decision-vote-weight-editor__list{display:grid;gap:10px;}'
            . '.omo-decision-vote-weight-editor__row{display:grid;grid-template-columns:minmax(88px,120px) minmax(0,1fr) auto;gap:10px;align-items:end;padding:12px;border:1px solid var(--color-border,#d1d5db);border-radius:var(--radius-md);background:var(--color-surface,#fff);}'
            . '.omo-decision-vote-weight-editor__row--locked{background:var(--color-surface-alt,#f8fafc);border-style:dashed;}'
            . '.omo-decision-vote-weight-editor__field{display:grid;gap:6px;min-width:0;}'
            . '.omo-decision-vote-weight-editor__actions{display:flex;align-items:flex-end;justify-content:flex-end;min-height:100%;}'
            . '.omo-decision-vote-weight-editor__toolbar{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;}'
            . '.omo-decision-vote-weight-editor__toggle{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}'
            . '.omo-decision-vote-weight-selector{display:grid;gap:12px;}'
            . '.omo-decision-vote-weight-selector__buttons{display:flex;gap:8px;flex-wrap:wrap;}'
            . '.omo-decision-vote-weight-selector__button{display:grid;gap:2px;min-width:120px;text-align:center;}'
            . '.omo-decision-vote-weight-selector__weight{font-size:.78rem;opacity:.78;}'
            . '@media (max-width:700px){.omo-decision-vote-weight-editor__row{grid-template-columns:1fr;}.omo-decision-vote-weight-editor__actions{justify-content:flex-start;}}'
        . '</style>'
        . '<script>(function(){'
            . 'if(typeof window.omoDecisionInitVoteWeightEditor==="function"){return;}'
            . 'var normalizeNumber=function(rawValue){'
                . 'var normalized=String(rawValue||"").trim().replace(",",".");'
                . 'var value;'
                . 'if(normalized===""){return"";}'
                . 'value=Number(normalized);'
                . 'if(!Number.isFinite(value)||value<=0){return"";}'
                . 'return String(value).replace(/\\.0+$/,"").replace(/(\\.\\d*?)0+$/,"$1");'
            . '};'
            . 'var parseOptions=function(rawValue,fallbackOptions){'
                . 'var options=[];'
                . 'var normalizedOptions=[];'
                . 'if(Array.isArray(rawValue)){options=rawValue;}else{'
                    . 'var source=String(rawValue||"").trim();'
                    . 'if(source!==""){'
                        . 'try{var decoded=JSON.parse(source);if(Array.isArray(decoded)){options=decoded;}}catch(error){'
                            . 'options=source.split(/\\r\\n|\\r|\\n/).map(function(line){'
                                . 'var parts=String(line||"").split("|");'
                                . 'return{weight:parts.length>0?parts[0]:"",label:parts.length>1?parts.slice(1).join("|"):""};'
                            . '});'
                        . '}'
                    . '}'
                . '}'
                . 'options.forEach(function(option){'
                    . 'if(!option||typeof option!=="object"){return;}'
                    . 'var weight=normalizeNumber(option.weight||option.value||"");'
                    . 'var label=String(option.label||"").trim();'
                    . 'if(weight===""||label===""){return;}'
                    . 'normalizedOptions.push({weight:weight,label:label});'
                . '});'
                . 'if(normalizedOptions.length===0&&fallbackOptions){return parseOptions(fallbackOptions,false);}'
                . 'return normalizedOptions;'
            . '};'
            . 'var buildSummaryText=function(enabled,options,yesLabel,noLabel){'
                . 'var weights=[];'
                . 'if(!enabled){return String(noLabel||"Non");}'
                . 'options=(Array.isArray(options)?options:[]).filter(function(option){return option&&option.weight&&option.label;});'
                . 'if(options.length===0){return String(yesLabel||"Oui");}'
                . 'weights=options.map(function(option){return Number(String(option.weight||"").replace(",", "."));}).filter(function(value){return Number.isFinite(value)&&value>0;});'
                . 'if(weights.length===0){return String(yesLabel||"Oui");}'
                . 'return String(yesLabel||"Oui")+" ("+String(options.length)+" options de "+normalizeNumber(String(Math.min.apply(Math,weights)))+" a "+normalizeNumber(String(Math.max.apply(Math,weights)))+")";'
            . '};'
            . 'window.omoDecisionInitVoteWeightSelector=function(root){'
                . 'if(!(root instanceof Element)){return null;}'
                . 'if(root._omoDecisionVoteWeightSelector){return root._omoDecisionVoteWeightSelector;}'
                . 'var input=root.querySelector("[data-omo-decision-vote-weight-selector-input]");'
                . 'var buttons=root.querySelectorAll("[data-omo-decision-vote-weight-selector-button]");'
                . 'var normalizeValue=function(value){return normalizeNumber(value)||"1";};'
                . 'var applyValue=function(rawValue){'
                    . 'var selectedValue=normalizeValue(rawValue);'
                    . 'if(input instanceof HTMLInputElement){input.value=selectedValue;}'
                    . 'Array.prototype.forEach.call(buttons,function(button){'
                        . 'var buttonValue=normalizeValue(button.getAttribute("data-omo-decision-vote-weight-selector-button")||"1");'
                        . 'var isActive=buttonValue===selectedValue;'
                        . 'button.classList.toggle("is-active",isActive);'
                        . 'button.setAttribute("aria-pressed",isActive?"true":"false");'
                    . '});'
                . '};'
                . 'Array.prototype.forEach.call(buttons,function(button){'
                    . 'if(button.dataset.omoDecisionVoteWeightSelectorBound==="1"){return;}'
                    . 'button.dataset.omoDecisionVoteWeightSelectorBound="1";'
                    . 'button.addEventListener("click",function(event){event.preventDefault();applyValue(button.getAttribute("data-omo-decision-vote-weight-selector-button")||"1");});'
                . '});'
                . 'applyValue(input instanceof HTMLInputElement?input.value:(root.getAttribute("data-selected-weight")||"1"));'
                . 'root._omoDecisionVoteWeightSelector={setValue:applyValue,getValue:function(){return input instanceof HTMLInputElement?normalizeValue(input.value):normalizeValue(root.getAttribute("data-selected-weight")||"1");}};'
                . 'return root._omoDecisionVoteWeightSelector;'
            . '};'
            . 'window.omoDecisionInitVoteWeightEditor=function(root){'
                . 'if(!(root instanceof Element)){return null;}'
                . 'var existing=root._omoDecisionVoteWeightEditor;'
                . 'if(existing){return existing;}'
                . 'var enabledInput=root.querySelector("[data-omo-decision-vote-weight-enabled]");'
                . 'var questionInput=root.querySelector("[data-omo-decision-vote-weight-question]");'
                . 'var list=root.querySelector("[data-omo-decision-vote-weight-list]");'
                . 'var addButton=root.querySelector("[data-omo-decision-vote-weight-add]");'
                . 'var emptyNode=root.querySelector("[data-omo-decision-vote-weight-empty]");'
                . 'var content=root.querySelector("[data-omo-decision-vote-weight-content]");'
                . 'var rowTemplate=root.querySelector("[data-omo-decision-vote-weight-row-template]");'
                . 'var canEdit=root.getAttribute("data-can-edit")==="1";'
                . 'var defaultOptions=root.getAttribute("data-default-options-json")||"[]";'
                . 'var baseLabel=root.getAttribute("data-base-label")||"Souhaitable";'
                . 'var baseWeightTitle=root.getAttribute("data-base-weight-title")||"Reference";'
                . 'var weightTitle=root.getAttribute("data-weight-title")||"Coefficient";'
                . 'var controller;'
                . 'var buildBaseOption=function(options){'
                    . 'var normalized=parseOptions(options||[],false);'
                    . 'var lockedOption=null;'
                    . 'var rows=[];'
                    . 'normalized.forEach(function(option){'
                        . 'if(!lockedOption&&String(option.weight||"")==="1"){lockedOption={weight:"1",label:String(option.label||"").trim()||baseLabel};return;}'
                        . 'rows.push({weight:String(option.weight||""),label:String(option.label||"").trim()});'
                    . '});'
                    . 'if(!lockedOption){lockedOption={weight:"1",label:baseLabel};}'
                    . 'rows.unshift(lockedOption);'
                    . 'return rows;'
                . '};'
                . 'var syncEmptyState=function(){'
                    . 'if(!(emptyNode instanceof Element)||!(list instanceof Element)){return;}'
                    . 'emptyNode.hidden=list.children.length>1;'
                . '};'
                . 'var syncExpandedState=function(){'
                    . 'var isEnabled=enabledInput instanceof HTMLInputElement&&enabledInput.checked;'
                    . 'if(content instanceof Element){content.hidden=!isEnabled;content.setAttribute("aria-hidden",isEnabled?"false":"true");}'
                    . 'if(questionInput instanceof HTMLInputElement){questionInput.disabled=!canEdit||!isEnabled;}'
                    . 'if(addButton instanceof HTMLButtonElement){addButton.disabled=!canEdit||!isEnabled;}'
                    . 'if(list instanceof Element){Array.prototype.forEach.call(list.querySelectorAll("[data-omo-decision-vote-weight-row-weight],[data-omo-decision-vote-weight-row-label],[data-omo-decision-vote-weight-row-remove]"),function(node){'
                        . 'var row,isLocked;'
                        . 'if(!(node instanceof HTMLInputElement)&&!(node instanceof HTMLButtonElement)){return;}'
                        . 'row=node.closest("[data-omo-decision-vote-weight-row]");'
                        . 'isLocked=!!(row&&row.classList.contains("omo-decision-vote-weight-editor__row--locked"));'
                        . 'if(node instanceof HTMLInputElement){node.disabled=!canEdit||!isEnabled;if(isLocked&&node.hasAttribute("data-omo-decision-vote-weight-row-weight")){node.readOnly=true;}}'
                        . 'if(node instanceof HTMLButtonElement){node.hidden=isLocked||!canEdit||!isEnabled;node.disabled=isLocked||!canEdit||!isEnabled;}'
                    . '});}'
                . '};'
                . 'var createRow=function(option,isLocked){'
                    . 'var fragment,row,weightInput,labelInput,removeButton,weightTitleNode;'
                    . 'if(!(rowTemplate instanceof HTMLTemplateElement)||!(list instanceof Element)){return;}'
                    . 'fragment=rowTemplate.content.cloneNode(true);'
                    . 'row=fragment.querySelector("[data-omo-decision-vote-weight-row]");'
                    . 'weightInput=fragment.querySelector("[data-omo-decision-vote-weight-row-weight]");'
                    . 'labelInput=fragment.querySelector("[data-omo-decision-vote-weight-row-label]");'
                    . 'removeButton=fragment.querySelector("[data-omo-decision-vote-weight-row-remove]");'
                    . 'weightTitleNode=fragment.querySelector("[data-omo-decision-vote-weight-row-weight-title]");'
                    . 'if(!(row instanceof Element)||!(weightInput instanceof HTMLInputElement)||!(labelInput instanceof HTMLInputElement)){return;}'
                    . 'row.classList.toggle("omo-decision-vote-weight-editor__row--locked",!!isLocked);'
                    . 'if(weightTitleNode instanceof Element){weightTitleNode.textContent=isLocked?baseWeightTitle:weightTitle;}'
                    . 'weightInput.value=isLocked?"1":String(option.weight||"");'
                    . 'weightInput.readOnly=!!isLocked;'
                    . 'weightInput.disabled=!canEdit;'
                    . 'labelInput.value=String(option.label||"");'
                    . 'labelInput.disabled=!canEdit;'
                    . 'if(removeButton instanceof HTMLButtonElement){'
                        . 'removeButton.hidden=!!isLocked||!canEdit;'
                        . 'removeButton.disabled=!!isLocked||!canEdit;'
                        . 'removeButton.addEventListener("click",function(event){event.preventDefault();if(row.parentNode){row.parentNode.removeChild(row);}syncEmptyState();});'
                    . '}'
                    . 'list.appendChild(fragment);'
                    . 'syncExpandedState();'
                . '};'
                . 'var render=function(options){'
                    . 'if(!(list instanceof Element)){return;}'
                    . 'list.innerHTML="";'
                    . 'buildBaseOption(options).forEach(function(option,index){createRow(option,index===0);});'
                    . 'syncEmptyState();'
                . '};'
                . 'controller={'
                    . 'setState:function(state){'
                        . 'state=state&&typeof state==="object"?state:{};'
                        . 'if(enabledInput instanceof HTMLInputElement){enabledInput.checked=!!state.enabled;enabledInput.disabled=!canEdit;}'
                        . 'if(questionInput instanceof HTMLInputElement){questionInput.value=String(state.question||"");questionInput.disabled=!canEdit;}'
                        . 'render(Array.isArray(state.options)?state.options:parseOptions(root.getAttribute("data-options-json")||"[]",false));'
                        . 'syncExpandedState();'
                    . '},'
                    . 'getState:function(){'
                        . 'var options=[];'
                        . 'if(list instanceof Element){Array.prototype.forEach.call(list.querySelectorAll("[data-omo-decision-vote-weight-row]"),function(row,index){'
                            . 'var weightInput=row.querySelector("[data-omo-decision-vote-weight-row-weight]");'
                            . 'var labelInput=row.querySelector("[data-omo-decision-vote-weight-row-label]");'
                            . 'var weight=normalizeNumber(weightInput&&weightInput.value?weightInput.value:(index===0?"1":""));'
                            . 'var label=String(labelInput&&labelInput.value?labelInput.value:"").trim();'
                            . 'if(index===0&&weight===""){weight="1";}'
                            . 'if(index===0&&label===""){label=baseLabel;}'
                            . 'if(weight===""||label===""){return;}'
                            . 'options.push({weight:weight,label:label});'
                        . '});}'
                        . 'return{enabled:enabledInput instanceof HTMLInputElement&&enabledInput.checked,question:questionInput instanceof HTMLInputElement?String(questionInput.value||"").trim():"",options:options};'
                    . '}'
                . '};'
                . 'if(enabledInput instanceof HTMLInputElement){enabledInput.addEventListener("change",syncExpandedState);}'
                . 'if(addButton instanceof HTMLButtonElement){addButton.disabled=!canEdit;addButton.addEventListener("click",function(event){event.preventDefault();createRow({weight:"",label:""},false);syncEmptyState();var lastRow=list.lastElementChild;var input=lastRow?lastRow.querySelector("[data-omo-decision-vote-weight-row-weight]"):null;if(input&&typeof input.focus==="function"){input.focus();}});}'
                . 'controller.setState({enabled:root.getAttribute("data-enabled")==="1",question:root.getAttribute("data-question")||"",options:parseOptions(root.getAttribute("data-options-json")||"[]",false)});'
                . 'root._omoDecisionVoteWeightEditor=controller;'
                . 'return controller;'
            . '};'
            . 'window.omoDecisionVoteWeightEditor={normalizeNumber:normalizeNumber,parseOptions:parseOptions,buildSummaryText:buildSummaryText};'
        . '})();</script>';
    }
}

if (!function_exists('omoDecisionRenderVoteWeightEditor')) {
    function omoDecisionRenderVoteWeightEditor($lang, array $sourceLang, $escape, array $config = [])
    {
        $canEdit = !empty($config['canEdit']);
        $enabled = !empty($config['enabled']);
        $question = trim((string)($config['question'] ?? ''));
        $options = omoDecisionBlockSettingsNormalizeVoteWeightOptions($config['options'] ?? [], false);
        $defaultOptions = omoDecisionBlockSettingsGetDefaultVoteWeightOptions();
        $baseLabel = 'Souhaitable';
        foreach ($defaultOptions as $defaultOption) {
            if ((string)($defaultOption['weight'] ?? '') === '1') {
                $baseLabel = trim((string)($defaultOption['label'] ?? '')) !== '' ? trim((string)$defaultOption['label']) : $baseLabel;
                break;
            }
        }

        ob_start();
        ?>
        <div
            class="omo-decision-vote-weight-editor generic-soft-panel generic-soft-panel--stack"
            data-omo-decision-vote-weight-editor
            data-can-edit="<?= $canEdit ? '1' : '0' ?>"
            data-enabled="<?= $enabled ? '1' : '0' ?>"
            data-question="<?= $escape($question) ?>"
            data-options-json="<?= $escape(omoDecisionModuleEncodeJsonPayload($options, '[]')) ?>"
            data-default-options-json="<?= $escape(omoDecisionModuleEncodeJsonPayload($defaultOptions, '[]')) ?>"
            data-base-label="<?= $escape($baseLabel) ?>"
            data-base-weight-title="<?= $escape(t('decisions.edit.block_settings.vote_weighting_weight_base', [], $lang, $sourceLang)) ?>"
            data-weight-title="<?= $escape(t('decisions.edit.block_settings.vote_weighting_weight', [], $lang, $sourceLang)) ?>"
        >
            <label class="omo-decision-vote-weight-editor__field">
                <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.edit.block_settings.vote_weighting', [], $lang, $sourceLang)) ?></span>
                <span class="omo-decision-vote-weight-editor__toggle">
                    <input type="checkbox" value="1" data-omo-decision-vote-weight-enabled <?= $canEdit ? '' : 'disabled' ?>>
                    <span><?= $escape(t('decisions.edit.block_settings.vote_weighting_enable', [], $lang, $sourceLang)) ?></span>
                </span>
            </label>
            <div class="omo-decision-vote-weight-editor__content" data-omo-decision-vote-weight-content hidden>
                <label class="omo-decision-vote-weight-editor__field">
                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.edit.block_settings.vote_weighting_question', [], $lang, $sourceLang)) ?></span>
                    <input
                        type="text"
                        class="generic-form-control"
                        maxlength="190"
                        placeholder="<?= $escape(t('decisions.edit.block_settings.vote_weighting_placeholder_question', [], $lang, $sourceLang)) ?>"
                        data-omo-decision-vote-weight-question
                        <?= $canEdit ? '' : 'disabled' ?>
                    >
                </label>
                <div class="omo-decision-vote-weight-editor__toolbar">
                    <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.edit.block_settings.vote_weighting_options', [], $lang, $sourceLang)) ?></span>
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-vote-weight-add <?= $canEdit ? '' : 'disabled' ?>><?= $escape(t('decisions.edit.block_settings.vote_weighting_add', [], $lang, $sourceLang)) ?></button>
                </div>
                <div class="omo-decision-vote-weight-editor__list" data-omo-decision-vote-weight-list></div>
                <p class="omo-decision-vote-weight-editor__empty generic-meta" data-omo-decision-vote-weight-empty hidden><?= $escape(t('decisions.edit.block_settings.vote_weighting_fixed_hint', [], $lang, $sourceLang)) ?></p>
                <p class="omo-decision-vote-weight-editor__hint generic-meta"><?= $escape(t('decisions.edit.block_settings.vote_weighting_fixed_hint', [], $lang, $sourceLang)) ?></p>
            </div>
            <template data-omo-decision-vote-weight-row-template>
                <div class="omo-decision-vote-weight-editor__row" data-omo-decision-vote-weight-row>
                    <label class="omo-decision-vote-weight-editor__field">
                        <span class="generic-card-title generic-card-title--small" data-omo-decision-vote-weight-row-weight-title><?= $escape(t('decisions.edit.block_settings.vote_weighting_weight', [], $lang, $sourceLang)) ?></span>
                        <input type="number" min="0.01" step="0.01" class="generic-form-control" data-omo-decision-vote-weight-row-weight>
                    </label>
                    <label class="omo-decision-vote-weight-editor__field">
                        <span class="generic-card-title generic-card-title--small"><?= $escape(t('decisions.edit.block_settings.vote_weighting_label', [], $lang, $sourceLang)) ?></span>
                        <input type="text" maxlength="90" class="generic-form-control" data-omo-decision-vote-weight-row-label>
                    </label>
                    <div class="omo-decision-vote-weight-editor__actions">
                        <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-vote-weight-row-remove><?= $escape(t('decisions.edit.block_settings.vote_weighting_remove', [], $lang, $sourceLang)) ?></button>
                    </div>
                </div>
            </template>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('omoDecisionRenderVoteWeightResponseSelector')) {
    function omoDecisionRenderVoteWeightResponseSelector($lang, array $sourceLang, $escape, array $config = [])
    {
        $selection = omoDecisionBlockSettingsResolveVoteWeightSelection(
            $config['selected_weight'] ?? null,
            [
                'enabled' => !empty($config['enabled']),
                'question' => $config['question'] ?? '',
                'options' => $config['options'] ?? [],
            ]
        );

        if (empty($selection['enabled'])) {
            return '';
        }

        $question = trim((string)($config['question'] ?? ''));
        if ($question === '') {
            $question = t('decisions.edit.block_settings.vote_weighting_question', [], $lang, $sourceLang);
        }

        $options = array_values((array)($selection['options'] ?? []));
        if (count($options) === 0) {
            return '';
        }

        ob_start();
        ?>
        <div
            class="omo-decision-vote-weight-selector generic-soft-panel generic-soft-panel--stack"
            data-omo-decision-vote-weight-selector
            data-selected-weight="<?= $escape((string)$selection['weight']) ?>"
        >
            <span class="generic-card-title generic-card-title--small"><?= $escape($question) ?></span>
            <input type="hidden" name="vote_weight" value="<?= $escape((string)$selection['weight']) ?>" data-omo-decision-vote-weight-selector-input>
            <div class="omo-decision-vote-weight-selector__buttons omo-segmented" role="group" aria-label="<?= $escape($question) ?>">
                <?php foreach ($options as $option): ?>
                <?php
                $weight = omoDecisionBlockSettingsNormalizeVoteWeightNumber($option['weight'] ?? '1');
                $label = trim((string)($option['label'] ?? ''));
                if ($weight === '' || $label === '') {
                    continue;
                }
                $isSelected = $weight === (string)$selection['weight'];
                ?>
                <button
                    type="button"
                    class="omo-segmented__button omo-decision-vote-weight-selector__button<?= $isSelected ? ' is-active' : '' ?>"
                    data-omo-decision-vote-weight-selector-button="<?= $escape($weight) ?>"
                    aria-pressed="<?= $isSelected ? 'true' : 'false' ?>"
                >
                    <span><?= $escape($label) ?></span>
                    <span class="omo-decision-vote-weight-selector__weight"><?= $escape($weight) ?>x</span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('omoDecisionModuleParseUniqueTextItems')) {
    function omoDecisionModuleParseUniqueTextItems($rawValue)
    {
        $items = is_array($rawValue) ? $rawValue : preg_split('/\r\n|\r|\n/', (string)$rawValue);
        $items = is_array($items) ? $items : [];

        $cleaned = [];
        foreach ($items as $item) {
            $item = trim((string)$item);
            if ($item === '') {
                continue;
            }

            if (!in_array($item, $cleaned, true)) {
                $cleaned[] = $item;
            }
        }

        return $cleaned;
    }
}

if (!function_exists('omoDecisionNormalizeProposalInfoUrl')) {
    function omoDecisionNormalizeProposalInfoUrl($value)
    {
        $value = trim((string)$value);
        return $value !== '' ? $value : null;
    }
}

if (!function_exists('omoDecisionGetDefaultProposalContent')) {
    function omoDecisionGetDefaultProposalContent()
    {
        return [
            'title' => true,
            'description' => true,
            'url' => true,
        ];
    }
}

if (!function_exists('omoDecisionNormalizeProposalContent')) {
    function omoDecisionNormalizeProposalContent($value)
    {
        $default = omoDecisionGetDefaultProposalContent();
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($value)) {
            return $default;
        }

        $hasKnownKey = array_key_exists('title', $value)
            || array_key_exists('description', $value)
            || array_key_exists('url', $value)
            || array_key_exists('info_url', $value);
        if (!$hasKnownKey) {
            return $default;
        }

        $content = [
            'title' => !empty($value['title']),
            'description' => !empty($value['description']),
            'url' => array_key_exists('url', $value)
                ? !empty($value['url'])
                : !empty($value['info_url']),
        ];
        if (!$content['title'] && !$content['description'] && !$content['url']) {
            $content['description'] = true;
        }

        return $content;
    }
}

if (!function_exists('omoDecisionProposalTitleIsVisible')) {
    function omoDecisionProposalTitleIsVisible($proposalContent, $title)
    {
        $proposalContent = omoDecisionNormalizeProposalContent($proposalContent);
        return !empty($proposalContent['title']) && trim((string)$title) !== '';
    }
}

if (!function_exists('omoDecisionGetProposalLabel')) {
    function omoDecisionGetProposalLabel($proposal, $proposalContent)
    {
        $title = is_object($proposal) && method_exists($proposal, 'get')
            ? trim((string)$proposal->get('title'))
            : '';
        if (omoDecisionProposalTitleIsVisible($proposalContent, $title)) {
            return $title;
        }

        $position = is_object($proposal) && method_exists($proposal, 'get')
            ? (int)$proposal->get('position')
            : 0;
        return $position > 0 ? 'Proposition ' . $position : 'Proposition';
    }
}

if (!function_exists('omoDecisionShuffleProposalsForParticipant')) {
    function omoDecisionShuffleProposalsForParticipant(array $proposals, array $context, $scope = '')
    {
        if (count($proposals) < 2) {
            return $proposals;
        }

        $participantId = omoDecisionGetContextParticipantId($context);
        $viewerKey = $participantId > 0
            ? 'participant:' . $participantId
            : ((int)($context['currentUserId'] ?? 0) > 0
                ? 'user:' . (int)$context['currentUserId']
                : 'token:' . trim((string)($context['publicToken'] ?? '')));
        $scope = trim((string)$scope);
        usort($proposals, static function ($left, $right) use ($viewerKey, $scope) {
            $leftId = is_object($left) && method_exists($left, 'getId') ? (int)$left->getId() : 0;
            $rightId = is_object($right) && method_exists($right, 'getId') ? (int)$right->getId() : 0;
            return strcmp(
                hash('sha256', $scope . '|' . $viewerKey . '|' . $leftId),
                hash('sha256', $scope . '|' . $viewerKey . '|' . $rightId)
            );
        });
        return $proposals;
    }
}

if (!function_exists('omoDecisionRenderProposalContentSettings')) {
    function omoDecisionRenderProposalContentSettings(array $content, $lang, array $sourceLang, $escape, $canEdit, $mode = 'inline')
    {
        $content = omoDecisionNormalizeProposalContent($content);
        if ($mode === 'hidden') {
            return '<input type="hidden" name="proposal_content_title" value="' . ($content['title'] ? '1' : '') . '" data-omo-decision-proposal-content-hidden-title>'
                . '<input type="hidden" name="proposal_content_description" value="' . ($content['description'] ? '1' : '') . '" data-omo-decision-proposal-content-hidden-description>'
                . '<input type="hidden" name="proposal_content_url" value="' . ($content['url'] ? '1' : '') . '" data-omo-decision-proposal-content-hidden-url>';
        }
        $disabled = $canEdit ? '' : ' disabled';
        $titleAttributes = ' data-omo-decision-proposal-content-popup-title';
        $descriptionAttributes = ' data-omo-decision-proposal-content-popup-description';
        $urlAttributes = ' data-omo-decision-proposal-content-popup-url';
        ob_start();
        ?>
        <div class="generic-soft-panel generic-soft-panel--stack omo-decision-proposal-content-settings">
            <strong><?= $escape(t('decisions.edit.proposal_content.title', [], $lang, $sourceLang)) ?></strong>
            <p class="generic-meta"><?= $escape(t('decisions.edit.proposal_content.hint', [], $lang, $sourceLang)) ?></p>
            <label class="omo-decision-proposal-content-settings__check">
                <input type="checkbox" value="1"<?= $content['title'] ? ' checked' : '' ?><?= $disabled . $titleAttributes ?>>
                <span><?= $escape(t('decisions.edit.proposal_content.title_field', [], $lang, $sourceLang)) ?></span>
            </label>
            <label class="omo-decision-proposal-content-settings__check">
                <input type="checkbox" value="1"<?= $content['description'] ? ' checked' : '' ?><?= $disabled . $descriptionAttributes ?>>
                <span><?= $escape(t('decisions.edit.proposal_content.description_field', [], $lang, $sourceLang)) ?></span>
            </label>
            <label class="omo-decision-proposal-content-settings__check">
                <input type="checkbox" value="1"<?= $content['url'] ? ' checked' : '' ?><?= $disabled . $urlAttributes ?>>
                <span><?= $escape(t('decisions.edit.proposal_content.url_field', [], $lang, $sourceLang)) ?></span>
            </label>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('omoDecisionBuildProposalContentSummary')) {
    function omoDecisionBuildProposalContentSummary(array $content, $lang, array $sourceLang)
    {
        $content = omoDecisionNormalizeProposalContent($content);
        $labels = [];
        if ($content['title']) {
            $labels[] = t('decisions.edit.proposal_content.title_field', [], $lang, $sourceLang);
        }
        if ($content['description']) {
            $labels[] = t('decisions.edit.proposal_content.description_field', [], $lang, $sourceLang);
        }
        if ($content['url']) {
            $labels[] = t('decisions.edit.proposal_content.url_field', [], $lang, $sourceLang);
        }
        return implode(', ', $labels);
    }
}

if (!function_exists('omoDecisionBuildProposalItemsFromInput')) {
    function omoDecisionBuildProposalItemsFromInput($titles, $descriptions = [], $infoUrls = [], $proposalIds = [], $proposalContent = null)
    {
        $titles = is_array($titles) ? array_values($titles) : [];
        $descriptions = is_array($descriptions) ? array_values($descriptions) : [];
        $infoUrls = is_array($infoUrls) ? array_values($infoUrls) : [];
        $proposalIds = is_array($proposalIds) ? array_values($proposalIds) : [];
        $proposalContent = omoDecisionNormalizeProposalContent($proposalContent);

        $rowCount = max(count($titles), count($descriptions), count($infoUrls), count($proposalIds));
        $items = [];

        for ($index = 0; $index < $rowCount; $index++) {
            $title = trim((string)($titles[$index] ?? ''));
            $description = trim((string)($descriptions[$index] ?? ''));
            $infoUrl = omoDecisionNormalizeProposalInfoUrl($infoUrls[$index] ?? '');

            if (!$proposalContent['description']) {
                $description = '';
            }
            if (!$proposalContent['url']) {
                $infoUrl = null;
            }

            if ($title === '' && $description === '' && $infoUrl === null) {
                continue;
            }

            $items[] = [
                'id' => max(0, (int)($proposalIds[$index] ?? 0)),
                'title' => $title,
                'description' => $description !== '' ? $description : null,
                'info_url' => $infoUrl,
            ];
        }

        return $items;
    }
}

if (!function_exists('omoDecisionBuildProposalItemsFromDecision')) {
    function omoDecisionBuildProposalItemsFromDecision($decision, $minimumCount = 0)
    {
        $items = [];
        if ((is_object($decision) && method_exists($decision, 'getProposals'))) {
            foreach ($decision->getProposals(true) as $proposal) {
                if (!$proposal instanceof DecisionProposal) {
                    continue;
                }

                $items[] = [
                    'id' => (int)$proposal->getId(),
                    'title' => trim((string)$proposal->get('title')),
                    'description' => trim((string)$proposal->get('description')) ?: null,
                    'info_url' => omoDecisionNormalizeProposalInfoUrl($proposal->get('info_url')),
                ];
            }
        }

        while (count($items) < max(0, (int)$minimumCount)) {
            $items[] = [
                'id' => 0,
                'title' => '',
                'description' => null,
                'info_url' => null,
            ];
        }

        return $items;
    }
}

if (!function_exists('omoDecisionCanSaveEmptyConsultationProposalList')) {
    function omoDecisionCanSaveEmptyConsultationProposalList($allowConsultationProposals, $consultationStartAt, $consultationEndAt)
    {
        if (empty($allowConsultationProposals)) {
            return false;
        }

        if (
            (!($consultationStartAt instanceof \DateTimeInterface) && trim((string)$consultationStartAt) === '')
            || (!($consultationEndAt instanceof \DateTimeInterface) && trim((string)$consultationEndAt) === '')
        ) {
            return false;
        }

        try {
            $consultationStart = $consultationStartAt instanceof \DateTimeInterface
                ? $consultationStartAt
                : new \DateTimeImmutable(trim((string)$consultationStartAt));
            $consultationEnd = $consultationEndAt instanceof \DateTimeInterface
                ? $consultationEndAt
                : new \DateTimeImmutable(trim((string)$consultationEndAt));
        } catch (\Throwable $exception) {
            return false;
        }

        return $consultationStart instanceof \DateTimeInterface
            && $consultationEnd instanceof \DateTimeInterface
            && $consultationStart < $consultationEnd;
    }
}

if (!function_exists('omoDecisionResponseIsAnonymous')) {
    function omoDecisionResponseIsAnonymous($response, $methodKey)
    {
        if (!$response instanceof \dbObject\DecisionResponse) {
            return false;
        }

        $parameters = omoDecisionModuleDecodeParameters($response->get('parameters'));
        $methodParameters = omoDecisionModuleGetMethodParameters($parameters, $methodKey);

        return !empty($methodParameters['is_anonymous']);
    }
}

if (!function_exists('omoDecisionRenderProposalSupplementHtml')) {
    function omoDecisionRenderProposalSupplementHtml($description, $infoUrl, $escape, $descriptionClass = '', $linkClass = '')
    {
        if (!is_callable($escape)) {
            $escape = 'omoApiEscape';
        }

        $description = trim((string)$description);
        $infoUrl = omoDecisionNormalizeProposalInfoUrl($infoUrl);
        if ($description === '' && $infoUrl === null) {
            return '';
        }

        $html = '';
        if ($description !== '') {
            $descriptionClasses = trim((string)$descriptionClass);
            $descriptionClasses = trim($descriptionClasses . ' omo-proposal-html-render');
            $classAttribute = $descriptionClasses !== '' ? ' class="' . $escape($descriptionClasses) . '"' : '';
            $descriptionHtml = \dbObject\PropertyFormat::sanitizeHtml($description);
            if ($descriptionHtml !== '' && !preg_match('/<[^>]+>/', $descriptionHtml)) {
                $descriptionHtml = nl2br($descriptionHtml);
            }
            $html .= '<div' . $classAttribute . '>' . $descriptionHtml . '</div>';
        }

        if ($infoUrl !== null) {
            $classAttribute = trim((string)$linkClass) !== '' ? ' class="' . $escape(trim((string)$linkClass)) . '"' : '';
            $html .= '<a' . $classAttribute . ' href="' . $escape($infoUrl) . '" target="_blank" rel="noopener noreferrer">Plus d infos</a>';
        }

        return $html;
    }
}

if (!function_exists('omoDecisionRenderGovernanceChanges')) {
    function omoDecisionRenderGovernanceChanges(DecisionProposal $proposal, $escape)
    {
        if (!$proposal->hasGovernanceActions()) {
            return '';
        }
        if (!is_callable($escape)) {
            $escape = 'omoApiEscape';
        }
        $labels = [
            \dbObject\DecisionGovernanceAction::TYPE_RULE_CREATE => 'Créer la règle',
            \dbObject\DecisionGovernanceAction::TYPE_RULE_UPDATE => 'Modifier la règle',
            \dbObject\DecisionGovernanceAction::TYPE_RULE_DELETE => 'Supprimer la règle',
            \dbObject\DecisionGovernanceAction::TYPE_HOLON_CREATE => 'Créer le rôle',
            \dbObject\DecisionGovernanceAction::TYPE_HOLON_UPDATE => 'Modifier le rôle',
            \dbObject\DecisionGovernanceAction::TYPE_HOLON_DELETE => 'Supprimer le rôle',
        ];
        $items = [];
        foreach ($proposal->getGovernanceActions() as $action) {
            if (!$action instanceof \dbObject\DecisionGovernanceAction
                || (string)$action->get('status') === \dbObject\DecisionGovernanceAction::STATUS_REMOVED) {
                continue;
            }
            $actionType = trim((string)$action->get('action_type'));
            $before = \dbObject\DecisionGovernanceAction::normalizeState($action->get('before_state'));
            $after = \dbObject\DecisionGovernanceAction::normalizeState($action->get('after_state'));
            $isRule = str_starts_with($actionType, 'rule.');
            $isDelete = str_ends_with($actionType, '.delete');
            $state = $isDelete ? $before : $after;
            $target = trim((string)($isRule ? ($state['title'] ?? '') : ($state['name'] ?? '')));
            if (str_ends_with($actionType, '.create')) {
                $summary = 'Cette proposition crée ' . ($isRule ? 'la règle' : 'le rôle') . '.';
            } elseif ($isDelete) {
                $summary = 'Cette proposition supprime ' . ($isRule ? 'la règle' : 'le rôle') . '.';
            } else {
                $summary = 'Cette proposition modifie ' . ($isRule ? 'la règle' : 'le rôle') . '.';
            }

            $heading = trim((string)($labels[$actionType] ?? 'Modification'));
            if ($target !== '') {
                $heading .= ' : ' . $target;
            }
            $authorities = [];
            if ($isRule) {
                $authorityIds = array_values(array_filter([
                    (int)($before['IDauthority'] ?? 0),
                    (int)($after['IDauthority'] ?? 0),
                ]));
                foreach (\dbObject\Authority::getLabelsByIds($authorityIds) as $authorityId => $authorityLabel) {
                    $authorities[] = ['id' => (int)$authorityId, 'label' => (string)$authorityLabel];
                }
            }
            $payload = base64_encode((string)json_encode([
                'governanceAction' => [
                    'type' => $actionType,
                    'before' => $before,
                    'after' => $after,
                ],
                'authorities' => $authorities,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $items[] = '<section class="omo-governance-proposal-changes__item">'
                . '<strong>' . $escape($heading) . '</strong>'
                . '<p class="omo-governance-proposal-changes__summary">' . $escape($summary) . '</p>'
                . '<details class="omo-change-details" data-omo-change-details-payload="' . $escape($payload) . '">'
                . '<summary>Détail</summary><div data-omo-change-details-container></div></details>'
                . '</section>';
        }
        return count($items) > 0
            ? '<div class="omo-governance-proposal-changes generic-soft-panel generic-soft-panel--stack"><strong>Modifications proposées</strong>' . implode('', $items) . '</div>'
            : '';
    }
}

if (!function_exists('omoDecisionGetContextAccountUserId')) {
    function omoDecisionGetContextAccountUserId(array $context)
    {
        if ((string)($context['accessMode'] ?? '') === 'public') {
            $participant = $context['participant'] ?? null;
            if (!$participant instanceof DecisionParticipant || (int)$participant->get('active') !== 1) {
                return 0;
            }

            $status = DecisionParticipant::normalizeStatus($participant->get('status'));
            if (in_array($status, [DecisionParticipant::STATUS_DECLINED, DecisionParticipant::STATUS_REVOKED], true)) {
                return 0;
            }

            return (int)$participant->get('IDuser');
        }

        return (int)($context['currentUserId'] ?? 0);
    }
}

if (!function_exists('omoDecisionLoadProposalForContext')) {
    function omoDecisionLoadProposalForContext($proposalId, array $context, $activeOnly = true)
    {
        $decision = $context['decision'] ?? null;
        $proposal = new DecisionProposal();
        if (
            !$decision instanceof DecisionProcess
            || (int)$proposalId <= 0
            || !$proposal->load((int)$proposalId)
            || (int)$proposal->get('IDdecision_process') !== (int)$decision->getId()
            || ($activeOnly && (int)$proposal->get('active') !== 1)
        ) {
            return null;
        }

        return $proposal;
    }
}

if (!function_exists('omoDecisionGetContextParticipant')) {
    function omoDecisionGetContextParticipant(array $context)
    {
        $participant = $context['participant'] ?? null;
        if (
            !($participant instanceof DecisionParticipant)
            || (int)$participant->getId() <= 0
            || (int)$participant->get('active') !== 1
        ) {
            return null;
        }

        $status = DecisionParticipant::normalizeStatus($participant->get('status'));
        if (in_array($status, [
            DecisionParticipant::STATUS_DECLINED,
            DecisionParticipant::STATUS_REVOKED,
        ], true)) {
            return null;
        }

        return $participant;
    }
}

if (!function_exists('omoDecisionGetContextParticipantId')) {
    function omoDecisionGetContextParticipantId(array $context)
    {
        $participant = omoDecisionGetContextParticipant($context);
        return $participant instanceof DecisionParticipant ? (int)$participant->getId() : 0;
    }
}

if (!function_exists('omoDecisionCanAccessProposalDiscussion')) {
    function omoDecisionCanAccessProposalDiscussion(DecisionProposal $proposal, array $context)
    {
        $decision = $context['decision'] ?? null;
        return (
                omoDecisionGetContextAccountUserId($context) > 0
                || omoDecisionGetContextParticipantId($context) > 0
            )
            && !empty($context['canView'])
            && $proposal->areDiscussionsEnabled()
            && (int)$proposal->get('active') === 1
            && $decision instanceof DecisionProcess
            && !$decision->hasConsultationEnded();
    }
}

if (!function_exists('omoDecisionFormatProposalDateLabel')) {
    function omoDecisionFormatProposalDateLabel($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d.m.Y H:i');
        }

        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }
        try {
            return (new \DateTimeImmutable($value))->format('d.m.Y H:i');
        } catch (\Throwable $exception) {
            return $value;
        }
    }
}

if (!function_exists('omoDecisionResolveExternalParticipantName')) {
    function omoDecisionResolveExternalParticipantName(DecisionParticipant $participant)
    {
        $displayName = trim((string)$participant->get('display_name'));
        $email = trim((string)$participant->get('email'));
        $candidate = $displayName !== '' ? $displayName : $email;
        $atPosition = strrpos($candidate, '@');
        if ($atPosition !== false) {
            $candidate = substr($candidate, 0, $atPosition);
        }

        return trim((string)$candidate);
    }
}

if (!function_exists('omoDecisionResolveProposalParticipantName')) {
    function omoDecisionResolveProposalParticipantName(DecisionProcess $decision, $userId, $fallbackName = '', $anonymous = false)
    {
        $userId = (int)$userId;
        $fallbackName = trim((string)$fallbackName);
        $isAdministrator = $userId > 0 && $userId === (int)$decision->get('IDuser');
        if ($userId > 0 && !empty($anonymous) && !$isAdministrator) {
            return $decision->getAnonymousPseudonymForUser($userId);
        }

        if ($userId > 0) {
            $user = new User();
            if ($user->load($userId)) {
                $displayName = trim((string)$user->getScopedDisplayName((int)$decision->get('IDorganization')));
                if ($displayName !== '') {
                    return $displayName;
                }
            }
        }

        return $fallbackName;
    }
}

if (!function_exists('omoDecisionResolveResponseParticipantName')) {
    function omoDecisionResolveResponseParticipantName(DecisionProcess $decision, DecisionResponse $response): string
    {
        $participant = new DecisionParticipant();
        if (!$participant->load((int)$response->get('IDdecision_participant'))) {
            return '';
        }
        return omoDecisionResolveProposalParticipantName(
            $decision,
            (int)$participant->get('IDuser'),
            omoDecisionResolveExternalParticipantName($participant),
            false
        );
    }
}

if (!function_exists('omoDecisionGetProposalDiscussionSummary')) {
    function omoDecisionGetProposalDiscussionSummary(DecisionProposal $proposal, array $context)
    {
        static $summaryCache = [];
        $decision = $context['decision'] ?? null;
        $decisionGroup = $context['decisionGroup'] ?? null;
        if (!$decision instanceof DecisionProcess || !$decisionGroup instanceof DecisionGroup) {
            return [];
        }

        $viewerUserId = omoDecisionGetContextAccountUserId($context);
        $viewerParticipantId = omoDecisionGetContextParticipantId($context);
        $cacheKey = implode(':', [
            (int)$decision->get('IDorganization'),
            (int)$decision->getId(),
            (int)$decisionGroup->getId(),
            (int)$viewerUserId,
            (int)$viewerParticipantId,
        ]);
        if (!array_key_exists($cacheKey, $summaryCache)) {
            $proposalIds = [];
            foreach ($decisionGroup->getProposals(true) as $groupProposal) {
                if ($groupProposal instanceof DecisionProposal && (int)$groupProposal->getId() > 0) {
                    $proposalIds[] = (int)$groupProposal->getId();
                }
            }
            $summaryCache[$cacheKey] = ChatThread::getSubjectDiscussionSummaries(
                (int)$decision->get('IDorganization'),
                ChatThread::SUBJECT_DECISION_PROPOSAL,
                $proposalIds,
                $viewerUserId,
                $viewerParticipantId
            );
        }

        return is_array($summaryCache[$cacheKey][(int)$proposal->getId()] ?? null)
            ? $summaryCache[$cacheKey][(int)$proposal->getId()]
            : [];
    }
}

if (!function_exists('omoDecisionRenderProposalMetadata')) {
    function omoDecisionRenderProposalMetadata(DecisionProposal $proposal, array $context, $escape)
    {
        $decision = $context['decision'] ?? null;
        if (!$decision instanceof DecisionProcess) {
            return '';
        }

        $isAnonymous = $proposal->isAnonymous();
        $authorUserId = $proposal->getAuthorUserId();
        $authorFallbackName = '';
        if (!$isAnonymous && method_exists($proposal, 'getAuthorParticipant')) {
            $authorParticipant = $proposal->getAuthorParticipant();
            if ($authorParticipant instanceof DecisionParticipant) {
                $authorFallbackName = omoDecisionResolveExternalParticipantName($authorParticipant);
            }
        }
        $authorName = omoDecisionResolveProposalParticipantName($decision, $authorUserId, $authorFallbackName, $isAnonymous);
        if ($authorName === '') {
            $authorName = $isAnonymous
                ? omoDecisionProposalT('decisions.proposals.metadata.anonymous_author')
                : omoDecisionProposalT('decisions.proposals.metadata.unknown_author');
        }

        $createdAt = $proposal->get('created_at');
        $updatedAt = $proposal->get('updated_at');
        $createdValue = $createdAt instanceof \DateTimeInterface ? $createdAt->format('Y-m-d H:i:s') : trim((string)$createdAt);
        $updatedValue = $updatedAt instanceof \DateTimeInterface ? $updatedAt->format('Y-m-d H:i:s') : trim((string)$updatedAt);
        $wasModified = $createdValue !== '' && $updatedValue !== '' && $updatedValue > $createdValue;
        $proposalDate = $wasModified ? $updatedAt : $createdAt;
        if ($proposalDate instanceof \DateTimeInterface) {
            $dateLabel = $proposalDate->format('d.m.Y');
        } else {
            try {
                $dateLabel = (new \DateTimeImmutable(trim((string)$proposalDate)))->format('d.m.Y');
            } catch (\Throwable $exception) {
                $dateLabel = '';
            }
        }

        $authorLine = '<span>' . $escape(omoDecisionProposalT('decisions.proposals.metadata.proposed_by')) . ' <strong>' . $escape($authorName) . '</strong>';
        if ($dateLabel !== '') {
            $authorLine .= '<span data-omo-proposal-date>, '
                . $escape($wasModified
                    ? omoDecisionProposalT('decisions.proposals.metadata.modified_on')
                    : omoDecisionProposalT('decisions.proposals.metadata.on'))
                . ' '
                . $escape($dateLabel)
                . '</span>';
        }
        $authorLine .= '</span>';
        $items = [$authorLine];

        if ($proposal->areDiscussionsEnabled() && !$decision->hasConsultationEnded()) {
            $summary = omoDecisionGetProposalDiscussionSummary($proposal, $context);
            $totalMessages = (int)($summary['total_messages'] ?? 0);
            $lastViewerMessageId = (int)($summary['last_viewer_message_id'] ?? 0);
            if ($lastViewerMessageId > 0) {
                $newMessages = max(0, (int)($summary['messages_since_viewer'] ?? 0));
                $items[] = '<span class="omo-proposal-meta__discussion">'
                    . ($newMessages === 0
                        ? $escape(omoDecisionProposalT('decisions.proposals.metadata.no_new_messages'))
                        : $escape(omoDecisionProposalT('decisions.proposals.metadata.new_messages', ['count' => $newMessages])))
                    . '</span>';
            } elseif ($totalMessages > 0) {
                $lastMessageType = (string)($summary['last_message_type'] ?? '');
                $lastMessageUserId = (int)($summary['last_message_user_id'] ?? 0);
                $lastMessageParticipantId = (int)($summary['last_message_participant_id'] ?? 0);
                if ($lastMessageType === 'system') {
                    $lastAuthor = omoDecisionProposalT('decisions.proposals.metadata.system');
                } elseif ($isAnonymous && $lastMessageUserId <= 0 && $lastMessageParticipantId > 0) {
                    $lastAuthor = $decision->getAnonymousPseudonymForParticipant($lastMessageParticipantId);
                } else {
                    $lastAuthor = omoDecisionResolveProposalParticipantName(
                        $decision,
                        $lastMessageUserId,
                        trim((string)($summary['last_message_author_name'] ?? '')),
                        $isAnonymous
                    );
                }
                if ($lastAuthor === '') {
                    $lastAuthor = (string)($summary['last_message_type'] ?? '') === 'system'
                        ? omoDecisionProposalT('decisions.proposals.metadata.system')
                        : omoDecisionProposalT('decisions.proposals.metadata.participant');
                }
                $lastDate = omoDecisionFormatProposalDateLabel($summary['last_message_at'] ?? '');
                $lastDetails = $lastDate !== ''
                    ? ' · ' . $escape(omoDecisionProposalT('decisions.proposals.metadata.last_message', ['date' => $lastDate, 'author' => $lastAuthor]))
                    : '';
                $items[] = '<span class="omo-proposal-meta__discussion">'
                    . $escape(omoDecisionProposalT('decisions.proposals.metadata.message_count', ['count' => $totalMessages]))
                    . $lastDetails
                    . '</span>';
            } else {
                $items[] = '<span class="omo-proposal-meta__discussion">' . $escape(omoDecisionProposalT('decisions.proposals.metadata.no_messages')) . '</span>';
            }
        }

        return '<div class="omo-proposal-meta">' . implode('', $items) . '</div>';
    }
}

if (!function_exists('omoDecisionCanEditProposalFromPublicInterface')) {
    function omoDecisionCanEditProposalFromPublicInterface(DecisionProposal $proposal, array $context)
    {
        $decision = $context['decision'] ?? null;
        $userId = omoDecisionGetContextAccountUserId($context);
        $participantId = omoDecisionGetContextParticipantId($context);
        return $decision instanceof DecisionProcess
            && !$decision->hasConsultationEnded()
            && !$decision->hasEvaluationStarted()
            && $proposal->canBeEditedByActor($userId, $participantId);
    }
}

if (!function_exists('omoDecisionBuildProposalDiscussionContextPayload')) {
    function omoDecisionBuildProposalDiscussionContextPayload(array $context)
    {
        $decisionGroup = $context['decisionGroup'] ?? null;
        $decision = $context['decision'] ?? null;
        $methodConfig = omoDecisionBuildMethodConfig($decisionGroup instanceof DecisionGroup ? $decisionGroup : $decision);
        return [
            'oid' => (int)($context['organizationId'] ?? 0),
            'cid' => (int)($context['targetHolonId'] ?? 0),
            'id' => $decision instanceof DecisionProcess ? (int)$decision->getId() : 0,
            'gid' => $decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0,
            'method' => $decisionGroup instanceof DecisionGroup
                ? trim((string)$decisionGroup->get('evaluation_method'))
                : ($decision instanceof DecisionProcess ? trim((string)$decision->get('evaluation_method')) : ''),
            'intent' => 'view',
            'token' => trim((string)($context['publicToken'] ?? '')),
            'proposalContent' => omoDecisionNormalizeProposalContent($methodConfig['proposal_content'] ?? null),
        ];
    }
}

if (!function_exists('omoDecisionRenderProposalDiscussionAssets')) {
    function omoDecisionRenderProposalDiscussionAssets()
    {
        static $alreadyRendered = false;
        if ($alreadyRendered) {
            return '';
        }

        $alreadyRendered = true;
        return '<link rel="stylesheet" href="/common/chat/thread.css?v=20260821-unified-chat-errors">'
            . '<link rel="stylesheet" href="/common/choice/proposal-discussion.css?v=20260821-unified-chat">'
            . '<link rel="stylesheet" href="/common/choice/change-details.css?v=20260816-2">'
            . '<script src="/common/choice/word-diff.js?v=20260815" defer></script>'
            . '<script src="/common/choice/change-details.js?v=20260816-governance-details" defer></script>'
            . '<script src="/common/choice/highlight-palette.js" defer></script>'
            . '<script src="/omo/assets/js/simple-html-field.js?v=20260903-toolbar-insert-focus" defer></script>'
            . '<script src="/common/choice/decision-anonymity.js?v=20260825-named-vote" defer></script>'
            . '<script src="/common/choice/decision-notifications.js?v=20260825-topbar-errors" defer></script>'
            . '<script src="/common/choice/proposal-html.js?v=20260824-proposal-content-refresh" defer></script>'
            . '<script src="/common/choice/proposal-discussion.js?v=20260817-generic-actions" defer></script>';
    }
}

if (!function_exists('omoDecisionRenderOneProposalAtATimeAssets')) {
    function omoDecisionRenderOneProposalAtATimeAssets()
    {
        static $alreadyRendered = false;
        if ($alreadyRendered) {
            return '';
        }

        $alreadyRendered = true;
        return '<link rel="stylesheet" href="/common/choice/one-proposal-at-a-time.css?v=20260825-1">'
            . '<script src="/common/choice/one-proposal-at-a-time.js?v=20260825-1" defer></script>';
    }
}

if (!function_exists('omoDecisionRenderProposalDiscussionActions')) {
    function omoDecisionRenderProposalDiscussionActions(DecisionProposal $proposal, array $context, $escape)
    {
        if (!is_callable($escape)) {
            $escape = 'omoApiEscape';
        }
        $canDiscuss = omoDecisionCanAccessProposalDiscussion($proposal, $context);
        $canEdit = omoDecisionCanEditProposalFromPublicInterface($proposal, $context);
        $discussionSummary = $canDiscuss ? omoDecisionGetProposalDiscussionSummary($proposal, $context) : [];
        $discussionMessageCount = max(0, (int)($discussionSummary['total_messages'] ?? 0));
        $contextPayload = omoDecisionModuleEncodeJsonPayload(omoDecisionBuildProposalDiscussionContextPayload($context));
        $html = '';
        if ($canDiscuss || $canEdit) {
            $html = '<div class="omo-proposal-discussion-actions" data-omo-proposal-discussion-actions>';
        }
        if ($canDiscuss) {
            $discussionCountHidden = $discussionMessageCount > 0 ? '' : ' hidden';
            $discussionCountLabel = omoDecisionProposalT('decisions.proposals.metadata.message_count_label', ['count' => $discussionMessageCount]);
            $html .= '<span class="omo-proposal-discussion-count" data-omo-proposal-discussion-count data-message-count="' . $escape($discussionMessageCount) . '" title="' . $escape($discussionCountLabel) . '" aria-label="' . $escape($discussionCountLabel) . '"' . $discussionCountHidden . '>'
                    . '<span class="omo-proposal-discussion-count-value" data-omo-proposal-discussion-count-value>' . $escape($discussionMessageCount) . '</span>'
                . '</span>';
            $html .= '<button type="button" class="generic-action-button generic-action-button--secondary omo-proposal-discussion-button"'
                    . ' data-omo-proposal-discussion-open'
                    . ' data-proposal-id="' . (int)$proposal->getId() . '"'
                    . ' data-proposal-context="' . $escape($contextPayload) . '">'
                    . '<span class="omo-proposal-action-label omo-proposal-action-label--full">' . $escape(omoDecisionProposalT('decisions.proposals.action.discuss_proposal')) . '</span>'
                    . '<span class="omo-proposal-action-label omo-proposal-action-label--short">' . $escape(omoDecisionProposalT('decisions.proposals.action.discuss')) . '</span>'
                . '</button>';
        }
        if ($canEdit) {
            $html .= '<button type="button" class="generic-action-button generic-action-button--secondary omo-proposal-edit-button"'
                    . ' data-omo-proposal-edit-open'
                    . ' data-proposal-id="' . (int)$proposal->getId() . '"'
                    . ' data-proposal-context="' . $escape($contextPayload) . '">'
                    . '<span class="omo-proposal-action-label omo-proposal-action-label--full">' . $escape(omoDecisionProposalT('decisions.proposals.action.edit_proposal')) . '</span>'
                    . '<span class="omo-proposal-action-label omo-proposal-action-label--short">' . $escape(omoDecisionProposalT('decisions.proposals.action.edit')) . '</span>'
                . '</button>';
            $html .= '<div class="omo-proposal-action-menu generic-menu" data-omo-proposal-action-menu>'
                    . '<button type="button" class="omo-proposal-action-menu__toggle generic-menu-toggle" data-omo-proposal-action-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="' . $escape(omoDecisionProposalT('decisions.proposals.action.other_actions')) . '">...</button>'
                    . '<div class="omo-proposal-action-menu__panel generic-menu-panel generic-menu-panel--wide" data-omo-proposal-action-menu-panel role="menu" hidden>'
                        . '<button type="button" class="generic-menu-item generic-menu-item--danger" data-omo-proposal-delete-open data-proposal-id="' . (int)$proposal->getId() . '" data-proposal-context="' . $escape($contextPayload) . '" role="menuitem">' . $escape(omoDecisionProposalT('decisions.proposals.action.delete')) . '</button>'
                    . '</div>'
                . '</div>';
        }
        if ($canDiscuss || $canEdit) {
            $html .= '</div>';
        }

        return $html . omoDecisionRenderProposalMetadata($proposal, $context, $escape);
    }
}

if (!function_exists('omoDecisionModuleRenderReadonlyMeta')) {
    function omoDecisionModuleRenderReadonlyMeta($label, $value, $escape, $extraClass = '')
    {
        if (trim((string)$value) === '') {
            return '';
        }

        $extraClass = trim((string)$extraClass);
        if ($extraClass !== '') {
            $extraClass = ' ' . $extraClass;
        }

        return '<div class="generic-soft-panel generic-soft-panel--stack' . $extraClass . '">'
            . '<span class="generic-card-title generic-card-title--small">' . $escape($label) . '</span>'
            . '<strong>' . $escape($value) . '</strong>'
            . '</div>';
    }
}

if (!function_exists('omoDecisionModuleEncodeJsonPayload')) {
    function omoDecisionModuleEncodeJsonPayload(array $payload, $fallback = '{}')
    {
        $encoded = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        return is_string($encoded) ? $encoded : (string)$fallback;
    }
}

if (!function_exists('omoDecisionEnsureMethodSharedLoaded')) {
    function omoDecisionEnsureMethodSharedLoaded($method)
    {
        if (!function_exists('omoDecisionGetModuleDefinition')) {
            $registryFile = __DIR__ . '/registry.php';
            if (is_file($registryFile)) {
                require_once $registryFile;
            }
        }

        $definition = function_exists('omoDecisionGetModuleDefinition')
            ? omoDecisionGetModuleDefinition($method)
            : null;

        if ($definition && !empty($definition['shared_file']) && is_file((string)$definition['shared_file'])) {
            require_once (string)$definition['shared_file'];
        }

        return $definition;
    }
}

if (!function_exists('omoDecisionBuildMethodConfig')) {
    function omoDecisionBuildMethodConfig($decision)
    {
        if (
            !($decision instanceof DecisionProcess)
            && !($decision instanceof DecisionGroup)
        ) {
            return [];
        }

        $method = DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method'));
        omoDecisionEnsureMethodSharedLoaded($method);

        switch ($method) {
            case DecisionProcess::METHOD_SIMPLE_VOTE:
                return function_exists('omoDecisionVoteBuildConfig')
                    ? omoDecisionVoteBuildConfig($decision)
                    : [];

            case DecisionProcess::METHOD_CONSULTATION_ONLY:
                return function_exists('omoDecisionConsultationOnlyBuildConfig')
                    ? omoDecisionConsultationOnlyBuildConfig($decision)
                    : [];

            case DecisionProcess::METHOD_MAJORITY_JUDGMENT:
                return function_exists('omoDecisionMajorityJudgmentBuildConfig')
                    ? omoDecisionMajorityJudgmentBuildConfig($decision)
                    : [];

            case DecisionProcess::METHOD_CONSENT:
                return function_exists('omoDecisionConsentBuildConfig')
                    ? omoDecisionConsentBuildConfig($decision)
                    : [];
        }

        return [];
    }
}

if (!function_exists('omoDecisionProposalGetSourceLang')) {
    function omoDecisionProposalGetSourceLang()
    {
        return [
            'decisions.proposals.add_title' => [
                'text' => 'Ajouter une proposition',
                'context' => 'Title of the public consultation proposal form.',
            ],
            'decisions.proposals.open_intro' => [
                'text' => 'La consultation est ouverte. Vous pouvez proposer une nouvelle option avec son contexte et un lien d’information.',
                'context' => 'Introduction shown above the public consultation proposal form.',
            ],
            'decisions.proposals.order_hint' => [
                'text' => 'La proposition sera ajoutée à la fin de la liste. Son ordre détaillé reste gérable ensuite dans l’interface principale.',
                'context' => 'Helper text explaining how a public consultation proposal is initially ordered.',
            ],
            'decisions.proposals.feedback_success_one' => [
                'text' => 'Proposition ajoutée à la consultation.',
                'context' => 'Success message after adding one public consultation proposal.',
            ],
            'decisions.proposals.feedback_success_other' => [
                'text' => '{count} propositions ajoutées à la consultation.',
                'context' => 'Success message after adding several public consultation proposals.',
            ],
            'decisions.proposals.feedback_duplicate' => [
                'text' => 'Toutes les propositions soumises existent déjà.',
                'context' => 'Feedback shown when submitted public proposals are all duplicates.',
            ],
            'decisions.proposals.feedback_empty' => [
                'text' => 'Ajoutez au moins une proposition.',
                'context' => 'Feedback shown when no public proposal was submitted.',
            ],
            'decisions.proposals.feedback_denied' => [
                'text' => 'Ce lien ne permet pas d’ajouter des propositions pour le moment.',
                'context' => 'Feedback shown when the public proposal link is no longer available.',
            ],
            'decisions.proposals.feedback_error' => [
                'text' => 'Impossible d’ajouter la proposition pour le moment.',
                'context' => 'Feedback shown when a public proposal cannot be saved.',
            ],
            'decisions.proposals.title_label' => [
                'text' => 'Titre',
                'context' => 'Title field label in the public consultation proposal form.',
            ],
            'decisions.proposals.title_placeholder' => [
                'text' => 'Nom de la proposition',
                'context' => 'Title field placeholder in the public consultation proposal form.',
            ],
            'decisions.proposals.description_label' => [
                'text' => 'Description',
                'context' => 'Description field label in the public consultation proposal form.',
            ],
            'decisions.proposals.description_placeholder' => [
                'text' => 'Contexte, détails, arguments utiles…',
                'context' => 'Description field placeholder in the public consultation proposal form.',
            ],
            'decisions.proposals.info_url_label' => [
                'text' => 'URL d’information',
                'context' => 'Information URL field label in the public consultation proposal form.',
            ],
            'decisions.proposals.submit' => [
                'text' => 'Ajouter la proposition',
                'context' => 'Submit button in the public consultation proposal form.',
            ],
            'decisions.proposals.denied.option_disabled' => [
                'text' => 'L’ajout de propositions n’est pas activé pour ce scrutin.',
                'context' => 'Error shown when public proposal submission is disabled for the decision.',
            ],
            'decisions.proposals.denied.consultation_not_started' => [
                'text' => 'La consultation n’a pas encore commencé.',
                'context' => 'Error shown when the consultation has not started yet.',
            ],
            'decisions.proposals.denied.consultation_ended' => [
                'text' => 'La phase d’élaboration est terminée : il n’est plus possible d’ajouter une proposition.',
                'context' => 'Error shown when the consultation period has ended.',
            ],
            'decisions.proposals.denied.evaluation_started' => [
                'text' => 'La phase de vote a déjà commencé.',
                'context' => 'Error shown when evaluation has already started.',
            ],
            'decisions.proposals.denied.participant_not_found' => [
                'text' => 'Aucun participant autorisé n’a été retrouvé pour ce lien ou ce compte.',
                'context' => 'Error shown when no authorized participant can be found.',
            ],
            'decisions.proposals.denied.participant_inactive' => [
                'text' => 'Ce participant n’est plus actif pour ce scrutin.',
                'context' => 'Error shown when the participant is inactive.',
            ],
            'decisions.proposals.denied.participant_status_declined' => [
                'text' => 'Votre participation a été refusée pour ce scrutin.',
                'context' => 'Error shown when the participant declined the decision.',
            ],
            'decisions.proposals.denied.participant_status_revoked' => [
                'text' => 'Votre accès à ce scrutin a été révoqué.',
                'context' => 'Error shown when the participant access was revoked.',
            ],
            'decisions.proposals.denied.invalid_decision' => [
                'text' => 'Le scrutin n’a pas pu être chargé.',
                'context' => 'Error shown when the decision cannot be loaded.',
            ],
            'decisions.proposals.denied.default' => [
                'text' => 'Ce lien ne permet pas d’ajouter des propositions pour le moment.',
                'context' => 'Fallback error for public proposal submission.',
            ],
            'decisions.proposals.metadata.anonymous_author' => [
                'text' => 'Auteur anonyme',
                'context' => 'Fallback proposal author name when the proposal is anonymous.',
            ],
            'decisions.proposals.metadata.unknown_author' => [
                'text' => 'Auteur inconnu',
                'context' => 'Fallback proposal author name when it cannot be resolved.',
            ],
            'decisions.proposals.metadata.proposed_by' => [
                'text' => 'Proposée par',
                'context' => 'Prefix before the proposal author name.',
            ],
            'decisions.proposals.metadata.modified_on' => [
                'text' => 'modifiée le',
                'context' => 'Date prefix when a proposal was modified.',
            ],
            'decisions.proposals.metadata.on' => [
                'text' => 'le',
                'context' => 'Date prefix when a proposal was created.',
            ],
            'decisions.proposals.metadata.no_new_messages' => [
                'text' => 'Aucun nouveau message depuis votre dernière intervention',
                'context' => 'Discussion metadata when no newer message exists.',
            ],
            'decisions.proposals.metadata.new_messages' => [
                'one' => '{count} nouveau message depuis votre dernière intervention',
                'other' => '{count} nouveaux messages depuis votre dernière intervention',
                'context' => 'Discussion metadata when newer messages exist.',
            ],
            'decisions.proposals.metadata.system' => [
                'text' => 'Système',
                'context' => 'System message author label.',
            ],
            'decisions.proposals.metadata.participant' => [
                'text' => 'Participant',
                'context' => 'Fallback discussion message author label.',
            ],
            'decisions.proposals.metadata.last_message' => [
                'text' => 'dernier le {date}, par {author}',
                'context' => 'Details about the latest discussion message.',
            ],
            'decisions.proposals.metadata.message_count' => [
                'one' => '{count} message',
                'other' => '{count} messages',
                'context' => 'Discussion message count.',
            ],
            'decisions.proposals.metadata.message_count_label' => [
                'one' => 'Nombre de messages : {count}',
                'other' => 'Nombre de messages : {count}',
                'context' => 'Accessible discussion message count label.',
            ],
            'decisions.proposals.metadata.no_messages' => [
                'text' => 'Aucun message',
                'context' => 'Discussion metadata when no messages exist.',
            ],
            'decisions.proposals.action.discuss_proposal' => [
                'text' => 'Discuter la proposition',
                'context' => 'Button to open the proposal discussion.',
            ],
            'decisions.proposals.action.discuss' => [
                'text' => 'Discuter',
                'context' => 'Short button label to open the proposal discussion.',
            ],
            'decisions.proposals.action.edit_proposal' => [
                'text' => 'Modifier la proposition',
                'context' => 'Button to edit a proposal.',
            ],
            'decisions.proposals.action.edit' => [
                'text' => 'Modifier',
                'context' => 'Short button label to edit a proposal.',
            ],
            'decisions.proposals.action.other_actions' => [
                'text' => 'Autres actions',
                'context' => 'Accessible label for the proposal actions menu.',
            ],
            'decisions.proposals.action.delete' => [
                'text' => 'Supprimer',
                'context' => 'Button to delete a proposal.',
            ],
        ];
    }
}

if (!function_exists('omoDecisionProposalT')) {
    function omoDecisionProposalT($key, array $variables = [])
    {
        static $sourceLang = null;
        static $lang = null;
        if ($sourceLang === null) {
            $sourceLang = omoDecisionProposalGetSourceLang();
            $lang = omoLoadTranslationBundle('omo_decision_proposals', $sourceLang);
        }

        return t($key, $variables, $lang, $sourceLang);
    }
}

if (!function_exists('omoDecisionCanSubmitConsultationProposal')) {
    function omoDecisionGetConsultationProposalAvailability($decision, array $context)
    {
        if (!$decision instanceof DecisionProcess || (int)$decision->getId() <= 0) {
            return [
                'allowed' => false,
                'reason' => 'invalid_decision',
            ];
        }

        $decisionGroup = ($context['decisionGroup'] ?? null) instanceof DecisionGroup
            ? $context['decisionGroup']
            : $decision->getPrimaryGroup(false);
        $config = omoDecisionBuildMethodConfig($decisionGroup instanceof DecisionGroup ? $decisionGroup : $decision);
        if (empty($config['allow_consultation_proposals'])) {
            return [
                'allowed' => false,
                'reason' => 'option_disabled',
            ];
        }

        if (!$decision->hasConsultationStarted()) {
            return [
                'allowed' => false,
                'reason' => 'consultation_not_started',
            ];
        }

        if ($decision->hasConsultationEnded()) {
            return [
                'allowed' => false,
                'reason' => 'consultation_ended',
            ];
        }

        if ($decision->hasEvaluationStarted()) {
            return [
                'allowed' => false,
                'reason' => 'evaluation_started',
            ];
        }

        $currentUserId = function_exists('commonGetCurrentUserId')
            ? (int)commonGetCurrentUserId()
            : (int)($_SESSION['currentUser'] ?? 0);
        if ($currentUserId > 0 && (int)$decision->get('IDuser') === $currentUserId) {
            return [
                'allowed' => true,
                'reason' => 'owner',
            ];
        }

        $participant = null;
        if ($currentUserId > 0) {
            $participant = DecisionParticipant::findByDecisionAndUser((int)$decision->getId(), $currentUserId);
            if ($participant instanceof DecisionParticipant) {
                $context['participant_lookup'] = 'user';
            }
        }

        if (!($participant instanceof DecisionParticipant)) {
            $currentUserEmail = trim((string)($context['currentUserEmail'] ?? ''));
            if ($currentUserEmail === '' && $currentUserId > 0) {
                $currentUser = new User();
                if ($currentUser->load($currentUserId)) {
                    $currentUserEmail = trim(mb_strtolower((string)$currentUser->getScopedEmail((int)$decision->get('IDorganization')), 'UTF-8'));
                }
            }

            if ($currentUserEmail !== '') {
                $participant = DecisionParticipant::findByDecisionAndEmail((int)$decision->getId(), $currentUserEmail);
                if ($participant instanceof DecisionParticipant) {
                    $context['participant_lookup'] = 'email';
                }
            }
        }

        if (
            !($participant instanceof DecisionParticipant)
            && (string)($context['accessMode'] ?? '') === 'public'
        ) {
            $participant = $context['participant'] ?? null;
            if ($participant instanceof DecisionParticipant) {
                $context['participant_lookup'] = 'public_token';
            }
        }

        if (!($participant instanceof DecisionParticipant)) {
            return [
                'allowed' => false,
                'reason' => 'participant_not_found',
            ];
        }

        if ((int)$participant->get('active') !== 1) {
            return [
                'allowed' => false,
                'reason' => 'participant_inactive',
            ];
        }

        $participantStatus = DecisionParticipant::normalizeStatus($participant->get('status'));
        if (in_array($participantStatus, [
            DecisionParticipant::STATUS_DECLINED,
            DecisionParticipant::STATUS_REVOKED,
        ], true)) {
            return [
                'allowed' => false,
                'reason' => 'participant_status_' . $participantStatus,
            ];
        }

        return [
            'allowed' => true,
            'reason' => (string)($context['participant_lookup'] ?? 'participant'),
        ];
    }
}

if (!function_exists('omoDecisionCanSubmitConsultationProposal')) {
    function omoDecisionCanSubmitConsultationProposal($decision, array $context)
    {
        $availability = omoDecisionGetConsultationProposalAvailability($decision, $context);
        return !empty($availability['allowed']);
    }
}

if (!function_exists('omoDecisionGetConsultationProposalDeniedMessage')) {
    function omoDecisionGetConsultationProposalDeniedMessage($reason)
    {
        switch (trim((string)$reason)) {
            case 'option_disabled':
                return omoDecisionProposalT('decisions.proposals.denied.option_disabled');
            case 'consultation_not_started':
                return omoDecisionProposalT('decisions.proposals.denied.consultation_not_started');
            case 'consultation_ended':
                return omoDecisionProposalT('decisions.proposals.denied.consultation_ended');
            case 'evaluation_started':
                return omoDecisionProposalT('decisions.proposals.denied.evaluation_started');
            case 'participant_not_found':
                return omoDecisionProposalT('decisions.proposals.denied.participant_not_found');
            case 'participant_inactive':
                return omoDecisionProposalT('decisions.proposals.denied.participant_inactive');
            case 'participant_status_declined':
                return omoDecisionProposalT('decisions.proposals.denied.participant_status_declined');
            case 'participant_status_revoked':
                return omoDecisionProposalT('decisions.proposals.denied.participant_status_revoked');
            case 'invalid_decision':
                return omoDecisionProposalT('decisions.proposals.denied.invalid_decision');
            default:
                return omoDecisionProposalT('decisions.proposals.denied.default');
        }
    }
}

if (!function_exists('omoDecisionBuildConsultationProposalSubmitUrl')) {
    function omoDecisionBuildConsultationProposalSubmitUrl()
    {
        return '/omo/api/decision/modules/proposals/consultation_add.php';
    }
}

if (!function_exists('omoDecisionBuildConsultationProposalReturnUrl')) {
    function omoDecisionBuildConsultationProposalReturnUrl(array $context)
    {
        $requestUri = trim((string)($_SERVER['REQUEST_URI'] ?? ''));
        if ($requestUri !== '' && strpos($requestUri, '/') === 0) {
            return $requestUri;
        }

        return omoDecisionBuildContextualEditorUrl($context, 'view');
    }
}

if (!function_exists('omoDecisionRenderConsultationProposalPublicPanel')) {
    function omoDecisionRenderConsultationProposalPublicPanel($decision, array $context, $escape, $extraClass = '')
    {
        if (!is_callable($escape)) {
            $escape = 'omoApiEscape';
        }

        if (!omoDecisionCanSubmitConsultationProposal($decision, $context)) {
            return '';
        }

        $decisionGroup = ($context['decisionGroup'] ?? null) instanceof DecisionGroup
            ? $context['decisionGroup']
            : $decision->getPrimaryGroup(false);
        $methodConfig = omoDecisionBuildMethodConfig($decisionGroup instanceof DecisionGroup ? $decisionGroup : $decision);
        $proposalContent = omoDecisionNormalizeProposalContent($methodConfig['proposal_content'] ?? null);
        $proposalFields = '';
        if ($proposalContent['title']) {
            $proposalFields .= '<label style="display:grid;gap:6px;">'
                . '<span class="generic-card-title generic-card-title--small">' . $escape(omoDecisionProposalT('decisions.proposals.title_label')) . '</span>'
                . '<input type="text" class="generic-form-control" name="consultation_proposal_title" value="" placeholder="' . $escape(omoDecisionProposalT('decisions.proposals.title_placeholder')) . '" required>'
                . '</label>';
        } else {
            $proposalFields .= '<input type="hidden" name="consultation_proposal_title" value="">';
        }
        if ($proposalContent['description']) {
            $proposalFields .= '<label style="display:grid;gap:6px;">'
                . '<span class="generic-card-title generic-card-title--small">' . $escape(omoDecisionProposalT('decisions.proposals.description_label')) . '</span>'
                . '<div data-omo-proposal-html-field>'
                    . '<div class="omo-proposal-html-editor" data-omo-proposal-html-editor></div>'
                    . '<textarea hidden aria-hidden="true" name="consultation_proposal_description" data-omo-proposal-html-value></textarea>'
                . '</div>'
            . '</label>';
        } else {
            $proposalFields .= '<input type="hidden" name="consultation_proposal_description" value="">';
        }
        if ($proposalContent['url']) {
            $proposalFields .= '<label style="display:grid;gap:6px;">'
                . '<span class="generic-card-title generic-card-title--small">' . $escape(omoDecisionProposalT('decisions.proposals.info_url_label')) . '</span>'
                . '<input type="url" class="generic-form-control" name="consultation_proposal_info_url" value="" placeholder="https://...">'
                . '</label>';
        } else {
            $proposalFields .= '<input type="hidden" name="consultation_proposal_info_url" value="">';
        }

        $extraClass = trim((string)$extraClass);
        if ($extraClass !== '') {
            $extraClass = ' ' . $extraClass;
        }

        $feedbackStatus = trim((string)($_GET['consultation_proposal_status'] ?? ''));
        $feedbackCount = max(0, (int)($_GET['consultation_proposal_count'] ?? 0));
        $feedbackMessage = '';
        $feedbackClass = '';
        $feedbackType = 'warning';

        if ($feedbackStatus === 'success') {
            $feedbackClass = ' style="background:color-mix(in srgb, var(--color-success, #16a34a) 10%, var(--color-surface, #ffffff));border-color:color-mix(in srgb, var(--color-success, #16a34a) 28%, var(--color-surface, #ffffff));"';
            $feedbackType = 'success';
            $feedbackMessage = $feedbackCount > 1
                ? omoDecisionProposalT('decisions.proposals.feedback_success_other', ['count' => $feedbackCount])
                : omoDecisionProposalT('decisions.proposals.feedback_success_one');
        } elseif ($feedbackStatus === 'duplicate') {
            $feedbackClass = ' style="background:color-mix(in srgb, var(--color-warning, #f59e0b) 10%, var(--color-surface, #ffffff));border-color:color-mix(in srgb, var(--color-warning, #f59e0b) 28%, var(--color-surface, #ffffff));"';
            $feedbackMessage = omoDecisionProposalT('decisions.proposals.feedback_duplicate');
        } elseif ($feedbackStatus === 'empty') {
            $feedbackClass = ' style="background:color-mix(in srgb, var(--color-warning, #f59e0b) 10%, var(--color-surface, #ffffff));border-color:color-mix(in srgb, var(--color-warning, #f59e0b) 28%, var(--color-surface, #ffffff));"';
            $feedbackMessage = omoDecisionProposalT('decisions.proposals.feedback_empty');
        } elseif ($feedbackStatus === 'denied') {
            $feedbackClass = ' style="background:color-mix(in srgb, var(--color-warning, #f59e0b) 10%, var(--color-surface, #ffffff));border-color:color-mix(in srgb, var(--color-warning, #f59e0b) 28%, var(--color-surface, #ffffff));"';
            $feedbackMessage = omoDecisionProposalT('decisions.proposals.feedback_denied');
        } elseif ($feedbackStatus === 'error') {
            $feedbackClass = ' style="background:color-mix(in srgb, var(--color-danger, #dc2626) 8%, var(--color-surface, #ffffff));border-color:color-mix(in srgb, var(--color-danger, #dc2626) 24%, var(--color-surface, #ffffff));"';
            $feedbackType = 'error';
            $feedbackMessage = omoDecisionProposalT('decisions.proposals.feedback_error');
        }

        $returnUrl = omoDecisionBuildConsultationProposalReturnUrl($context);
        $html = '<div class="generic-soft-panel generic-soft-panel--stack' . $extraClass . '">'
            . '<div style="display:grid;gap:6px;">'
                . '<h2 class="generic-card-title generic-card-title--section" style="margin:0;">' . $escape(omoDecisionProposalT('decisions.proposals.add_title')) . '</h2>'
                . '<p style="margin:0;color:var(--color-text-light,#475569);line-height:1.6;">' . $escape(omoDecisionProposalT('decisions.proposals.open_intro')) . '</p>'
                . '<p style="margin:0;color:var(--color-text-light,#64748b);font-size:13px;line-height:1.5;">' . $escape(omoDecisionProposalT('decisions.proposals.order_hint')) . '</p>'
            . '</div>';

        if ($feedbackMessage !== '') {
            $html .= '<div class="generic-soft-panel generic-soft-panel--stack"'
                . $feedbackClass
                . ' data-omo-decision-consultation-proposal-notification'
                . ' data-omo-decision-consultation-proposal-notification-type="' . $escape($feedbackType) . '"'
                . ' data-omo-decision-consultation-proposal-notification-message="' . $escape($feedbackMessage) . '"'
                . ' hidden>'
                . '<p style="margin:0;line-height:1.5;">' . $escape($feedbackMessage) . '</p>'
            . '</div>';
        }

        $html .= '<form method="post" action="' . $escape(omoDecisionBuildConsultationProposalSubmitUrl()) . '" style="display:grid;gap:12px;" data-omo-decision-consultation-proposal-form data-omo-decision-return-url="' . $escape($returnUrl) . '">'
                . '<input type="hidden" name="oid" value="' . $escape((int)($context['organizationId'] ?? 0)) . '">'
                . '<input type="hidden" name="cid" value="' . $escape((int)($context['targetHolonId'] ?? 0)) . '">'
                . '<input type="hidden" name="id" value="' . $escape((int)$decision->getId()) . '">'
                . '<input type="hidden" name="gid" value="' . $escape((int)((($context['decisionGroup'] ?? null) instanceof DecisionGroup) ? $context['decisionGroup']->getId() : 0)) . '">'
                . '<input type="hidden" name="method" value="' . $escape((string)(((($context['decisionGroup'] ?? null) instanceof DecisionGroup) ? $context['decisionGroup']->get('evaluation_method') : $decision->get('evaluation_method')))) . '">'
                . '<input type="hidden" name="intent" value="view">'
                . '<input type="hidden" name="ajax" value="1">'
                . '<input type="hidden" name="return_url" value="' . $escape($returnUrl) . '">'
                . omoDecisionRenderPublicTokenInput($context, $escape)
                . '<div style="display:grid;gap:10px;">' . $proposalFields . '</div>'
                . '<div data-omo-decision-consultation-proposal-feedback hidden></div>'
                . '<div style="display:flex;justify-content:flex-end;">'
                    . '<button type="submit" class="generic-action-button generic-action-button--main">' . $escape(omoDecisionProposalT('decisions.proposals.submit')) . '</button>'
                . '</div>'
            . '</form>'
        . '</div>';

        return $html;
    }
}

if (!function_exists('omoDecisionRenderConsultationProposalPublicSection')) {
    function omoDecisionRenderConsultationProposalPublicSection($decision, array $context, $escape, $extraClass = '')
    {
        $panel = omoDecisionRenderConsultationProposalPublicPanel($decision, $context, $escape);
        if ($panel === '') {
            return '';
        }

        $extraClass = trim((string)$extraClass);
        if ($extraClass !== '') {
            $extraClass = ' ' . $extraClass;
        }

        return '<section class="generic-section generic-section--stack' . $extraClass . '">' . $panel . '</section>';
    }
}

if (!function_exists('omoDecisionRenderPublicTokenInput')) {
    function omoDecisionRenderPublicTokenInput(array $context, $escape)
    {
        if (
            (($context['accessMode'] ?? '') !== 'public')
            || trim((string)($context['publicToken'] ?? '')) === ''
        ) {
            return '';
        }

        return '<input type="hidden" name="token" value="' . $escape((string)$context['publicToken']) . '">';
    }
}

if (!function_exists('omoDecisionInvitationGetSourceLang')) {
    function omoDecisionInvitationGetSourceLang()
    {
        return [
            'decisions.invitations.title' => [
                'text' => 'Participants invités',
                'context' => 'Shared section title for explicit decision invitations.',
            ],
            'decisions.invitations.configure' => [
                'text' => 'Inviter',
                'context' => 'Button opening the invitation popup.',
            ],
            'decisions.invitations.popup_title' => [
                'text' => 'Inviter des participants',
                'context' => 'Topbar modal title used by the invitation popup.',
            ],
            'decisions.invitations.send' => [
                'text' => 'Envoyer',
                'context' => 'Button opening the send invitations popup.',
            ],
            'decisions.invitations.send_popup_title' => [
                'text' => 'Envoyer les invitations',
                'context' => 'Topbar modal title used by the send invitations popup.',
            ],
            'decisions.invitations.unsaved' => [
                'text' => 'Enregistrez d’abord ce scrutin pour inviter d’autres personnes ou structures.',
                'context' => 'Hint shown before a decision exists and invitations cannot be configured yet.',
            ],
            'decisions.invitations.default_scope' => [
                'text' => 'Par défaut, seuls les membres du contexte courant participent.',
                'context' => 'Summary shown when no explicit invitations exist.',
            ],
            'decisions.invitations.additional_members' => [
                'one' => '{count} autre membre',
                'other' => '{count} autres membres',
                'context' => 'Summary fragment for organization members invited outside selected holons.',
            ],
            'decisions.invitations.members' => [
                'one' => '{count} membre',
                'other' => '{count} membres',
                'context' => 'Summary fragment for individually invited organization members without selected holons.',
            ],
            'decisions.invitations.guests' => [
                'one' => '{count} invité',
                'other' => '{count} invités',
                'context' => 'Summary fragment for external email guests.',
            ],
            'decisions.invitations.summary_connector' => [
                'text' => 'et',
                'context' => 'Connector placed before the last item of an invitation summary.',
            ],
            'decisions.invitations.summary_total_people' => [
                'one' => '{count} personne',
                'other' => '{count} personnes',
                'context' => 'Bold total shown before the explicit invitation summary details.',
            ],
            'decisions.invitations.total_people' => [
                'one' => '1 personne au total',
                'other' => '{count} personnes au total',
                'context' => 'Summary fragment showing the total number of unique people represented by the invitations.',
            ],
            'decisions.invitations.public_opt_in_count' => [
                'one' => '1 personne ajoutée via le lien public',
                'other' => '{count} personnes ajoutées via le lien public',
                'context' => 'Summary fragment for participants who requested access from the public link.',
            ],
            'decisions.invitations.public_opt_in_label' => [
                'text' => 'Ajoutés via le lien public',
                'context' => 'Label shown before listing people who joined through the public link.',
            ],
            'decisions.invitations.public_opt_in_member_badge' => [
                'text' => 'Ajouté via le lien public',
                'context' => 'Small note shown on an organization member row when the person already joined from the public link.',
            ],
            'decisions.invitations.public_opt_in_guest_label' => [
                'text' => 'Personnes déjà ajoutées via le lien public',
                'context' => 'Label shown near guest emails for people who already joined from the public link.',
            ],
            'decisions.invitations.public_opt_in_guest_hint' => [
                'text' => 'Ces personnes restent distinctes des invitations explicites, mais elles ont déjà demandé un accès.',
                'context' => 'Hint shown near the list of people who already joined from the public link.',
            ],
            'decisions.invitations.inline_intro' => [
                'text' => 'Définissez ici les participants explicites du scrutin. Sans invitation explicite, seuls les membres du contexte courant restent autorisés.',
                'context' => 'Intro text shown in the inline invitation editor inside the main decision form.',
            ],
            'decisions.invitations.inline_no_structure' => [
                'text' => 'Cette organisation n’a pas encore de structure. Vous pouvez inviter directement des membres de l’organisation ou des adresses e-mail externes.',
                'context' => 'Hint shown in the inline invitation editor when the organization has no holon structure.',
            ],
            'decisions.invitations.inline_save_hint' => [
                'text' => 'Ces invitations seront enregistrées avec le scrutin.',
                'context' => 'Helper text shown below the inline invitation editor before the main decision form is saved.',
            ],
            'decisions.invitations.tab.holons' => [
                'text' => 'Holons',
                'context' => 'Tab label for invited holons in the inline invitation editor.',
            ],
            'decisions.invitations.tab.members' => [
                'text' => 'Membres',
                'context' => 'Tab label for invited members in the inline invitation editor.',
            ],
            'decisions.invitations.tab.guests' => [
                'text' => 'Invités',
                'context' => 'Tab label for invited guest emails in the inline invitation editor.',
            ],
            'decisions.invitations.inline_holons_title' => [
                'text' => 'Holons invités',
                'context' => 'Section title for invited holons in the inline invitation editor.',
            ],
            'decisions.invitations.inline_holons_hint' => [
                'text' => 'Le holon courant apparaît ici comme n’importe quel autre. S’il n’est pas coché, ses membres ne seront pas inclus dès qu’une invitation explicite existe.',
                'context' => 'Hint for the invited holons tab in the inline invitation editor.',
            ],
            'decisions.invitations.inline_members_title' => [
                'text' => 'Membres supplémentaires de l’organisation',
                'context' => 'Section title for invited members in the inline invitation editor.',
            ],
            'decisions.invitations.inline_members_hint_structure' => [
                'text' => 'Cochez les membres à inviter individuellement, en plus des holons sélectionnés.',
                'context' => 'Hint for invited members when a holon structure exists in the inline invitation editor.',
            ],
            'decisions.invitations.inline_members_hint_flat' => [
                'text' => 'Cochez les membres à inviter individuellement. Sans structure, ils représentent le contexte organisationnel.',
                'context' => 'Hint for invited members when no holon structure exists in the inline invitation editor.',
            ],
            'decisions.invitations.inline_guests_title' => [
                'text' => 'Adresses e-mail externes',
                'context' => 'Section title for guest email invitations in the inline invitation editor.',
            ],
            'decisions.invitations.inline_guests_placeholder' => [
                'text' => 'prenom.nom@exemple.ch',
                'context' => 'Textarea placeholder for guest email invitations in the inline invitation editor.',
            ],
            'decisions.invitations.inline_guests_hint' => [
                'text' => 'Une adresse par ligne. Les invitations seront envoyées plus tard.',
                'context' => 'Hint below the guest email textarea in the inline invitation editor.',
            ],
            'decisions.invitations.inline_public_open_title' => [
                'text' => 'Participation sans invitation',
                'context' => 'Title of the public self-registration checkbox in the inline invitation editor.',
            ],
            'decisions.invitations.inline_public_open_hint' => [
                'text' => 'Toute personne disposant du lien public peut demander un code par e-mail. Si son adresse n’est pas encore associée à ce scrutin, un participant est créé automatiquement.',
                'context' => 'Hint for the public self-registration checkbox in the inline invitation editor.',
            ],
            'decisions.invitations.inline_current_holon' => [
                'text' => '(courant)',
                'context' => 'Suffix shown next to the current holon in the inline invitation editor tree.',
            ],
            'decisions.invitations.tabs_aria' => [
                'text' => 'Catégories d’invitations',
                'context' => 'Accessibility label for the invitation editor tabs.',
            ],
            'decisions.invitations.email.context' => [
                'text' => 'Contexte',
                'context' => 'Context label in a decision invitation email.',
            ],
            'decisions.invitations.email.start' => [
                'text' => 'Début',
                'context' => 'Consultation start label in a decision invitation email.',
            ],
            'decisions.invitations.email.end' => [
                'text' => 'Fin',
                'context' => 'Consultation end label in a decision invitation email.',
            ],
            'decisions.invitations.email.default_title' => [
                'text' => 'Prise de décision',
                'context' => 'Fallback decision title in a decision invitation email.',
            ],
            'decisions.invitations.email.open_decision' => [
                'text' => 'Ouvrir la prise de décision',
                'context' => 'Button label in a decision invitation email.',
            ],
            'decisions.invitations.email.open_vote' => [
                'text' => 'Ouvrir directement le scrutin',
                'context' => 'Button label in a personal access code email.',
            ],
            'decisions.invitations.email.footer' => [
                'text' => 'Ce message a été envoyé depuis {organization}.',
                'context' => 'Footer in a decision invitation email.',
            ],
            'decisions.invitations.email.invalid_recipient' => [
                'text' => 'Aucune adresse e-mail valide n’a été trouvée pour ce participant.',
                'context' => 'Error returned when an invitation recipient has no valid email address.',
            ],
            'decisions.invitations.email.invalid_link' => [
                'text' => 'Impossible de générer un lien public valide pour ce participant.',
                'context' => 'Error returned when a personal invitation link cannot be generated.',
            ],
            'decisions.invitations.email.send_link_failed' => [
                'text' => 'Impossible d’envoyer ce lien pour le moment.',
                'context' => 'Error returned when a personal invitation email cannot be sent.',
            ],
            'decisions.invitations.email.access_subject' => [
                'text' => 'Code d’accès à la prise de décision',
                'context' => 'Default subject for a personal access code email.',
            ],
            'decisions.invitations.email.greeting' => [
                'text' => 'Bonjour,',
                'context' => 'Greeting in a personal access code email.',
            ],
            'decisions.invitations.email.request_intro' => [
                'text' => 'Vous avez demandé un accès à la prise de décision « {title} ».',
                'context' => 'Introductory sentence in a personal access code email.',
            ],
            'decisions.invitations.email.request_instructions' => [
                'text' => 'Vous pouvez soit cliquer sur le lien personnel reçu dans cet e-mail, soit copier le code ci-dessous sur la page publique pour continuer.',
                'context' => 'Instructions in a personal access code email.',
            ],
            'decisions.invitations.email.code_expiry' => [
                'text' => 'Ce code est valable jusqu’au {date}.',
                'context' => 'Code expiry sentence in a personal access code email.',
            ],
            'decisions.invitations.email.goodbye' => [
                'text' => 'À bientôt,',
                'context' => 'Closing in a personal access code email.',
            ],
            'decisions.invitations.email.valid_until' => [
                'text' => 'Valable jusqu’au {date}.',
                'context' => 'Code expiry label displayed near the code in an email.',
            ],
            'decisions.invitations.email.direct_link' => [
                'text' => 'Lien direct personnel',
                'context' => 'Direct link label in a personal access code email.',
            ],
            'decisions.invitations.email.invalid_code' => [
                'text' => 'Impossible de générer un code d’accès pour le moment.',
                'context' => 'Error returned when an access code cannot be generated.',
            ],
            'decisions.invitations.email.send_code_failed' => [
                'text' => 'Impossible d’envoyer ce code pour le moment.',
                'context' => 'Error returned when a personal access code email cannot be sent.',
            ],
        ];
    }
}

if (!function_exists('omoDecisionInvitationT')) {
    function omoDecisionInvitationT($key, array $variables = [])
    {
        static $sourceLang = null;
        static $lang = null;
        if ($sourceLang === null) {
            $sourceLang = omoDecisionInvitationGetSourceLang();
            $lang = omoLoadTranslationBundle('omo_decision_invitations', $sourceLang);
        }

        return t($key, $variables, $lang, $sourceLang);
    }
}

if (!function_exists('omoDecisionBuildInvitationPopupUrl')) {
    function omoDecisionBuildInvitationPopupUrl($organizationId, $holonId = 0, $decisionId = 0, $method = '')
    {
        $query = [
            'oid' => (int)$organizationId,
            'id' => (int)$decisionId,
        ];

        if ((int)$holonId > 0) {
            $query['cid'] = (int)$holonId;
        }

        $method = trim((string)$method);
        if ($method !== '') {
            $query['method'] = $method;
        }

        return '/omo/api/decision/invitations_popup.php?' . http_build_query($query);
    }
}

if (!function_exists('omoDecisionBuildInvitationSendPopupUrl')) {
    function omoDecisionBuildInvitationSendPopupUrl($organizationId, $holonId = 0, $decisionId = 0, $method = '')
    {
        $query = [
            'oid' => (int)$organizationId,
            'id' => (int)$decisionId,
        ];

        if ((int)$holonId > 0) {
            $query['cid'] = (int)$holonId;
        }

        $method = trim((string)$method);
        if ($method !== '') {
            $query['method'] = $method;
        }

        return '/omo/api/decision/send_invitations_popup.php?' . http_build_query($query);
    }
}

if (!function_exists('omoDecisionParseInvitationEmails')) {
    function omoDecisionParseInvitationEmails($value)
    {
        $rawItems = is_array($value)
            ? $value
            : preg_split('/[\r\n,;]+/', (string)$value);
        $rawItems = is_array($rawItems) ? $rawItems : [];

        $emails = [];
        foreach ($rawItems as $item) {
            $email = trim(mb_strtolower((string)$item, 'UTF-8'));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if (!in_array($email, $emails, true)) {
                $emails[] = $email;
            }
        }

        return $emails;
    }
}

if (!function_exists('omoDecisionExtractInvitationSelections')) {
    function omoDecisionExtractInvitationSelections($decision)
    {
        $selectedHolonIds = [];
        $selectedUserIds = [];
        $selectedEmails = [];

        if ($decision instanceof DecisionProcess) {
            foreach ($decision->getInvitations(true) as $invitation) {
                if (
                    !($invitation instanceof DecisionInvitation)
                    || DecisionInvitation::normalizeStatus($invitation->get('status')) === DecisionInvitation::STATUS_REVOKED
                ) {
                    continue;
                }

                $type = DecisionInvitation::normalizeType($invitation->get('invitation_type'));
                if ($type === DecisionInvitation::TYPE_HOLON) {
                    $selectedHolonIds[] = (int)$invitation->get('IDholon');
                    continue;
                }

                if ($type === DecisionInvitation::TYPE_USER) {
                    $selectedUserIds[] = (int)$invitation->get('IDuser');
                    continue;
                }

                $email = trim((string)$invitation->get('email'));
                if ($email !== '') {
                    $selectedEmails[] = $email;
                }
            }
        }

        $selectedHolonIds = array_values(array_unique(array_filter(array_map('intval', $selectedHolonIds), static function ($holonId) {
            return $holonId > 0;
        })));
        $selectedUserIds = array_values(array_unique(array_filter(array_map('intval', $selectedUserIds), static function ($userId) {
            return $userId > 0;
        })));
        $selectedEmails = array_values(array_unique(array_filter($selectedEmails, static function ($email) {
            return trim((string)$email) !== '';
        })));

        return [
            'holon_ids' => $selectedHolonIds,
            'user_ids' => $selectedUserIds,
            'emails' => $selectedEmails,
            'count' => count($selectedHolonIds) + count($selectedUserIds) + count($selectedEmails),
        ];
    }
}

if (!function_exists('omoDecisionExtractPublicOptInSelections')) {
    function omoDecisionExtractPublicOptInSelections($decision)
    {
        $entries = [];
        $userIds = [];
        $emails = [];

        if ($decision instanceof DecisionProcess && method_exists($decision, 'getPublicSelfRegistrationParticipants')) {
            foreach ($decision->getPublicSelfRegistrationParticipants(true) as $participant) {
                if (!($participant instanceof \dbObject\DecisionParticipant)) {
                    continue;
                }

                $participantId = (int)$participant->getId();
                if ($participantId <= 0) {
                    continue;
                }

                $userId = (int)$participant->get('IDuser');
                $recipient = method_exists($decision, 'getParticipantInvitationRecipientData')
                    ? $decision->getParticipantInvitationRecipientData($participant)
                    : null;
                $email = trim(mb_strtolower((string)($recipient['email'] ?? $participant->get('email')), 'UTF-8'));
                $label = trim((string)$participant->get('display_name'));
                if ($label === '' && is_array($recipient)) {
                    $label = trim((string)($recipient['display_name'] ?? ''));
                }
                if ($label === '' && $email !== '') {
                    $label = $email;
                }
                if ($label === '') {
                    $label = 'Participant #' . $participantId;
                }

                if ($userId > 0) {
                    $userIds[$userId] = $userId;
                }
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emails[$email] = $email;
                }

                $entries[$participantId] = [
                    'participantId' => $participantId,
                    'userId' => $userId,
                    'email' => $email,
                    'label' => $label,
                ];
            }
        }

        return [
            'entries' => array_values($entries),
            'user_ids' => array_values($userIds),
            'emails' => array_values($emails),
            'count' => count($entries),
        ];
    }
}

if (!function_exists('omoDecisionBuildInvitationEditorHolonTreeData')) {
    function omoDecisionBuildInvitationEditorHolonTreeData(Holon $holon, \dbObject\Organization $organization, array $selectedHolonIds, $currentHolonId)
    {
        if (!$organization->containsHolon($holon) || !$holon->canViewDetail()) {
            return null;
        }

        $holonId = (int)$holon->getId();
        $children = [];
        $hasSelectedDescendant = in_array($holonId, $selectedHolonIds, true);
        $hasCurrentDescendant = $holonId === (int)$currentHolonId;

        foreach ($holon->getChildren() as $child) {
            if (!$child instanceof Holon) {
                continue;
            }

            $childNode = omoDecisionBuildInvitationEditorHolonTreeData($child, $organization, $selectedHolonIds, $currentHolonId);
            if (!is_array($childNode)) {
                continue;
            }

            $children[] = $childNode;
            if (!empty($childNode['hasSelectedDescendant'])) {
                $hasSelectedDescendant = true;
            }
            if (!empty($childNode['hasCurrentDescendant'])) {
                $hasCurrentDescendant = true;
            }
        }

        return [
            'id' => $holonId,
            'label' => trim((string)$holon->getDisplayName()),
            'typeLabel' => trim((string)$holon->getTemplateLabel(true)),
            'isCurrent' => $holonId === (int)$currentHolonId,
            'isSelected' => in_array($holonId, $selectedHolonIds, true),
            'children' => $children,
            'hasChildren' => count($children) > 0,
            'hasSelectedDescendant' => $hasSelectedDescendant,
            'hasCurrentDescendant' => $hasCurrentDescendant,
            'isExpanded' => $holonId === (int)$currentHolonId || $hasSelectedDescendant || $hasCurrentDescendant,
        ];
    }
}

if (!function_exists('omoDecisionRenderInvitationEditorHolonTreeNode')) {
    function omoDecisionRenderInvitationEditorHolonTreeNode(array $node, $escape, $currentLabel, $fieldName = 'invitation_holon_ids[]')
    {
        $hasChildren = !empty($node['hasChildren']);
        $isExpanded = !empty($node['isExpanded']);
        ?>
        <div class="omo-decision-invitations-editor__tree-node<?= $hasChildren ? ' has-children' : '' ?>" data-omo-decision-holon-node>
            <div class="omo-decision-invitations-editor__tree-row">
                <?php if ($hasChildren): ?>
                <button
                    type="button"
                    class="omo-decision-invitations-editor__tree-toggle"
                    data-omo-decision-holon-toggle
                    aria-expanded="<?= $isExpanded ? 'true' : 'false' ?>"
                >
                    <span aria-hidden="true">&#9662;</span>
                </button>
                <?php else: ?>
                <span class="omo-decision-invitations-editor__tree-spacer" aria-hidden="true"></span>
                <?php endif; ?>

                <label class="omo-decision-invitations-editor__check">
                    <input type="checkbox" name="<?= $escape($fieldName) ?>" value="<?= (int)$node['id'] ?>"<?= !empty($node['isSelected']) ? ' checked' : '' ?>>
                    <span class="omo-decision-invitations-editor__check-meta">
                        <strong><?= $escape((string)$node['label']) ?><?= !empty($node['isCurrent']) ? ' ' . $escape((string)$currentLabel) : '' ?></strong>
                        <span class="omo-decision-invitations-editor__check-type"><?= $escape((string)$node['typeLabel']) ?></span>
                    </span>
                </label>
            </div>

            <?php if ($hasChildren): ?>
            <div class="omo-decision-invitations-editor__tree-children" data-omo-decision-holon-children<?= $isExpanded ? '' : ' hidden' ?>>
                <?php foreach ((array)$node['children'] as $childNode): ?>
                    <?php omoDecisionRenderInvitationEditorHolonTreeNode($childNode, $escape, $currentLabel, $fieldName); ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('omoDecisionBuildInvitationEditorState')) {
    function omoDecisionBuildInvitationEditorState($decision, array $context)
    {
        $organization = $context['organization'] ?? null;
        $effectiveHolon = $context['effectiveHolon'] ?? null;
        $targetHolonId = (int)($context['targetHolonId'] ?? 0);
        $organizationId = (int)($context['organizationId'] ?? 0);
        $selectionState = omoDecisionExtractInvitationSelections($decision);
        $publicOptInState = omoDecisionExtractPublicOptInSelections($decision);
        $allowPublicSelfRegistration = $decision instanceof DecisionProcess
            && method_exists($decision, 'isPublicSelfRegistrationEnabled')
            ? $decision->isPublicSelfRegistrationEnabled()
            : false;

        $rootHolon = $organization instanceof \dbObject\Organization ? $organization->getEnabledStructuralRootHolon() : null;
        $holonTree = $rootHolon instanceof Holon
            ? omoDecisionBuildInvitationEditorHolonTreeData($rootHolon, $organization, $selectionState['holon_ids'], $targetHolonId)
            : null;

        $memberships = new \dbObject\ArrayUserOrganization();
        if ($organizationId > 0) {
            $memberships->loadActiveForOrganization($organizationId);
        }

        return [
            'organization' => $organization,
            'effectiveHolon' => $effectiveHolon,
            'organizationId' => $organizationId,
            'targetHolonId' => $targetHolonId,
            'selectedHolonIds' => $selectionState['holon_ids'],
            'selectedUserIds' => $selectionState['user_ids'],
            'selectedEmails' => $selectionState['emails'],
            'hasExplicitInvitations' => $selectionState['count'] > 0,
            'publicOptInEntries' => $publicOptInState['entries'],
            'publicOptInUserIds' => $publicOptInState['user_ids'],
            'publicOptInEmails' => $publicOptInState['emails'],
            'allowPublicSelfRegistration' => $allowPublicSelfRegistration,
            'holonTree' => $holonTree,
            'hasHolonStructure' => is_array($holonTree),
            'currentContextLabel' => $effectiveHolon instanceof Holon ? 'du contexte courant' : 'de l organisation',
            'memberships' => $memberships,
        ];
    }
}

if (!function_exists('omoDecisionApplyInvitationSelections')) {
    function omoDecisionApplyInvitationSelections(DecisionProcess $decision, \dbObject\Organization $organization, $organizationId, array $selectedHolonIds, array $selectedUserIds, $selectedEmails, $allowPublicSelfRegistration = false)
    {
        $organizationId = (int)$organizationId;
        if ((int)$decision->getId() <= 0 || $organizationId <= 0) {
            return [
                'status' => false,
                'message' => 'Contexte d invitations invalide.',
            ];
        }

        $selectedHolonIds = array_values(array_unique(array_filter(array_map('intval', $selectedHolonIds), static function ($holonId) {
            return $holonId > 0;
        })));
        $selectedUserIds = array_values(array_unique(array_filter(array_map('intval', $selectedUserIds), static function ($userId) {
            return $userId > 0;
        })));
        $selectedEmails = omoDecisionParseInvitationEmails($selectedEmails);

        $validHolonLabels = [];
        foreach ($selectedHolonIds as $holonId) {
            $holon = new Holon();
            if (!$holon->load($holonId) || !$organization->containsHolon($holon) || !$holon->canViewDetail()) {
                return [
                    'status' => false,
                    'message' => 'Un holon selectionne est invalide.',
                ];
            }

            $validHolonLabels[$holonId] = trim((string)$holon->getDisplayName());
        }

        $validUserLabels = [];
        foreach ($selectedUserIds as $userId) {
            $membership = new \dbObject\UserOrganization();
            if (
                !$membership->load([
                    ['IDorganization', $organizationId],
                    ['IDuser', $userId],
                ])
                || !(bool)$membership->get('active')
            ) {
                return [
                    'status' => false,
                    'message' => 'Un membre selectionne est invalide.',
                ];
            }

            $validUserLabels[$userId] = trim((string)$membership->getUserDisplayName());
        }

        $existingInvitations = [];
        foreach ($decision->getInvitations(false) as $invitation) {
            if ($invitation instanceof DecisionInvitation) {
                $existingInvitations[$invitation->getIdentityKey()] = $invitation;
            }
        }

        $desiredInvitations = [];
        foreach ($selectedHolonIds as $holonId) {
            $desiredInvitations['holon:' . $holonId] = [
                'invitation_type' => DecisionInvitation::TYPE_HOLON,
                'IDholon' => $holonId,
                'display_name' => $validHolonLabels[$holonId] ?? '',
            ];
        }
        foreach ($selectedUserIds as $userId) {
            $desiredInvitations['user:' . $userId] = [
                'invitation_type' => DecisionInvitation::TYPE_USER,
                'IDuser' => $userId,
                'display_name' => $validUserLabels[$userId] ?? '',
            ];
        }
        foreach ($selectedEmails as $email) {
            $desiredInvitations['email:' . $email] = [
                'invitation_type' => DecisionInvitation::TYPE_EMAIL,
                'email' => $email,
                'display_name' => $email,
            ];
        }

        foreach ($desiredInvitations as $identityKey => $invitationData) {
            $invitation = $existingInvitations[$identityKey] ?? new DecisionInvitation();
            $invitation->set('IDdecision_process', (int)$decision->getId());
            $invitation->set('invitation_type', $invitationData['invitation_type']);
            $invitation->set('IDholon', $invitationData['IDholon'] ?? null);
            $invitation->set('IDuser', $invitationData['IDuser'] ?? null);
            $invitation->set('email', $invitationData['email'] ?? null);
            $invitation->set('display_name', $invitationData['display_name'] ?? null);
            $invitation->set('status', DecisionInvitation::STATUS_INVITED);
            $invitation->set('active', 1);
            $invitation->set('parameters', [
                'updated_from_inline' => 1,
            ]);

            $saveResult = $invitation->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                return [
                    'status' => false,
                    'message' => 'Impossible d enregistrer les invitations pour le moment.',
                ];
            }
        }

        foreach ($existingInvitations as $identityKey => $invitation) {
            if (isset($desiredInvitations[$identityKey])) {
                continue;
            }

            $invitation->set('active', 0);
            $invitation->set('status', DecisionInvitation::STATUS_REVOKED);
            $saveResult = $invitation->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                return [
                    'status' => false,
                    'message' => 'Impossible de retirer une invitation pour le moment.',
                ];
            }
        }

        if (
            method_exists($decision, 'setPublicSelfRegistrationEnabled')
            && !$decision->setPublicSelfRegistrationEnabled($allowPublicSelfRegistration)
        ) {
            return [
                'status' => false,
                'message' => 'Impossible d enregistrer le mode de participation publique.',
            ];
        }

        return [
            'status' => true,
            'count' => count($desiredInvitations),
        ];
    }
}

if (!function_exists('omoDecisionPersistInlineInvitationDraft')) {
    function omoDecisionPersistInlineInvitationDraft(DecisionProcess $decision, array $context, array $input)
    {
        if (empty($input['invitation_inline_enabled'])) {
            return [
                'status' => true,
                'applied' => false,
            ];
        }

        $organization = $context['organization'] ?? null;
        if (!$organization instanceof \dbObject\Organization) {
            return [
                'status' => false,
                'message' => 'Organisation introuvable pour les invitations.',
            ];
        }

        return omoDecisionApplyInvitationSelections(
            $decision,
            $organization,
            (int)($context['organizationId'] ?? 0),
            (array)($input['invitation_holon_ids'] ?? []),
            (array)($input['invitation_user_ids'] ?? []),
            $input['invitation_emails'] ?? [],
            !empty($input['allow_public_self_registration'])
        );
    }
}

if (!function_exists('omoDecisionRenderInlineInvitationEditorScript')) {
    function omoDecisionRenderInlineInvitationEditorScript()
    {
        static $alreadyRendered = false;
        if ($alreadyRendered) {
            return '';
        }

        $alreadyRendered = true;

        return '<script>(function(){'
            . 'if(typeof window.omoDecisionInitInvitationEditors!=="function"){'
                . 'window.omoDecisionInitInvitationEditors=function(root){'
                    . 'var scope=(root&&root.querySelectorAll)?root:document;'
                    . 'if(typeof window.initGenericComponents==="function"){window.initGenericComponents(scope);}'
                    . 'Array.prototype.forEach.call(scope.querySelectorAll("[data-omo-decision-invitations-editor]"),function(editor){'
                        . 'if(editor.dataset.omoDecisionInvitationsReady==="1"){return;}'
                        . 'editor.dataset.omoDecisionInvitationsReady="1";'
                        . 'Array.prototype.forEach.call(editor.querySelectorAll("[data-omo-decision-holon-toggle]"),function(toggle){'
                            . 'if(toggle.dataset.omoDecisionBound==="1"){return;}'
                            . 'toggle.dataset.omoDecisionBound="1";'
                            . 'toggle.addEventListener("click",function(event){'
                                . 'var node,children,isExpanded;'
                                . 'event.preventDefault();'
                                . 'event.stopPropagation();'
                                . 'node=toggle.closest("[data-omo-decision-holon-node]");'
                                . 'children=node?node.querySelector("[data-omo-decision-holon-children]"):null;'
                                . 'if(!children){return;}'
                                . 'isExpanded=toggle.getAttribute("aria-expanded")==="true";'
                                . 'toggle.setAttribute("aria-expanded",isExpanded?"false":"true");'
                                . 'children.hidden=isExpanded;'
                            . '});'
                        . '});'
                    . '});'
                . '};'
            . '}'
            . 'if(typeof window.omoDecisionInitInvitationEditors==="function"){window.omoDecisionInitInvitationEditors(document);}'
        . '})();</script>';
    }
}

if (!function_exists('omoDecisionRenderInlineInvitationSection')) {
    function omoDecisionRenderInlineInvitationSection($decision, array $context, $lang, array $sourceLang, $escape, $extraClass = '')
    {
        $editorState = omoDecisionBuildInvitationEditorState($decision, $context);
        $hasHolonStructure = !empty($editorState['hasHolonStructure']);
        $memberships = $editorState['memberships'];
        $holonTree = $editorState['holonTree'];
        $selectedUserIds = $editorState['selectedUserIds'];
        $selectedEmails = $editorState['selectedEmails'];
        $publicOptInEntries = $editorState['publicOptInEntries'];
        $publicOptInUserIds = $editorState['publicOptInUserIds'];
        $allowPublicSelfRegistration = !empty($editorState['allowPublicSelfRegistration']);

        static $instanceCounter = 0;
        $instanceCounter += 1;
        $instanceId = 'omoDecisionInvitationsInline' . $instanceCounter;
        $membersTabId = $instanceId . 'Members';
        $guestsTabId = $instanceId . 'Guests';
        $holonsTabId = $instanceId . 'Holons';

        ob_start();
        ?>
        <div class="generic-soft-panel generic-soft-panel--stack omo-decision-invitations-editor<?= $extraClass !== '' ? ' ' . $escape(trim((string)$extraClass)) : '' ?>" data-omo-decision-invitations-editor>
            <span class="generic-card-title"><?= $escape(t('decisions.invitations.title', [], $lang, $sourceLang)) ?></span>
            <p class="omo-decision-invitations-editor__intro"><?= $escape(t('decisions.invitations.inline_intro', [], $lang, $sourceLang)) ?></p>

            <?php if (!$hasHolonStructure): ?>
            <p class="omo-decision-invitations-editor__hint"><?= $escape(t('decisions.invitations.inline_no_structure', [], $lang, $sourceLang)) ?></p>
            <?php endif; ?>

            <input type="hidden" name="invitation_inline_enabled" value="1">

            <div class="generic-tabs omo-decision-invitations-editor__tabs" data-generic-tabs>
                <div class="generic-tabs__list" aria-label="<?= $escape(t('decisions.invitations.tabs_aria', [], $lang, $sourceLang)) ?>">
                    <?php if ($hasHolonStructure): ?>
                    <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="<?= $escape($holonsTabId) ?>"><?= $escape(t('decisions.invitations.tab.holons', [], $lang, $sourceLang)) ?></button>
                    <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="<?= $escape($membersTabId) ?>"><?= $escape(t('decisions.invitations.tab.members', [], $lang, $sourceLang)) ?></button>
                    <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="<?= $escape($guestsTabId) ?>"><?= $escape(t('decisions.invitations.tab.guests', [], $lang, $sourceLang)) ?></button>
                    <?php else: ?>
                    <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="<?= $escape($membersTabId) ?>"><?= $escape(t('decisions.invitations.tab.members', [], $lang, $sourceLang)) ?></button>
                    <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="<?= $escape($guestsTabId) ?>"><?= $escape(t('decisions.invitations.tab.guests', [], $lang, $sourceLang)) ?></button>
                    <?php endif; ?>
                </div>
                <div class="generic-tabs__panels">
                    <?php if ($hasHolonStructure): ?>
                    <div id="<?= $escape($holonsTabId) ?>" class="generic-tabs__panel omo-decision-invitations-editor__tab-panel" data-generic-tab-panel>
                        <strong><?= $escape(t('decisions.invitations.inline_holons_title', [], $lang, $sourceLang)) ?></strong>
                        <p class="omo-decision-invitations-editor__hint"><?= $escape(t('decisions.invitations.inline_holons_hint', [], $lang, $sourceLang)) ?></p>
                        <div class="omo-decision-invitations-editor__checklist">
                            <?php if (is_array($holonTree)): ?>
                                <?php omoDecisionRenderInvitationEditorHolonTreeNode($holonTree, $escape, t('decisions.invitations.inline_current_holon', [], $lang, $sourceLang)); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div id="<?= $escape($membersTabId) ?>" class="generic-tabs__panel omo-decision-invitations-editor__tab-panel" data-generic-tab-panel<?= $hasHolonStructure ? ' hidden' : '' ?>>
                        <strong><?= $escape(t('decisions.invitations.inline_members_title', [], $lang, $sourceLang)) ?></strong>
                        <div class="omo-decision-invitations-editor__member-list">
                            <?php foreach ($memberships as $membership): ?>
                                <?php
                                $userId = (int)$membership->get('IDuser');
                                if ($userId <= 0) {
                                    continue;
                                }
                                $displayName = $membership->getUserDisplayName();
                                $secondary = $membership->getScopedEmail() !== '' ? $membership->getScopedEmail() : $membership->getUserSecondaryLabel();
                                $isExplicitUser = in_array($userId, $selectedUserIds, true);
                                $isPublicOnlyUser = in_array($userId, $publicOptInUserIds, true) && !$isExplicitUser;
                                ?>
                                <label class="omo-decision-invitations-editor__check">
                                    <input
                                        type="checkbox"
                                        name="invitation_user_ids[]"
                                        value="<?= $userId ?>"
                                        <?= ($isExplicitUser || $isPublicOnlyUser) ? ' checked' : '' ?>
                                        <?= $isPublicOnlyUser ? ' disabled title="' . $escape(t('decisions.invitations.public_opt_in_member_badge', [], $lang, $sourceLang)) . '"' : '' ?>
                                    >
                                    <span class="omo-decision-invitations-editor__check-meta">
                                        <strong><?= $escape($displayName) ?></strong>
                                        <?php if ($secondary !== ''): ?>
                                        <span class="omo-decision-invitations-editor__member-email"><?= $escape($secondary) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="omo-decision-invitations-editor__hint">
                            <?= $escape($hasHolonStructure
                                ? t('decisions.invitations.inline_members_hint_structure', [], $lang, $sourceLang)
                                : t('decisions.invitations.inline_members_hint_flat', [], $lang, $sourceLang)) ?>
                        </p>
                    </div>

                    <div id="<?= $escape($guestsTabId) ?>" class="generic-tabs__panel omo-decision-invitations-editor__tab-panel" data-generic-tab-panel hidden>
                        <label for="<?= $escape($instanceId) ?>Emails"><strong><?= $escape(t('decisions.invitations.inline_guests_title', [], $lang, $sourceLang)) ?></strong></label>
                        <textarea
                            id="<?= $escape($instanceId) ?>Emails"
                            name="invitation_emails"
                            class="omo-decision-invitations-editor__textarea generic-form-control"
                            placeholder="<?= $escape(t('decisions.invitations.inline_guests_placeholder', [], $lang, $sourceLang)) ?>"
                        ><?= $escape(implode("\n", $selectedEmails)) ?></textarea>
                        <p class="omo-decision-invitations-editor__hint"><?= $escape(t('decisions.invitations.inline_guests_hint', [], $lang, $sourceLang)) ?></p>
                        <?php if (count($publicOptInEntries) > 0): ?>
                        <div class="generic-soft-panel generic-soft-panel--stack">
                            <strong><?= $escape(t('decisions.invitations.public_opt_in_guest_label', [], $lang, $sourceLang)) ?></strong>
                            <div class="omo-decision-invitations-editor__checklist">
                                <?php foreach ($publicOptInEntries as $publicOptInEntry): ?>
                                <span class="omo-decision-invitations-editor__member-email">
                                    <?= $escape((string)$publicOptInEntry['label']) ?><?= trim((string)$publicOptInEntry['email']) !== '' && trim((string)$publicOptInEntry['email']) !== trim((string)$publicOptInEntry['label']) ? ' - ' . $escape((string)$publicOptInEntry['email']) : '' ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <p class="omo-decision-invitations-editor__hint"><?= $escape(t('decisions.invitations.public_opt_in_guest_hint', [], $lang, $sourceLang)) ?></p>
                        </div>
                        <?php endif; ?>
                        <div class="generic-soft-panel generic-soft-panel--stack">
                            <label class="omo-decision-invitations-editor__check">
                                <input type="checkbox" name="allow_public_self_registration" value="1"<?= $allowPublicSelfRegistration ? ' checked' : '' ?>>
                                <span class="omo-decision-invitations-editor__check-meta">
                                    <strong><?= $escape(t('decisions.invitations.inline_public_open_title', [], $lang, $sourceLang)) ?></strong>
                                    <span class="omo-decision-invitations-editor__member-email"><?= $escape(t('decisions.invitations.inline_public_open_hint', [], $lang, $sourceLang)) ?></span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <p class="omo-decision-invitations-editor__hint"><?= $escape(t('decisions.invitations.inline_save_hint', [], $lang, $sourceLang)) ?></p>
        </div>
        <?= omoDecisionRenderInlineInvitationEditorScript() ?>
        <?php

        return (string)ob_get_clean();
    }
}

if (!function_exists('omoDecisionSendParticipantAccessEmail')) {
    function omoDecisionSendParticipantAccessEmail(DecisionProcess $decision, DecisionParticipant $participant, $message = '', $subject = '')
    {
        $recipient = $decision->getParticipantInvitationRecipientData($participant);
        $email = trim((string)($recipient['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => false,
                'message' => omoDecisionInvitationT('decisions.invitations.email.invalid_recipient'),
            ];
        }

        $accessUrl = trim((string)$participant->getPublicAccessUrl());
        if ($accessUrl === '') {
            return [
                'status' => false,
                'message' => omoDecisionInvitationT('decisions.invitations.email.invalid_link'),
            ];
        }

        require_once dirname(__DIR__, 4) . '/common/email_layout.php';

        $organization = $decision->getOrganizationObject();
        $organizationName = $organization ? trim((string)$organization->get('name')) : 'Organisation';
        $decisionTitle = trim((string)$decision->get('title'));
        $message = trim((string)$message);
        if ($message === '') {
            $message = $decision->buildPublicAccessRequestEmailMessage();
        }

        $subject = trim((string)$subject);
        if ($subject === '') {
            $subject = $decision->buildDefaultInvitationEmailSubject();
        }

        $fromAddress = trim((string)($GLOBALS['mailUser'] ?? ''));
        if ($fromAddress === '') {
            $host = preg_replace('/:\d+$/', '', commonGetRootHost() ?: 'localhost');
            $fromAddress = 'noreply@' . ($host !== '' ? $host : 'localhost');
        }

        $holon = $decision->getHolonObject();
        $detailsItems = [];
        if ($holon instanceof Holon) {
            $detailsItems[] = '<li><strong>' . commonMailEscape(omoDecisionInvitationT('decisions.invitations.email.context')) . '</strong> : '
                . commonMailEscape(trim((string)$holon->getTemplateLabel(true)) . ' ' . trim((string)$holon->getDisplayName()))
                . '</li>';
        }

        $consultationStart = DecisionProcess::normalizeDateTimeValue($decision->get('consultation_start_at'));
        if ($consultationStart instanceof DateTimeInterface) {
            $detailsItems[] = '<li><strong>' . commonMailEscape(omoDecisionInvitationT('decisions.invitations.email.start')) . '</strong> : ' . commonMailEscape($consultationStart->format('d.m.Y H:i')) . '</li>';
        }

        $consultationEnd = DecisionProcess::normalizeDateTimeValue($decision->get('consultation_end_at'));
        if ($consultationEnd instanceof DateTimeInterface) {
            $detailsItems[] = '<li><strong>' . commonMailEscape(omoDecisionInvitationT('decisions.invitations.email.end')) . '</strong> : ' . commonMailEscape($consultationEnd->format('d.m.Y H:i')) . '</li>';
        }

        $detailsHtml = count($detailsItems) > 0
            ? '<ul style="margin:0; padding-left:18px; color:#475569; line-height:1.7;">' . implode('', $detailsItems) . '</ul>'
            : '';

        $html = commonRenderMailLayout([
            'brand_name' => $organizationName,
            'brand_color' => $organization ? trim((string)$organization->get('color')) : '',
            'logo_url' => $organization ? trim((string)$organization->get('logo')) : '',
            'banner_url' => $organization ? trim((string)$organization->get('banner')) : '',
            'heading' => $decisionTitle !== '' ? $decisionTitle : omoDecisionInvitationT('decisions.invitations.email.default_title'),
            'intro_html' => commonMailTextToHtml($message),
            'details_html' => $detailsHtml,
            'button_label' => omoDecisionInvitationT('decisions.invitations.email.open_decision'),
            'button_url' => $accessUrl,
            'footer_html' => '<p style="margin:0;">' . commonMailEscape(omoDecisionInvitationT('decisions.invitations.email.footer', ['organization' => $organizationName])) . '</p>',
        ]);

        $mailSent = myHTMLMail([$fromAddress, $organizationName !== '' ? $organizationName : 'Organisation'], $email, $subject, $html);
        if (!$mailSent) {
            return [
                'status' => false,
                'message' => omoDecisionInvitationT('decisions.invitations.email.send_link_failed'),
            ];
        }

        $participant->markInvitationSent();

        return [
            'status' => true,
            'email' => $email,
            'display_name' => trim((string)($recipient['display_name'] ?? '')),
            'access_url' => $accessUrl,
        ];
    }
}

if (!function_exists('omoDecisionSendParticipantAccessCodeEmail')) {
    function omoDecisionSendParticipantAccessCodeEmail(DecisionProcess $decision, DecisionParticipant $participant, $publicRequestUrl = '', $subject = '')
    {
        $recipient = $decision->getParticipantInvitationRecipientData($participant);
        $email = trim((string)($recipient['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => false,
                'message' => omoDecisionInvitationT('decisions.invitations.email.invalid_recipient'),
            ];
        }

        $codeResult = $participant->issuePublicAccessCode(900);
        if (empty($codeResult['status'])) {
            return [
                'status' => false,
                'message' => trim((string)($codeResult['message'] ?? omoDecisionInvitationT('decisions.invitations.email.invalid_code'))),
            ];
        }

        require_once dirname(__DIR__, 4) . '/common/email_layout.php';

        $organization = $decision->getOrganizationObject();
        $organizationName = $organization ? trim((string)$organization->get('name')) : 'Organisation';
        $decisionTitle = trim((string)$decision->get('title'));
        $publicRequestUrl = trim((string)$publicRequestUrl);
        if ($publicRequestUrl === '') {
            $publicRequestUrl = $decision->getGenericPublicAccessUrl('participate');
        }
        $directIntent = $decision->isParticipationInterfaceOpen() ? 'participate' : 'view';
        $directAccessUrl = trim((string)$participant->getPublicAccessUrl($directIntent));
        if ($directAccessUrl === '') {
            $directAccessUrl = $publicRequestUrl;
        }

        $subject = trim((string)$subject);
        if ($subject === '') {
            $subject = omoDecisionInvitationT('decisions.invitations.email.access_subject');
            if ($decisionTitle !== '') {
                $subject .= ' : ' . $decisionTitle;
            }
        }

        $fromAddress = trim((string)($GLOBALS['mailUser'] ?? ''));
        if ($fromAddress === '') {
            $host = preg_replace('/:\d+$/', '', commonGetRootHost() ?: 'localhost');
            $fromAddress = 'noreply@' . ($host !== '' ? $host : 'localhost');
        }

        $expiresAt = $codeResult['expires_at'] ?? null;
        $expiresLabel = $expiresAt instanceof DateTimeInterface
            ? $expiresAt->format('d.m.Y H:i')
            : '';

        $messageLines = [
            omoDecisionInvitationT('decisions.invitations.email.greeting'),
            '',
            omoDecisionInvitationT('decisions.invitations.email.request_intro', ['title' => $decisionTitle !== '' ? $decisionTitle : omoDecisionInvitationT('decisions.invitations.email.default_title')]),
            omoDecisionInvitationT('decisions.invitations.email.request_instructions'),
        ];
        if ($expiresLabel !== '') {
            $messageLines[] = omoDecisionInvitationT('decisions.invitations.email.code_expiry', ['date' => $expiresLabel]);
        }
        $messageLines[] = '';
        $messageLines[] = omoDecisionInvitationT('decisions.invitations.email.goodbye');
        $messageLines[] = $organizationName;

        $codeHtml = '<div style="display:inline-block;padding:16px 22px;background:#f3f4f6;border-radius:var(--radius-md);border:1px solid #e5e7eb;font:700 32px/1.2 Consolas, Monaco, monospace;letter-spacing:0.22em;color:#111827;">'
            . commonMailEscape((string)($codeResult['code'] ?? ''))
            . '</div>';
        if ($expiresLabel !== '') {
            $codeHtml .= '<p style="margin:14px 0 0;color:#64748b;line-height:1.6;">' . commonMailEscape(omoDecisionInvitationT('decisions.invitations.email.valid_until', ['date' => $expiresLabel])) . '</p>';
        }
        if ($directAccessUrl !== '') {
            $codeHtml .= '<div style="margin-top:18px;">'
                . '<p style="margin:0 0 8px;color:#111827;line-height:1.6;"><strong>' . commonMailEscape(omoDecisionInvitationT('decisions.invitations.email.direct_link')) . '</strong></p>'
                . '<div style="padding:12px 14px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:var(--radius-md);word-break:break-all;line-height:1.6;">'
                . '<a href="' . commonMailEscape($directAccessUrl) . '" style="color:#2563eb;text-decoration:none;">' . commonMailEscape($directAccessUrl) . '</a>'
                . '</div>'
                . '</div>';
        }

        $html = commonRenderMailLayout([
            'brand_name' => $organizationName,
            'brand_color' => $organization ? trim((string)$organization->get('color')) : '',
            'logo_url' => $organization ? trim((string)$organization->get('logo')) : '',
            'banner_url' => $organization ? trim((string)$organization->get('banner')) : '',
            'heading' => $decisionTitle !== '' ? $decisionTitle : omoDecisionInvitationT('decisions.invitations.email.default_title'),
            'intro_html' => commonMailTextToHtml(implode("\n", $messageLines)),
            'details_html' => $codeHtml,
            'button_label' => omoDecisionInvitationT('decisions.invitations.email.open_vote'),
            'button_url' => $directAccessUrl !== '' ? $directAccessUrl : $publicRequestUrl,
            'footer_html' => '<p style="margin:0;">' . commonMailEscape(omoDecisionInvitationT('decisions.invitations.email.footer', ['organization' => $organizationName])) . '</p>',
        ]);

        $mailSent = myHTMLMail([$fromAddress, $organizationName !== '' ? $organizationName : 'Organisation'], $email, $subject, $html);
        if (!$mailSent) {
            $participant->clearPublicAccessCode();
            return [
                'status' => false,
                'message' => omoDecisionInvitationT('decisions.invitations.email.send_code_failed'),
            ];
        }

        $participant->markInvitationSent();

        return [
            'status' => true,
            'email' => $email,
            'display_name' => trim((string)($recipient['display_name'] ?? '')),
            'expires_at' => $expiresAt instanceof DateTimeInterface ? $expiresAt->format('c') : '',
            'public_url' => $publicRequestUrl,
            'direct_url' => $directAccessUrl,
        ];
    }
}

if (!function_exists('omoDecisionBuildInvitationSummaryData')) {
    function omoDecisionBuildInvitationSummaryData($decision, array $context, $lang = null, array $sourceLang = [])
    {
        $currentHolon = $context['effectiveHolon'] ?? null;
        $method = $decision instanceof DecisionProcess
            ? DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method'))
            : trim((string)($context['method'] ?? ''));

        $data = [
            'isPersisted' => $decision instanceof DecisionProcess && (int)$decision->getId() > 0,
            'popupUrl' => '',
            'sendPopupUrl' => '',
            'publicUrl' => '',
            'sendEnabled' => false,
            'invitationCount' => 0,
            'recipientCount' => 0,
            'hasExplicitInvitations' => false,
            'publicOptInEntries' => [],
            'summaryDetails' => '',
            'totalLabel' => '',
            'recipientTooltip' => '',
            'summary' => '',
        ];

        if (!$data['isPersisted']) {
            $data['popupUrl'] = omoDecisionBuildInvitationPopupUrl(
                (int)($context['organizationId'] ?? 0),
                (int)($context['targetHolonId'] ?? 0),
                0,
                $method
            ) . '&draft=1';
            $data['summary'] = t('decisions.invitations.default_scope', [], $lang, $sourceLang);
            if ($currentHolon instanceof Holon) {
                $data['summary'] = trim(
                    (string)$currentHolon->getDisplayName()
                ) . ' ' . t('decisions.invitations.inline_current_holon', [], $lang, $sourceLang);
            }
            return $data;
        }

        $data['popupUrl'] = omoDecisionBuildInvitationPopupUrl(
            (int)($context['organizationId'] ?? 0),
            (int)($context['targetHolonId'] ?? 0),
            (int)$decision->getId(),
            $method
        );
        $data['sendPopupUrl'] = omoDecisionBuildInvitationSendPopupUrl(
            (int)($context['organizationId'] ?? 0),
            (int)($context['targetHolonId'] ?? 0),
            (int)$decision->getId(),
            $method
        );
        $data['publicUrl'] = $decision->getGenericPublicAccessUrl('view');
        $data['sendEnabled'] = count($decision->getInvitationEmailRecipients()) > 0
            && DecisionProcess::normalizeStatus($decision->get('status')) !== DecisionProcess::STATUS_DRAFT;
        $data['recipientCount'] = method_exists($decision, 'getInvitationRecipientCount')
            ? (int)$decision->getInvitationRecipientCount(false)
            : 0;
        $hasPublicSelfRegistration = method_exists($decision, 'isPublicSelfRegistrationEnabled')
            && $decision->isPublicSelfRegistrationEnabled();
        $publicOptInState = omoDecisionExtractPublicOptInSelections($decision);
        $data['publicOptInEntries'] = $publicOptInState['entries'];

        $invitations = [];
        foreach ($decision->getInvitations(true) as $invitation) {
            if ($invitation instanceof DecisionInvitation && DecisionInvitation::normalizeStatus($invitation->get('status')) !== DecisionInvitation::STATUS_REVOKED) {
                $invitations[] = $invitation;
            }
        }
        $data['invitationCount'] = count($invitations);
        $data['hasExplicitInvitations'] = $data['invitationCount'] > 0 || $hasPublicSelfRegistration;

        if (count($invitations) === 0) {
            $defaultSummary = t('decisions.invitations.default_scope', [], $lang, $sourceLang);
            if ($currentHolon instanceof Holon) {
                $defaultSummary = rtrim($defaultSummary, '.');
                $defaultSummary .= ' ' . $currentHolon->getTemplateLabel(true) . ' ' . trim((string)$currentHolon->getDisplayName()) . '.';
            }

            if ($hasPublicSelfRegistration) {
                $defaultSummary .= ' Participation publique ouverte.';
            }
            if ($publicOptInState['count'] > 0) {
                $defaultSummary .= ' ' . t('decisions.invitations.public_opt_in_count', ['count' => (string)$publicOptInState['count']], $lang, $sourceLang) . '.';
            }
            if ($data['recipientCount'] > 0) {
                $defaultSummary .= ' ' . t('decisions.invitations.total_people', ['count' => (string)$data['recipientCount']], $lang, $sourceLang) . '.';
            }

            $data['summary'] = $defaultSummary;
            return $data;
        }

        $organizationId = (int)($context['organizationId'] ?? 0);
        $holonLabels = [];
        $holonUserIds = [];
        $additionalUserIds = [];
        $guestEmails = [];
        $membersByUserId = [];

        $organizationMembers = new \dbObject\ArrayUserOrganization();
        if ($organizationId > 0) {
            $organizationMembers->loadActiveForOrganization($organizationId);
        }
        foreach ($organizationMembers as $membership) {
            $userId = (int)$membership->get('IDuser');
            if ($userId > 0) {
                $membersByUserId[$userId] = $membership;
            }
        }

        foreach ($invitations as $invitation) {
            $type = DecisionInvitation::normalizeType($invitation->get('invitation_type'));
            if ($type === DecisionInvitation::TYPE_HOLON) {
                $holonId = (int)$invitation->get('IDholon');
                $holonLabel = trim((string)$invitation->get('display_name'));
                if ($holonId > 0) {
                    $holon = new Holon();
                    if ($holon->load($holonId)) {
                        $holonLabel = trim(
                            trim((string)$holon->getTemplateLabel(true))
                            . ' '
                            . trim((string)$holon->getDisplayName())
                        );
                        foreach ($holon->getAssociatedMemberUserIds([
                            'organizationId' => $organizationId,
                            'skipPermissionFilter' => true,
                        ]) as $userId) {
                            $userId = (int)$userId;
                            if ($userId > 0) {
                                $holonUserIds[$userId] = $userId;
                            }
                        }
                    }
                }
                if ($holonLabel !== '') {
                    $holonLabels[] = $holonLabel;
                }
                continue;
            }

            if ($type === DecisionInvitation::TYPE_USER) {
                $userId = (int)$invitation->get('IDuser');
                if ($userId > 0) {
                    $additionalUserIds[$userId] = $userId;
                }
                continue;
            }

            $email = trim(mb_strtolower((string)$invitation->get('email'), 'UTF-8'));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $guestEmails[$email] = $email;
            }
        }

        foreach ($holonUserIds as $userId) {
            unset($additionalUserIds[$userId]);
        }

        $recipientUserIds = $holonUserIds + $additionalUserIds;
        $recipientEmails = [];
        foreach ($guestEmails as $email) {
            $matchesRecipient = false;
            foreach ($recipientUserIds as $userId) {
                $membership = $membersByUserId[(int)$userId] ?? null;
                if (
                    $membership instanceof \dbObject\UserOrganization
                    && trim(mb_strtolower((string)$membership->getScopedEmail(), 'UTF-8')) === $email
                ) {
                    $matchesRecipient = true;
                    break;
                }
            }
            if (!$matchesRecipient) {
                $recipientEmails[$email] = $email;
            }
        }

        $recipientLabels = [];
        foreach ($recipientUserIds as $userId) {
            $membership = $membersByUserId[(int)$userId] ?? null;
            if (!($membership instanceof \dbObject\UserOrganization)) {
                continue;
            }
            $displayName = trim((string)$membership->getUserDisplayName());
            $username = trim((string)$membership->getScopedUsername());
            $email = trim((string)$membership->getScopedEmail());
            $secondary = $username !== '' ? '@' . $username : $email;
            $recipientLabels[] = $secondary !== '' && $secondary !== $displayName
                ? $displayName . ' - ' . $secondary
                : ($displayName !== '' ? $displayName : $secondary);
        }
        foreach ($recipientEmails as $email) {
            $recipientLabels[] = $email;
        }

        $summaryParts = array_values(array_unique($holonLabels));
        if (count($additionalUserIds) > 0) {
            $summaryParts[] = omoDecisionInvitationT(
                count($holonLabels) > 0
                    ? 'decisions.invitations.additional_members'
                    : 'decisions.invitations.members',
                ['count' => (string)count($additionalUserIds)]
            );
        }
        if (count($recipientEmails) > 0) {
            $summaryParts[] = omoDecisionInvitationT('decisions.invitations.guests', ['count' => (string)count($recipientEmails)]);
        }
        if ($hasPublicSelfRegistration) {
            $summaryParts[] = 'Participation publique ouverte';
        }
        if ($publicOptInState['count'] > 0) {
            $summaryParts[] = t('decisions.invitations.public_opt_in_count', ['count' => (string)$publicOptInState['count']], $lang, $sourceLang);
        }

        $data['recipientCount'] = count($recipientUserIds) + count($recipientEmails);
        $summaryParts = array_values(array_filter($summaryParts, static function ($value) {
            return trim((string)$value) !== '';
        }));
        if (count($summaryParts) > 1) {
            $lastSummaryPart = array_pop($summaryParts);
            $data['summaryDetails'] = implode(', ', $summaryParts)
                . ' '
                . omoDecisionInvitationT('decisions.invitations.summary_connector')
                . ' '
                . $lastSummaryPart;
        } else {
            $data['summaryDetails'] = implode('', $summaryParts);
        }
        $data['totalLabel'] = omoDecisionInvitationT('decisions.invitations.summary_total_people', ['count' => (string)$data['recipientCount']]);
        $data['recipientTooltip'] = implode("\n", array_filter(array_values(array_unique($recipientLabels)), static function ($value) {
            return trim((string)$value) !== '';
        }));
        $data['summary'] = trim($data['totalLabel'] . ($data['summaryDetails'] !== '' ? ' (' . $data['summaryDetails'] . ')' : ''));

        return $data;
    }
}

if (!function_exists('omoDecisionRenderInvitationSection')) {
    function omoDecisionRenderInvitationSection($decision, array $context, $lang, array $sourceLang, $escape, $extraClass = '')
    {
        $summaryData = omoDecisionBuildInvitationSummaryData($decision, $context, $lang, $sourceLang);

        $extraClass = trim((string)$extraClass);
        if ($extraClass !== '') {
            $extraClass = ' ' . $extraClass;
        }

        $buttonDisabled = trim((string)$summaryData['popupUrl']) === '';
        $sendDisabled = empty($summaryData['isPersisted'])
            || trim((string)$summaryData['sendPopupUrl']) === ''
            || empty($summaryData['sendEnabled']);

        $isDraft = empty($summaryData['isPersisted']);
        $primarySummary = trim((string)($summaryData['totalLabel'] ?? '')) !== ''
            ? (string)$summaryData['totalLabel']
            : (string)$summaryData['summary'];
        $draftFields = '';
        if ($isDraft) {
            $targetHolonId = (int)($context['targetHolonId'] ?? 0);
            $draftFields = '<div hidden data-omo-decision-invitations-draft-fields>'
                . '<input type="hidden" name="invitation_inline_enabled" value="1">'
                . ($targetHolonId > 0 ? '<input type="hidden" name="invitation_holon_ids[]" value="' . $targetHolonId . '">' : '')
                . '<input type="hidden" name="invitation_emails" value="">'
                . '</div>';
        }

        return '<div class="generic-soft-panel generic-soft-panel--stack' . $extraClass . '">'
            . $draftFields
            . '<div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">'
                . '<span class="generic-card-title">' . $escape(t('decisions.invitations.title', [], $lang, $sourceLang)) . '</span>'
                . '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">'
                    . '<button'
                        . ' type="button"'
                        . ' class="generic-action-button generic-action-button--secondary"'
                        . ' data-omo-decision-invitations-open'
                        . ' data-omo-decision-invitations-url="' . $escape((string)$summaryData['popupUrl']) . '"'
                        . ' data-omo-decision-invitations-title="' . $escape(t('decisions.invitations.popup_title', [], $lang, $sourceLang)) . '"'
                        . ($isDraft ? ' data-omo-decision-invitations-draft="1"' : '')
                        . ($buttonDisabled ? ' disabled' : '')
                    . '>'
                        . $escape(t('decisions.invitations.configure', [], $lang, $sourceLang))
                    . '</button>'
                    . ($isDraft ? '' : '<button'
                        . ' type="button"'
                        . ' class="generic-action-button generic-action-button--main"'
                        . ' data-omo-decision-invitations-send-open'
                        . ' data-omo-decision-invitations-send-url="' . $escape((string)$summaryData['sendPopupUrl']) . '"'
                        . ' data-omo-decision-invitations-send-title="' . $escape(t('decisions.invitations.send_popup_title', [], $lang, $sourceLang)) . '"'
                        . ($sendDisabled ? ' disabled' : '')
                    . '>'
                        . $escape(t('decisions.invitations.send', [], $lang, $sourceLang))
                    . '</button>')
                    . ($isDraft ? '' : '<a'
                        . ' class="generic-action-button generic-action-button--secondary"'
                        . ' href="' . $escape((string)$summaryData['publicUrl']) . '"'
                        . ' target="_blank"'
                        . ' rel="noopener noreferrer"'
                        . (trim((string)$summaryData['publicUrl']) === '' ? ' aria-disabled="true"' : '')
                    . '>'
                        . $escape('Lien public')
                    . '</a>')
                . '</div>'
            . '</div>'
            . '<p style="margin:0;color:var(--color-text-light,#475569);line-height:1.6;" data-omo-decision-invitations-summary>'
                . '<strong'
                    . (trim((string)($summaryData['recipientTooltip'] ?? '')) !== '' ? ' title="' . $escape((string)$summaryData['recipientTooltip']) . '" tabindex="0"' : '')
                . '>' . $escape($primarySummary) . '</strong>'
                . (trim((string)($summaryData['summaryDetails'] ?? '')) !== '' ? ' (' . $escape((string)$summaryData['summaryDetails']) . ')' : '')
            . '</p>'
            . (
                count((array)($summaryData['publicOptInEntries'] ?? [])) > 0
                    ? '<p style="margin:0;color:var(--color-text-light,#475569);line-height:1.6;"><strong>'
                        . $escape(t('decisions.invitations.public_opt_in_label', [], $lang, $sourceLang))
                        . ':</strong> '
                        . $escape(implode(', ', array_map(static function (array $entry) {
                            $label = trim((string)($entry['label'] ?? ''));
                            $email = trim((string)($entry['email'] ?? ''));
                            if ($label === '') {
                                return $email;
                            }
                            if ($email !== '' && $email !== $label) {
                                return $label . ' - ' . $email;
                            }
                            return $label;
                        }, (array)$summaryData['publicOptInEntries'])))
                    . '</p>'
                    : ''
            )
        . '</div>';
    }
}
