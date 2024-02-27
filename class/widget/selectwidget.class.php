<?php
	namespace widget;


	class SelectWidget extends Widget
	{
		
		private $_options = array(); // Liste des options 
							
		function getDefaultTemplate() {
			return "defaultSelect.php";
		}
		
		// Ajoute une option à la liste
		public function addOption ($value, $label=NULL) {
			$this->_options[]=(object) [
										'_value' => $value,
										'_label' => (is_null($label)?$value:$label),
									   ];
		}
		
		public function addOptions ($array) {
			foreach ($array as $elem) {
				$this->_options[]=(object) [
											'_value' => $elem->getId(),
											'_label' => $elem->getLabel(),
										   ];				
				}
	
		}
		
		public function getOptions() {
			return $this->_options;
		}
	}
	
?>
