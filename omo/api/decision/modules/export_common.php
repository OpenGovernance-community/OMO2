<?php

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/registry.php';

use dbObject\DecisionGroup;
use dbObject\DecisionInvitation;
use dbObject\DecisionParticipant;
use dbObject\DecisionProcess;
use dbObject\DecisionProposal;
use dbObject\DecisionResponse;
use dbObject\DecisionResult;

if (!function_exists('omoDecisionExportNormalizeValue')) {
    function omoDecisionExportNormalizeValue($value)
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('c');
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = omoDecisionExportNormalizeValue($item);
            }
            return $normalized;
        }

        if ($value instanceof stdClass) {
            return omoDecisionExportNormalizeValue((array)$value);
        }

        return $value;
    }
}

if (!function_exists('omoDecisionExportRemoveHiddenParameterKeys')) {
    function omoDecisionExportRemoveHiddenParameterKeys(array $parameters, array $hiddenKeys = [])
    {
        foreach ($hiddenKeys as $hiddenKey) {
            if (array_key_exists($hiddenKey, $parameters)) {
                unset($parameters[$hiddenKey]);
            }
        }

        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                $parameters[$key] = omoDecisionExportRemoveHiddenParameterKeys($value, $hiddenKeys);
            }
        }

        return $parameters;
    }
}

if (!function_exists('omoDecisionExportSerializeDbObject')) {
    function omoDecisionExportSerializeDbObject($object)
    {
        if (!is_object($object) || !method_exists($object, 'getId') || !method_exists($object, 'get')) {
            return null;
        }

        $className = get_class($object);
        if (!method_exists($className, 'attributeLabels')) {
            return null;
        }

        $data = [];
        foreach (array_keys($className::attributeLabels()) as $field) {
            if ($field === 'id') {
                $data['id'] = (int)$object->getId();
                continue;
            }

            $data[$field] = omoDecisionExportNormalizeValue($object->get($field));
        }

        if ($object instanceof DecisionParticipant) {
            unset($data['access_token']);
            $parameters = isset($data['parameters']) && is_array($data['parameters']) ? $data['parameters'] : [];
            $data['parameters'] = omoDecisionExportRemoveHiddenParameterKeys($parameters, ['public_access_code']);
        }

        return $data;
    }
}

if (!function_exists('omoDecisionExportSerializeList')) {
    function omoDecisionExportSerializeList(iterable $items)
    {
        $serialized = [];
        foreach ($items as $item) {
            $row = omoDecisionExportSerializeDbObject($item);
            if ($row !== null) {
                $serialized[] = $row;
            }
        }
        return $serialized;
    }
}

if (!function_exists('omoDecisionExportCountSubmittedResponses')) {
    function omoDecisionExportCountSubmittedResponses(array $responses)
    {
        $count = 0;
        foreach ($responses as $response) {
            if ($response instanceof DecisionResponse && (string)$response->get('status') === DecisionResponse::STATUS_SUBMITTED) {
                $count++;
            }
        }
        return $count;
    }
}

if (!function_exists('omoDecisionExportBuildBundle')) {
    function omoDecisionExportBuildBundle(DecisionProcess $decision, ?DecisionGroup $decisionGroup = null)
    {
        $decisionGroup = $decisionGroup instanceof DecisionGroup ? $decisionGroup : $decision->getPrimaryGroup(true);
        $methodOwner = $decisionGroup instanceof DecisionGroup ? $decisionGroup : $decision;
        $method = $decisionGroup instanceof DecisionGroup
            ? DecisionProcess::normalizeEvaluationMethod($decisionGroup->get('evaluation_method'))
            : DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method'));

        $proposals = [];
        if ($decisionGroup instanceof DecisionGroup) {
            foreach ($decisionGroup->getProposals(false) as $proposal) {
                if ($proposal instanceof DecisionProposal) {
                    $proposals[] = $proposal;
                }
            }
        } else {
            foreach ($decision->getProposals(false) as $proposal) {
                if ($proposal instanceof DecisionProposal) {
                    $proposals[] = $proposal;
                }
            }
        }

        $responses = [];
        $submittedResponses = [];
        $responseSource = $decisionGroup instanceof DecisionGroup ? $decisionGroup->getResponses('') : $decision->getResponses('');
        foreach ($responseSource as $response) {
            if (!$response instanceof DecisionResponse) {
                continue;
            }

            $responses[] = $response;
            if ((string)$response->get('status') === DecisionResponse::STATUS_SUBMITTED) {
                $submittedResponses[] = $response;
            }
        }

        $participants = [];
        foreach ($decision->getParticipants(false) as $participant) {
            if ($participant instanceof DecisionParticipant) {
                $participants[] = $participant;
            }
        }

        $invitations = [];
        foreach ($decision->getInvitations(false) as $invitation) {
            if ($invitation instanceof DecisionInvitation) {
                $invitations[] = $invitation;
            }
        }

        $result = $decisionGroup instanceof DecisionGroup
            ? $decisionGroup->getResult()
            : $decision->getResult();

        return [
            'decision' => $decision,
            'decisionGroup' => $decisionGroup,
            'methodOwner' => $methodOwner,
            'method' => $method,
            'config' => omoDecisionBuildMethodConfig($methodOwner),
            'proposals' => $proposals,
            'participants' => $participants,
            'invitations' => $invitations,
            'responses' => $responses,
            'submittedResponses' => $submittedResponses,
            'result' => $result instanceof DecisionResult ? $result : null,
        ];
    }
}

if (!function_exists('omoDecisionExportBuildBundleList')) {
    function omoDecisionExportBuildBundleList(DecisionProcess $decision, ?DecisionGroup $decisionGroup = null, $includeAllGroups = false)
    {
        if ($decisionGroup instanceof DecisionGroup) {
            return [omoDecisionExportBuildBundle($decision, $decisionGroup)];
        }

        if (!$includeAllGroups) {
            return [omoDecisionExportBuildBundle($decision, null)];
        }

        $bundles = [];
        foreach ($decision->getDecisionGroups(true) as $group) {
            if (!$group instanceof DecisionGroup) {
                continue;
            }

            $bundles[] = omoDecisionExportBuildBundle($decision, $group);
        }

        if (count($bundles) === 0) {
            $bundles[] = omoDecisionExportBuildBundle($decision, null);
        }

        return $bundles;
    }
}

if (!function_exists('omoDecisionExportNormalizeBundleList')) {
    function omoDecisionExportNormalizeBundleList(array $bundle)
    {
        $bundles = isset($bundle['bundles']) && is_array($bundle['bundles'])
            ? $bundle['bundles']
            : [$bundle];

        return array_values(array_filter($bundles, static function ($item): bool {
            return is_array($item) && (($item['decision'] ?? null) instanceof DecisionProcess);
        }));
    }
}

if (!function_exists('omoDecisionExportBuildPublishedResultData')) {
    function omoDecisionExportBuildPublishedResultData($result)
    {
        if (!($result instanceof DecisionResult)) {
            return null;
        }

        return [
            'status' => trim((string)$result->get('status')),
            'summary' => trim((string)$result->get('summary')),
            'computed_at' => omoDecisionExportNormalizeValue($result->get('computed_at')),
            'published_at' => omoDecisionExportNormalizeValue($result->get('published_at')),
            'parameters' => omoDecisionExportNormalizeValue(omoDecisionModuleDecodeParameters($result->get('parameters'))),
        ];
    }
}

if (!function_exists('omoDecisionExportBuildBlockBlueprintData')) {
    function omoDecisionExportBuildBlockBlueprintData(array $bundle)
    {
        $decisionGroup = $bundle['decisionGroup'] ?? null;
        $proposals = [];

        foreach ((array)($bundle['proposals'] ?? []) as $proposal) {
            if (!$proposal instanceof DecisionProposal) {
                continue;
            }

            $proposals[] = [
                'source_proposal_id' => (int)$proposal->getId(),
                'position' => (int)$proposal->get('position'),
                'title' => trim((string)$proposal->get('title')),
                'description' => trim((string)$proposal->get('description')),
                'info_url' => trim((string)$proposal->get('info_url')),
                'active' => (int)$proposal->get('active') === 1,
                'parameters' => omoDecisionExportNormalizeValue(omoDecisionModuleDecodeParameters($proposal->get('parameters'))),
            ];
        }

        return [
            'source_block_id' => $decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0,
            'title' => $decisionGroup instanceof DecisionGroup ? trim((string)$decisionGroup->get('title')) : '',
            'description' => $decisionGroup instanceof DecisionGroup ? trim((string)$decisionGroup->get('description')) : '',
            'decision_type' => $decisionGroup instanceof DecisionGroup
                ? trim((string)$decisionGroup->get('decision_type'))
                : DecisionProcess::TYPE_DECISION,
            'evaluation_method' => $decisionGroup instanceof DecisionGroup
                ? trim((string)$decisionGroup->get('evaluation_method'))
                : trim((string)($bundle['method'] ?? '')),
            'position' => $decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->get('position') : 1,
            'method_config' => omoDecisionExportNormalizeValue((array)($bundle['config'] ?? [])),
            'parameters' => $decisionGroup instanceof DecisionGroup
                ? omoDecisionExportRemoveHiddenParameterKeys(
                    omoDecisionExportNormalizeValue(omoDecisionModuleDecodeParameters($decisionGroup->get('parameters'))),
                    []
                )
                : [],
            'proposals' => $proposals,
        ];
    }
}

if (!function_exists('omoDecisionExportBuildProcessJsonData')) {
    function omoDecisionExportBuildProcessJsonData(DecisionProcess $decision, array $bundles)
    {
        $bundles = array_values(array_filter($bundles, static function ($bundle): bool {
            return is_array($bundle) && (($bundle['decision'] ?? null) instanceof DecisionProcess);
        }));
        if (count($bundles) === 0) {
            $bundles = [omoDecisionExportBuildBundle($decision, null)];
        }

        $methodCatalog = DecisionProcess::getEvaluationMethodCatalog();
        $decisionTypeCatalog = DecisionProcess::getDecisionTypeCatalog();
        $decisionType = DecisionProcess::normalizeDecisionType($decision->get('decision_type'));
        $decisionTypeMeta = (array)($decisionTypeCatalog[$decisionType] ?? []);
        $decisionParameters = omoDecisionExportRemoveHiddenParameterKeys(
            omoDecisionExportNormalizeValue(omoDecisionModuleDecodeParameters($decision->get('parameters'))),
            ['decision_public_access']
        );

        $blockItems = [];
        $resultBlocks = [];
        $submittedResponseCount = 0;
        $methods = [];

        foreach ($bundles as $bundle) {
            $method = trim((string)($bundle['method'] ?? ''));
            if ($method !== '') {
                $methods[$method] = true;
            }

            $blockItems[] = omoDecisionExportBuildBlockBlueprintData($bundle);

            $blockSubmittedCount = omoDecisionExportCountSubmittedResponses((array)($bundle['responses'] ?? []));
            $submittedResponseCount += $blockSubmittedCount;
            $decisionGroup = $bundle['decisionGroup'] ?? null;
            $resultBlocks[] = [
                'source_block_id' => $decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0,
                'submitted_response_count' => $blockSubmittedCount,
                'published_result' => omoDecisionExportBuildPublishedResultData($bundle['result'] ?? null),
            ];
        }

        $methodKeys = array_keys($methods);
        $hasSingleMethod = count($methodKeys) === 1;
        $exportMethod = $hasSingleMethod ? (string)$methodKeys[0] : 'multiple';
        $exportMethodMeta = $hasSingleMethod ? (array)($methodCatalog[$exportMethod] ?? []) : [];

        $data = [
            'export' => [
                'schema_version' => count($blockItems) > 1 ? 2 : 1,
                'generated_at' => gmdate('c'),
                'source_decision_id' => (int)$decision->getId(),
                'source_block_id' => count($blockItems) === 1 ? (int)($blockItems[0]['source_block_id'] ?? 0) : 0,
                'method' => $exportMethod,
                'method_label' => $hasSingleMethod
                    ? (string)($exportMethodMeta['label'] ?? $exportMethod)
                    : 'Multi-blocs',
                'block_count' => count($blockItems),
            ],
            'blueprint' => [
                'decision' => [
                    'title' => trim((string)$decision->get('title')),
                    'description' => trim((string)$decision->get('description')),
                    'decision_type' => $decisionType,
                    'decision_type_label' => (string)($decisionTypeMeta['label'] ?? $decisionType),
                    'evaluation_method' => $hasSingleMethod
                        ? $exportMethod
                        : trim((string)$decision->get('evaluation_method')),
                    'evaluation_method_label' => $hasSingleMethod
                        ? (string)($exportMethodMeta['label'] ?? $exportMethod)
                        : 'Multi-blocs',
                    'visibility_type' => trim((string)$decision->get('visibility_type')),
                    'parameters' => $decisionParameters,
                ],
                'blocks' => $blockItems,
            ],
            'results' => [
                'submitted_response_count' => $submittedResponseCount,
                'published_result' => count($resultBlocks) === 1
                    ? ($resultBlocks[0]['published_result'] ?? null)
                    : null,
                'blocks' => $resultBlocks,
            ],
        ];

        if (count($blockItems) === 1) {
            $data['blueprint']['block'] = $blockItems[0];
            $data['blueprint']['proposals'] = (array)($blockItems[0]['proposals'] ?? []);
            $firstBundle = $bundles[0];
            $data['method_config'] = omoDecisionExportNormalizeValue((array)($firstBundle['config'] ?? []));
        }

        return $data;
    }
}

if (!function_exists('omoDecisionExportBuildBaseJsonData')) {
    function omoDecisionExportBuildBaseJsonData(array $bundle)
    {
        $bundles = omoDecisionExportNormalizeBundleList($bundle);
        $decision = $bundle['decision'] ?? ($bundles[0]['decision'] ?? null);
        if (!$decision instanceof DecisionProcess) {
            return [];
        }

        return omoDecisionExportBuildProcessJsonData($decision, $bundles);
    }
}

if (!function_exists('omoDecisionExportEncodeCsv')) {
    function omoDecisionExportEncodeCsv(array $header, array $rows)
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return '';
        }

        fputcsv($handle, $header);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return is_string($content) ? str_replace("\r\n", "\n", $content) : '';
    }
}

if (!function_exists('omoDecisionExportBuildFileBaseName')) {
    function omoDecisionExportBuildFileBaseName(DecisionProcess $decision, $format)
    {
        $title = trim((string)$decision->get('title'));
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', $title);
        $slug = trim((string)$slug, '-');
        if ($slug === '') {
            $slug = 'decision-' . (int)$decision->getId();
        }

        return strtolower($slug . '-' . trim((string)$format));
    }
}

if (!function_exists('omoDecisionExportBuildDownloadResult')) {
    function omoDecisionExportBuildDownloadResult(DecisionProcess $decision, $format, $content, $mimeType)
    {
        return [
            'status' => true,
            'filename' => omoDecisionExportBuildFileBaseName($decision, $format) . '.' . $format,
            'content' => (string)$content,
            'mimeType' => (string)$mimeType,
        ];
    }
}

if (!function_exists('omoDecisionExportBuildCsvMetadataRow')) {
    function omoDecisionExportBuildCsvMetadataRow(array $bundle, DecisionProposal $proposal)
    {
        $decision = $bundle['decision'] ?? null;
        $decisionGroup = $bundle['decisionGroup'] ?? null;
        $method = (string)($bundle['method'] ?? '');
        $methodCatalog = DecisionProcess::getEvaluationMethodCatalog();
        $decisionTypeCatalog = DecisionProcess::getDecisionTypeCatalog();
        $decisionType = $decision instanceof DecisionProcess
            ? DecisionProcess::normalizeDecisionType($decision->get('decision_type'))
            : DecisionProcess::TYPE_DECISION;

        return [
            'source_decision_id' => $decision instanceof DecisionProcess ? (int)$decision->getId() : 0,
            'source_bloc_id' => $decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0,
            'source_question_id' => (int)$proposal->getId(),
            'type_scrutin' => $method,
            'type_scrutin_label' => (string)(($methodCatalog[$method] ?? [])['label'] ?? $method),
            'type_decision' => $decisionType,
            'type_decision_label' => (string)(($decisionTypeCatalog[$decisionType] ?? [])['label'] ?? $decisionType),
            'bloc' => $decisionGroup instanceof DecisionGroup ? trim((string)$decisionGroup->get('title')) : '',
            'detail_bloc' => $decisionGroup instanceof DecisionGroup ? trim((string)$decisionGroup->get('description')) : '',
            'position_bloc' => $decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->get('position') : 0,
            'question' => trim((string)$proposal->get('title')),
            'detail_question' => trim((string)$proposal->get('description')),
            'info_question' => trim((string)$proposal->get('info_url')),
            'position_question' => (int)$proposal->get('position'),
        ];
    }
}

if (!function_exists('omoDecisionExportFlattenCsvRow')) {
    function omoDecisionExportFlattenCsvRow(array $metadata, $result, $detail)
    {
        return [
            (string)($metadata['source_decision_id'] ?? ''),
            (string)($metadata['source_bloc_id'] ?? ''),
            (string)($metadata['source_question_id'] ?? ''),
            (string)($metadata['type_scrutin'] ?? ''),
            (string)($metadata['type_scrutin_label'] ?? ''),
            (string)($metadata['type_decision'] ?? ''),
            (string)($metadata['type_decision_label'] ?? ''),
            (string)($metadata['bloc'] ?? ''),
            (string)($metadata['detail_bloc'] ?? ''),
            (string)($metadata['position_bloc'] ?? ''),
            (string)($metadata['question'] ?? ''),
            (string)($metadata['detail_question'] ?? ''),
            (string)($metadata['info_question'] ?? ''),
            (string)($metadata['position_question'] ?? ''),
            (string)$result,
            (string)$detail,
        ];
    }
}

if (!function_exists('omoDecisionExportGetCsvHeader')) {
    function omoDecisionExportGetCsvHeader()
    {
        return [
            'source_decision_id',
            'source_bloc_id',
            'source_question_id',
            'type_scrutin',
            'type_scrutin_label',
            'type_decision',
            'type_decision_label',
            'bloc',
            'detail_bloc',
            'position_bloc',
            'question',
            'detail_question',
            'info_question',
            'position_question',
            'resultat',
            'detail_resultat',
        ];
    }
}

if (!function_exists('omoDecisionExportAppendXmlNode')) {
    function omoDecisionExportAppendXmlNode(DOMDocument $document, DOMElement $parent, $key, $value)
    {
        $key = is_string($key) && trim($key) !== '' ? trim($key) : 'item';
        $key = preg_replace('/[^A-Za-z0-9_\-]/', '_', $key);
        if ($key === '' || is_numeric($key)) {
            $key = 'item';
        }

        if (is_array($value)) {
            $isSequential = array_keys($value) === range(0, count($value) - 1);
            $node = $document->createElement($key);
            $parent->appendChild($node);

            if ($isSequential) {
                foreach ($value as $item) {
                    omoDecisionExportAppendXmlNode($document, $node, 'item', $item);
                }
            } else {
                foreach ($value as $childKey => $childValue) {
                    omoDecisionExportAppendXmlNode($document, $node, (string)$childKey, $childValue);
                }
            }

            return;
        }

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif ($value === null) {
            $value = '';
        }

        $node = $document->createElement($key);
        $node->appendChild($document->createTextNode((string)$value));
        $parent->appendChild($node);
    }
}

if (!function_exists('omoDecisionExportEncodeXml')) {
    function omoDecisionExportEncodeXml(array $data, $rootName = 'decision_export')
    {
        if (!class_exists('DOMDocument')) {
            return false;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $root = $document->createElement($rootName);
        $document->appendChild($root);

        foreach ($data as $key => $value) {
            omoDecisionExportAppendXmlNode($document, $root, (string)$key, $value);
        }

        return $document->saveXML();
    }
}

if (!function_exists('omoDecisionEnsureMethodExportLoaded')) {
    function omoDecisionEnsureMethodExportLoaded($method)
    {
        $definition = omoDecisionEnsureMethodSharedLoaded($method);
        if ($definition && !empty($definition['export_file']) && is_file((string)$definition['export_file'])) {
            require_once (string)$definition['export_file'];
        }

        return $definition;
    }
}
