<?php
namespace dbObject;

class OrganizationalMaturityAssessment extends DbObject
{
    public static function tableName()
    
    {
        return 'organizational_maturity_assessment';
    }

    public static function rules()
    {
        return [
            [['id'], 'integer'],
            [['IDuser', 'IDorganization', 'IDinvitation'], 'fk'],
            [['public_token', 'private_token_hash'], 'string'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDuser' => 'Utilisateur',
            'IDorganization' => 'Organisation',
            'public_token' => 'Lien public',
            'private_token_hash' => 'Cle de modification',
            'created_at' => 'Cree le',
            'updated_at' => 'Modifie le',
        ];
    }

    public static function attributeLength()
    {
        return [
            'public_token' => 64,
            'private_token_hash' => 64,
        ];
    }

    public static function findByPublicToken($token)
    {
        $token = trim((string)$token);
        if (!preg_match('/^(?:[a-f0-9]{16}|[a-f0-9]{48})$/i', $token)) {
            return null;
        }

        $row = self::fetchRow(
            'SELECT * FROM organizational_maturity_assessment WHERE public_token = :public_token LIMIT 1',
            ['public_token' => $token]
        );
        if ($row === false) {
            return null;
        }

        $assessment = new self();
        $assessment->loadFromArray($row);
        $assessment->setId((int)$row['id']);
        return $assessment;
    }

    public static function findByPrivateToken($token)
    {
        $token = trim((string)$token);
        if (!preg_match('/^[a-f0-9]{48,64}$/i', $token)) {
            return null;
        }

        $row = self::fetchRow(
            'SELECT * FROM organizational_maturity_assessment WHERE private_token_hash = :private_token_hash LIMIT 1',
            ['private_token_hash' => hash('sha256', $token)]
        );
        if ($row === false) {
            return null;
        }

        $assessment = new self();
        $assessment->loadFromArray($row);
        $assessment->setId((int)$row['id']);
        return $assessment;
    }

    public static function findByInvitation($invitationId)
    {
        $row = self::fetchRow('SELECT * FROM organizational_maturity_assessment WHERE IDinvitation = :invitation_id LIMIT 1', ['invitation_id' => (int)$invitationId]);
        if ($row === false) return null;
        $assessment = new self(); $assessment->loadFromArray($row); $assessment->setId((int)$row['id']); return $assessment;
    }

    public static function getOrganizationAggregate($organizationId)
    {
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return ['principles' => [], 'commonGround' => ['today' => [], 'tomorrow' => []], 'participatedMembers' => 0, 'activeMembers' => 0];
        }

        $principleRows = self::fetchAll(
            'SELECT r.principle_number,
                    COUNT(*) AS response_count,
                    AVG(r.affinity_score) AS affinity_average,
                    STDDEV_POP(r.affinity_score) AS affinity_stddev,
                    AVG(r.today_score) AS today_average,
                    STDDEV_POP(r.today_score) AS today_stddev,
                    AVG(r.tomorrow_score) AS tomorrow_average,
                    STDDEV_POP(r.tomorrow_score) AS tomorrow_stddev,
                    SUM(CASE WHEN r.today_score <= 2 THEN 1 ELSE 0 END) AS today_low_count,
                    SUM(CASE WHEN r.today_score = 3 THEN 1 ELSE 0 END) AS today_middle_count,
                    SUM(CASE WHEN r.today_score >= 4 THEN 1 ELSE 0 END) AS today_high_count,
                    SUM(CASE WHEN r.tomorrow_score <= 2 THEN 1 ELSE 0 END) AS tomorrow_low_count,
                    SUM(CASE WHEN r.tomorrow_score = 3 THEN 1 ELSE 0 END) AS tomorrow_middle_count,
                    SUM(CASE WHEN r.tomorrow_score >= 4 THEN 1 ELSE 0 END) AS tomorrow_high_count,
                    SUM(CASE WHEN r.affinity_score <= 2 THEN 1 ELSE 0 END) AS affinity_low_count,
                    SUM(CASE WHEN r.affinity_score = 3 THEN 1 ELSE 0 END) AS affinity_middle_count,
                    SUM(CASE WHEN r.affinity_score >= 4 THEN 1 ELSE 0 END) AS affinity_high_count
             FROM organizational_maturity_assessment_response r
             INNER JOIN organizational_maturity_assessment a ON a.id = r.IDassessment
             WHERE a.IDorganization = :organization_id
             GROUP BY r.principle_number
             ORDER BY r.principle_number ASC',
            ['organization_id' => $organizationId]
        );

        $participationRow = self::fetchRow(
            'SELECT COUNT(DISTINCT uo.IDuser) AS active_members,
                    COUNT(DISTINCT CASE WHEN a.id IS NOT NULL THEN uo.IDuser END) AS participated_members
             FROM user_organization uo
             LEFT JOIN organizational_maturity_assessment a
                ON a.IDorganization = uo.IDorganization
               AND a.IDuser = uo.IDuser
             WHERE uo.IDorganization = :organization_id
               AND uo.active = 1',
            ['organization_id' => $organizationId]
        );

        $principles = [];
        foreach (is_array($principleRows) ? $principleRows : [] as $row) {
            $principleNumber = (int)($row['principle_number'] ?? 0);
            if ($principleNumber < 1 || $principleNumber > 10) {
                continue;
            }
            $principles[$principleNumber] = [
                'responseCount' => (int)($row['response_count'] ?? 0),
                'affinityAverage' => (float)($row['affinity_average'] ?? 0),
                'affinityStddev' => (float)($row['affinity_stddev'] ?? 0),
                'affinityAgreement' => self::classifyDistribution(
                    (int)($row['response_count'] ?? 0),
                    (float)($row['affinity_stddev'] ?? 0),
                    (int)($row['affinity_low_count'] ?? 0),
                    (int)($row['affinity_middle_count'] ?? 0),
                    (int)($row['affinity_high_count'] ?? 0)
                ),
                'todayAverage' => (float)($row['today_average'] ?? 0),
                'todayStddev' => (float)($row['today_stddev'] ?? 0),
                'tomorrowAverage' => (float)($row['tomorrow_average'] ?? 0),
                'tomorrowStddev' => (float)($row['tomorrow_stddev'] ?? 0),
                'todayAgreement' => self::classifyDistribution(
                    (int)($row['response_count'] ?? 0),
                    (float)($row['today_stddev'] ?? 0),
                    (int)($row['today_low_count'] ?? 0),
                    (int)($row['today_middle_count'] ?? 0),
                    (int)($row['today_high_count'] ?? 0)
                ),
                'tomorrowAgreement' => self::classifyDistribution(
                    (int)($row['response_count'] ?? 0),
                    (float)($row['tomorrow_stddev'] ?? 0),
                    (int)($row['tomorrow_low_count'] ?? 0),
                    (int)($row['tomorrow_middle_count'] ?? 0),
                    (int)($row['tomorrow_high_count'] ?? 0)
                ),
            ];
        }

        return [
            'principles' => $principles,
            'commonGround' => self::buildCommonGround($principles),
            'participatedMembers' => (int)($participationRow['participated_members'] ?? 0),
            'activeMembers' => (int)($participationRow['active_members'] ?? 0),
        ];
    }

    private static function buildCommonGround(array $principles)
    {
        $candidates = ['today' => [], 'tomorrow' => []];
        foreach ($principles as $principleNumber => $principle) {
            foreach (['today', 'tomorrow'] as $period) {
                if ((int)($principle['responseCount'] ?? 0) < 3) {
                    continue;
                }
                $standardDeviation = (float)($principle[$period . 'Stddev'] ?? 0);
                $candidates[$period][] = [
                    'principle' => (int)$principleNumber,
                    'period' => $period,
                    'average' => round((float)($principle[$period . 'Average'] ?? 0), 2),
                    'standardDeviation' => round($standardDeviation, 2),
                    'alignmentScore' => self::calculateAlignmentScore($standardDeviation),
                ];
            }
        }

        $sortCandidates = static function (array $first, array $second): int {
            $scoreComparison = $second['alignmentScore'] <=> $first['alignmentScore'];
            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }
            $deviationComparison = $first['standardDeviation'] <=> $second['standardDeviation'];
            if ($deviationComparison !== 0) {
                return $deviationComparison;
            }
            return $first['principle'] <=> $second['principle'];
        };
        usort($candidates['today'], $sortCandidates);
        usort($candidates['tomorrow'], $sortCandidates);

        return [
            'today' => array_slice($candidates['today'], 0, 4),
            'tomorrow' => array_slice($candidates['tomorrow'], 0, 4),
        ];
    }

    private static function calculateAlignmentScore($standardDeviation)
    {
        $variance = max(0.0, (float)$standardDeviation * (float)$standardDeviation);
        $normalizedVariance = min(1.0, $variance / 2.0);
        return (int)round(100 * (1.0 - $normalizedVariance));
    }

    public static function getOrganizationProfileAnalysis($organizationId)
    {
        $profiles = self::getOrganizationProfiles((int)$organizationId);
        $participantCount = count($profiles);
        if ($participantCount < 4) {
            return [
                'status' => 'insufficient',
                'participantCount' => $participantCount,
                'silhouette' => null,
                'groups' => [],
            ];
        }

        $featureNames = [];
        foreach (['today', 'tomorrow', 'affinity'] as $dimension) {
            for ($principle = 1; $principle <= 10; $principle++) {
                $featureNames[] = $dimension . '_' . $principle;
            }
        }

        $rawVectors = [];
        foreach ($profiles as $profile) {
            $vector = [];
            foreach ($featureNames as $featureName) {
                $vector[] = (float)$profile[$featureName];
            }
            $rawVectors[] = $vector;
        }
        $vectors = self::standardizeVectors($rawVectors);
        $maximumGroupCount = min(5, (int)floor($participantCount / 2));
        $best = null;
        for ($groupCount = 2; $groupCount <= $maximumGroupCount; $groupCount++) {
            $candidate = self::findBestKMeans($vectors, $groupCount);
            if ($candidate === null || min($candidate['clusterSizes']) < 2) {
                continue;
            }
            $candidate['silhouette'] = self::calculateSilhouette($vectors, $candidate['assignments'], $groupCount);
            if ($best === null || $candidate['silhouette'] > $best['silhouette']) {
                $best = $candidate;
            }
        }

        if ($best === null || $best['silhouette'] < 0.15) {
            return [
                'status' => 'no_structure',
                'participantCount' => $participantCount,
                'silhouette' => $best !== null ? round((float)$best['silhouette'], 3) : null,
                'groups' => [],
            ];
        }

        $groups = self::characterizeGroups($rawVectors, $featureNames, $best['assignments'], count($best['clusterSizes']));
        return [
            'status' => 'clustered',
            'participantCount' => $participantCount,
            'silhouette' => round((float)$best['silhouette'], 3),
            'groups' => $groups,
        ];
    }

    private static function classifyDistribution($responseCount, $standardDeviation, $lowCount, $middleCount, $highCount)
    {
        $responseCount = (int)$responseCount;
        if ($responseCount < 3) {
            return 'insufficient';
        }

        $lowShare = (int)$lowCount / $responseCount;
        $middleShare = (int)$middleCount / $responseCount;
        $highShare = (int)$highCount / $responseCount;
        if (
            $responseCount >= 4
            && $lowShare >= 0.25
            && $highShare >= 0.25
            && $middleShare <= 0.35
            && (float)$standardDeviation >= 0.95
        ) {
            return 'polarized';
        }

        return (float)$standardDeviation <= 0.70 ? 'aligned' : 'dispersed';
    }

    private static function getOrganizationProfiles($organizationId)
    {
        if ((int)$organizationId <= 0) {
            return [];
        }

        $rows = self::fetchAll(
            'SELECT a.id AS assessment_id,
                    a.IDuser,
                    r.principle_number,
                    r.affinity_score,
                    r.today_score,
                    r.tomorrow_score
             FROM organizational_maturity_assessment a
             INNER JOIN organizational_maturity_assessment_response r ON r.IDassessment = a.id
             WHERE a.IDorganization = :organization_id
             ORDER BY a.id ASC, r.principle_number ASC',
            ['organization_id' => (int)$organizationId]
        );

        $profileTotals = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $assessmentId = (int)($row['assessment_id'] ?? 0);
            $userId = (int)($row['IDuser'] ?? 0);
            $principle = (int)($row['principle_number'] ?? 0);
            if ($assessmentId <= 0 || $principle < 1 || $principle > 10) {
                continue;
            }
            $profileKey = $userId > 0 ? 'user_' . $userId : 'assessment_' . $assessmentId;
            if (!isset($profileTotals[$profileKey])) {
                $profileTotals[$profileKey] = [];
            }
            foreach (['affinity', 'today', 'tomorrow'] as $dimension) {
                $databaseField = $dimension . '_score';
                $featureName = $dimension . '_' . $principle;
                if (!isset($profileTotals[$profileKey][$featureName])) {
                    $profileTotals[$profileKey][$featureName] = ['total' => 0.0, 'count' => 0];
                }
                $profileTotals[$profileKey][$featureName]['total'] += (float)($row[$databaseField] ?? 0);
                $profileTotals[$profileKey][$featureName]['count']++;
            }
        }

        $profiles = [];
        foreach ($profileTotals as $profileTotal) {
            if (count($profileTotal) !== 30) {
                continue;
            }
            $profile = [];
            foreach ($profileTotal as $featureName => $total) {
                if ((int)$total['count'] <= 0) {
                    continue 2;
                }
                $profile[$featureName] = (float)$total['total'] / (int)$total['count'];
            }
            $profiles[] = $profile;
        }
        return $profiles;
    }

    private static function standardizeVectors(array $vectors)
    {
        $rowCount = count($vectors);
        $dimensionCount = $rowCount > 0 ? count($vectors[0]) : 0;
        if ($rowCount === 0 || $dimensionCount === 0) {
            return [];
        }

        $means = array_fill(0, $dimensionCount, 0.0);
        foreach ($vectors as $vector) {
            foreach ($vector as $index => $value) {
                $means[$index] += (float)$value;
            }
        }
        foreach ($means as $index => $total) {
            $means[$index] = $total / $rowCount;
        }

        $standardDeviations = array_fill(0, $dimensionCount, 0.0);
        foreach ($vectors as $vector) {
            foreach ($vector as $index => $value) {
                $difference = (float)$value - $means[$index];
                $standardDeviations[$index] += $difference * $difference;
            }
        }
        foreach ($standardDeviations as $index => $total) {
            $standardDeviations[$index] = sqrt($total / $rowCount);
        }

        $standardized = [];
        foreach ($vectors as $vector) {
            $row = [];
            foreach ($vector as $index => $value) {
                $deviation = $standardDeviations[$index];
                $row[] = $deviation > 0.000001 ? ((float)$value - $means[$index]) / $deviation : 0.0;
            }
            $standardized[] = $row;
        }
        return $standardized;
    }

    private static function findBestKMeans(array $vectors, $groupCount)
    {
        $best = null;
        $startCount = min(6, count($vectors));
        for ($start = 0; $start < $startCount; $start++) {
            $candidate = self::runKMeans($vectors, (int)$groupCount, $start);
            if ($candidate !== null && ($best === null || $candidate['inertia'] < $best['inertia'])) {
                $best = $candidate;
            }
        }
        return $best;
    }

    private static function runKMeans(array $vectors, $groupCount, $startIndex)
    {
        $rowCount = count($vectors);
        if ($rowCount < $groupCount || $groupCount < 2) {
            return null;
        }

        $centers = [$vectors[$startIndex % $rowCount]];
        while (count($centers) < $groupCount) {
            $bestIndex = null;
            $bestDistance = -1.0;
            foreach ($vectors as $index => $vector) {
                $nearestDistance = INF;
                foreach ($centers as $center) {
                    $nearestDistance = min($nearestDistance, self::squaredDistance($vector, $center));
                }
                if ($nearestDistance > $bestDistance) {
                    $bestDistance = $nearestDistance;
                    $bestIndex = $index;
                }
            }
            if ($bestIndex === null) {
                return null;
            }
            $centers[] = $vectors[$bestIndex];
        }

        $assignments = array_fill(0, $rowCount, -1);
        for ($iteration = 0; $iteration < 60; $iteration++) {
            $changed = false;
            foreach ($vectors as $index => $vector) {
                $nearestGroup = 0;
                $nearestDistance = INF;
                foreach ($centers as $group => $center) {
                    $distance = self::squaredDistance($vector, $center);
                    if ($distance < $nearestDistance) {
                        $nearestDistance = $distance;
                        $nearestGroup = $group;
                    }
                }
                if ($assignments[$index] !== $nearestGroup) {
                    $assignments[$index] = $nearestGroup;
                    $changed = true;
                }
            }

            $totals = array_fill(0, $groupCount, array_fill(0, count($vectors[0]), 0.0));
            $clusterSizes = array_fill(0, $groupCount, 0);
            foreach ($vectors as $index => $vector) {
                $group = $assignments[$index];
                $clusterSizes[$group]++;
                foreach ($vector as $dimension => $value) {
                    $totals[$group][$dimension] += (float)$value;
                }
            }
            if (min($clusterSizes) === 0) {
                return null;
            }
            foreach ($totals as $group => $total) {
                foreach ($total as $dimension => $value) {
                    $totals[$group][$dimension] = $value / $clusterSizes[$group];
                }
            }
            $centers = $totals;
            if (!$changed) {
                break;
            }
        }

        $inertia = 0.0;
        foreach ($vectors as $index => $vector) {
            $inertia += self::squaredDistance($vector, $centers[$assignments[$index]]);
        }
        return [
            'assignments' => $assignments,
            'clusterSizes' => $clusterSizes,
            'inertia' => $inertia,
        ];
    }

    private static function calculateSilhouette(array $vectors, array $assignments, $groupCount)
    {
        $scores = [];
        foreach ($vectors as $index => $vector) {
            $ownGroup = $assignments[$index];
            $ownDistances = [];
            $otherDistances = array_fill(0, $groupCount, []);
            foreach ($vectors as $otherIndex => $otherVector) {
                if ($otherIndex === $index) {
                    continue;
                }
                $distance = sqrt(self::squaredDistance($vector, $otherVector));
                $otherGroup = $assignments[$otherIndex];
                if ($otherGroup === $ownGroup) {
                    $ownDistances[] = $distance;
                } else {
                    $otherDistances[$otherGroup][] = $distance;
                }
            }
            if (count($ownDistances) === 0) {
                $scores[] = 0.0;
                continue;
            }
            $within = array_sum($ownDistances) / count($ownDistances);
            $nearestOther = INF;
            foreach ($otherDistances as $group => $distances) {
                if ($group === $ownGroup || count($distances) === 0) {
                    continue;
                }
                $nearestOther = min($nearestOther, array_sum($distances) / count($distances));
            }
            $denominator = max($within, $nearestOther);
            $scores[] = $denominator > 0 && is_finite($nearestOther) ? ($nearestOther - $within) / $denominator : 0.0;
        }
        return count($scores) > 0 ? array_sum($scores) / count($scores) : 0.0;
    }

    private static function characterizeGroups(array $vectors, array $featureNames, array $assignments, $groupCount)
    {
        $participantCount = count($vectors);
        $dimensionCount = count($featureNames);
        $overallMeans = array_fill(0, $dimensionCount, 0.0);
        foreach ($vectors as $vector) {
            foreach ($vector as $index => $value) {
                $overallMeans[$index] += (float)$value;
            }
        }
        foreach ($overallMeans as $index => $total) {
            $overallMeans[$index] = $total / $participantCount;
        }

        $groups = [];
        for ($group = 0; $group < $groupCount; $group++) {
            $memberVectors = [];
            foreach ($vectors as $index => $vector) {
                if ($assignments[$index] === $group) {
                    $memberVectors[] = $vector;
                }
            }
            $memberCount = count($memberVectors);
            $averages = ['today' => [], 'tomorrow' => [], 'affinity' => []];
            $features = [];
            for ($dimensionIndex = 0; $dimensionIndex < $dimensionCount; $dimensionIndex++) {
                $values = array_column($memberVectors, $dimensionIndex);
                $mean = array_sum($values) / $memberCount;
                $variance = 0.0;
                foreach ($values as $value) {
                    $difference = (float)$value - $mean;
                    $variance += $difference * $difference;
                }
                $standardDeviation = sqrt($variance / $memberCount);
                [$dimension, $principle] = explode('_', $featureNames[$dimensionIndex], 2);
                $averages[$dimension][(int)$principle] = round($mean, 2);
                $difference = $mean - $overallMeans[$dimensionIndex];
                if (abs($difference) < 0.35) {
                    continue;
                }
                $features[] = [
                    'dimension' => $dimension,
                    'principle' => (int)$principle,
                    'mean' => round($mean, 2),
                    'overallMean' => round($overallMeans[$dimensionIndex], 2),
                    'difference' => round($difference, 2),
                    'standardDeviation' => round($standardDeviation, 2),
                    'characterizationScore' => abs($difference) * (2.0 - min(1.5, $standardDeviation)),
                ];
            }
            usort($features, static function (array $first, array $second): int {
                return $second['characterizationScore'] <=> $first['characterizationScore'];
            });
            $selectedFeatures = [];
            $selectedPrinciples = [];
            foreach ($features as $feature) {
                if (isset($selectedPrinciples[$feature['principle']])) {
                    continue;
                }
                unset($feature['characterizationScore']);
                $selectedFeatures[] = $feature;
                $selectedPrinciples[$feature['principle']] = true;
                if (count($selectedFeatures) === 3) {
                    break;
                }
            }
            $groups[] = [
                'size' => $memberCount,
                'share' => round(($memberCount / $participantCount) * 100),
                'features' => $selectedFeatures,
                'averages' => $averages,
            ];
        }
        usort($groups, static function (array $first, array $second): int {
            return $second['size'] <=> $first['size'];
        });
        return $groups;
    }

    private static function squaredDistance(array $first, array $second)
    {
        $distance = 0.0;
        foreach ($first as $index => $value) {
            $difference = (float)$value - (float)$second[$index];
            $distance += $difference * $difference;
        }
        return $distance;
    }

    public static function normalizeAnswers($answers)
    {
        if (!is_array($answers) || count($answers) !== 10) {
            return false;
        }

        $normalized = [];
        foreach (array_values($answers) as $index => $answer) {
            if (!is_array($answer)) {
                return false;
            }

            $affinity = self::normalizeScore($answer['affinity'] ?? null);
            $today = self::normalizeScore($answer['situation']['today'] ?? null);
            $tomorrow = self::normalizeScore($answer['situation']['tomorrow'] ?? null);
            if ($affinity === null || $today === null || $tomorrow === null) {
                return false;
            }

            $normalized[] = [
                'principle_number' => $index + 1,
                'affinity_score' => $affinity,
                'today_score' => $today,
                'tomorrow_score' => $tomorrow,
            ];
        }

        return $normalized;
    }

    public static function createFromAnswers(array $answers)
    {
        $normalized = self::normalizeAnswers($answers);
        if ($normalized === false) {
            return false;
        }

        try {
            $privateToken = bin2hex(random_bytes(32));
            $publicToken = bin2hex(random_bytes(8));
        } catch (\Throwable $error) {
            return false;
        }

        $assessment = new self();
        $assessment->set('public_token', $publicToken);
        $assessment->set('private_token_hash', hash('sha256', $privateToken));
        $assessment->set('created_at', new \DateTimeImmutable());
        $assessment->set('updated_at', new \DateTimeImmutable());
        $saveResult = $assessment->save();
        if (empty($saveResult['status']) || (int)$assessment->getId() <= 0) {
            return false;
        }

        if (!OrganizationalMaturityAssessmentResponse::replaceForAssessment((int)$assessment->getId(), $normalized)) {
            return false;
        }

        return [
            'assessment' => $assessment,
            'privateToken' => $privateToken,
        ];
    }

    public function updateAnswers(array $answers)
    {
        $assessmentId = (int)$this->getId();
        $normalized = self::normalizeAnswers($answers);
        if ($assessmentId <= 0 || $normalized === false) {
            return false;
        }

        if (!OrganizationalMaturityAssessmentResponse::replaceForAssessment($assessmentId, $normalized)) {
            return false;
        }

        $this->set('updated_at', new \DateTimeImmutable());
        $saveResult = $this->save();
        return !empty($saveResult['status']);
    }

    public function attachToUserOrganization($userId, $organizationId)
    {
        $assessmentId = (int)$this->getId();
        $userId = (int)$userId;
        $organizationId = (int)$organizationId;
        if ($assessmentId <= 0 || $userId <= 0 || $organizationId <= 0 || !UserOrganization::hasActiveMembership($userId, $organizationId)) {
            return false;
        }

        $this->set('IDuser', $userId);
        $this->set('IDorganization', $organizationId);
        $this->set('updated_at', new \DateTimeImmutable());
        $saveResult = $this->save();
        return !empty($saveResult['status']);
    }

    public function attachToInvitation(OrganizationalMaturityInvitation $invitation)
    {
        $user = $invitation->resolveOrCreateUser();
        $organizationId = (int)$invitation->get('IDorganization');
        if (!$user || $organizationId <= 0) return false;
        $this->set('IDuser', (int)$user->getId()); $this->set('IDorganization', $organizationId); $this->set('IDinvitation', (int)$invitation->getId()); $this->set('updated_at', new \DateTimeImmutable());
        return !empty($this->save()['status']);
    }

    public function getSurveyAnswers()
    {
        $responses = OrganizationalMaturityAssessmentResponse::fetchForAssessment((int)$this->getId());
        if (count($responses) !== 10) {
            return [];
        }

        $answers = [];
        foreach ($responses as $response) {
            $answers[] = [
                'affinity' => (int)$response->get('affinity_score'),
                'situation' => [
                    'today' => (int)$response->get('today_score'),
                    'tomorrow' => (int)$response->get('tomorrow_score'),
                ],
            ];
        }
        return $answers;
    }

    private static function normalizeScore($value)
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        return $value !== false && $value >= 1 && $value <= 5 ? (int)$value : null;
    }
}
?>
