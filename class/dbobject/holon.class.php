<?php
	namespace dbObject;


	class Holon extends DbObject
	{
	    public static function tableName()
		{
			return 'holon'; // Nom de la table correspondante
		}	
		
		// Défini le contenu de la table
		public static function rules()
		{
			return [
				[['id'], 'required'],				// Champs obligatoires
				[['id'], 'integer'],					
				[['name','templatename','accesskey'], 'string'],			// Texte libre
				[['datecreation','datemodification'], 'datetime'],	// Date avec précision des heures
				[['IDuser','IDtypeholon','IDholon_parent','IDholon_template','IDorganization'], 'fk'],				// Clé étrangères
				[['active','visible'], 'boolean'],				// Clé étrangères
				[['id'], 'safe'],								// Champs protégés (n'apparaîssent pas dans les formulaires)
			];
		}
		
		// Défini les labels standarts pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'name' => 'Nom',
				'IDholon_org' => 'Organisation',
				'IDuser' => 'Créateur et administrateur',
				'datecreation' => 'Date de création',
				'datemodification' => 'Date de modification',
				'active' => 'Actif ?',
				'visible' => 'Visible ?',
				'templatename' => 'Nom de template',
				'IDorganization' => 'Organisation',
				'IDtypeholon' => 'Type de holon',
				'IDholon_parent' => 'Parent',
				'IDholon_template' => 'Template',
				'accesskey' => 'Clé accès',
			];
		}

		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "name";
		}
		
		public function canEdit() {
				return $_SESSION["currentUser"]==$this->get("IDuser");
		}

		// Retourne tous les enfants (uniquement pour les orga
		public function getAllChildren() {
			if ($this->get("IDtypeholon")==4) {
				$children=new \dbObject\ArrayHolon();
				$children->load([
					"where" => [
						["field" => "active", "value" => 1],
						["field" => "visible", "value" => 1],
						["field" => "IDholon_org", "value" => $this->get("id")],
					],
				]);
				return $children;	
			} else return null;		
		}
		
		public function setPropertyValue($key,$value) {
			// Charge la propriété avec cette clé et cette référence au noeud
			$property=new \dbObject\HolonProperty();
			$property->load([['IDholon',$this->getId()],['IDproperty',$key]]);
			// Si trouvé, la réactive et la met à jour
			if ($property->getId()>0) {
				$property->set("value",$value["value"]);
				$property->set("active",true);
			} else {
				
				// Si pas trouvé, la crée et l'initialise
				$property->set("IDholon",$this->getId());
				$property->set("IDproperty",$key);
				$property->set("value",$value["value"]);
			}
			// Sauve la valeur
			$property->save();
				
		}
		
		// Retourne les propriétés de l'objet, incluant celles associées à l'objet et celles associées à ses templates
		// Pour l'instant, uniquement celles de l'objet
		public function getProperties() {
			// Récupère la liste des propriétés spécifiques au noeud
			$properties=new \dbObject\ArrayProperty();
			$properties->load([
				"joins" => ["holonproperty"],
				"where" => [
					["field" => "holonproperty.IDholon", "value" => $this->getId()],
				],
				"orderBy" => [
					["field" => "holonproperty.position", "dir" => "ASC"],
				],
			]);
			return $properties;	
			
		}
		
		public function getHolonProperties() {
			// Récupère la liste des propriétés spécifiques au noeud
			$properties=new \dbObject\ArrayHolonProperty();
			$properties->load([
				"where" => [
					["field" => "IDholon", "value" => $this->getId()],
				],
				"orderBy" => [
					["field" => "position", "dir" => "ASC"],
				],
			]);
			return $properties;	
			
		}
		
		public function getPropertiesValue() {
			// Récupère l'ensemble des propriétés et leurs valeurs
			$values=new \dbObject\ArrayHolonProperty();
			$values->loadAllValues($this);
			return $values;
			
		}

		public function getRepresentationData(array $options = array()) {
			$keyPrefix = isset($options['propertyKeyPrefix']) ? (string)$options['propertyKeyPrefix'] : 'd';
			$includeAncestors = !isset($options['includePropertyAncestors']) || $options['includePropertyAncestors'];
			$data = array();

			foreach ($this->getPropertiesValue() as $property) {
				$value = $property->get('value');
				$ancestor = $property->get('value_parents');

				if ($value === null && $ancestor === null) {
					continue;
				}

				$item = array(
					'value' => $value !== null ? (string)$value : '',
				);

				if ($includeAncestors) {
					$item['ancestor'] = $ancestor !== null ? (string)$ancestor : '';
				}

				$data[$keyPrefix . $property->get('IDproperty')] = $item;
			}

			return $data;
		}

		public function toRepresentationArray(array $options = array()) {
			$options = array_merge(array(
				'representation' => 'default',
				'includeRepresentation' => false,
				'includeDbId' => true,
				'includeProperties' => true,
				'includeChildren' => null,
				'includeSize' => true,
				'childrenField' => 'children',
				'childrenMethod' => 'getChildren',
				'idField' => 'ID',
				'dbIdField' => 'IDdb',
				'nameField' => 'name',
				'typeField' => 'type',
				'leafSize' => 10,
				'containerSize' => 20,
				'maxDepth' => null,
				'currentDepth' => 0,
			), $options);

			$type = (int)$this->get('IDtypeholon');
			$nameField = $options['nameField'];
			$idField = $options['idField'];
			$typeField = $options['typeField'];
			$dbIdField = $options['dbIdField'];
			$childrenField = $options['childrenField'];
			$currentDepth = (int)$options['currentDepth'];
			$maxDepth = isset($options['maxDepth']) ? $options['maxDepth'] : null;

			$node = array(
				$nameField => (string)$this->get('name'),
				$idField => (string)$this->getId(),
				$typeField => (string)$type,
			);

			if (!empty($options['includeDbId'])) {
				$node[$dbIdField] = (string)$this->getId();
			}

			if (!empty($options['includeRepresentation'])) {
				$node['representation'] = (string)$options['representation'];
			}

			if (!empty($options['includeProperties'])) {
				$data = $this->getRepresentationData($options);
				if (count($data) > 0) {
					$node['data'] = $data;
				}
			}

			$shouldIncludeChildren = is_null($options['includeChildren'])
				? ($type > 1)
				: (bool)$options['includeChildren'];
			$canTraverseChildren = is_null($maxDepth) || $currentDepth < (int)$maxDepth;

			if ($shouldIncludeChildren && $canTraverseChildren && method_exists($this, $options['childrenMethod'])) {
				$children = array();
				$childrenOptions = $options;
				$childrenOptions['currentDepth'] = $currentDepth + 1;
				$childCollection = $this->{$options['childrenMethod']}();

				if ($childCollection) {
					foreach ($childCollection as $child) {
						$children[] = $child->toRepresentationArray($childrenOptions);
					}
				}

				$node[$childrenField] = $children;
			}

			if (!empty($options['includeSize'])) {
				$node['size'] = $shouldIncludeChildren ? (int)$options['containerSize'] : (int)$options['leafSize'];
			}

			return $node;
		}

		public function toRepresentationJson(array $options = array()) {
			$jsonFlags = isset($options['jsonFlags'])
				? (int)$options['jsonFlags']
				: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

			unset($options['jsonFlags']);

			return json_encode($this->toRepresentationArray($options), $jsonFlags);
		}

		public function getParentHolon() {
			$parentId = (int)$this->get('IDholon_parent');
			if ($parentId <= 0) {
				return null;
			}

			$parent = new self();
			return $parent->load($parentId) ? $parent : null;
		}

		public function getPathHolons($includeSelf = true) {
			$path = array();
			$current = $includeSelf ? $this : $this->getParentHolon();
			$guard = 0;

			while ($current !== null && $guard < 100) {
				$path[] = $current;
				$current = $current->getParentHolon();
				$guard += 1;
			}

			return array_reverse($path);
		}

		public function isDescendantOf($ancestor, $includeSelf = true) {
			$ancestorId = is_object($ancestor) ? (int)$ancestor->getId() : (int)$ancestor;
			if ($ancestorId <= 0) {
				return false;
			}

			$current = $includeSelf ? $this : $this->getParentHolon();
			$guard = 0;

			while ($current !== null && $guard < 100) {
				if ((int)$current->getId() === $ancestorId) {
					return true;
				}

				$current = $current->getParentHolon();
				$guard += 1;
			}

			return false;
		}

		public function getPropertyEntries(array $options = array()) {
			$keyPrefix = isset($options['propertyKeyPrefix']) ? (string)$options['propertyKeyPrefix'] : 'd';
			$entries = array();

			foreach ($this->getPropertiesValue() as $property) {
				$value = $property->get('value');
				$ancestor = $property->get('value_parents');
				$effectiveValue = null;

				if ($value !== null && trim((string)$value) !== '') {
					$effectiveValue = (string)$value;
				} elseif ($ancestor !== null && trim((string)$ancestor) !== '') {
					$effectiveValue = (string)$ancestor;
				}

				$entries[] = array(
					'id' => (int)$property->get('IDproperty'),
					'key' => $keyPrefix . $property->get('IDproperty'),
					'shortname' => (string)$property->get('shortname'),
					'name' => (string)$property->get('name'),
					'value' => $value !== null ? (string)$value : '',
					'ancestor' => $ancestor !== null ? (string)$ancestor : '',
					'effectiveValue' => $effectiveValue !== null ? $effectiveValue : '',
				);
			}

			return $entries;
		}

		// Retourne tous les enfants (uniquement pour les orga
		public function getChildren() {

			$children=new \dbObject\ArrayHolon();
			$children->load([
				"where" => [
					["field" => "active", "value" => 1],
					["field" => "visible", "value" => 1],
					["field" => "IDholon_parent", "value" => $this->get("id")],
				],
			]);
			return $children;	
	
		}

		public function disableAllProperty() {
			$query="update holonproperty set active=0 where IDholon=0".$this->get("id");
			$this->executeSQL($query);
		}

		// Désactive tous les enfants
		public function disableAllChildren() {
			foreach ($this->getAllChildren() as $children) {
				$children->set("active",false);
				$children->save();
			}					
			
		}
		
	}
	
?>
