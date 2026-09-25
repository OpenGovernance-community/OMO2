<?php
return static function (\dbObject\FAQ $faq): array {
    $votes = (int)$faq->get('total_votes');
    return [
        'title' => $faq->get('question'),
        'fields' => [
            'rating' => $votes > 0 ? round((float)$faq->get('reliability') * 100) . ' %' : omoSearchPreviewT('unrated'),
            'votes' => $votes,
            'updated' => $faq->get('updated'),
        ],
        'sections' => [omoSearchPreviewSection('summary', trim((string)$faq->get('answer')) !== '' ? $faq->get('answer') : $faq->get('detail'))],
    ];
};
