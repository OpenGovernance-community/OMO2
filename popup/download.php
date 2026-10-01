<form id='pv-download-form' method='POST' action='/genWord.php' enctype='multipart/form-data'>
	<input type='hidden' name='data' value='' id='downloadpostdata'>
	<select name='fontsize'>
		<option value='8'>8pt</option>
		<option value='9'>9pt</option>
		<option value='10'>10pt</option>
		<option value='10.5' selected>10.5pt</option>
		<option value='11'>11pt</option>
		<option value='12'>12pt</option>
		<option value='14'>14pt</option>
	
	</select>
	<button type='submit' name='format' value='docx'>Télécharger au format Word (.docx)</button>
	<button type='submit' name='format' value='odt'>Télécharger au format OpenDocument (.odt)</button>
	<button type='submit' name='format' value='pdf'>Télécharger au format PDF</button>
</form>
