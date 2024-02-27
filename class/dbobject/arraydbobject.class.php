<?php
	namespace dbObject;


	abstract class ArrayDbObject extends \ArrayObject
	{

	    abstract public static function objectName();

		// Constructeur
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
		// Fonctions de base pour l'accès à la base de données 
		// *****************************************
		
		function load($params=null) {
			// Valeurs par défaut des paramètres
			if (!isset($params["order"]) || $params["order"]=="") $params["order"]=$this->objectName()::getOrder();

			
			// Traitement des paramètres
			$joinStr="";
			if (isset($params["join"])) {
				// Dans quel sens faire le join? Est-ce que IDjoin existe dans l'élément de la table?
				if ($this->objectName()::getFieldType("ID".$params["join"])=="fk") {
					$joinStr=" join ".$params["join"]." on (".$params["join"].".id=".$this->objectName()::tableName().".ID".$params["join"].") ";
				} else
					$joinStr=" join ".$params["join"]." on (".$this->objectName()::tableName().".id=".$params["join"].".ID".$this->objectName()::tableName().") ";
			}
			
			// Construit la requête avec les paramètres
			$query="select ".$this->objectName()::tableName().".id from ".$this->objectName()::tableName().$joinStr.(isset($params["filter"]) && $params["filter"]!=""?" where ".$params["filter"]:"").(isset($params["order"]) && $params["order"]!=""?" order by ".$params["order"]:"").(isset($params["limit"])?" limit ".$params["limit"]:"");

			$dbh= \dbObject\DbObject::getDbh();
			$result=$dbh->query($query);

			if ($result) {

				while ($row = $result->fetch_assoc()){
					
					$name=$this::objectName();
					$object=new $name();
					// Pour accélérer, on ne charge plus... seulement si c'est nécessaire
					//$object->load($row["id"]);
					
					$object->setId($row["id"]);
					$this[]=$object;
				}
			} else {
				// Traitement d'erreur de chargement
				if (!$result) Die ("Erreur dans la requête : ".$query);
			}			
		}
		
		public function get($id) {
			foreach ($this as $elem) {
				if ($elem->getId()==$id) return $elem;
			}
		}
		// Fonctions d'affichage
		// *****************************************
		
		function display($template, $params=[]) {
			include ($_SERVER['DOCUMENT_ROOT']."/views/".$template);
		}
		
		function getFieldType ($key) {
			return $this->objectName()::getFieldType($key);
		}
		
	}
	
?>
