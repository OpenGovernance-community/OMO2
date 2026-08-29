<?php
namespace dbObject;

class OrganizationalMaturityAssessmentResponse extends DbObject
{
    public static function tableName()
    {
        return 'organizational_maturity_assessment_response';
    }

    public static function rules()
    {
        return [
            [['id', 'principle_number', 'affinity_score', 'today_score', 'tomorrow_score'], 'integer'],
            [['IDassessment'], 'fk'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDassessment' => 'Evaluation',
            'principle_number' => 'Principe',
            'affinity_score' => 'Affinite',
            'today_score' => 'Aujourd hui',
            'tomorrow_score' => 'Demain',
        ];
    }

    public static function replaceForAssessment($assessmentId, array $responses)
    {
        $assessmentId = (int)$assessmentId;
        if ($assessmentId <= 0 || count($responses) !== 10) {
            return false;
        }

        foreach ($responses as $response) {
            $principleNumber = (int)($response['principle_number'] ?? 0);
            $item = new self();
            if (!$item->load([
                ['IDassessment', $assessmentId],
                ['principle_number', $principleNumber],
            ])) {
                $item->set('IDassessment', $assessmentId);
                $item->set('principle_number', $principleNumber);
            }

            $item->set('affinity_score', (int)($response['affinity_score'] ?? 0));
            $item->set('today_score', (int)($response['today_score'] ?? 0));
            $item->set('tomorrow_score', (int)($response['tomorrow_score'] ?? 0));
            $saveResult = $item->save();
            if (empty($saveResult['status'])) {
                return false;
            }
        }

        return true;
    }

    public static function fetchForAssessment($assessmentId)
    {
        $assessmentId = (int)$assessmentId;
        if ($assessmentId <= 0) {
            return [];
        }

        $rows = self::fetchAll(
            'SELECT * FROM organizational_maturity_assessment_response WHERE IDassessment = :assessment_id ORDER BY principle_number ASC',
            ['assessment_id' => $assessmentId]
        );
        if ($rows === false) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $item = new self();
            $item->loadFromArray($row);
            $item->setId((int)$row['id']);
            $items[] = $item;
        }
        return $items;
    }
}
?>
