<?php
// convert HTML to DOCX

	require_once 'classes/CreateDocx.php';
	require_once 'dompdf/autoload.inc.php';
	use Dompdf\Dompdf;
	
	//$docx = new CreateDocx();
	$docx = new CreateDocxFromTemplate('template.docx');
	if (isset($_POST["data"])) {
		$style="<style>h1 {font-size:".($_POST["fontsize"]*1.5)."pt;} h2 {font-size:".($_POST["fontsize"]*1.3)."pt;} h3 {font-size:".($_POST["fontsize"]*1.1)."pt;} h4,h5 {margin-left:30px;margin-right:30px; font-size:".($_POST["fontsize"])."pt; font-weight:normal} p {font-size:".($_POST["fontsize"])."pt;  margin-bottom:".($_POST["fontsize"])."pt;} li {font-size:".($_POST["fontsize"])."pt;}</style>";
		$data=json_decode($_POST["data"],true);
		if (isset($data["oj"]) && isset($data["oj"][0])) {
			// Le titre
			$docx->replaceVariableByHTML('TITLE', 'block',$style."<h1>".$data["title"]."</h1>", array('wordStyles' => array('<h4>' => 'Heading4','<h5>' => 'Heading5','<h6>' => 'Heading6',)));
			//Le lieu
			$docx->replaceVariableByHTML('LOCATION', 'block',$style."<h2>".$data["location"]."</h2>", array('wordStyles' => array('<h4>' => 'Heading4','<h5>' => 'Heading5','<h6>' => 'Heading6',)));

			// le contenu
			$allcontent="";
			foreach ($data["oj"] as $elem) {
				// Concatène le contenu
				$allcontent.=$elem["content"];
				
				// Concatène l'ordre du jour
			}
			
			$docx->replaceVariableByHTML('CONTENT', 'block',$style.$allcontent, array('wordStyles' => array('<h4>' => 'Heading4','<h5>' => 'Heading5','<h6>' => 'Heading6',)));

			
			
			
			
			
			$html="<div style='font-size:70%;'><i>Généré sur pv.systemdd.ch</i></div>";
		} else {
			$html = '<h1 style="color: #b70000">Aucun point</h1>';
			
		}
		
	} else {

		$html = '<h1 style="color: #b70000">Aucune donnée transmise</h1>';
	}

	$docx->embedHTML($html);

	// Sauve un fichier temporaire
	$docx->createDocx('PV');

	if (isset($_POST["pdf"]) && $_POST["pdf"]=="pdf") {
		//generate some PDFs!
		$dompdf = new Dompdf(); 

		$docx->transformDocument('PV.docx', 'PV.pdf', 'native',array("dompdf" => $dompdf));
		
		header("Content-type: application/pdf");
		header("Content-Disposition: attachment; filename=PV_".((new \DateTime())->format('Ymd_Hi'))."_".preg_replace('/[^a-zA-Z0-9]/s', '', $data["title"]).".pdf");
		@readfile('PV.pdf');
	} else {
		header("Content-type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
		header("Content-Disposition: attachment; filename=PV_".((new \DateTime())->format('Ymd_Hi'))."_".preg_replace('/[^a-zA-Z0-9]/s', '', $data["title"]).".docx");
		@readfile('PV.docx');
		
	}
	
?>
