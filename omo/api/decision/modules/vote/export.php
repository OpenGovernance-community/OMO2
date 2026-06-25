<?php

require_once dirname(__DIR__) . '/export_common.php';
require_once __DIR__ . '/shared.php';

use dbObject\DecisionProcess;
use dbObject\DecisionProposal;

if (!function_exists('omoDecisionVoteBuildExportRows')) {
    function omoDecisionVoteBuildExportRows(array $bundle)
    {
        $proposalVoteCounts = [];
        $submittedResponses = (array)($bundle['submittedResponses'] ?? []);
        $submittedVoteCount = count($submittedResponses);

        foreach ($submittedResponses as $submittedResponse) {
            $submittedProposalIds = omoDecisionVoteExtractSelectedProposalIds($submittedResponse);
            foreach ($submittedProposalIds as $submittedProposalId) {
                if (!isset($proposalVoteCounts[$submittedProposalId])) {
                    $proposalVoteCounts[$submittedProposalId] = 0;
                }
                $proposalVoteCounts[$submittedProposalId]++;
            }
        }

        $proposalOriginalOrder = [];
        foreach ((array)($bundle['proposals'] ?? []) as $proposalIndex => $proposal) {
            if (!$proposal instanceof DecisionProposal) {
                continue;
            }

            $proposalOriginalOrder[(int)$proposal->getId()] = (int)$proposal->get('position') > 0
                ? (int)$proposal->get('position')
                : ($proposalIndex + 1);
        }

        $rows = [];
        $orderedProposals = (array)($bundle['proposals'] ?? []);
        usort($orderedProposals, function ($left, $right) use ($proposalVoteCounts, $proposalOriginalOrder) {
            $leftId = $left instanceof DecisionProposal ? (int)$left->getId() : 0;
            $rightId = $right instanceof DecisionProposal ? (int)$right->getId() : 0;
            $leftVotes = (int)($proposalVoteCounts[$leftId] ?? 0);
            $rightVotes = (int)($proposalVoteCounts[$rightId] ?? 0);

            if ($leftVotes !== $rightVotes) {
                return $rightVotes <=> $leftVotes;
            }

            return (int)($proposalOriginalOrder[$leftId] ?? 0) <=> (int)($proposalOriginalOrder[$rightId] ?? 0);
        });

        foreach ($orderedProposals as $proposal) {
            if (!$proposal instanceof DecisionProposal) {
                continue;
            }

            $proposalId = (int)$proposal->getId();
            $voteCount = (int)($proposalVoteCounts[$proposalId] ?? 0);
            $percentage = $submittedVoteCount > 0
                ? round(($voteCount / $submittedVoteCount) * 100, 2)
                : 0;

            $rows[] = [
                'proposal_id' => $proposalId,
                'question' => trim((string)$proposal->get('title')),
                'result' => number_format($percentage, 2, '.', '') . ' %',
                'detail' => $voteCount . ' voix sur ' . $submittedVoteCount,
                'stats' => [
                    'vote_count' => $voteCount,
                    'submitted_response_count' => $submittedVoteCount,
                    'percentage' => $percentage,
                ],
            ];
        }

        return [
            'submitted_vote_count' => $submittedVoteCount,
            'rows' => $rows,
        ];
    }
}

if (!function_exists('omoDecisionVoteBuildExportPayload')) {
    function omoDecisionVoteBuildExportPayload(DecisionProcess $decision, $decisionGroup, array $context, $format, array $bundle)
    {
        $bundles = omoDecisionExportNormalizeBundleList($bundle);
        $primaryBundle = $bundles[0] ?? $bundle;
        $config = omoDecisionVoteBuildConfig($primaryBundle['methodOwner'] ?? $decision);

        if ($format === 'csv') {
            $csvRows = [];
            foreach ($bundles as $blockBundle) {
                $rowsData = omoDecisionVoteBuildExportRows($blockBundle);
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

        foreach ($bundles as $blockBundle) {
            $rowsData = omoDecisionVoteBuildExportRows($blockBundle);
            $decisionGroupItem = $blockBundle['decisionGroup'] ?? null;
            $submittedVoteCount += (int)($rowsData['submitted_vote_count'] ?? 0);

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
            'choice_mode' => (string)($config['choice_mode'] ?? 'single'),
            'max_choices' => (int)($config['max_choices'] ?? 1),
            'submitted_vote_count' => $submittedVoteCount,
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
