<?php
	namespace dbObject;


	class Property extends DbObject
	{
    protected static function uiMessageSourceLang(): array
    {
        return parent::uiMessageSourceLang() + [
            'copy.type_de_propriete_invalide' => ['text' => 'Type de propriété invalide.', 'context' => 'Interface text in class/dbobject/property.class.php'],
            'copy.liste_detaillee' => ['text' => 'Liste détaillée', 'context' => 'Interface text in class/dbobject/property.class.php'],
            'copy.autorite' => ['text' => 'Autorité', 'context' => 'Interface text in class/dbobject/property.class.php'],
            'copy.veuillez_confirmer_la_conversion_de_la_liste_avant_de_l_enregistrer' => ['text' => 'Veuillez confirmer la conversion de la liste avant de l’enregistrer.', 'context' => 'Interface text in class/dbobject/property.class.php'],
            'copy.une_liste_heritee_doit_etre_convertie_depuis_le_modele_qui_la_definit' => ['text' => 'Une liste héritée doit être convertie depuis le modèle qui la definit.', 'context' => 'Interface text in class/dbobject/property.class.php'],
            'copy.l_organisation_de_la_liste_est_introuvable' => ['text' => 'L’organisation de la liste est introuvable.', 'context' => 'Interface text in class/dbobject/property.class.php'],
            'copy.les_valeurs_de_la_liste_ne_peuvent_pas_etre_chargees' => ['text' => 'Les valeurs de la liste ne peuvent pas être chargées.', 'context' => 'Interface text in class/dbobject/property.class.php'],
            'copy.la_liste_contient_une_valeur_qui_n_est_pas_un_texte' => ['text' => 'La liste contient une valeur qui n’est pas un texte.', 'context' => 'Interface text in class/dbobject/property.class.php'],
            'copy.creation_d_autorite_impossible' => ['text' => 'Création d’autorité impossible.', 'context' => 'Interface text in class/dbobject/property.class.php'],
            'copy.context_une_reference_d_autorite_est_invalide_la_conversion_est_annule' => ['text' => '{context}une référence d’autorité est invalide. La conversion est annulée.', 'context' => 'Interface text in class/dbobject/property.class.php'],
            'copy.context_l_autorite_id_n_existe_plus_reparez_cette_reference_avant_la_c' => ['text' => '{context}l’autorité #{id} n’existe plus. Réparez cette référence avant la conversion.', 'context' => 'Interface text in class/dbobject/property.class.php'],
            'copy.context_l_autorite_id_appartient_a_une_autre_organisation_la_conversio' => ['text' => '{context}l’autorité #{id} appartient à une autre organisation. La conversion est annulée.', 'context' => 'Interface text in class/dbobject/property.class.php'],

            'option.texte' => ['text' => 'Texte', 'context' => 'Display label or help in a model choice catalog. Stored option values stay unchanged.'],
            'option.chiffre' => ['text' => 'Chiffre', 'context' => 'Display label or help in a model choice catalog. Stored option values stay unchanged.'],
            'option.date' => ['text' => 'Date', 'context' => 'Display label or help in a model choice catalog. Stored option values stay unchanged.'],
            'option.espace' => ['text' => 'Espace', 'context' => 'Display label or help in a model choice catalog. Stored option values stay unchanged.'],
            'option.projet' => ['text' => 'Projet', 'context' => 'Display label or help in a model choice catalog. Stored option values stay unchanged.'],
            'option.valeur' => ['text' => 'Valeur', 'context' => 'Display label or help in a model choice catalog. Stored option values stay unchanged.'],
        ];
    }

		public const TYPES = ['type1', 'type2', 'type3', 'type4', 'type5'];

		public static function isTypeEnabled($type, array $lexicon): bool
		{
			$type = self::normalizeType($type);
			return (bool)($lexicon[$type]['enabled'] ?? in_array($type, ['type1', 'type2', 'type3'], true));
		}

		public static function filterEnabledDefinitions(array $definitions, array $lexicon): array
		{
			return array_values(array_filter($definitions, static fn($definition) => self::isTypeEnabled($definition['type'] ?? null, $lexicon)));
		}

		public static function normalizeType($type): string
		{
			$type = $type === null || $type === '' ? 'type1' : (string)$type;
			if (!in_array($type, self::TYPES, true)) {
				throw new \InvalidArgumentException(self::uiMessage('copy.type_de_propriete_invalide'));
			}
			return $type;
		}

		public static function permissionKey(string $operation, $type): string
		{
			return 'CAN_' . strtoupper($operation) . '_' . strtoupper(self::normalizeType($type)) . '_PROPERTIES';
		}

		public static function getTypeOptions(array $lexicon, ?Holon $context = null, bool $creatingHolon = false): array
		{
			$options = [];
			foreach (self::TYPES as $type) {
				if (!self::isTypeEnabled($type, $lexicon)) continue;
				$options[] = [
					'id' => $type,
					'name' => Organization::getLexiconLabel($lexicon, $type),
					'canCreate' => $context && $context->canUsePropertyPermission('CREATE', $type, $creatingHolon),
					'canEdit' => $context && $context->canEditPropertyValue($type, $creatingHolon),
					'canDelete' => $context && $context->canUsePropertyPermission('DELETE', $type, $creatingHolon),
				];
			}
			return $options;
		}

		public static function canCreateAnyType(?Holon $context): bool
		{
			foreach ($context ? self::getTypeOptions($context->getPropertyTypeLexicon(), $context) : [] as $option) {
				if ($option['canCreate']) {
					return true;
				}
			}
			return false;
		}

		public static function canActOnAnyType(?Holon $context): bool
		{
			foreach ($context ? self::getTypeOptions($context->getPropertyTypeLexicon(), $context) : [] as $option) {
				foreach (['canCreate', 'canEdit', 'canDelete'] as $operation) {
					if ($option[$operation]) {
						return true;
					}
				}
			}
			return false;
		}
		public const LIST_ITEM_TEXT = 'text';
		public const LIST_ITEM_NUMBER = 'number';
		public const LIST_ITEM_DATE = 'date';
		public const LIST_ITEM_DETAIL = 'detail';
		public const LIST_ITEM_HOLON = 'holon';
		public const LIST_ITEM_PROJECT = 'project';
		public const LIST_ITEM_AUTHORITY = 'authority';
		public const LIST_ITEM_VALUE = 'value';

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
				[['name','shortname','type','listitemtype','listholontypeids'], 'string'],			// Texte libre
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
				'type' => 'Type',
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
				array('id' => self::LIST_ITEM_TEXT, 'name' => self::uiMessage('option.texte')),
				array('id' => self::LIST_ITEM_NUMBER, 'name' => self::uiMessage('option.chiffre')),
				array('id' => self::LIST_ITEM_DATE, 'name' => self::uiMessage('option.date')),
				array('id' => self::LIST_ITEM_DETAIL, 'name' => self::uiMessage('copy.liste_detaillee')),
				array('id' => self::LIST_ITEM_HOLON, 'name' => self::uiMessage('option.espace')),
				array('id' => self::LIST_ITEM_PROJECT, 'name' => self::uiMessage('option.projet')),
				array('id' => self::LIST_ITEM_AUTHORITY, 'name' => self::uiMessage('copy.autorite')),
				array('id' => self::LIST_ITEM_VALUE, 'name' => self::uiMessage('option.valeur')),
			);
		}

		public static function getTemplateListItemTypeOptions()
		{
			return array(
				array('id' => self::LIST_ITEM_TEXT, 'name' => self::uiMessage('option.texte')),
				array('id' => self::LIST_ITEM_NUMBER, 'name' => self::uiMessage('option.chiffre')),
				array('id' => self::LIST_ITEM_DATE, 'name' => self::uiMessage('option.date')),
				array('id' => self::LIST_ITEM_DETAIL, 'name' => self::uiMessage('copy.liste_detaillee')),
				array('id' => self::LIST_ITEM_PROJECT, 'name' => self::uiMessage('option.projet')),
				array('id' => self::LIST_ITEM_AUTHORITY, 'name' => self::uiMessage('copy.autorite')),
				array('id' => self::LIST_ITEM_VALUE, 'name' => self::uiMessage('option.valeur')),
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
				self::LIST_ITEM_VALUE,
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
					if (!self::isTypeEnabled($stored['type'] ?? null, $holon->getPropertyTypeLexicon())) continue;
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
						throw new \RuntimeException(self::uiMessage('copy.veuillez_confirmer_la_conversion_de_la_liste_avant_de_l_enregistrer'));
					}
					$template = $holon->getTemplateHolon();
					if ($template instanceof Holon) {
						foreach ($template->getTemplatePropertyDefinitions() as $inherited) {
							if ((int)($inherited['id'] ?? 0) === $propertyId) {
								throw new \RuntimeException(self::uiMessage('copy.une_liste_heritee_doit_etre_convertie_depuis_le_modele_qui_la_definit'));
							}
						}
					}
					$local = new HolonProperty();
					$root = new Holon();
					if (!$root->load((int)$stored['IDholon_organization'])) {
						throw new \RuntimeException(self::uiMessage('copy.l_organisation_de_la_liste_est_introuvable'));
					}
					if (!$local->load([['IDholon', $holon->getId()], ['IDproperty', $propertyId]])
						|| (int)$stored['IDholon_organization'] !== (int)($holon->get('IDholon_org') ?: $holon->getId())) {
						throw new \RuntimeException(\dbObject\Organization::formatLexiconText('Cette définition de liste ne peut pas être convertie depuis ce holon.'));
					}
					$rows = self::fetchAll('SELECT id FROM `holonproperty` WHERE IDproperty = :id ORDER BY id FOR UPDATE', ['id' => $propertyId]);
					if (!is_array($rows)) {
						throw new \RuntimeException(self::uiMessage('copy.les_valeurs_de_la_liste_ne_peuvent_pas_etre_chargees'));
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
									throw new \RuntimeException(self::uiMessage('copy.la_liste_contient_une_valeur_qui_n_est_pas_un_texte'));
								}
								$label = trim((string)$item);
								if ($label === '') { continue; }
								$authority = new Authority();
								$authority->set('IDholon', (int)$value->get('IDholon'));
								$authority->set('label', $label);
								$authority->set('is_local', true);
								$result = $authority->save();
								if (empty($result['status'])) { throw new \RuntimeException($result['text'] ?? self::uiMessage('copy.creation_d_autorite_impossible')); }
								$items[] = (int)$authority->getId();
							} else {
								$id = self::listAuthorityReferenceId($item);
								// Old type changes left plain text in authority lists. Keep that text intact.
								if ($id === 0 && is_string($item)) {
									$items[] = $item;
									continue;
								}
								$context = 'Liste "' . (string)$stored['name'] . \dbObject\Organization::formatLexiconText('", holon #') . (int)$value->get('IDholon') . ' : ';
								if ($id === 0) {
									throw new \RuntimeException(self::uiMessage('copy.context_une_reference_d_autorite_est_invalide_la_conversion_est_annule', ['context' => $context]));
								}
								$authority = new Authority();
								if (!$authority->load($id)) {
									throw new \RuntimeException(self::uiMessage('copy.context_l_autorite_id_n_existe_plus_reparez_cette_reference_avant_la_c', ['context' => $context, 'id' => $id]));
								}
								if ((int)$authority->getOrganizationId() !== (int)$root->get('IDorganization')) {
									throw new \RuntimeException(self::uiMessage('copy.context_l_autorite_id_appartient_a_une_autre_organisation_la_conversio', ['context' => $context, 'id' => $id]));
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
