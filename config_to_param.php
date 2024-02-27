<?
	// Identité du site
	$GLOBALS["siteTitle"] = "Your site name";
	$GLOBALS["homeTitle"] = "Home page name";
	$GLOBALS["lang"] = "Your base languge (FR,EN,DE,...)";
	
	// Config base de données
	$GLOBALS["dbName"] ="Mysqli database name";
	$GLOBALS["dbServer"] = "Database server";
	$GLOBALS["dbUser"] = "Database user";
	$GLOBALS["dbPassword"] = "Database password";
	$GLOBALS["OpenAI"] = "OpenAI Tocken";

	// Config du mail
	$GLOBALS["mailHost"] = 'Mail server';
	$GLOBALS["mailPort"] = 587;
	$GLOBALS["mailSecure"] = 'SSL';
	$GLOBALS["mailAuth"] = true;
	$GLOBALS["mailCharset"] = "UTF-8";
	$GLOBALS["mailUser"] = 'Mail user';
	$GLOBALS["mailPassword"] = 'Mail password';
	
	// clé API du Bot TELEGRAM à modifier
	define('TOKEN', 'Telegram BOT token'); 

	// clé et informations de base pour OpenAI
	define('MODEL','gpt-4-turbo-preview');
	define('OpenAI',"OpenAI token");
?>
