<?php
// convert HTML to DOCX

	require_once 'classes/CreateDocx.php';
	require_once 'dompdf/autoload.inc.php';
	use Dompdf\Dompdf;
	
	//$docx = new CreateDocx();
	$docx = new CreateDocxFromTemplate('template.docx');
	if (isset($_POST["data"])) {
		//$style="<style>h1 {font-size:".($_POST["fontsize"]*1.5)."pt;} h2 {font-size:".($_POST["fontsize"]*1.3)."pt;} h3 {font-size:".($_POST["fontsize"]*1.1)."pt;} h4,h5 {margin-left:30px;margin-right:30px; font-size:".($_POST["fontsize"])."pt; font-weight:normal} p {font-size:".($_POST["fontsize"])."pt;  margin-bottom:".($_POST["fontsize"])."pt;} li {font-size:".($_POST["fontsize"])."pt;}</style>";
		$style="<style>h1, h2, h3 {font-weight:normal;}</style>";
		$data=json_decode($_POST["data"],true);
		if (isset($data["oj"]) && isset($data["oj"][0])) {
			$variables = array('TITLE' => $data["title"], 'LOCATION' => $data["location"].", ".$data["dateevent"].", ".$data["starttime"]."-".$data["endtime"]);

			$options = array('parseLineBreaks' => true);
			$docx->replaceVariableByText($variables, $options);


			// le contenu
			$allcontent="";
			foreach ($data["oj"] as $elem) {
				
				// Ajoute le titre du point et son auteur
				if ($elem["content"]!="") {
					$allcontent.="<h1>".($elem["who"]!=""?$elem["who"]." - ":"")."<b>".$elem["title"].(max($elem["duration"],$elem["realduration"])>0?" (".max($elem["duration"],$elem["realduration"])."')":"")."</b></h1>";
					// Concatène le contenu
					$allcontent.=$elem["content"]."<br/><hr>";
				}
				
				// Concatène l'ordre du jour
			}
			
			$docx->replaceVariableByHTML('CONTENT', 'block',$style.$allcontent, array('addDefaultStyles' => false, 'wordStyles' => array( 'pStyle' => 'Normal','<h1>' => 'Heading1','<h2>' => 'Heading2','<h3>' => 'Heading3','<h4>' => 'Heading4','<h5>' => 'Heading5','<h6>' => 'Heading6','bold' => false ), 'numId' => 0));

			
			
			
			
			
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
