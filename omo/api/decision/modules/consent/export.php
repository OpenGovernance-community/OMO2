<?php

require_once dirname(__DIR__) . '/export_common.php';
require_once __DIR__ . '/shared.php';

use dbObject\DecisionProcess;
use dbObject\DecisionProposal;

if (!function_exists('omoDecisionConsentBuildExportRows')) {
    function omoDecisionConsentBuildExportRows(array $bundle)
    {
        $choices = omoDecisionConsentGetChoices();
        $proposalObjects = (array)($bundle['proposals'] ?? []);
        $proposalStats = omoDecisionConsentBuildStats($proposalObjects, (array)($bundle['submittedResponses'] ?? []));
        $submittedVoteCount = count((array)($bundle['submittedResponses'] ?? []));
        $rows = [];

        foreach ($proposalObjects as $proposal) {
            if (!$proposal instanceof DecisionProposal) {
                continue;
            }

            $proposalId = (int)$proposal->getId();
            $stat = (array)($proposalStats[$proposalId] ?? []);
            $distribution = (array)($stat['distribution'] ?? []);
            $detailParts = [];

            foreach ($choices as $choiceKey => $choiceLabel) {
                $detailParts[] = $choiceLabel . ': ' . (int)($distribution[$choiceKey] ?? 0);
            }

            $rows[] = [
                'proposal_id' => $proposalId,
                'question' => trim((string)$proposal->get('title')),
                'result' => !empty($stat['has_objection'])
                    ? ((int)($stat['objection_count'] ?? 0) . ' objection(s)')
                    : 'Sans objection',
                'detail' => implode(' | ', $detailParts),
                'stats' => [
                    'count' => (int)($stat['count'] ?? 0),
                    'objection_count' => (int)($stat['objection_count'] ?? 0),
                    'has_objection' => !empty($stat['has_objection']),
                    'dominant_choice' => (string)($stat['dominant_choice'] ?? 'no_objection'),
                    'distribution' => omoDecisionExportNormalizeValue($distribution),
                ],
            ];
        }

        return [
            'choice_catalog' => $choices,
            'submitted_vote_count' => $submittedVoteCount,
            'proposal_stats' => $proposalStats,
            'rows' => $rows,
        ];
    }
}

if (!function_exists('omoDecisionConsentBuildExportPayload')) {
    function omoDecisionConsentBuildExportPayload(DecisionProcess $decision, $decisionGroup, array $context, $format, array $bundle)
    {
        $bundles = omoDecisionExportNormalizeBundleList($bundle);

        if ($format === 'csv') {
            $csvRows = [];
            foreach ($bundles as $blockBundle) {
                $rowsData = omoDecisionConsentBuildExportRows($blockBundle);
                $proposalById = [];
                foreach ((array)($blockBundle['proposals'] ?? []) as $proposal) {
                    if ($proposal instanceof DecisionProposal) {
                        $proposalById[(int)$proposal->getId()] = $proposal;
                    }
                }

                foreach ((array)$rowsData['rows'] as $row) {
                    $proposal = $proposalById[(int)($row['proposal_id'] ?? 0)] ?? null;
                    if (!$proposal instanceof DecisionProposal) {
                        continue;
                    }

                    $metadata = omoDecisionExportBuildCsvMetadataRow($blockBundle, $proposal);
                    $csvRows[] = omoDecisionExportFlattenCsvRow($metadata, (string)$row['result'], (string)$row['detail']);
                }
            }

            return omoDecisionExportBuildDownloadResult(
                $decision,
                'csv',
                omoDecisionExportEncodeCsv(omoDecisionExportGetCsvHeader(), $csvRows),
                'text/csv; charset=UTF-8'
            );
        }

        $structuredData = omoDecisionExportBuildBaseJsonData($bundle);
        $blockExports = [];
        $allRows = [];
        $submittedVoteCount = 0;
        $choiceCatalog = [];

        foreach ($bundles as $blockBundle) {
            $rowsData = omoDecisionConsentBuildExportRows($blockBundle);
            $decisionGroupItem = $blockBundle['decisionGroup'] ?? null;
            $submittedVoteCount += (int)($rowsData['submitted_vote_count'] ?? 0);
            if ($choiceCatalog === []) {
                $choiceCatalog = omoDecisionExportNormalizeValue((array)($rowsData['choice_catalog'] ?? []));
            }

            $blockRows = array_map(static function (array $row) use (&$allRows, $decisionGroupItem): array {
                $normalized = [
                    'proposal_id' => (int)$row['proposal_id'],
                    'question' => (string)$row['question'],
                    'result' => (string)$row['result'],
                    'detail' => (string)$row['detail'],
                    'stats' => (array)$row['stats'],
                    'source_block_id' => $decisionGroupItem instanceof \dbObject\DecisionGroup ? (int)$decisionGroupItem->getId() : 0,
                ];
                $allRows[] = $normalized;
                return $normalized;
            }, (array)$rowsData['rows']);

            $blockExports[] = [
                'source_block_id' => $decisionGroupItem instanceof \dbObject\DecisionGroup ? (int)$decisionGroupItem->getId() : 0,
                'title' => $decisionGroupItem instanceof \dbObject\DecisionGroup ? trim((string)$decisionGroupItem->get('title')) : '',
                'position' => $decisionGroupItem instanceof \dbObject\DecisionGroup ? (int)$decisionGroupItem->get('position') : 1,
                'submitted_vote_count' => (int)($rowsData['submitted_vote_count'] ?? 0),
                'rows' => $blockRows,
            ];
        }

        $structuredData['module_export'] = [
            'submitted_vote_count' => $submittedVoteCount,
            'choice_catalog' => $choiceCatalog,
            'rows' => $allRows,
            'blocks' => $blockExports,
        ];

        if ($format === 'xml') {
            $xmlContent = omoDecisionExportEncodeXml($structuredData);
            if (!is_string($xmlContent) || $xmlContent === '') {
                return [
                    'status' => false,
                    'message' => 'Impossible de generer l export XML.',
                ];
            }

            return omoDecisionExportBuildDownloadResult(
                $decision,
                'xml',
                $xmlContent,
                'application/xml; charset=UTF-8'
            );
        }

        return omoDecisionExportBuildDownloadResult(
            $decision,
            'json',
            json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
            'application/json; charset=UTF-8'
        );
    }
}
