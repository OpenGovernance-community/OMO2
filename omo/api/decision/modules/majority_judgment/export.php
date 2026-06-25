<?php

require_once dirname(__DIR__) . '/export_common.php';
require_once __DIR__ . '/shared.php';

use dbObject\DecisionProcess;
use dbObject\DecisionProposal;

if (!function_exists('omoDecisionMajorityJudgmentBuildExportRows')) {
    function omoDecisionMajorityJudgmentBuildExportRows(array $bundle)
    {
        $config = omoDecisionMajorityJudgmentBuildConfig($bundle['methodOwner'] ?? []);
        $proposalObjects = (array)($bundle['proposals'] ?? []);
        $proposalStats = omoDecisionMajorityJudgmentBuildStats($proposalObjects, (array)($bundle['submittedResponses'] ?? []), $config);
        $mentions = (array)($config['all_mentions'] ?? []);
        $submittedVoteCount = count((array)($bundle['submittedResponses'] ?? []));

        $proposalOriginalOrder = [];
        foreach ($proposalObjects as $proposalIndex => $proposal) {
            if (!$proposal instanceof DecisionProposal) {
                continue;
            }

            $proposalOriginalOrder[(int)$proposal->getId()] = (int)$proposal->get('position') > 0
                ? (int)$proposal->get('position')
                : ($proposalIndex + 1);
        }

        usort($proposalObjects, function ($left, $right) use ($proposalStats, $proposalOriginalOrder, $config) {
            $leftId = $left instanceof DecisionProposal ? (int)$left->getId() : 0;
            $rightId = $right instanceof DecisionProposal ? (int)$right->getId() : 0;
            $comparison = omoDecisionMajorityJudgmentCompareStats(
                (array)($proposalStats[$leftId] ?? []),
                (array)($proposalStats[$rightId] ?? []),
                $config
            );

            if ($comparison !== 0) {
                return $comparison;
            }

            return (int)($proposalOriginalOrder[$leftId] ?? 0) <=> (int)($proposalOriginalOrder[$rightId] ?? 0);
        });

        $rows = [];
        foreach ($proposalObjects as $proposal) {
            if (!$proposal instanceof DecisionProposal) {
                continue;
            }

            $proposalId = (int)$proposal->getId();
            $stat = (array)($proposalStats[$proposalId] ?? []);
            $distribution = omoDecisionExportNormalizeValue((array)($stat['distribution'] ?? []));
            $detailParts = [];
            foreach ($distribution as $score => $count) {
                if ((int)$count <= 0) {
                    continue;
                }

                $detailParts[] = (string)($mentions[(int)$score] ?? ('Score ' . (int)$score)) . ': ' . (int)$count;
            }

            if ((int)($stat['no_opinion_count'] ?? 0) > 0) {
                $detailParts[] = 'Sans avis: ' . (int)$stat['no_opinion_count'];
            }

            $rows[] = [
                'proposal_id' => $proposalId,
                'question' => trim((string)$proposal->get('title')),
                'result' => trim((string)($stat['majority_label'] ?? '')) !== ''
                    ? (string)$stat['majority_label']
                    : 'Aucune mention',
                'detail' => count($detailParts) > 0
                    ? implode(' | ', $detailParts)
                    : 'Aucune evaluation soumise',
                'stats' => [
                    'count' => (int)($stat['count'] ?? 0),
                    'counted_count' => (int)($stat['counted_count'] ?? 0),
                    'no_opinion_count' => (int)($stat['no_opinion_count'] ?? 0),
                    'majority_score' => $stat['majority_score'] ?? null,
                    'majority_label' => (string)($stat['majority_label'] ?? ''),
                    'distribution' => $distribution,
                ],
            ];
        }

        return [
            'config' => $config,
            'submitted_vote_count' => $submittedVoteCount,
            'proposal_stats' => $proposalStats,
            'rows' => $rows,
        ];
    }
}

if (!function_exists('omoDecisionMajorityJudgmentBuildExportPayload')) {
    function omoDecisionMajorityJudgmentBuildExportPayload(DecisionProcess $decision, $decisionGroup, array $context, $format, array $bundle)
    {
        $bundles = omoDecisionExportNormalizeBundleList($bundle);
        $primaryBundle = $bundles[0] ?? $bundle;

        if ($format === 'csv') {
            $csvRows = [];
            foreach ($bundles as $blockBundle) {
                $rowsData = omoDecisionMajorityJudgmentBuildExportRows($blockBundle);
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
        $mentionCatalog = [];

        foreach ($bundles as $blockBundle) {
            $rowsData = omoDecisionMajorityJudgmentBuildExportRows($blockBundle);
            $decisionGroupItem = $blockBundle['decisionGroup'] ?? null;
            $submittedVoteCount += (int)($rowsData['submitted_vote_count'] ?? 0);
            if ($mentionCatalog === []) {
                $mentionCatalog = omoDecisionExportNormalizeValue((array)($rowsData['config']['all_mentions'] ?? []));
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
            'mention_catalog' => $mentionCatalog,
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
