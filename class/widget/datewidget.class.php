<?php
	namespace widget;


	class DateWidget extends Widget
	{

		protected $_value2;

		function setValue2($value) {
			$this->_value2=$value;
		}
				
		function getValue2() {
			return $this->_value2;
		}
				
		public function __construct($name="", $label="", $value1="",$value2="")
		{
			$this->setName($name);
			$this->setLabel($label);
			$this->setValue($value1);
			$this->setValue2($value2);
			
		}

								
		function getDefaultTemplate() {
			return "defaultDate.php";
		}
		
	}
	
?>
