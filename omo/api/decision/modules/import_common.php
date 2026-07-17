<?php

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/registry.php';

use dbObject\DecisionGroup;
use dbObject\DecisionParticipant;
use dbObject\DecisionProcess;
use dbObject\DecisionProposal;

if (!function_exists('omoDecisionImportJsonResponse')) {
    function omoDecisionImportJsonResponse($statusCode, array $payload)
    {
        http_response_code((int)$statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('omoDecisionImportArrayIsList')) {
    function omoDecisionImportArrayIsList(array $value)
    {
        $index = 0;
        foreach (array_keys($value) as $key) {
            if ($key !== $index) {
                return false;
            }
            $index++;
        }

        return true;
    }
}

if (!function_exists('omoDecisionImportNormalizeSequentialItems')) {
    function omoDecisionImportNormalizeSequentialItems($value)
    {
        if (!is_array($value)) {
            return [];
        }

        if (array_key_exists('item', $value) && count($value) === 1) {
            $value = $value['item'];
        }

        if (!is_array($value)) {
            return [];
        }

        return omoDecisionImportArrayIsList($value) ? $value : [$value];
    }
}

if (!function_exists('omoDecisionImportBuildFallbackTitleFromFilename')) {
    function omoDecisionImportBuildFallbackTitleFromFilename($filename)
    {
        $name = pathinfo((string)$filename, PATHINFO_FILENAME);
        $name = preg_replace('/[^A-Za-z0-9]+/', ' ', (string)$name);
        $name = trim((string)$name);

        return $name !== '' ? $name : 'Import decision';
    }
}

if (!function_exists('omoDecisionImportDecodeJsonFile')) {
    function omoDecisionImportDecodeJsonFile($content)
    {
        $decoded = json_decode((string)$content, true);
        return is_array($decoded) ? $decoded : null;
    }
}

if (!function_exists('omoDecisionImportSimpleXmlToArray')) {
    function omoDecisionImportSimpleXmlToArray(SimpleXMLElement $element)
    {
        $children = $element->children();
        if (count($children) === 0) {
            return trim((string)$element);
        }

        $result = [];
        foreach ($children as $childName => $child) {
            $value = omoDecisionImportSimpleXmlToArray($child);
            if (array_key_exists($childName, $result)) {
                if (!is_array($result[$childName]) || !omoDecisionImportArrayIsList($result[$childName])) {
                    $result[$childName] = [$result[$childName]];
                }
                $result[$childName][] = $value;
            } else {
                $result[$childName] = $value;
            }
        }

        return $result;
    }
}

if (!function_exists('omoDecisionImportDecodeXmlFile')) {
    function omoDecisionImportDecodeXmlFile($content)
    {
        if (!function_exists('simplexml_load_string')) {
            return null;
        }

        $xml = @simplexml_load_string((string)$content, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$xml instanceof SimpleXMLElement) {
            return null;
        }

        return omoDecisionImportSimpleXmlToArray($xml);
    }
}

if (!function_exists('omoDecisionImportDecodeCsvFile')) {
    function omoDecisionImportDecodeCsvFile($content)
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return null;
        }

        fwrite($handle, (string)$content);
        rewind($handle);

        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);
            return null;
        }

        $normalizedHeader = [];
        foreach ($header as $index => $columnName) {
            $normalizedColumn = trim((string)$columnName);
            if ($index === 0) {
                $normalizedColumn = preg_replace('/^\xEF\xBB\xBF/', '', $normalizedColumn);
            }
            $normalizedHeader[] = $normalizedColumn;
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (!is_array($row)) {
                continue;
            }

            $assoc = [];
            foreach ($normalizedHeader as $index => $columnName) {
                $assoc[$columnName] = isset($row[$index]) ? trim((string)$row[$index]) : '';
            }
            $rows[] = $assoc;
        }

        fclose($handle);

        return [
            'header' => $normalizedHeader,
            'rows' => $rows,
        ];
    }
}

if (!function_exists('omoDecisionImportLoadUploadedFile')) {
    function omoDecisionImportLoadUploadedFile(array $file)
    {
        $originalName = trim((string)($file['name'] ?? ''));
        $tmpName = trim((string)($file['tmp_name'] ?? ''));
        if ($originalName === '' || $tmpName === '' || !is_uploaded_file($tmpName)) {
            return [
                'status' => false,
                'message' => 'Fichier d import invalide.',
            ];
        }

        $content = @file_get_contents($tmpName);
        if (!is_string($content) || $content === '') {
            return [
                'status' => false,
                'message' => 'Impossible de lire le fichier d import.',
            ];
        }

        $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
        $decoded = null;

        if ($extension === 'json') {
            $decoded = omoDecisionImportDecodeJsonFile($content);
        } elseif ($extension === 'xml') {
            $decoded = omoDecisionImportDecodeXmlFile($content);
        } elseif ($extension === 'csv') {
            $decoded = omoDecisionImportDecodeCsvFile($content);
        }

        if (!is_array($decoded)) {
            return [
                'status' => false,
                'message' => 'Format de fichier non reconnu ou contenu invalide.',
            ];
        }

        return [
            'status' => true,
            'name' => $originalName,
            'extension' => $extension,
            'content' => $content,
            'data' => $decoded,
        ];
    }
}

if (!function_exists('omoDecisionImportNormalizeProposalBlueprint')) {
    function omoDecisionImportNormalizeProposalBlueprint(array $proposal, $fallbackPosition)
    {
        return [
            'position' => max(1, (int)($proposal['position'] ?? $proposal['position_question'] ?? $fallbackPosition)),
            'title' => trim((string)($proposal['title'] ?? $proposal['question'] ?? '')),
            'description' => trim((string)($proposal['description'] ?? $proposal['detail_question'] ?? '')),
            'info_url' => trim((string)($proposal['info_url'] ?? $proposal['info_question'] ?? '')),
            'parameters' => omoDecisionImportNormalizeTypedStructure(omoDecisionModuleDecodeParameters($proposal['parameters'] ?? [])),
        ];
    }
}

if (!function_exists('omoDecisionImportNormalizeTypedStructure')) {
    function omoDecisionImportNormalizeTypedStructure($value)
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = omoDecisionImportNormalizeTypedStructure($item);
            }
            return $normalized;
        }

        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === 'true') {
            return true;
        }
        if ($trimmed === 'false') {
            return false;
        }
        if ($trimmed === 'null') {
            return null;
        }
        if (preg_match('/^-?[0-9]+$/', $trimmed)) {
            return (int)$trimmed;
        }
        if (preg_match('/^-?[0-9]+\.[0-9]+$/', $trimmed)) {
            return (float)$trimmed;
        }

        return $value;
    }
}

if (!function_exists('omoDecisionImportNormalizeBlockBlueprint')) {
    function omoDecisionImportNormalizeBlockBlueprint(array $block, array $fallback = [])
    {
        $method = DecisionProcess::normalizeEvaluationMethod(
            (string)($block['evaluation_method'] ?? $block['type_scrutin'] ?? ($fallback['method'] ?? ''))
        );
        $decisionType = DecisionProcess::normalizeDecisionType(
            (string)($block['decision_type'] ?? $block['type_decision'] ?? ($fallback['decision_type'] ?? DecisionProcess::TYPE_DECISION))
        );
        $proposals = [];
        $rawProposals = [];

        if (!empty($block['proposals'])) {
            $rawProposals = omoDecisionImportNormalizeSequentialItems($block['proposals']);
        } elseif (!empty($fallback['proposals'])) {
            $rawProposals = omoDecisionImportNormalizeSequentialItems($fallback['proposals']);
        }

        foreach ($rawProposals as $index => $proposal) {
            if (!is_array($proposal)) {
                continue;
            }

            $normalizedProposal = omoDecisionImportNormalizeProposalBlueprint($proposal, $index + 1);
            if ($normalizedProposal['title'] === '') {
                continue;
            }

            $proposals[] = $normalizedProposal;
        }

        usort($proposals, static function (array $left, array $right): int {
            return (int)$left['position'] <=> (int)$right['position'];
        });

        return [
            'method' => $method,
            'decision_type' => $decisionType,
            'title' => trim((string)($block['title'] ?? $block['bloc'] ?? '')),
            'description' => trim((string)($block['description'] ?? $block['detail_bloc'] ?? '')),
            'position' => max(1, (int)($block['position'] ?? $block['position_bloc'] ?? ($fallback['position'] ?? 1))),
            'method_config' => omoDecisionImportNormalizeTypedStructure(
                omoDecisionModuleDecodeParameters($block['method_config'] ?? ($fallback['method_config'] ?? []))
            ),
            'proposals' => $proposals,
        ];
    }
}

if (!function_exists('omoDecisionImportNormalizeStructuredData')) {
    function omoDecisionImportNormalizeStructuredData(array $data, $fallbackTitle)
    {
        $blueprint = is_array($data['blueprint'] ?? null) ? $data['blueprint'] : [];
        $decisionBlueprint = is_array($blueprint['decision'] ?? null) ? $blueprint['decision'] : [];
        $singleBlock = is_array($blueprint['block'] ?? null) ? $blueprint['block'] : [];
        $blockList = omoDecisionImportNormalizeSequentialItems($blueprint['blocks'] ?? []);
        $topLevelMethod = DecisionProcess::normalizeEvaluationMethod((string)($data['export']['method'] ?? ''));
        $topLevelMethodConfig = omoDecisionModuleDecodeParameters($data['method_config'] ?? []);

        if (count($blockList) === 0 && count($singleBlock) > 0) {
            $singleBlock['proposals'] = $singleBlock['proposals'] ?? ($blueprint['proposals'] ?? []);
            $singleBlock['method_config'] = $singleBlock['method_config'] ?? $topLevelMethodConfig;
            $blockList = [$singleBlock];
        }

        $blocks = [];
        foreach ($blockList as $index => $rawBlock) {
            if (!is_array($rawBlock)) {
                continue;
            }

            $normalizedBlock = omoDecisionImportNormalizeBlockBlueprint($rawBlock, [
                'method' => $topLevelMethod,
                'decision_type' => $decisionBlueprint['decision_type'] ?? DecisionProcess::TYPE_DECISION,
                'position' => $index + 1,
            ]);
            if ($normalizedBlock['title'] === '') {
                $normalizedBlock['title'] = 'Bloc ' . (string)($index + 1);
            }
            if (count($normalizedBlock['proposals']) === 0) {
                continue;
            }
            $blocks[] = $normalizedBlock;
        }

        return [
            'status' => count($blocks) > 0,
            'message' => count($blocks) > 0 ? '' : 'Aucun bloc importable trouve dans ce fichier.',
            'process' => [
                'title' => trim((string)($decisionBlueprint['title'] ?? '')) !== ''
                    ? trim((string)$decisionBlueprint['title'])
                    : omoDecisionImportBuildFallbackTitleFromFilename($fallbackTitle),
                'description' => trim((string)($decisionBlueprint['description'] ?? '')),
                'decision_type' => DecisionProcess::normalizeDecisionType((string)($decisionBlueprint['decision_type'] ?? DecisionProcess::TYPE_DECISION)),
                'visibility_type' => DecisionProcess::normalizeVisibilityType((string)($decisionBlueprint['visibility_type'] ?? DecisionProcess::getDefaultVisibilityType())),
                'parameters' => omoDecisionImportNormalizeTypedStructure(
                    omoDecisionModuleDecodeParameters($decisionBlueprint['parameters'] ?? [])
                ),
            ],
            'blocks' => $blocks,
        ];
    }
}

if (!function_exists('omoDecisionImportNormalizeCsvData')) {
    function omoDecisionImportNormalizeCsvData(array $data, $fallbackTitle)
    {
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $blocksByKey = [];

        foreach ($rows as $rowIndex => $row) {
            if (!is_array($row)) {
                continue;
            }

            $questionTitle = trim((string)($row['question'] ?? ''));
            $method = DecisionProcess::normalizeEvaluationMethod((string)($row['type_scrutin'] ?? ''));
            if ($questionTitle === '' || $method === '') {
                continue;
            }

            $blockId = trim((string)($row['source_bloc_id'] ?? ''));
            $blockTitle = trim((string)($row['bloc'] ?? ''));
            $blockPosition = max(1, (int)($row['position_bloc'] ?? 1));
            $groupKey = $blockId !== ''
                ? 'id:' . $blockId
                : implode('|', [$method, $blockTitle, (string)$blockPosition]);

            if (!isset($blocksByKey[$groupKey])) {
                $blocksByKey[$groupKey] = [
                    'method' => $method,
                    'decision_type' => DecisionProcess::normalizeDecisionType((string)($row['type_decision'] ?? DecisionProcess::TYPE_DECISION)),
                    'title' => $blockTitle !== '' ? $blockTitle : ('Bloc ' . (string)$blockPosition),
                    'description' => trim((string)($row['detail_bloc'] ?? '')),
                    'position' => $blockPosition,
                    'method_config' => [],
                    'proposals' => [],
                ];
            }

            $blocksByKey[$groupKey]['proposals'][] = omoDecisionImportNormalizeProposalBlueprint($row, $rowIndex + 1);
        }

        $blocks = array_values(array_filter($blocksByKey, static function (array $block): bool {
            return count((array)$block['proposals']) > 0;
        }));

        usort($blocks, static function (array $left, array $right): int {
            return (int)$left['position'] <=> (int)$right['position'];
        });

        foreach ($blocks as $index => $block) {
            usort($blocks[$index]['proposals'], static function (array $left, array $right): int {
                return (int)$left['position'] <=> (int)$right['position'];
            });
        }

        $firstBlock = $blocks[0] ?? [];

        return [
            'status' => count($blocks) > 0,
            'message' => count($blocks) > 0 ? '' : 'Aucune ligne importable trouvee dans ce CSV.',
            'process' => [
                'title' => omoDecisionImportBuildFallbackTitleFromFilename($fallbackTitle),
                'description' => '',
                'decision_type' => DecisionProcess::normalizeDecisionType((string)($firstBlock['decision_type'] ?? DecisionProcess::TYPE_DECISION)),
                'visibility_type' => DecisionProcess::getDefaultVisibilityType(),
                'parameters' => [],
            ],
            'blocks' => $blocks,
        ];
    }
}

if (!function_exists('omoDecisionImportNormalizePayload')) {
    function omoDecisionImportNormalizePayload(array $loadedFile)
    {
        $extension = (string)($loadedFile['extension'] ?? '');
        $data = is_array($loadedFile['data'] ?? null) ? $loadedFile['data'] : [];
        $fallbackTitle = (string)($loadedFile['name'] ?? 'decision-import');

        if ($extension === 'csv') {
            return omoDecisionImportNormalizeCsvData($data, $fallbackTitle);
        }

        return omoDecisionImportNormalizeStructuredData($data, $fallbackTitle);
    }
}

if (!function_exists('omoDecisionEnsureMethodImportLoaded')) {
    function omoDecisionEnsureMethodImportLoaded($method)
    {
        $definition = omoDecisionEnsureMethodSharedLoaded($method);
        if ($definition && !empty($definition['import_file']) && is_file((string)$definition['import_file'])) {
            require_once (string)$definition['import_file'];
        }

        return $definition;
    }
}

if (!function_exists('omoDecisionImportEnsureOwnerParticipant')) {
    function omoDecisionImportEnsureOwnerParticipant(DecisionProcess $decision, $userId)
    {
        $decisionId = (int)$decision->getId();
        $userId = (int)$userId;
        if ($decisionId <= 0 || $userId <= 0) {
            return [
                'status' => false,
                'message' => 'Participant proprietaire invalide.',
            ];
        }

        $participant = DecisionParticipant::findByDecisionAndUser($decisionId, $userId);
        if (!$participant instanceof DecisionParticipant) {
            $participant = new DecisionParticipant();
        }

        $participant->set('IDdecision_process', $decisionId);
        $participant->set('IDuser', $userId);
        $participant->set('role', DecisionParticipant::ROLE_OWNER);
        $participant->set('status', DecisionParticipant::STATUS_ACTIVE);
        $participant->set('active', 1);

        $saveResult = $participant->save();
        return !empty($saveResult['status'])
            ? ['status' => true, 'participant' => $participant]
            : ['status' => false, 'message' => 'Impossible d enregistrer le proprietaire du scrutin.'];
    }
}

if (!function_exists('omoDecisionImportSyncGroupProposals')) {
    function omoDecisionImportSyncGroupProposals(DecisionProcess $decision, DecisionGroup $group, array $proposals, $methodKey)
    {
        $decisionId = (int)$decision->getId();
        $groupId = (int)$group->getId();
        if ($decisionId <= 0 || $groupId <= 0) {
            return [
                'status' => false,
                'message' => 'Bloc de decision invalide pour les propositions.',
            ];
        }

        $existingActiveProposals = [];
        foreach ($group->getProposals(false) as $proposal) {
            if ($proposal instanceof DecisionProposal && (int)$proposal->get('active') === 1) {
                $existingActiveProposals[] = $proposal;
            }
        }

        foreach ($proposals as $index => $proposalItem) {
            $proposal = $existingActiveProposals[$index] ?? new DecisionProposal();
            $proposal->set('IDdecision_process', $decisionId);
            $proposal->set('IDdecision_group', $groupId);
            $proposal->set('title', (string)$proposalItem['title']);
            $proposal->set('description', trim((string)($proposalItem['description'] ?? '')) !== '' ? (string)$proposalItem['description'] : null);
            $proposal->set('info_url', trim((string)($proposalItem['info_url'] ?? '')) !== '' ? (string)$proposalItem['info_url'] : null);
            $proposal->set('position', $index + 1);
            $proposal->set('parameters', [
                $methodKey => [
                    'ballot_position' => $index + 1,
                ],
            ]);
            $proposal->set('active', 1);

            $saveResult = $proposal->save();
            if (empty($saveResult['status'])) {
                return [
                    'status' => false,
                    'message' => 'Impossible d enregistrer une proposition importee.',
                ];
            }
        }

        for ($index = count($proposals); $index < count($existingActiveProposals); $index++) {
            $proposal = $existingActiveProposals[$index];
            $proposal->set('active', 0);
            $saveResult = $proposal->save();
            if (empty($saveResult['status'])) {
                return [
                    'status' => false,
                    'message' => 'Impossible d archiver une ancienne proposition.',
                ];
            }
        }

        return ['status' => true];
    }
}
