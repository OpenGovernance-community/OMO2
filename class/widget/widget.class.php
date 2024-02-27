<?php
	namespace widget;


	abstract class Widget
	{

		abstract function getDefaultTemplate();
				
		protected $_value;
		protected $_name;
		protected $_label;
		protected $_enable = true;
		
		// Constructeur
   
		public function __construct($name="", $label="", $value="", $selected="")
		{
			$this->setName($name);
			$this->setLabel($label);
			$this->setValue($value);
			
		}

		function setWidth($width) {
		}
		
		function setLabelWidth($width) {
		}
		
		// Retourne la chaîne de caractère générée par l'affichage
		function getString($template, $params=[]) {
			ob_start();
			$this->display($template,$params);
			$myStr = ob_get_contents();
			ob_end_clean();
			return $myStr;
		}
		
		function setLabel($label) {
			$this->_label=$label;
		}
				
		function getLabel() {
			return $this->_label;
		}
				
		function setName($name) {
			$this->_name=$name;
		}
				
		function getName() {
			return $this->_name;
		}
				
		function setValue($value) {
			$this->_value=$value;
		}
				
		function getValue() {
			return $this->_value;
		}
		
		function disable() {
			$this->_enable=false;
		}
		
		function isEnabled() {
			return $this->_enable;
		}
		
		function enable() {
			$this->_enable=true;
		}
				
		// Fonctions d'affichage
		// *****************************************
		
		function display($template="", $params=[]) {
			if ($template=="") $template=$this->getDefaultTemplate();
			include ($_SERVER['DOCUMENT_ROOT']."/widget/".$template);
		}
		
	}
	
?>
