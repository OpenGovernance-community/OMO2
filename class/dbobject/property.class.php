<?php
	namespace dbObject;


	class Property extends DbObject
	{
		public const LIST_ITEM_TEXT = 'text';
		public const LIST_ITEM_NUMBER = 'number';
		public const LIST_ITEM_DATE = 'date';
		public const LIST_ITEM_DETAIL = 'detail';
		public const LIST_ITEM_HOLON = 'holon';
		public const LIST_ITEM_PROJECT = 'project';
		public const LIST_ITEM_AUTHORITY = 'authority';

	    public static function tableName()
		{
			return 'property'; // Nom de la table correspondante
		}	
		
		// Défini le contenu de la table
		public static function rules()
		{
			return [
				[['id'], 'required'],				// Champs obligatoires
				[['id','position'], 'integer'],					
				[['name','shortname','listitemtype','listholontypeids'], 'string'],			// Texte libre
				[['IDpropertyformat','IDholon_organization'], 'fk'],			// Texte libre
				[['datecreation'], 'datetime'],				// Clé étrangères
				[['active'], 'boolean'],				// Booléens
				[['id'], 'safe'],								// Champs protégés (n'apparaissent pas dans les formulaires)
			];
		}
		
		// Défini les labels standarts pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'name' => 'Nom',
				'shortname' => 'Nom court',
				'IDpropertyformat' => 'Format',
				'listitemtype' => "Type d'éléments de liste",
				'listholontypeids' => 'Types d’espaces autorisés',
				'IDholon_organization' => 'Organisation',
				'datecreation' => 'Date de création',
				'position' => 'Position',
				'active' => 'Actif ?',
			];
		}

		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "name";
		}

		public static function buildShortnameFromName($name)
		{
			$name = trim((string)$name);
			if ($name === '') {
				return 'property';
			}

			$normalized = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
			if (!is_string($normalized) || trim($normalized) === '') {
				$normalized = $name;
			}

			$normalized = strtolower($normalized);
			$normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized);
			$normalized = trim((string)$normalized, '_');

			if ($normalized === '') {
				$normalized = 'property';
			}

			return substr($normalized, 0, 20);
		}

		public static function getListItemTypeOptions()
		{
			return array(
				array('id' => self::LIST_ITEM_TEXT, 'name' => 'Texte'),
				array('id' => self::LIST_ITEM_NUMBER, 'name' => 'Chiffre'),
				array('id' => self::LIST_ITEM_DATE, 'name' => 'Date'),
				array('id' => self::LIST_ITEM_DETAIL, 'name' => 'Liste detaillee'),
				array('id' => self::LIST_ITEM_HOLON, 'name' => 'Espace'),
				array('id' => self::LIST_ITEM_PROJECT, 'name' => 'Projet'),
				array('id' => self::LIST_ITEM_AUTHORITY, 'name' => 'Autorite'),
			);
		}

		public static function getTemplateListItemTypeOptions()
		{
			return array(
				array('id' => self::LIST_ITEM_TEXT, 'name' => 'Texte'),
				array('id' => self::LIST_ITEM_NUMBER, 'name' => 'Chiffre'),
				array('id' => self::LIST_ITEM_DATE, 'name' => 'Date'),
				array('id' => self::LIST_ITEM_DETAIL, 'name' => 'Liste detaillee'),
				array('id' => self::LIST_ITEM_PROJECT, 'name' => 'Projet'),
				array('id' => self::LIST_ITEM_AUTHORITY, 'name' => 'Autorite'),
			);
		}

		public static function normalizeListItemType($value)
		{
			$value = trim((string)$value);
			$allowed = array(
				self::LIST_ITEM_TEXT,
				self::LIST_ITEM_NUMBER,
				self::LIST_ITEM_DATE,
				self::LIST_ITEM_DETAIL,
				self::LIST_ITEM_HOLON,
				self::LIST_ITEM_PROJECT,
				self::LIST_ITEM_AUTHORITY,
			);

			return in_array($value, $allowed, true) ? $value : self::LIST_ITEM_TEXT;
		}

		public static function normalizeTemplateListItemType($value)
		{
			$normalized = self::normalizeListItemType($value);
			return $normalized === self::LIST_ITEM_HOLON ? self::LIST_ITEM_TEXT : $normalized;
		}

		public static function parseHolonTypeIds($value)
		{
			if (is_array($value)) {
				$items = $value;
			} else {
				$value = trim((string)$value);
				$items = $value === '' ? array() : explode(',', $value);
			}

			$typeIds = array();
			foreach ($items as $item) {
				$typeId = (int)$item;
				if ($typeId > 0) {
					$typeIds[$typeId] = $typeId;
				}
			}

			return array_values($typeIds);
		}

		public static function serializeHolonTypeIds($value)
		{
			$typeIds = self::parseHolonTypeIds($value);
			return count($typeIds) > 0 ? implode(',', $typeIds) : null;
		}

		/** Convert every stored value of a shared definition before changing its type. */
		public static function convertListDefinitions(Holon $holon, array &$definitions)
		{
			$pdo = self::getPdo();
			$ownsTransaction = !$pdo->inTransaction();
			$convertedDefinitions = $definitions;
			try {
				if ($ownsTransaction) {
					$pdo->beginTransaction();
				} else {
					$pdo->exec('SAVEPOINT property_list_conversion');
				}
				foreach ($convertedDefinitions as &$definition) {
					$propertyId = (int)($definition['id'] ?? 0);
					if ($propertyId <= 0) {
						continue;
					}
					$stored = self::fetchRow('SELECT * FROM `property` WHERE id = :id FOR UPDATE', ['id' => $propertyId]);
					if (!is_array($stored)) {
						throw new \RuntimeException('La liste est introuvable. Rechargez le formulaire.');
					}
					$from = self::normalizeListItemType($stored['listitemtype'] ?? '');
					$to = self::normalizeListItemType($definition['listItemType'] ?? '');
					$sourceFormat = (int)$stored['IDpropertyformat'];
					$targetFormat = (int)($definition['formatId'] ?? 0);
					$confirmedFrom = (string)($definition['listConversionFrom'] ?? '');
					if ($confirmedFrom !== '' && $confirmedFrom !== $from) {
						throw new \RuntimeException('Le type de cette liste a change. Rechargez le formulaire avant de la convertir.');
					}
					if (!PropertyFormat::isListFormat($sourceFormat) || !PropertyFormat::isListFormat($targetFormat)
						|| $from === $to || !in_array($from, [self::LIST_ITEM_TEXT, self::LIST_ITEM_AUTHORITY], true)
						|| !in_array($to, [self::LIST_ITEM_TEXT, self::LIST_ITEM_AUTHORITY], true)) {
						if ($confirmedFrom !== '') {
							throw new \RuntimeException('Enregistrez la conversion avant de modifier le format de la liste.');
						}
						continue;
					}
					if ($confirmedFrom !== $from || $sourceFormat !== $targetFormat) {
						throw new \RuntimeException('Veuillez confirmer la conversion de la liste avant de l enregistrer.');
					}
					$template = $holon->getTemplateHolon();
					if ($template instanceof Holon) {
						foreach ($template->getTemplatePropertyDefinitions() as $inherited) {
							if ((int)($inherited['id'] ?? 0) === $propertyId) {
								throw new \RuntimeException('Une liste heritee doit etre convertie depuis le modele qui la definit.');
							}
						}
					}
					$local = new HolonProperty();
					$root = new Holon();
					if (!$root->load((int)$stored['IDholon_organization'])) {
						throw new \RuntimeException('L organisation de la liste est introuvable.');
					}
					if (!$local->load([['IDholon', $holon->getId()], ['IDproperty', $propertyId]])
						|| (int)$stored['IDholon_organization'] !== (int)($holon->get('IDholon_org') ?: $holon->getId())) {
						throw new \RuntimeException('Cette definition de liste ne peut pas etre convertie depuis ce holon.');
					}
					$rows = self::fetchAll('SELECT id FROM `holonproperty` WHERE IDproperty = :id ORDER BY id FOR UPDATE', ['id' => $propertyId]);
					if (!is_array($rows)) {
						throw new \RuntimeException('Les valeurs de la liste ne peuvent pas etre chargees.');
					}
					$authoritiesToDelete = [];
					foreach ($rows as $row) {
						$value = new HolonProperty();
						if (!$value->load((int)$row['id'], true)) {
							throw new \RuntimeException('Une valeur de liste est introuvable.');
						}
						$isCurrent = (int)$value->get('IDholon') === (int)$holon->getId();
						// Reverse conversion uses the stored authority IDs, never IDs supplied by the browser.
						$raw = $isCurrent && $from === self::LIST_ITEM_TEXT
							? ($definition['value'] ?? '') : $value->get('value');
						$parts = self::listConversionParts($raw, $sourceFormat);
						$items = [];
						foreach ($parts['items'] as $item) {
							if ($from === self::LIST_ITEM_TEXT) {
								if (!is_scalar($item)) {
									throw new \RuntimeException('La liste contient une valeur qui n est pas un texte.');
								}
								$label = trim((string)$item);
								if ($label === '') { continue; }
								$authority = new Authority();
								$authority->set('IDholon', (int)$value->get('IDholon'));
								$authority->set('label', $label);
								$authority->set('is_local', true);
								$result = $authority->save();
								if (empty($result['status'])) { throw new \RuntimeException($result['text'] ?? 'Creation d autorite impossible.'); }
								$items[] = (int)$authority->getId();
							} else {
								$id = self::listAuthorityReferenceId($item);
								// Old type changes left plain text in authority lists. Keep that text intact.
								if ($id === 0 && is_string($item)) {
									$items[] = $item;
									continue;
								}
								$context = 'Liste "' . (string)$stored['name'] . '", holon #' . (int)$value->get('IDholon') . ' : ';
								if ($id === 0) {
									throw new \RuntimeException($context . 'une reference d autorite est invalide. La conversion est annulee.');
								}
								$authority = new Authority();
								if (!$authority->load($id)) {
									throw new \RuntimeException($context . 'l autorite #' . $id . ' n existe plus. Reparez cette reference avant la conversion.');
								}
								if ((int)$authority->getOrganizationId() !== (int)$root->get('IDorganization')) {
									throw new \RuntimeException($context . 'l autorite #' . $id . ' appartient a une autre organisation. La conversion est annulee.');
								}
								$items[] = (string)$authority->get('label');
								$authoritiesToDelete[$id] = $id;
							}
						}
						$parts['items'] = $items;
						$converted = $raw === null ? null : json_encode($sourceFormat === PropertyFormat::FORMAT_HTML_LIST ? $parts : $items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
						$value->set('value', $converted);
						$result = $value->save();
						if (empty($result['status'])) { throw new \RuntimeException($result['text'] ?? 'Conversion de valeur impossible.'); }
						if ($isCurrent) { $definition['value'] = $converted; }
					}
					if ($authoritiesToDelete) {
						Authority::deleteForListConversion(array_values($authoritiesToDelete), $propertyId);
					}
					$property = new self();
					$property->load($propertyId, true);
					$property->set('listitemtype', $to);
					$result = $property->save();
					if (empty($result['status'])) { throw new \RuntimeException($result['text'] ?? 'Conversion de liste impossible.'); }
					unset($definition['listConversionFrom']);
				}
				unset($definition);
				if ($ownsTransaction) { $pdo->commit(); } else { $pdo->exec('RELEASE SAVEPOINT property_list_conversion'); }
				$definitions = $convertedDefinitions;
				return ['status' => true];
			} catch (\Throwable $exception) {
				if ($ownsTransaction && $pdo->inTransaction()) { $pdo->rollBack(); }
				elseif (!$ownsTransaction) { $pdo->exec('ROLLBACK TO SAVEPOINT property_list_conversion'); }
				self::$preload = [];
				return ['status' => false, 'message' => $exception->getMessage()];
			}
		}

		public static function listAuthorityReferenceId($item)
		{
			$id = is_array($item) ? ($item['id'] ?? null) : $item;
			return (is_int($id) || (is_string($id) && ctype_digit(trim($id)))) && (int)$id > 0 ? (int)$id : 0;
		}

		public static function listConversionParts($raw, $formatId)
		{
			if ($raw === null || trim((string)$raw) === '') { return ['items' => []]; }
			$decoded = json_decode((string)$raw, true);
			if ((int)$formatId === PropertyFormat::FORMAT_LIST && !is_array($decoded)) {
				return ['items' => preg_split('/\r\n|\r|\n|\|/', (string)$raw)];
			}
			if (json_last_error() !== JSON_ERROR_NONE) {
				throw new \RuntimeException('Le contenu de la liste est invalide.');
			}
			$parts = (int)$formatId === PropertyFormat::FORMAT_HTML_LIST ? $decoded : ['items' => $decoded];
			if (!is_array($parts) || !is_array($parts['items'] ?? null)) { throw new \RuntimeException('Le contenu de la liste est invalide.'); }
			return $parts;
		}
	}
	
?>
