<form method='POST' action='genWord.php'>
	<script>
		$(function () {
			$('#downloadpostdata').val(localStorage.getItem("savedata"));
		});
	</script>
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
	<button type='submit' name='word' value='word'>Télécharger au format Word</button>
	<button type='submit' name='pdf' value='pdf'>Télécharger au format PDF</button>
</form>
