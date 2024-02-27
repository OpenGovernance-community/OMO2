<?php
	namespace widget;


	abstract class WidgetContainer 
	{
			
		protected $_widgets = [];	
		
		abstract function getDefaultTemplate();
		
		function add($widget) {
			$this->_widgets[]=$widget;
		}
								
		// Fonctions d'affichage
		// *****************************************
		
		function display($template="", $params=[]) {
			if ($template=="") $template=$this->getDefaultTemplate();
			include ($_SERVER['DOCUMENT_ROOT']."/widget/".$template);
		}
		
		
	}
	
?>
