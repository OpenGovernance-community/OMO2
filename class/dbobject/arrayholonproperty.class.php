<?php
	namespace dbObject;

	class ArrayHolonProperty extends ArrayDbObject
	{
		protected const GROUP_CONCAT_MAX_LEN = 16777215;
		
		public static function objectName() {
			return "\dbObject\HolonProperty";
		}

		protected function ensureSessionGroupConcatCapacity()
		{
			// The inherited property chain can contain long JSON lists.
			// Raise the session cap so GROUP_CONCAT does not cut values mid-string.
			\dbObject\DbObject::execute(
				"SET SESSION group_concat_max_len = " . (int)self::GROUP_CONCAT_MAX_LEN
			);
		}

		public static function fetchAllValuesByHolonIds(array $holonIds)
		{
			$holonIds = array_values(array_unique(array_filter(array_map('intval', $holonIds), static function ($holonId) {
				return $holonId > 0;
			})));

			if (count($holonIds) === 0) {
				return array();
			}

			$instance = new self();
			$instance->ensureSessionGroupConcatCapacity();

			$params = array();
			$placeholders = array();
			foreach ($holonIds as $index => $holonId) {
				$key = 'structure_holon_' . $index;
				$params[$key] = $holonId;
				$placeholders[] = ':' . $key;
			}

			$query = "WITH RECURSIVE ParentTree AS (
					SELECT
						h.id AS source_holon_id,
						h.id AS IDholon,
						h.IDholon_template,
						0 AS depth
					FROM holon h
					WHERE h.id IN (" . implode(', ', $placeholders) . ")

					UNION ALL

					SELECT
						pt.source_holon_id,
						h.id AS IDholon,
						h.IDholon_template,
						pt.depth + 1 AS depth
					FROM ParentTree pt
					INNER JOIN holon h ON h.id = pt.IDholon_template
					WHERE pt.depth < 20
				),
				RankedDefinitions AS (
					SELECT
						pt.source_holon_id,
						hp.id,
						hp.IDholon,
						hp.IDproperty,
						hp.value,
						hp.position,
						hp.datemodification,
						hp.IDusermodification,
						hp.mandatory,
						hp.locked,
						hp.active,
						pt.depth,
						ROW_NUMBER() OVER (
							PARTITION BY pt.source_holon_id, hp.IDproperty
							ORDER BY pt.depth ASC, hp.id DESC
						) AS definition_rank
					FROM ParentTree pt
					INNER JOIN holonproperty hp ON hp.IDholon = pt.IDholon
				),
				PropertyLocks AS (
					SELECT
						rd.source_holon_id,
						rd.IDproperty,
						MIN(CASE WHEN rd.active = 1 AND rd.locked = 1 THEN rd.depth ELSE NULL END) AS locked_depth
					FROM RankedDefinitions rd
					GROUP BY rd.source_holon_id, rd.IDproperty
				)

				SELECT
					rd.source_holon_id,
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
					) AS value_parents
				FROM RankedDefinitions rd
				INNER JOIN property p ON p.id = rd.IDproperty
				LEFT JOIN propertyformat pf ON pf.id = p.IDpropertyformat
				LEFT JOIN PropertyLocks pl
					ON pl.source_holon_id = rd.source_holon_id
					AND pl.IDproperty = rd.IDproperty
				WHERE EXISTS (
					SELECT 1
					FROM RankedDefinitions nearest
					WHERE nearest.source_holon_id = rd.source_holon_id
					  AND nearest.IDproperty = rd.IDproperty
					  AND nearest.definition_rank = 1
					  AND nearest.active = 1
				)
				GROUP BY rd.source_holon_id, p.id
				ORDER BY rd.source_holon_id ASC, effective_position ASC, p.position ASC, p.id ASC";

			$rows = \dbObject\DbObject::fetchAll($query, $params);
			if (!is_array($rows)) {
				return array();
			}

			$rowsByHolonId = array();
			foreach ($rows as $row) {
				$holonId = (int)($row['source_holon_id'] ?? 0);
				if ($holonId <= 0) {
					continue;
				}

				if (!isset($rowsByHolonId[$holonId])) {
					$rowsByHolonId[$holonId] = array();
				}
				$rowsByHolonId[$holonId][] = $row;
			}

			return $rowsByHolonId;
		}
		
		public function loadAllValues($node) {
			if (!is_numeric($node)) $node=$node->get("id");
			$this->ensureSessionGroupConcatCapacity();
			
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
						hp.datemodification,
						hp.IDusermodification,
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

					MAX(
						CASE
							WHEN rd.depth = 0
								AND rd.active = 1
							THEN rd.datemodification
							ELSE NULL
						END
					) AS datemodification,

					MAX(
						CASE
							WHEN rd.depth = 0
								AND rd.active = 1
							THEN rd.IDusermodification
							ELSE NULL
						END
					) AS IDusermodification,

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
