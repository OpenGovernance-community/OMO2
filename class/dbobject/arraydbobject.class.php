<?php
	namespace dbObject;

	/*
	 * Chargement recommandé:
	 * $items->load([
	 *     "where" => [
	 *         ["field" => "IDdocument", "value" => 12],
	 *         ["field" => "active", "value" => 1],
	 *     ],
	 *     "whereAny" => [
	 *         ["field" => "IDuser", "value" => 5],
	 *         ["field" => "ispublic", "value" => 1],
	 *     ],
	 *     "joins" => ["holonproperty"],
	 *     "orderBy" => [
	 *         ["field" => "holonproperty.position", "dir" => "ASC"],
	 *         ["field" => "name", "dir" => "ASC"],
	 *     ],
	 *     "limit" => 50,
	 *     "offset" => 0,
	 * ]);
	 *
	 * Paramètres supportés:
	 * - where: liste de critères combinés avec AND
	 * - whereAny: liste de critères combinés avec OR
	 * - joins: liste des tables à joindre automatiquement
	 * - orderBy: liste de tris sécurisés
	 * - limit / offset: entiers
	 *
	 * Format d'un critère:
	 * - ["field" => "IDdocument", "value" => 12]
	 * - ["field" => "active", "op" => "=", "value" => 1]
	 * - ["field" => "name", "op" => "like", "value" => "%abc%"]
	 * - ["field" => "id", "op" => "in", "value" => [1, 2, 3]]
	 * - ["field" => "deleted_at", "op" => "is null"]
	 *
	 * Notes:
	 * - Le champ peut être qualifié, ex: "holonproperty.position"
	 * - Les opérateurs autorisés sont: =, !=, >, >=, <, <=, like, in, not in, is null, is not null
	 * - Les anciens paramètres "filter", "join" et "order" restent tolérés pour compatibilité,
	 *   mais ils sont legacy et doivent être remplacés progressivement.
	 */

	abstract class ArrayDbObject extends \ArrayObject
	{

	    abstract public static function objectName();

		public function __construct()
		{
		}
		
		function rules() {
			return $this->objectName()::rules();
		}
		
		function getValues($field) {
			return $this->objectName()::getValues($field);
		}
		
		function getLabel() {
			$str="";
			foreach ($this as $elem) {
				$str.=$elem->getLabel().", ";
			}
			return substr($str,0,strlen($str)-2);
		}

		private static function isValidIdentifier($identifier) {
			return is_string($identifier) && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier);
		}

		private static function quoteIdentifier($identifier) {
			return "`".$identifier."`";
		}

		private function getBaseTableName() {
			return $this->objectName()::tableName();
		}

		private function getQualifiedField($field, $allowedTables) {
			if (!is_string($field) || trim($field)=="") {
				return false;
			}

			$field = trim($field);
			if (strpos($field, ".") !== false) {
				$parts = explode(".", $field, 2);
				if (count($parts)!==2 || !self::isValidIdentifier($parts[0]) || !self::isValidIdentifier($parts[1])) {
					return false;
				}
				if (!in_array($parts[0], $allowedTables, true)) {
					return false;
				}
				return self::quoteIdentifier($parts[0]).".".self::quoteIdentifier($parts[1]);
			}

			if (!self::isValidIdentifier($field)) {
				return false;
			}

			return self::quoteIdentifier($this->getBaseTableName()).".".self::quoteIdentifier($field);
		}

		private function normalizeJoinTables($params) {
			$joins = array();

			if (isset($params["joins"]) && is_array($params["joins"])) {
				foreach ($params["joins"] as $joinTable) {
					if (is_string($joinTable) && $joinTable!=="") {
						$joins[] = $joinTable;
					}
				}
			}

			if (isset($params["join"]) && is_string($params["join"]) && $params["join"]!=="") {
				$joins[] = $params["join"];
			}

			return array_values(array_unique($joins));
		}

		private function buildJoinClause($joins) {
			$baseTable = $this->getBaseTableName();
			$joinSql = "";

			foreach ($joins as $joinTable) {
				if (!self::isValidIdentifier($joinTable)) {
					Die ("Invalid join table: ".$joinTable);
				}

				if ($this->objectName()::getFieldType("ID".$joinTable)=="fk") {
					$joinSql .= " join ".self::quoteIdentifier($joinTable)." on (".
						self::quoteIdentifier($joinTable).".`id`=".
						self::quoteIdentifier($baseTable).".".self::quoteIdentifier("ID".$joinTable).") ";
				} else {
					$joinSql .= " join ".self::quoteIdentifier($joinTable)." on (".
						self::quoteIdentifier($baseTable).".`id`=".
						self::quoteIdentifier($joinTable).".".self::quoteIdentifier("ID".$baseTable).") ";
				}
			}

			return $joinSql;
		}

		private function normalizeCriterion($criterion) {
			if (!is_array($criterion)) {
				return false;
			}

			if (array_key_exists("field", $criterion)) {
				return array(
					"field" => $criterion["field"],
					"op" => isset($criterion["op"]) ? $criterion["op"] : "=",
					"value" => array_key_exists("value", $criterion) ? $criterion["value"] : null,
				);
			}

			if (isset($criterion[0])) {
				return array(
					"field" => $criterion[0],
					"op" => isset($criterion[2]) ? $criterion[1] : "=",
					"value" => isset($criterion[2]) ? $criterion[2] : (isset($criterion[1]) ? $criterion[1] : null),
				);
			}

			return false;
		}

		private function buildCriterionSql($criterion, &$bindings, &$bindingIndex, $allowedTables) {
			$criterion = $this->normalizeCriterion($criterion);
			if ($criterion === false) {
				Die ("Invalid where criterion");
			}

			$fieldSql = $this->getQualifiedField($criterion["field"], $allowedTables);
			if ($fieldSql === false) {
				Die ("Invalid field in collection load: ".$criterion["field"]);
			}

			$op = strtolower(trim((string)$criterion["op"]));
			$value = $criterion["value"];

			if ($value === null && ($op === "=" || $op === "is null")) {
				return $fieldSql." is null";
			}

			if ($value === null && ($op === "!=" || $op === "<>" || $op === "is not null")) {
				return $fieldSql." is not null";
			}

			switch ($op) {
				case "=":
				case "!=":
				case "<>":
				case ">":
				case ">=":
				case "<":
				case "<=":
				case "like":
					$paramName = "w_".$bindingIndex++;
					$bindings[$paramName] = $value;
					return $fieldSql." ".$op." :".$paramName;
				case "in":
				case "not in":
					if (!is_array($value)) {
						Die ("The operator ".$op." expects an array");
					}
					if (count($value)===0) {
						return $op === "in" ? "1=0" : "1=1";
					}
					$placeholders = array();
					foreach ($value as $item) {
						$paramName = "w_".$bindingIndex++;
						$bindings[$paramName] = $item;
						$placeholders[] = ":".$paramName;
					}
					return $fieldSql." ".$op." (".implode(", ", $placeholders).")";
				case "is null":
					return $fieldSql." is null";
				case "is not null":
					return $fieldSql." is not null";
				default:
					Die ("Unsupported operator in collection load: ".$criterion["op"]);
			}
		}

		private function buildCriteriaGroup($criteria, $glue, &$bindings, &$bindingIndex, $allowedTables) {
			if (!is_array($criteria) || count($criteria)===0) {
				return "";
			}

			$parts = array();
			foreach ($criteria as $criterion) {
				$parts[] = $this->buildCriterionSql($criterion, $bindings, $bindingIndex, $allowedTables);
			}

			if (count($parts)===1) {
				return $parts[0];
			}

			return "(".implode(" ".$glue." ", $parts).")";
		}

		private function parseLegacyOrder($order, $allowedTables) {
			if (!is_string($order) || trim($order)=="") {
				return "";
			}

			$parts = array();
			foreach (explode(",", $order) as $rawPart) {
				$rawPart = trim($rawPart);
				if ($rawPart=="") {
					continue;
				}

				if (!preg_match('/^([A-Za-z_][A-Za-z0-9_\.]*)(?:\s+(ASC|DESC))?$/i', $rawPart, $matches)) {
					Die ("Invalid legacy order clause: ".$order);
				}

				$fieldSql = $this->getQualifiedField($matches[1], $allowedTables);
				if ($fieldSql === false) {
					Die ("Invalid order field: ".$matches[1]);
				}

				$direction = isset($matches[2]) ? strtoupper($matches[2]) : "ASC";
				$parts[] = $fieldSql." ".$direction;
			}

			return implode(", ", $parts);
		}

		private function buildOrderClause($params, $allowedTables) {
			$orderParts = array();

			if (isset($params["orderBy"]) && is_array($params["orderBy"])) {
				foreach ($params["orderBy"] as $order) {
					if (is_string($order)) {
						$fieldSql = $this->getQualifiedField($order, $allowedTables);
						if ($fieldSql === false) {
							Die ("Invalid order field: ".$order);
						}
						$orderParts[] = $fieldSql." ASC";
						continue;
					}

					if (!is_array($order) || !isset($order["field"])) {
						Die ("Invalid orderBy clause");
					}

					$fieldSql = $this->getQualifiedField($order["field"], $allowedTables);
					if ($fieldSql === false) {
						Die ("Invalid order field: ".$order["field"]);
					}

					$direction = isset($order["dir"]) ? strtoupper(trim($order["dir"])) : "ASC";
					if (!in_array($direction, array("ASC", "DESC"), true)) {
						Die ("Invalid order direction: ".$direction);
					}

					$orderParts[] = $fieldSql." ".$direction;
				}
			}

			if (count($orderParts)>0) {
				return implode(", ", $orderParts);
			}

			if (isset($params["order"]) && $params["order"]!="") {
				return $this->parseLegacyOrder($params["order"], $allowedTables);
			}

			return $this->parseLegacyOrder($this->objectName()::getOrder(), $allowedTables);
		}

		function load($params=null) {
			if (is_null($params)) {
				$params = array();
			}

			$baseTable = $this->getBaseTableName();
			if (!self::isValidIdentifier($baseTable)) {
				Die ("Invalid base table: ".$baseTable);
			}

			$joins = $this->normalizeJoinTables($params);
			$allowedTables = array_merge(array($baseTable), $joins);

			$query = "select ".self::quoteIdentifier($baseTable).".`id` from ".self::quoteIdentifier($baseTable);
			$query .= $this->buildJoinClause($joins);

			$bindings = array();
			$bindingIndex = 0;
			$whereParts = array();

			$whereAllSql = $this->buildCriteriaGroup(isset($params["where"]) ? $params["where"] : array(), "and", $bindings, $bindingIndex, $allowedTables);
			if ($whereAllSql!="") {
				$whereParts[] = $whereAllSql;
			}

			$whereAnySql = $this->buildCriteriaGroup(isset($params["whereAny"]) ? $params["whereAny"] : array(), "or", $bindings, $bindingIndex, $allowedTables);
			if ($whereAnySql!="") {
				$whereParts[] = $whereAnySql;
			}

			if (isset($params["filter"]) && trim((string)$params["filter"])!="") {
				$whereParts[] = "(".$params["filter"].")";
			}

			if (count($whereParts)>0) {
				$query .= " where ".implode(" and ", $whereParts);
			}

			$orderSql = $this->buildOrderClause($params, $allowedTables);
			if ($orderSql!="") {
				$query .= " order by ".$orderSql;
			}

			if (isset($params["limit"])) {
				$query .= " limit ".max(0, (int)$params["limit"]);
			}

			if (isset($params["offset"])) {
				if (!isset($params["limit"])) {
					$query .= " limit 18446744073709551615";
				}
				$query .= " offset ".max(0, (int)$params["offset"]);
			}

			$rows = \dbObject\DbObject::fetchAll($query, $bindings);

			if ($rows !== false) {
				foreach ($rows as $row){
					$name=$this::objectName();
					$object=new $name();
					$object->setId($row["id"]);
					$this[]=$object;
				}
			} else {
				Die ("Erreur dans la requÃªte : ".$query);
			}
		}
		
		public function get($id) {
			foreach ($this as $elem) {
				if ($elem->getId()==$id) return $elem;
			}
		}

		function display($template, $params=[]) {
			include ($_SERVER['DOCUMENT_ROOT']."/views/".$template);
		}
		
		function getFieldType ($key) {
			return $this->objectName()::getFieldType($key);
		}
		
	}
	
?>
