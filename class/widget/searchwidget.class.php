<?php
	namespace widget;


	class SearchWidget extends Widget
	{
		
		private $_categories; // Liste des catégories
		private $_regions; // Liste des régions
		
								
		function getDefaultTemplate() {
			return "defaultSearch.php";
		}
				
		public function __construct($name="", $label="", $value="", $selected="")
		{
			
			parent::__construct();
			
			$this->_categories = new \dbObject\arrayCategorie();
			$params= array(
				"filter" => "visible=1",		// Filtre
			);
			$this->_categories->load($params);
			
			$this->_regions = new \dbObject\arrayRegion();
			$this->_regions->load();
			
		}
		
		public function getRegions() {
			return $this->_regions;
		}
		public function getCategories() {
			return $this->_categories;
		}
		
	}
	
?>
