<?php
	namespace widget;


	class FormWidget extends WidgetContainer
	{
		
		protected $_submitButton = "Se connecter";
		
		function setSubmitLabel($txt) {
			$this->_submitButton=$txt;
		}
		
		function getDefaultTemplate() {
			return "defaultForm.php";
		}
		
	}
	
?>
