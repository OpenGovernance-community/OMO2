<?php
namespace dbObject;

// Shared origin snapshots for objects created by a meeting or collective decision.
class ObjectCreationContext
{
    public static function fromDecision(DecisionProcess $decision): array
    {
        return ['type' => 'decision', 'id' => (int)$decision->getId(),
            'label' => (string)$decision->get('title'), 'holon_id' => (int)$decision->get('IDholon')];
    }

    public static function fromDocument(Document $document): array
    {
        $context = ['type' => 'document', 'id' => (int)$document->getId(),
            'label' => (string)$document->get('title'), 'holon_id' => (int)$document->getPvContextHolonId()];
        $event = $document->getAssociatedEvent();
        if ($event instanceof Event && (int)$event->get('IDorganization') === (int)$document->get('IDorganization')) {
            $date = $event->get('start_at');
            $context = ['type' => 'event', 'id' => (int)$event->getId(),
                'label' => (string)$event->get('title') . ($date instanceof \DateTimeInterface ? ' du ' . $date->format('d.m.Y') : ''),
                'title' => (string)$event->get('title'),
                'holon_id' => (int)$event->get('IDholon'), 'document_id' => (int)$document->getId()];
        }
        return $context;
    }

    public static function fromProposal(DeferredProposal $proposal): array
    {
        $organizationId = (int)$proposal->get('IDorganization');
        $decisionProposal = new DecisionProposal();
        if ((int)$proposal->get('IDdecision_proposal') > 0
            && $decisionProposal->load((int)$proposal->get('IDdecision_proposal'))) {
            $decision = $decisionProposal->getDecisionProcess();
            if ($decision instanceof DecisionProcess && (int)$decision->get('IDorganization') === $organizationId) {
                return self::fromDecision($decision);
            }
        }
        $point = new DocumentPvPoint();
        $document = new Document();
        if (!$point->load((int)$proposal->get('IDdocument_pv_point'))
            || !$document->load((int)$point->get('IDdocument'))
            || (int)$document->get('IDorganization') !== $organizationId) return [];
        return self::fromDocument($document);
    }
}
