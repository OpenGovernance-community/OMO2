<?php
	namespace dbObject;


	class HolonProperty extends DbObject
	{
		protected $_fieldsSup = array(); // Espace de chargement des champs supplémentaires

		
	    public static function tableName()
		{
			return 'holonproperty'; // Nom de la table correspondante
		}	
		
		// Défini le contenu de la table
		public static function rules()
		{
			return [
				[['id'], 'required'],				// Champs obligatoires
				[['id','position'], 'integer'],					
				[['IDholon','IDproperty'], 'fk'],			// Texte libre
				[['value'], 'text'],				// Clé étrangères
				[['active','mandatory','locked'], 'boolean'],				// Clé étrangères
				[['id'], 'safe'],								// Champs protégés (n'apparaîssent pas dans les formulaires)
			];
		}
		
		// Défini les labels standarts pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDholon' => 'Holon',
				'IDproperty' => 'Propriété',
				'value' => 'Valeur',
				'position' => 'Position',
				'mandatory' => 'Obligatoire',
				'locked' => 'Verrouillé',
				'active' => 'Actif ?',
			];
		}

		function set($field, $value) {
			// Crée ce qui est nécessaire pour stocker des données qui ne sont pas enregistrées dans la DB
			if (!isset($this->attributeLabels()[$field])) {
				// Pas défini ci-dessus, donc considère que c'est un champ supplémentaire
				if (is_null($value) || (is_string($value) && trim($value)==""))
					$this->_fields[$field]=null;
				else {
					$this->_fieldsSup[$field] = $value;
				}

			} else {
				// Sinon, utilise le fonctionnement de base
				parent::set($field, $value);
			}
		}
		
		function get($field) {
			// Crée ce qui est nécessaire pour stocker des données qui ne sont pas enregistrées dans la DB
			if (!isset($this->attributeLabels()[$field])) {
					return (isset($this->_fieldsSup[$field])?$this->_fieldsSup[$field]:null);
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
