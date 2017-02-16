<?php
	function printError ($err) {
	$errorCodes=[
		"1" => "Ticket invalido",
		"2" => "Errore interno",
		"3" => "Probabilmente non dovresti avere questo ticket",
		"4" => "Il tipo di contenuto richiesto non è visualizzabile",
		"5" => "Tropp@ utent@ conness@, risorse terminate",
		"6" => "Non posso creare il container, contatta l'amministratore",
		"7" => "Non riesco a fare partire il container, contatta \"amministratore"
	];
		if ( (! isset($err) ) or (! in_array($err, array_keys($errorCodes) ) ) ) {
			echo 'Errore indefinito';
		} else {
			echo($errorCodes["$err"]);
		}
	}
?>
<html>
	<head>
		<title>VisoreRemoto | Errore</title>
    	<link rel="stylesheet" href="error.css">
	</head>
	<body class="errorbody">
		<section>
		<h1>Ooooops!</h1>
		<section class="errmsg">
				<h3>Questo è un errore</h3>
				<p><?php printError($_GET['err']); ?></p>
		</section>
		</section>
		<section>
			<div> Torna alla <a href="Bho" title="Ricerca magazzini digitali">ricerca</a></div>
		</section>
	</body>
</html>
