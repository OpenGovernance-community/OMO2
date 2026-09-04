<?php
	namespace dbObject;


	class PropertyFormat extends DbObject
	{
		public const FORMAT_TEXT = 1;
		public const FORMAT_LIST = 2;
		public const FORMAT_NUMBER = 3;
		public const FORMAT_DATE = 4;
		public const FORMAT_HTML = 5;
		public const FORMAT_TEXT_HTML = 6;
		public const FORMAT_HTML_LIST = 7;

	    public static function tableName()
		{
			return 'propertyformat';
		}

		public static function rules()
		{
			return [
				[['id'], 'required'],
				[['id'], 'integer'],
				[['name'], 'string'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'name' => 'Nom',
			];
		}

		public static function getOrder() {
			return "id";
		}

		public static function getBuiltinFormats()
		{
			return array(
				array('id' => self::FORMAT_TEXT, 'name' => 'Texte libre'),
				array('id' => self::FORMAT_LIST, 'name' => 'Liste'),
				array('id' => self::FORMAT_NUMBER, 'name' => 'Chiffre'),
				array('id' => self::FORMAT_DATE, 'name' => 'Date'),
				array('id' => self::FORMAT_HTML, 'name' => 'HTML'),
				array('id' => self::FORMAT_TEXT_HTML, 'name' => 'Texte avec detail HTML'),
				array('id' => self::FORMAT_HTML_LIST, 'name' => 'HTML et liste'),
			);
		}

		public static function getBuiltinFormatName($formatId)
		{
			$formatId = (int)$formatId;
			foreach (self::getBuiltinFormats() as $format) {
				if ((int)$format['id'] === $formatId) {
					return (string)$format['name'];
				}
			}

			return '';
		}

		public static function isHtmlFormat($formatId)
		{
			return (int)$formatId === self::FORMAT_HTML;
		}

		public static function isListFormat($formatId)
		{
			return in_array((int)$formatId, array(self::FORMAT_LIST, self::FORMAT_HTML_LIST), true);
		}

		public static function getTextHtmlParts($value)
		{
			$decoded = is_array($value) ? $value : json_decode((string)$value, true);
			if (!is_array($decoded)) {
				$decoded = array('text' => is_scalar($value) ? (string)$value : '', 'detail' => '');
			}
			return array(
				'text' => trim((string)($decoded['text'] ?? '')),
				'detail' => self::sanitizeHtml($decoded['detail'] ?? ''),
			);
		}

		public static function getHtmlListParts($value)
		{
			$decoded = is_array($value) ? $value : json_decode((string)$value, true);
			if (!is_array($decoded)) {
				$decoded = array('before' => '', 'items' => array(), 'after' => '');
			}
			return array(
				'before' => self::sanitizeHtml($decoded['before'] ?? ''),
				'items' => is_array($decoded['items'] ?? null) ? array_values($decoded['items']) : array(),
				'after' => self::sanitizeHtml($decoded['after'] ?? ''),
			);
		}

		public static function remapListReferenceIds($value, $formatId, array $referenceIdMap)
		{
			$value = trim((string)$value);
			$formatId = (int)$formatId;
			if ($value === '' || !self::isListFormat($formatId) || count($referenceIdMap) === 0) {
				return $value;
			}

			$isHtmlList = $formatId === self::FORMAT_HTML_LIST;
			$decoded = json_decode($value, true);
			$htmlList = $isHtmlList
				? array('before' => '', 'items' => array(), 'after' => '')
				: null;
			$items = array();

			if (is_array($decoded)) {
				if ($isHtmlList) {
					$htmlList = $decoded;
					$items = is_array($decoded['items'] ?? null) ? array_values($decoded['items']) : array();
				} else {
					$items = array_values($decoded);
				}
			} else {
				$segments = preg_split('/(?<=[\]\}])\s*\|\s*(?=[\[\{])/', $value);
				$decodedSegmentCount = 0;
				foreach ($segments as $segment) {
					$segmentValue = json_decode((string)$segment, true);
					if (!is_array($segmentValue)) {
						continue;
					}
					$decodedSegmentCount += 1;
					if ($isHtmlList) {
						if ($htmlList['before'] === '' && trim((string)($segmentValue['before'] ?? '')) !== '') {
							$htmlList['before'] = (string)$segmentValue['before'];
						}
						if (trim((string)($segmentValue['after'] ?? '')) !== '') {
							$htmlList['after'] = (string)$segmentValue['after'];
						}
						$segmentItems = is_array($segmentValue['items'] ?? null) ? $segmentValue['items'] : array();
					} else {
						$segmentItems = $segmentValue;
					}
					foreach ($segmentItems as $item) {
						$items[] = $item;
					}
				}
				if ($decodedSegmentCount === 0) {
					return $value;
				}
			}

			$remappedItems = array();
			$seenItems = array();
			foreach ($items as $item) {
				$itemId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
				if ($itemId > 0 && isset($referenceIdMap[$itemId])) {
					if (is_array($item)) {
						$item['id'] = (int)$referenceIdMap[$itemId];
					} else {
						$item = (int)$referenceIdMap[$itemId];
					}
				}
				$itemKey = is_array($item)
					? json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
					: (string)$item;
				if (isset($seenItems[$itemKey])) {
					continue;
				}
				$seenItems[$itemKey] = true;
				$remappedItems[] = $item;
			}

			if ($isHtmlList) {
				$htmlList['items'] = $remappedItems;
				return json_encode($htmlList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}

			return json_encode($remappedItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		public static function normalizeValueForStorage($formatId, $value)
		{
			if ((int)$formatId === self::FORMAT_TEXT_HTML) {
				return json_encode(self::getTextHtmlParts($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}

			if ((int)$formatId === self::FORMAT_HTML_LIST) {
				return json_encode(self::getHtmlListParts($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}

			$normalizedValue = is_scalar($value) ? (string)$value : '';

			if (self::isHtmlFormat($formatId)) {
				return self::sanitizeHtml($normalizedValue);
			}

			return $normalizedValue;
		}

		public static function isEmptyValue($formatId, $value)
		{
			if ((int)$formatId === self::FORMAT_TEXT_HTML) {
				$parts = self::getTextHtmlParts($value);
				return $parts['text'] === '' && self::isEmptyValue(self::FORMAT_HTML, $parts['detail']);
			}

			if ((int)$formatId === self::FORMAT_HTML_LIST) {
				$parts = self::getHtmlListParts($value);
				return self::isEmptyValue(self::FORMAT_HTML, $parts['before'])
					&& count($parts['items']) === 0
					&& self::isEmptyValue(self::FORMAT_HTML, $parts['after']);
			}
			$normalizedValue = self::normalizeValueForStorage($formatId, $value);

			if (!self::isHtmlFormat($formatId)) {
				return trim($normalizedValue) === '';
			}

			if (trim($normalizedValue) === '') {
				return true;
			}

			$textContent = html_entity_decode(
				strip_tags(str_ireplace(array('<br>', '<br/>', '<br />'), ' ', $normalizedValue)),
				ENT_QUOTES | ENT_HTML5,
				'UTF-8'
			);

			return trim(preg_replace('/\s+/', ' ', (string)$textContent)) === '';
		}

		public static function sanitizeHtml($html)
		{
			$html = is_scalar($html) ? (string)$html : '';
			if (trim($html) === '') {
				return '';
			}

			if (!class_exists('\DOMDocument')) {
				return self::sanitizeHtmlWithoutDom($html);
			}

			$document = new \DOMDocument('1.0', 'UTF-8');
			$previousState = libxml_use_internal_errors(true);
			$document->loadHTML(
				'<?xml encoding="utf-8" ?><div>' . $html . '</div>',
				LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
			);
			libxml_clear_errors();
			libxml_use_internal_errors($previousState);

			$sourceRoot = $document->documentElement;
			if (!$sourceRoot) {
				return '';
			}

			$cleanDocument = new \DOMDocument('1.0', 'UTF-8');
			$wrapper = $cleanDocument->createElement('div');
			$cleanDocument->appendChild($wrapper);

			foreach (iterator_to_array($sourceRoot->childNodes) as $childNode) {
				self::appendSanitizedHtmlChild($wrapper, self::sanitizeHtmlNode($childNode, $cleanDocument));
			}

			$sanitizedHtml = trim(self::extractInnerHtml($wrapper));
			$sanitizedHtml = preg_replace('/<p>(?:\s|&nbsp;|&#160;|<br\s*\/?>)*<\/p>/i', '', $sanitizedHtml);
			$sanitizedHtml = trim((string)$sanitizedHtml);

			return self::isEmptyValue(self::FORMAT_TEXT, $sanitizedHtml) ? '' : $sanitizedHtml;
		}

		protected static function sanitizeHtmlNode(\DOMNode $node, \DOMDocument $document)
		{
			if ($node->nodeType === XML_TEXT_NODE) {
				return $document->createTextNode($node->nodeValue ?? '');
			}

			if ($node->nodeType !== XML_ELEMENT_NODE) {
				return $document->createDocumentFragment();
			}

			$sourceTagName = strtoupper((string)$node->nodeName);
			if ($sourceTagName === '') {
				return $document->createDocumentFragment();
			}

			if (in_array($sourceTagName, array('SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'META', 'LINK'), true)) {
				return $document->createDocumentFragment();
			}

			if ($node instanceof \DOMElement && ($node->hasAttribute('data-omo-project-embed-runtime') || $node->hasAttribute('data-omo-checklist-embed-runtime'))) {
				return $document->createDocumentFragment();
			}

			if (self::isAllowedDocumentEmbedNode($node)) {
				$element = $document->createElement('span');
				$element->setAttribute('class', 'omo-document-embed');
				$element->setAttribute('contenteditable', 'false');
				$element->setAttribute('data-omo-embed-type', 'document');
				$element->setAttribute('data-omo-document-id', (string)self::getDocumentEmbedNodeId($node));

				$title = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-document-title'));
				if ($title !== '') {
					$element->setAttribute('data-omo-document-title', $title);
				}

				$description = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-document-description'));
				if ($description !== '') {
					$element->setAttribute('data-omo-document-description', $description);
				}

				foreach (iterator_to_array($node->childNodes) as $childNode) {
					self::appendSanitizedHtmlChild($element, self::sanitizeHtmlNode($childNode, $document));
				}

				return $element;
			}

			if (self::isAllowedDecisionEmbedNode($node)) {
				$element = $document->createElement('span');
				$element->setAttribute('class', 'omo-decision-embed');
				$element->setAttribute('contenteditable', 'false');
				$element->setAttribute('data-omo-embed-type', 'decision');
				$element->setAttribute('data-omo-decision-id', (string)self::getDecisionEmbedNodeId($node));

				$title = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-decision-title'));
				if ($title !== '') {
					$element->setAttribute('data-omo-decision-title', $title);
				}

				$type = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-decision-type'));
				if ($type !== '') {
					$element->setAttribute('data-omo-decision-type', $type);
				}

				$summary = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-decision-summary'));
				if ($summary !== '') {
					$element->setAttribute('data-omo-decision-summary', $summary);
				}

				foreach (iterator_to_array($node->childNodes) as $childNode) {
					self::appendSanitizedHtmlChild($element, self::sanitizeHtmlNode($childNode, $document));
				}

				return $element;
			}

			if (self::isAllowedEventEmbedNode($node)) {
				$element = $document->createElement('span');
				$element->setAttribute('class', 'omo-event-embed');
				$element->setAttribute('contenteditable', 'false');
				$element->setAttribute('data-omo-embed-type', 'event');
				$element->setAttribute('data-omo-event-id', (string)self::getEventEmbedNodeId($node));

				foreach (array('title', 'schedule', 'location', 'description') as $attributeName) {
					$value = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-event-' . $attributeName));
					if ($value !== '') {
						$element->setAttribute('data-omo-event-' . $attributeName, $value);
					}
				}

				foreach (iterator_to_array($node->childNodes) as $childNode) {
					self::appendSanitizedHtmlChild($element, self::sanitizeHtmlNode($childNode, $document));
				}

				return $element;
			}

			if (self::isAllowedProjectEmbedNode($node)) {
				$element = $document->createElement('span');
				$element->setAttribute('class', 'omo-project-embed');
				$element->setAttribute('contenteditable', 'false');
				$element->setAttribute('data-omo-embed-type', 'project');
				$element->setAttribute('data-omo-project-id', (string)self::getProjectEmbedNodeId($node));

				$title = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-project-title'));
				if ($title !== '') {
					$element->setAttribute('data-omo-project-title', $title);
				}

				foreach (iterator_to_array($node->childNodes) as $childNode) {
					self::appendSanitizedHtmlChild($element, self::sanitizeHtmlNode($childNode, $document));
				}

				return $element;
			}

			if (self::isAllowedChecklistEmbedNode($node)) {
				$element = $document->createElement('span');
				$element->setAttribute('class', 'omo-checklist-embed');
				$element->setAttribute('contenteditable', 'false');
				$element->setAttribute('data-omo-embed-type', 'checklist');
				$element->setAttribute('data-omo-checklist-id', (string)self::getChecklistEmbedNodeId($node));
				$title = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-checklist-title'));
				if ($title !== '') { $element->setAttribute('data-omo-checklist-title', $title); }
				foreach (array('STRONG', 'EM') as $tagName) {
					foreach (iterator_to_array($node->childNodes) as $childNode) {
						if (!($childNode instanceof \DOMElement) || strtoupper((string)$childNode->nodeName) !== $tagName) {
							continue;
						}
						self::appendSanitizedHtmlChild($element, self::sanitizeHtmlNode($childNode, $document));
						break;
					}
				}
				return $element;
			}

			if (self::isAllowedIndicatorEmbedNode($node)) {
				$indicatorId = self::getIndicatorEmbedNodeId($node);
				$sourceClassName = ' ' . trim((string)self::getDomNodeAttributeValue($node, 'class')) . ' ';
				$isOverdue = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-indicator-overdue')) === '1'
					|| strpos($sourceClassName, ' omo-indicator-embed--overdue ') !== false
					|| strpos($sourceClassName, ' omo-indicator-embed--warning ') !== false;
				$overdueSeverity = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-indicator-overdue-severity')) === 'warning'
					|| strpos($sourceClassName, ' omo-indicator-embed--warning ') !== false
					? 'warning'
					: 'error';
				$statusLabel = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-indicator-status'));
				if ($statusLabel === '') {
					foreach (iterator_to_array($node->getElementsByTagName('em')) as $statusNode) {
						$parentNode = $statusNode->parentNode;
						if ($parentNode instanceof \DOMElement && strpos(' ' . $parentNode->getAttribute('class') . ' ', ' omo-indicator-embed__values ') !== false) {
							$statusLabel = trim((string)$statusNode->textContent);
							break;
						}
					}
				}
				$hasStatus = $statusLabel !== '' || strpos($sourceClassName, ' omo-indicator-embed--current ') !== false;
				$element = $document->createElement('span');
				$element->setAttribute('class', 'omo-indicator-embed' . ($isOverdue ? ($overdueSeverity === 'warning' ? ' omo-indicator-embed--warning' : ' omo-indicator-embed--overdue') : ($hasStatus ? ' omo-indicator-embed--current' : '')));
				$element->setAttribute('contenteditable', 'false');
				$element->setAttribute('data-omo-embed-type', 'indicator');
				$element->setAttribute('data-omo-indicator-id', (string)$indicatorId);
				$indicatorKind = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-indicator-kind')) === 'group' ? 'group' : 'indicator';
				$element->setAttribute('data-omo-indicator-kind', $indicatorKind);

				foreach (array('title', 'description', 'value', 'date', 'context', 'chart-min', 'chart-max', 'overdue-severity') as $attributeName) {
					$value = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-indicator-' . $attributeName));
					if ($value !== '') {
						$element->setAttribute('data-omo-indicator-' . $attributeName, $value);
					}
				}
				if ($statusLabel !== '') {
					$element->setAttribute('data-omo-indicator-status', $statusLabel);
				}
				if ($isOverdue) {
					$element->setAttribute('data-omo-indicator-overdue', '1');
				}

				$title = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-indicator-title'));
				$description = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-indicator-description'));
				$titleNode = $document->createElement('strong');
				$linkNode = $document->createElement('a');
				$linkNode->setAttribute('class', 'omo-indicator-embed__title');
				$linkNode->setAttribute('href', $indicatorKind === 'group' ? '#stats' : ('#stats-i' . $indicatorId));
				$statusDotNode = $document->createElement('span');
				$statusDotNode->setAttribute('class', 'omo-indicator-embed__status-dot' . ($isOverdue ? ($overdueSeverity === 'warning' ? ' omo-indicator-embed__status-dot--warning' : ' omo-indicator-embed__status-dot--overdue') : ($hasStatus ? ' omo-indicator-embed__status-dot--current' : ' omo-indicator-embed__status-dot--unknown')));
				$statusDotNode->setAttribute('aria-hidden', 'true');
				$linkNode->appendChild($statusDotNode);
				$titleTextNode = $document->createElement('span');
				$titleTextNode->appendChild($document->createTextNode($title !== '' ? $title : ('Indicateur #' . $indicatorId)));
				$linkNode->appendChild($titleTextNode);
				$titleNode->appendChild($linkNode);
				$element->appendChild($titleNode);
				if ($description !== '') {
					$descriptionNode = $document->createElement('span');
					$descriptionNode->setAttribute('class', 'omo-indicator-embed__description');
					$descriptionNode->appendChild($document->createTextNode($description));
					$element->appendChild($descriptionNode);
				}

				$mainNode = $document->createElement('span');
				$mainNode->setAttribute('class', 'omo-indicator-embed__main');
				self::appendSanitizedIndicatorChart($mainNode, $node, $document);
				$copyNode = $document->createElement('span');
				$copyNode->setAttribute('class', 'omo-indicator-embed__copy');
				$copyNode->appendChild($titleNode);
				if ($description !== '') {
					$copyNode->appendChild($descriptionNode);
				}
				$mainNode->appendChild($copyNode);
				$valuesNode = $document->createElement('span');
				$valuesNode->setAttribute('class', 'omo-indicator-embed__values');
				$valueLabel = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-indicator-value'));
				$dateLabel = trim((string)self::getDomNodeAttributeValue($node, 'data-omo-indicator-date'));
				if ($valueLabel !== '') {
					$valueNode = $document->createElement('b');
					$valueNode->appendChild($document->createTextNode($valueLabel));
					$valuesNode->appendChild($valueNode);
				}
				if ($dateLabel !== '') {
					$dateNode = $document->createElement('time');
					$dateNode->appendChild($document->createTextNode($dateLabel));
					$valuesNode->appendChild($dateNode);
				}
				if ($statusLabel !== '') {
					$statusNode = $document->createElement('em');
					$statusNode->appendChild($document->createTextNode($statusLabel));
					$valuesNode->appendChild($statusNode);
				}
				$mainNode->appendChild($valuesNode);
				$element->appendChild($mainNode);
				return $element;
			}

			$tagName = $sourceTagName === 'DIV' ? 'p' : strtolower($sourceTagName);
			$allowedTags = array(
				'p',
				'h1',
				'h2',
				'h3',
				'blockquote',
				'table',
				'thead',
				'tbody',
				'tr',
				'th',
				'td',
				'br',
				'strong',
				'b',
				'em',
				'i',
				'u',
				'span',
				'ul',
				'ol',
				'li',
				'a',
			);

			if (!in_array($tagName, $allowedTags, true)) {
				$fragment = $document->createDocumentFragment();
				foreach (iterator_to_array($node->childNodes) as $childNode) {
					self::appendSanitizedHtmlChild($fragment, self::sanitizeHtmlNode($childNode, $document));
				}
				return $fragment;
			}

			if ($tagName === 'a') {
				$hrefAttribute = $node->attributes ? $node->attributes->getNamedItem('href') : null;
				$href = self::sanitizeHtmlLink($hrefAttribute ? (string)$hrefAttribute->nodeValue : '');
				if ($href === '') {
					$fragment = $document->createDocumentFragment();
					foreach (iterator_to_array($node->childNodes) as $childNode) {
						self::appendSanitizedHtmlChild($fragment, self::sanitizeHtmlNode($childNode, $document));
					}
					return $fragment;
				}

				$element = $document->createElement('a');
				$element->setAttribute('href', $href);

				$targetAttribute = $node->attributes ? $node->attributes->getNamedItem('target') : null;
				$target = strtolower(trim($targetAttribute ? (string)$targetAttribute->nodeValue : ''));
				if ($target === '_blank') {
					$element->setAttribute('target', '_blank');
					$element->setAttribute('rel', 'noopener noreferrer');
				}
			} elseif (in_array($tagName, array('th', 'td'), true)) {
				$element = $document->createElement($tagName);

				$colspanAttribute = $node->attributes ? $node->attributes->getNamedItem('colspan') : null;
				$colspanValue = $colspanAttribute ? max(1, (int)$colspanAttribute->nodeValue) : 0;
				if ($colspanValue > 1) {
					$element->setAttribute('colspan', (string)$colspanValue);
				}

				$rowspanAttribute = $node->attributes ? $node->attributes->getNamedItem('rowspan') : null;
				$rowspanValue = $rowspanAttribute ? max(1, (int)$rowspanAttribute->nodeValue) : 0;
				if ($rowspanValue > 1) {
					$element->setAttribute('rowspan', (string)$rowspanValue);
				}
			} else {
				$element = $document->createElement($tagName);
			}

			$safeBackgroundColorStyle = self::sanitizeBackgroundColorStyle(self::getDomNodeAttributeValue($node, 'style'));
			if ($safeBackgroundColorStyle !== '') {
				$element->setAttribute('style', $safeBackgroundColorStyle);
			}

			foreach (iterator_to_array($node->childNodes) as $childNode) {
				self::appendSanitizedHtmlChild($element, self::sanitizeHtmlNode($childNode, $document));
			}

			return $element;
		}

		protected static function getDomNodeAttributeValue(\DOMNode $node, string $attributeName): string
		{
			if (!$node instanceof \DOMElement || !$node->hasAttribute($attributeName)) {
				return '';
			}

			return (string)$node->getAttribute($attributeName);
		}

		protected static function getDocumentEmbedNodeId(\DOMNode $node): int
		{
			return (int)trim((string)self::getDomNodeAttributeValue($node, 'data-omo-document-id'));
		}

		protected static function isAllowedDocumentEmbedNode(\DOMNode $node): bool
		{
			if (!($node instanceof \DOMElement)) {
				return false;
			}

			if (trim((string)$node->getAttribute('data-omo-embed-type')) !== 'document') {
				return false;
			}

			return self::getDocumentEmbedNodeId($node) > 0;
		}

		protected static function getDecisionEmbedNodeId(\DOMNode $node): int
		{
			return (int)trim((string)self::getDomNodeAttributeValue($node, 'data-omo-decision-id'));
		}

		protected static function isAllowedDecisionEmbedNode(\DOMNode $node): bool
		{
			if (!($node instanceof \DOMElement)) {
				return false;
			}

			if (trim((string)$node->getAttribute('data-omo-embed-type')) !== 'decision') {
				return false;
			}

			return self::getDecisionEmbedNodeId($node) > 0;
		}

		protected static function getProjectEmbedNodeId(\DOMNode $node): int
		{
			return (int)trim((string)self::getDomNodeAttributeValue($node, 'data-omo-project-id'));
		}

		protected static function isAllowedProjectEmbedNode(\DOMNode $node): bool
		{
			if (!($node instanceof \DOMElement)) {
				return false;
			}

			if (trim((string)$node->getAttribute('data-omo-embed-type')) !== 'project') {
				return false;
			}

			return self::getProjectEmbedNodeId($node) > 0;
		}

		protected static function getChecklistEmbedNodeId(\DOMNode $node): int
		{
			return (int)trim((string)self::getDomNodeAttributeValue($node, 'data-omo-checklist-id'));
		}

		protected static function isAllowedChecklistEmbedNode(\DOMNode $node): bool
		{
			return $node instanceof \DOMElement
				&& trim((string)$node->getAttribute('data-omo-embed-type')) === 'checklist'
				&& self::getChecklistEmbedNodeId($node) > 0;
		}

		protected static function getEventEmbedNodeId(\DOMNode $node): int
		{
			return (int)trim((string)self::getDomNodeAttributeValue($node, 'data-omo-event-id'));
		}

		protected static function getIndicatorEmbedNodeId(\DOMNode $node): int
		{
			return (int)trim((string)self::getDomNodeAttributeValue($node, 'data-omo-indicator-id'));
		}

		protected static function isAllowedIndicatorEmbedNode(\DOMNode $node): bool
		{
			return $node instanceof \DOMElement
				&& trim((string)$node->getAttribute('data-omo-embed-type')) === 'indicator'
				&& self::getIndicatorEmbedNodeId($node) > 0;
		}

		protected static function appendSanitizedIndicatorChart(\DOMNode $parentNode, \DOMNode $sourceNode, \DOMDocument $document): void
		{
			if (!($sourceNode instanceof \DOMElement)) {
				return;
			}

			$sourceCharts = $sourceNode->getElementsByTagName('svg');
			$sourceChart = $sourceCharts->length > 0 ? $sourceCharts->item(0) : null;
			if (!($sourceChart instanceof \DOMElement) || strpos(' ' . $sourceChart->getAttribute('class') . ' ', ' omo-stats-chart ') === false) {
				return;
			}

			$chartNode = $document->createElement('svg');
			$indicatorKind = trim((string)self::getDomNodeAttributeValue($sourceNode, 'data-omo-indicator-kind')) === 'group' ? 'group' : 'indicator';
			$chartNode->setAttribute('class', 'omo-stats-chart omo-stats-chart--compact' . ($indicatorKind === 'group' ? ' omo-stats-chart--group' : ''));
			$chartNode->setAttribute('viewBox', '0 0 180 54');
			$chartNode->setAttribute('aria-hidden', 'true');
			foreach (array('polyline', 'circle', 'line', 'text') as $tagName) {
				foreach (iterator_to_array($sourceChart->getElementsByTagName($tagName)) as $sourceShape) {
					if (!($sourceShape instanceof \DOMElement)) {
						continue;
					}
					$className = trim((string)$sourceShape->getAttribute('class'));
					if ($tagName === 'polyline' && $className !== 'omo-stats-chart__reference' && !preg_match('/^omo-stats-chart__line(?: omo-stats-chart__line--(?:background|sum))?$/', $className)) {
						continue;
					}
					if ($tagName === 'circle' && $className !== 'omo-stats-chart__point') {
						continue;
					}
					if ($tagName === 'line' && !in_array($className, array(
						'omo-stats-chart__scale-line',
						'omo-stats-chart__reference omo-stats-chart__reference--ceiling',
						'omo-stats-chart__baseline',
					), true)) {
						continue;
					}
					if ($tagName === 'text' && $className !== 'omo-stats-chart__scale-label') {
						continue;
					}

					$shapeNode = $document->createElement($tagName);
					$shapeNode->setAttribute('class', $className);
					if ($tagName === 'polyline') {
						$points = trim((string)$sourceShape->getAttribute('points'));
						if (strlen($points) > 4000 || !preg_match('/^-?[0-9.]+,-?[0-9.]+(?:\s+-?[0-9.]+,-?[0-9.]+)*$/', $points)) {
							continue;
						}
						$shapeNode->setAttribute('points', $points);
						$strokeStyle = trim((string)$sourceShape->getAttribute('style'));
						if (preg_match('/^stroke:\s*#[0-9a-f]{6};?$/i', $strokeStyle)) {
							$shapeNode->setAttribute('style', $strokeStyle);
						}
					} elseif ($tagName === 'circle') {
						$cx = trim((string)$sourceShape->getAttribute('cx'));
						$cy = trim((string)$sourceShape->getAttribute('cy'));
						$radius = trim((string)$sourceShape->getAttribute('r'));
						if (!preg_match('/^-?[0-9.]+$/', $cx) || !preg_match('/^-?[0-9.]+$/', $cy) || !preg_match('/^-?[0-9.]+$/', $radius)) {
							continue;
						}
						$shapeNode->setAttribute('cx', $cx);
						$shapeNode->setAttribute('cy', $cy);
						$shapeNode->setAttribute('r', $radius);
					} elseif ($tagName === 'line') {
						$x1 = trim((string)$sourceShape->getAttribute('x1'));
						$y1 = trim((string)$sourceShape->getAttribute('y1'));
						$x2 = trim((string)$sourceShape->getAttribute('x2'));
						$y2 = trim((string)$sourceShape->getAttribute('y2'));
						if (!preg_match('/^-?[0-9.]+$/', $x1) || !preg_match('/^-?[0-9.]+$/', $y1) || !preg_match('/^-?[0-9.]+$/', $x2) || !preg_match('/^-?[0-9.]+$/', $y2)) {
							continue;
						}
						$shapeNode->setAttribute('x1', $x1);
						$shapeNode->setAttribute('y1', $y1);
						$shapeNode->setAttribute('x2', $x2);
						$shapeNode->setAttribute('y2', $y2);
					} else {
						$x = trim((string)$sourceShape->getAttribute('x'));
						$y = trim((string)$sourceShape->getAttribute('y'));
						$label = trim((string)$sourceShape->textContent);
						if (!preg_match('/^-?[0-9.]+$/', $x) || !preg_match('/^-?[0-9.]+$/', $y) || !preg_match('/^-?[0-9.,\s]+$/', $label)) {
							continue;
						}
						$shapeNode->setAttribute('x', $x);
						$shapeNode->setAttribute('y', $y);
						$shapeNode->appendChild($document->createTextNode($label));
					}
					$chartNode->appendChild($shapeNode);
				}
			}

			if (!$chartNode->hasChildNodes()) {
				return;
			}

			$wrapper = $document->createElement('span');
			$wrapper->setAttribute('class', 'omo-indicator-embed__chart');
			$plotNode = $document->createElement('span');
			$plotNode->setAttribute('class', 'omo-indicator-embed__chart-plot');
			$svgNode = $document->createElement('span');
			$svgNode->setAttribute('class', 'omo-indicator-embed__chart-svg');
			$svgNode->appendChild($chartNode);
			$plotNode->appendChild($svgNode);
			$wrapper->appendChild($plotNode);
			$parentNode->appendChild($wrapper);
		}

		protected static function isAllowedEventEmbedNode(\DOMNode $node): bool
		{
			if (!($node instanceof \DOMElement)) {
				return false;
			}

			if (trim((string)$node->getAttribute('data-omo-embed-type')) !== 'event') {
				return false;
			}

			return self::getEventEmbedNodeId($node) > 0;
		}

		protected static function appendSanitizedHtmlChild(\DOMNode $parentNode, \DOMNode $childNode)
		{
			if ($childNode instanceof \DOMDocumentFragment && !$childNode->hasChildNodes()) {
				return;
			}

			$parentNode->appendChild($childNode);
		}

		protected static function sanitizeHtmlLink($url)
		{
			$url = trim(html_entity_decode((string)$url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
			if ($url === '') {
				return '';
			}
			if (preg_match('/[\x00-\x1F\x7F]/', $url)) {
				return '';
			}

			$schemeProbe = preg_replace('/[\x00-\x20\x7F]+/', '', $url);
			if (preg_match('/^[a-z][a-z0-9+.-]*:/i', (string)$schemeProbe)) {
				return preg_match('/^(https?:|mailto:|tel:)/i', (string)$schemeProbe) ? $url : '';
			}

			if (preg_match('/^(#|\/)/', $url)) {
				return $url;
			}

			$colonPosition = strpos($url, ':');
			$firstPathDelimiterPosition = strcspn($url, '/?#');
			return $colonPosition === false || $colonPosition > $firstPathDelimiterPosition ? $url : '';
		}

		protected static function sanitizeHtmlWithoutDom($html)
		{
			$plainText = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", (string)$html);
			$plainText = preg_replace('/<\s*\/\s*(?:p|h[1-6]|blockquote|li|tr)\s*>/i', "\n", (string)$plainText);
			$plainText = html_entity_decode(strip_tags((string)$plainText), ENT_QUOTES | ENT_HTML5, 'UTF-8');
			$plainText = trim((string)preg_replace('/[\t ]+/', ' ', (string)$plainText));
			if ($plainText === '') {
				return '';
			}

			return nl2br(htmlspecialchars($plainText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);
		}

		public static function sanitizeBackgroundColorStyle($style)
		{
			$safeDeclarations = array();
			foreach (explode(';', (string)$style) as $declaration) {
				$separator = strpos($declaration, ':');
				if ($separator === false || $separator < 1) {
					continue;
				}

				$property = strtolower(trim(substr($declaration, 0, $separator)));
				$value = strtolower(trim(substr($declaration, $separator + 1)));
				if ($property !== 'background-color') {
					continue;
				}

				if (!preg_match('/^(?:#[0-9a-f]{3,8}|(?:rgb|hsl)a?\(\s*[-+0-9.%]+(?:\s*,\s*[-+0-9.%]+){2,3}\s*\)|[a-z]{1,32})$/i', $value)) {
					continue;
				}

				$safeDeclarations[] = $property . ': ' . $value;
			}

			return implode('; ', $safeDeclarations);
		}

		protected static function extractInnerHtml(\DOMNode $node)
		{
			$html = '';
			foreach ($node->childNodes as $childNode) {
				$html .= $node->ownerDocument->saveHTML($childNode);
			}

			return $html;
		}
	}
	
?>
