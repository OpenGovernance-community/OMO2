<?php
	namespace dbObject;


	class PropertyFormat extends DbObject
	{
		public const FORMAT_TEXT = 1;
		public const FORMAT_LIST = 2;
		public const FORMAT_NUMBER = 3;
		public const FORMAT_DATE = 4;
		public const FORMAT_HTML = 5;

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

		public static function normalizeValueForStorage($formatId, $value)
		{
			$normalizedValue = is_scalar($value) ? (string)$value : '';

			if (self::isHtmlFormat($formatId)) {
				return self::sanitizeHtml($normalizedValue);
			}

			return $normalizedValue;
		}

		public static function isEmptyValue($formatId, $value)
		{
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
				$fallback = strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><a>');
				return self::isEmptyValue(self::FORMAT_TEXT, $fallback) ? '' : trim($fallback);
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

			$tagName = $sourceTagName === 'DIV' ? 'p' : strtolower($sourceTagName);
			$allowedTags = array(
				'p',
				'br',
				'strong',
				'b',
				'em',
				'i',
				'u',
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
			} else {
				$element = $document->createElement($tagName);
			}

			foreach (iterator_to_array($node->childNodes) as $childNode) {
				self::appendSanitizedHtmlChild($element, self::sanitizeHtmlNode($childNode, $document));
			}

			return $element;
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
			$url = trim((string)$url);
			if ($url === '') {
				return '';
			}

			if (preg_match('/^(#|\/)/', $url)) {
				return $url;
			}

			if (!preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) {
				return $url;
			}

			return preg_match('/^(https?:|mailto:|tel:)/i', $url) ? $url : '';
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
