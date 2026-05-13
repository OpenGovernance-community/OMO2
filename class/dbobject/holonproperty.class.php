<?php
	namespace dbObject;


	class HolonProperty extends DbObject
	{
		protected $_fieldsSup = array(); // Espace de chargement des champs supplementaires


	    public static function tableName()
		{
			return 'holonproperty'; // Nom de la table correspondante
		}

		// Defini le contenu de la table
		public static function rules()
		{
			return [
				[['id'], 'required'],				// Champs obligatoires
				[['id', 'position'], 'integer'],
				[['IDholon', 'IDproperty', 'IDusermodification'], 'fk'],
				[['value'], 'text'],
				[['datemodification'], 'datetime'],
				[['active', 'mandatory', 'locked'], 'boolean'],
				[['id'], 'safe'],								// Champs proteges (n'apparaissent pas dans les formulaires)
			];
		}

		// Defini les labels standards pour cet objet, affiches dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDholon' => 'Holon',
				'IDproperty' => 'Propriete',
				'value' => 'Valeur',
				'position' => 'Position',
				'datemodification' => 'Date de modification',
				'IDusermodification' => 'Utilisateur de modification',
				'mandatory' => 'Obligatoire',
				'locked' => 'Verrouille',
				'active' => 'Actif ?',
			];
		}

		protected function resolveCurrentUserId()
		{
			if (function_exists('commonGetCurrentUserId')) {
				return (int)\commonGetCurrentUserId();
			}

			return (int)($_SESSION['currentUser'] ?? 0);
		}

		protected function loadPersistedTrackedState()
		{
			if ($this->getId() > 0) {
				$row = self::fetchRow(
					"SELECT id, value FROM `holonproperty` WHERE `id` = :id LIMIT 1",
					array('id' => (int)$this->getId())
				);

				if (is_array($row)) {
					return array(
						'exists' => true,
						'value' => $row['value'] ?? null,
					);
				}
			}

			$holonId = (is_array($this->_fields) && array_key_exists('IDholon', $this->_fields))
				? (int)$this->_fields['IDholon']
				: 0;
			$propertyId = (is_array($this->_fields) && array_key_exists('IDproperty', $this->_fields))
				? (int)$this->_fields['IDproperty']
				: 0;

			if ($holonId > 0 && $propertyId > 0) {
				$row = self::fetchRow(
					"SELECT id, value FROM `holonproperty` WHERE `IDholon` = :holon_id AND `IDproperty` = :property_id LIMIT 1",
					array(
						'holon_id' => $holonId,
						'property_id' => $propertyId,
					)
				);

				if (is_array($row)) {
					return array(
						'exists' => true,
						'value' => $row['value'] ?? null,
					);
				}
			}

			return array(
				'exists' => false,
				'value' => null,
			);
		}

		protected function normalizeTrackedValue($value)
		{
			if (is_null($value)) {
				return null;
			}

			if ($value instanceof \DateTimeInterface) {
				return $value->format('Y-m-d H:i:s');
			}

			if (is_scalar($value)) {
				return (string)$value;
			}

			return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		protected function hasTrackedValueChanged($previousValue, $currentValue, $recordExists)
		{
			if (!$recordExists) {
				return $this->normalizeTrackedValue($currentValue) !== null;
			}

			return $this->normalizeTrackedValue($previousValue) !== $this->normalizeTrackedValue($currentValue);
		}

		public function save()
		{
			if ($this->getId() > 0 && !$this->_loaded) {
				$this->load($this->getId());
			}

			$persistedState = $this->loadPersistedTrackedState();
			$currentValue = is_array($this->_fields) && array_key_exists('value', $this->_fields)
				? $this->_fields['value']
				: null;

			if ($this->hasTrackedValueChanged($persistedState['value'] ?? null, $currentValue, !empty($persistedState['exists']))) {
				$this->set('datemodification', new \DateTime());
				$currentUserId = $this->resolveCurrentUserId();
				$this->set('IDusermodification', $currentUserId > 0 ? $currentUserId : null);
			}

			return parent::save();
		}

		function set($field, $value) {
			// Cree ce qui est necessaire pour stocker des donnees qui ne sont pas enregistrees dans la DB
			if (!isset($this->attributeLabels()[$field])) {
				// Pas defini ci-dessus, donc considere que c'est un champ supplementaire
				if (is_null($value) || (is_string($value) && trim($value) == "")) {
					unset($this->_fieldsSup[$field]);
				} else {
					$this->_fieldsSup[$field] = $value;
				}

				unset($this->_fields[$field]);
			} else {
				// Sinon, utilise le fonctionnement de base
				parent::set($field, $value);
			}
		}

		function get($field) {
			// Cree ce qui est necessaire pour stocker des donnees qui ne sont pas enregistrees dans la DB
			if (!isset($this->attributeLabels()[$field])) {
				return (isset($this->_fieldsSup[$field]) ? $this->_fieldsSup[$field] : null);
			} else {
				// Sinon, utilise le fonctionnement de base
				return parent::get($field);
			}
		}

		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "position";
		}
	}

?>
