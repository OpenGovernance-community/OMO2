<?php
	namespace dbObject;

	class ArrayHolonProperty extends ArrayDbObject
	{
		
		public static function objectName() {
			return "\dbObject\HolonProperty";
		}
		
		public function loadAllValues($node) {
			if (!is_numeric($node)) $node=$node->get("id");
			
			// Crée la requête
			$query="WITH RECURSIVE ParentTree AS (

					SELECT 
						h.id AS IDholon,
						h.name AS holon_name,
						h.IDholon_template
					FROM holon h
					WHERE h.id = ".$node." 

					UNION ALL

					SELECT 
						h.id AS IDholon,
						h.name AS holon_name,
						h.IDholon_template
					FROM holon h
					INNER JOIN ParentTree pt ON h.id = pt.IDholon_template
				)

				SELECT 

					MAX(CASE WHEN pt.IDholon = ".$node." THEN hp.id ELSE NULL END) AS id,

					p.id AS IDproperty,            
					p.shortname,                   
					p.name,                        

					 MAX(CASE WHEN pt.IDholon = ".$node." THEN hp.value ELSE NULL END) AS value,

					GROUP_CONCAT(CASE WHEN pt.IDholon=".$node." THEN null ELSE hp.value END ORDER BY pt.IDholon ASC SEPARATOR '|') AS value_parents,

					GROUP_CONCAT(CASE WHEN pt.IDholon=".$node." THEN null ELSE pt.IDholon END ORDER BY pt.IDholon ASC SEPARATOR ',') AS list_parent

				FROM ParentTree pt

				LEFT JOIN holonproperty hp ON hp.IDholon = pt.IDholon

				INNER JOIN property p ON p.id = hp.IDproperty 

				GROUP BY p.id ORDER BY p.position";
			
			// Exécute la requête SQL complexe
			$dbh= \dbObject\DbObject::getDbh();
			$result=$dbh->query($query);

			if ($result) {

			// Parcours chaque élément de la réponse
				while ($row = $result->fetch_assoc()){
					$name=$this::objectName();
					$object=new $name();
					// Pour accélérer, on ne charge plus... seulement si c'est nécessaire
					//$object->load($row["id"]);
					
					$object->setId($row["id"]);
					
					// Initialise tous les champs qui ne sont pas des champs standards
					foreach ($row as $key=>$value) {
						// C'est la fonction qui se charge de classer correctement les valeurs
						$object->set($key,$value);
					}
					
					$this[]=$object;
				}
			} else {
				// Traitement d'erreur de chargement
				if (!$result) Die ("Erreur dans la requête : ".$query);
			}						
			

			
		} 
	}
	
?>
