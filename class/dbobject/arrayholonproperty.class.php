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
						h.IDholon_template,
						0 AS depth
					FROM holon h
					WHERE h.id = ".$node." 

					UNION ALL

					SELECT 
						h.id AS IDholon,
						h.name AS holon_name,
						h.IDholon_template,
						pt.depth + 1 AS depth
					FROM holon h
					INNER JOIN ParentTree pt ON h.id = pt.IDholon_template
				),
				RankedDefinitions AS (
					SELECT
						hp.id,
						hp.IDholon,
						hp.IDproperty,
						hp.value,
						hp.position,
						hp.mandatory,
						hp.locked,
						hp.active,
						pt.depth,
						ROW_NUMBER() OVER (
							PARTITION BY hp.IDproperty
							ORDER BY pt.depth ASC, hp.id DESC
						) AS definition_rank
					FROM ParentTree pt
					INNER JOIN holonproperty hp ON hp.IDholon = pt.IDholon
				),
				PropertyLocks AS (
					SELECT
						rd.IDproperty,
						MIN(CASE WHEN rd.active = 1 AND rd.locked = 1 THEN rd.depth ELSE NULL END) AS locked_depth
					FROM RankedDefinitions rd
					GROUP BY rd.IDproperty
				)

				SELECT

					MAX(CASE WHEN rd.depth = 0 AND rd.active = 1 THEN rd.id ELSE NULL END) AS id,

					p.id AS IDproperty,
					p.shortname,
					p.name,
					MAX(p.IDpropertyformat) AS IDpropertyformat,
					MAX(pf.name) AS propertyformat_name,
					MAX(p.listitemtype) AS listitemtype,
					MAX(p.listholontypeids) AS listholontypeids,
					MAX(CASE WHEN rd.active = 1 THEN rd.mandatory ELSE 0 END) AS mandatory,
					MAX(CASE WHEN rd.active = 1 THEN rd.locked ELSE 0 END) AS locked,
					COALESCE(
						MAX(CASE WHEN rd.depth = 0 AND rd.active = 1 THEN rd.position ELSE NULL END),
						CAST(
							SUBSTRING_INDEX(
								GROUP_CONCAT(CASE WHEN rd.active = 1 AND rd.position IS NOT NULL THEN rd.position END ORDER BY rd.depth ASC SEPARATOR ','),
								',',
								1
							) AS UNSIGNED
						),
						p.position,
						p.id
					) AS effective_position,

					MAX(
						CASE
							WHEN rd.depth = 0
								AND rd.active = 1
								AND (pl.locked_depth IS NULL OR pl.locked_depth = 0)
							THEN rd.value
							ELSE NULL
						END
					) AS value,

					GROUP_CONCAT(
						CASE
							WHEN rd.depth > 0
								AND rd.active = 1
								AND rd.value IS NOT NULL
								AND TRIM(rd.value) <> ''
								AND (pl.locked_depth IS NULL OR rd.depth >= pl.locked_depth)
							THEN rd.value
							ELSE NULL
						END
						ORDER BY rd.depth ASC SEPARATOR '|'
					) AS value_parents,

					GROUP_CONCAT(
						CASE
							WHEN rd.depth > 0
								AND rd.active = 1
								AND (pl.locked_depth IS NULL OR rd.depth >= pl.locked_depth)
							THEN rd.IDholon
							ELSE NULL
						END
						ORDER BY rd.depth ASC SEPARATOR ','
					) AS list_parent

				FROM RankedDefinitions rd

				INNER JOIN property p ON p.id = rd.IDproperty
				LEFT JOIN propertyformat pf ON pf.id = p.IDpropertyformat
				LEFT JOIN PropertyLocks pl ON pl.IDproperty = rd.IDproperty

				WHERE EXISTS (
					SELECT 1
					FROM RankedDefinitions nearest
					WHERE nearest.IDproperty = rd.IDproperty
					  AND nearest.definition_rank = 1
					  AND nearest.active = 1
				)

				GROUP BY p.id
				ORDER BY effective_position ASC, p.position ASC, p.id ASC";
			
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
