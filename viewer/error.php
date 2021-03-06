<!DOCTYPE html>
<?php

/**
 * error.php file, provides user-friendly messages when something goes wrong.
 *
 * This file is used to print out messages accordingly to errors happening in index.php
 *
 * @author svalo@libersoft.it
 * @copyright ???
 * @license   ???
 *
 */

    /**
     * Prints error message based on index received
     *
     * @param string $err The error message index in $errorCodes array
     *
     * @return string The error message
     *
     */
	function printError ($err) {
	$errorCodes=[
		"1" => "Ticket invalido",
		"2" => "Errore interno",
		"3" => "Probabilmente non dovresti avere questo ticket",
		"4" => "Il tipo di contenuto richiesto non è visualizzabile",
		"5" => "Tropp@ utent@ conness@, risorse terminate",
		"6" => "Non posso creare il container, contatta l'amministratore",
		"7" => "Non riesco a fare partire il container, contatta l' amministratore"
	];
		if ( (! isset($err) ) or (! in_array($err, array_keys($errorCodes) ) ) ) {
			return 'Errore indefinito';
		} else {
			return $errorCodes["$err"];
		}
	}
?>
<html>
	<head>
		<title>VisoreRemoto | Errore</title>
    	<link rel="stylesheet" href="app/styles/error.css">
	</head>
	<body class="errorbody">
		<section>
		<h1>Ooooops!</h1>
		<section class="errmsg">
				<h3>Questo è un errore</h3>
				<p><?php echo printError(urlencode($_GET['err'])); ?></p>
		</section>
		</section>
		<section>
			<div> Torna alla <a href="http://md-www.test.bncf.lan/index.php/opac/" title="Ricerca">ricerca</a></div>
		</section>
	</body>
</html>
